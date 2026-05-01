<?php

namespace App\Http\Controllers;

use App\Models\HoTroHoiThoai;
use App\Models\HoTroTinNhan;
use App\Models\KhachHang;
use App\Models\NhanVien;
use App\Services\SupportAiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupportChatController extends Controller
{
    public function __construct(
        private readonly SupportAiService $supportAiService
    ) {
    }

    public function customerConversation(Request $request): JsonResponse
    {
        $khachHang = $this->resolveSupportCustomer($request);
        $guestSessionId = $this->resolveGuestSessionId($request);

        if (! $khachHang && ! $guestSessionId) {
            return response()->json([
                'message' => 'Thiếu mã phiên khách vãng lai.',
            ], 422);
        }

        $conversation = $this->baseConversationQuery(true)
            ->when(
                $khachHang instanceof KhachHang,
                fn (Builder $query) => $query->where('id_khach_hang', $khachHang->id_khach_hang),
                fn (Builder $query) => $query->where('guest_session_id', $guestSessionId)
            )
            ->first();

        if (! $conversation) {
            return response()->json([
                'message' => 'Chua co hoi thoai ho tro.',
                'data' => null,
            ]);
        }

        if ($request->boolean('mark_read')) {
            $this->markMessagesRead($conversation, 'staff');
            $conversation->load($this->conversationRelations(true));
        }

        return response()->json([
            'message' => 'Lay hoi thoai ho tro thanh cong.',
            'data' => $this->transformConversation(
                $conversation,
                $request->boolean('include_messages'),
                'customer'
            ),
        ]);
    }

    public function customerSendMessage(Request $request): JsonResponse
    {
        $khachHang = $this->resolveSupportCustomer($request);
        $guestSessionId = $this->resolveGuestSessionId($request);

        if (! $khachHang && ! $guestSessionId) {
            return response()->json([
                'message' => 'Thiếu mã phiên khách vãng lai.',
            ], 422);
        }

        $validated = $request->validate([
            'noi_dung' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $customerMessage = trim($validated['noi_dung']);
        $aiResult = $this->supportAiService->replyToCustomerQuestion($customerMessage);
        $needsPharmacist = (bool) ($aiResult['needs_pharmacist'] ?? false);
        $aiReply = trim((string) ($aiResult['reply'] ?? ''));
        $intent = trim((string) ($aiResult['intent'] ?? 'tra_cuu_thuoc'));
        $purchaseAction = $aiResult['purchase_action'] ?? null;
        $suggestedProducts = $this->supportAiService->buildSuggestedProductsPayload(
            collect($aiResult['matched_products'] ?? [])
        );

        $conversation = DB::transaction(function () use ($khachHang, $guestSessionId, $customerMessage, $needsPharmacist, $aiReply, $suggestedProducts, $intent, $purchaseAction) {
            $lookup = $khachHang instanceof KhachHang
                ? ['id_khach_hang' => $khachHang->id_khach_hang]
                : ['guest_session_id' => $guestSessionId];

            $conversation = HoTroHoiThoai::query()->firstOrCreate($lookup, [
                'guest_display_name' => $khachHang instanceof KhachHang ? null : 'Khách Vãng Lai',
                'trang_thai' => 'moi',
                'thoi_gian_tin_nhan_cuoi' => now(),
            ]);

            $customerMessageTime = now();
            $aiMessageTime = $customerMessageTime->copy()->addSecond();
            $lastMessageTime = $aiReply !== '' ? $aiMessageTime : $customerMessageTime;

            $conversation->update([
                'trang_thai' => $needsPharmacist ? 'moi' : 'dang_trao_doi',
                'thoi_gian_tin_nhan_cuoi' => $lastMessageTime,
            ]);

            HoTroTinNhan::create([
                'id_hoi_thoai' => $conversation->id_hoi_thoai,
                'nguoi_gui_loai' => 'customer',
                'id_khach_hang' => $khachHang instanceof KhachHang ? $khachHang->id_khach_hang : null,
                'noi_dung' => $customerMessage,
                'da_doc' => false,
                'thoi_gian' => $customerMessageTime,
            ]);

            if ($aiReply !== '') {
                HoTroTinNhan::create([
                    'id_hoi_thoai' => $conversation->id_hoi_thoai,
                    'nguoi_gui_loai' => 'ai',
                    'noi_dung' => $aiReply,
                    'du_lieu_bo_sung' => [
                        'intent' => $intent,
                        'san_pham_goi_y' => $suggestedProducts,
                        'hanh_dong_mua_hang' => $purchaseAction,
                    ],
                    'da_doc' => true,
                    'thoi_gian' => $aiMessageTime,
                ]);
            }

            return $this->baseConversationQuery(true)->find($conversation->id_hoi_thoai);
        });

        return response()->json([
            'message' => 'Gui tin nhan ho tro thanh cong.',
            'data' => $this->transformConversation($conversation, true, 'customer'),
        ], 201);
    }

    public function guestDisconnect(Request $request): JsonResponse
    {
        if ($this->resolveSupportCustomer($request) instanceof KhachHang) {
            return response()->json([
                'message' => 'Tai khoan khach hang da dang nhap duoc giu lai hoi thoai.',
                'data' => [
                    'deleted' => false,
                ],
            ]);
        }

        $guestSessionId = $this->resolveGuestSessionId($request);

        if (! $guestSessionId) {
            return response()->json([
                'message' => 'Thieu ma phien khach vang lai.',
            ], 422);
        }

        $conversation = HoTroHoiThoai::query()
            ->where('guest_session_id', $guestSessionId)
            ->whereNull('id_khach_hang')
            ->first();

        if (! $conversation) {
            return response()->json([
                'message' => 'Khong co hoi thoai khach vang lai can xoa.',
                'data' => [
                    'deleted' => false,
                ],
            ]);
        }

        $conversation->delete();

        return response()->json([
            'message' => 'Da xoa hoi thoai khach vang lai.',
            'data' => [
                'deleted' => true,
            ],
        ]);
    }

    public function staffConversationIndex(): JsonResponse
    {
        $conversations = $this->baseConversationQuery()
            ->orderByRaw("
                CASE
                    WHEN trang_thai = 'moi' THEN 0
                    WHEN trang_thai = 'dang_trao_doi' THEN 1
                    ELSE 2
                END
            ")
            ->orderByDesc('thoi_gian_tin_nhan_cuoi')
            ->orderByDesc('id_hoi_thoai')
            ->get();

        return response()->json([
            'message' => 'Lay danh sach hoi thoai ho tro thanh cong.',
            'data' => $conversations
                ->map(fn (HoTroHoiThoai $conversation) => $this->transformConversation($conversation, false, 'staff'))
                ->values(),
        ]);
    }

    public function staffConversationShow(Request $request, int $id): JsonResponse
    {
        $nhanVien = $request->user();
        $conversation = $this->baseConversationQuery(true)->find($id);

        if (! $conversation) {
            return response()->json([
                'message' => 'Khong tim thay hoi thoai ho tro.',
            ], 404);
        }

        if ($nhanVien instanceof NhanVien && ! $conversation->id_nhan_vien_phu_trach) {
            $conversation->update([
                'id_nhan_vien_phu_trach' => $nhanVien->id_nhan_vien,
            ]);
        }

        $this->markMessagesRead($conversation, 'customer');
        $conversation->load($this->conversationRelations(true));

        return response()->json([
            'message' => 'Lay chi tiet hoi thoai ho tro thanh cong.',
            'data' => $this->transformConversation($conversation, true, 'staff'),
        ]);
    }

    public function staffSendMessage(Request $request, int $id): JsonResponse
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return response()->json([
                'message' => 'Tai khoan nay khong duoc phep gui tin nhan ho tro.',
            ], 403);
        }

        $validated = $request->validate([
            'noi_dung' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $conversation = DB::transaction(function () use ($id, $nhanVien, $validated) {
            $conversation = HoTroHoiThoai::query()->lockForUpdate()->find($id);

            if (! $conversation) {
                return null;
            }

            $conversation->update([
                'id_nhan_vien_phu_trach' => $nhanVien->id_nhan_vien,
                'trang_thai' => 'dang_trao_doi',
                'thoi_gian_tin_nhan_cuoi' => now(),
            ]);

            HoTroTinNhan::create([
                'id_hoi_thoai' => $conversation->id_hoi_thoai,
                'nguoi_gui_loai' => 'staff',
                'id_nhan_vien' => $nhanVien->id_nhan_vien,
                'noi_dung' => trim($validated['noi_dung']),
                'da_doc' => false,
                'thoi_gian' => now(),
            ]);

            return $this->baseConversationQuery(true)->find($conversation->id_hoi_thoai);
        });

        if (! $conversation) {
            return response()->json([
                'message' => 'Khong tim thay hoi thoai ho tro.',
            ], 404);
        }

        return response()->json([
            'message' => 'Gui phan hoi cho khach hang thanh cong.',
            'data' => $this->transformConversation($conversation, true, 'staff'),
        ]);
    }

    public function staffCloseConversation(Request $request, int $id): JsonResponse
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return response()->json([
                'message' => 'Tai khoan nay khong duoc phep dong hoi thoai.',
            ], 403);
        }

        $conversation = HoTroHoiThoai::query()->find($id);

        if (! $conversation) {
            return response()->json([
                'message' => 'Khong tim thay hoi thoai ho tro.',
            ], 404);
        }

        $conversation->update([
            'id_nhan_vien_phu_trach' => $conversation->id_nhan_vien_phu_trach ?: $nhanVien->id_nhan_vien,
            'trang_thai' => 'da_dong',
        ]);

        $conversation->load($this->conversationRelations(true));

        return response()->json([
            'message' => 'Da dong hoi thoai ho tro.',
            'data' => $this->transformConversation($conversation, true, 'staff'),
        ]);
    }

    public function staffDeleteConversation(Request $request, int $id): JsonResponse
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return response()->json([
                'message' => 'Tai khoan nay khong duoc phep xoa hoi thoai.',
            ], 403);
        }

        $conversation = HoTroHoiThoai::query()->find($id);

        if (! $conversation) {
            return response()->json([
                'message' => 'Khong tim thay hoi thoai ho tro.',
            ], 404);
        }

        $conversation->delete();

        return response()->json([
            'message' => 'Da xoa hoi thoai ho tro.',
        ]);
    }

    private function baseConversationQuery(bool $includeMessages = false): Builder
    {
        return HoTroHoiThoai::query()
            ->with($this->conversationRelations($includeMessages))
            ->withCount([
                'tinNhans as so_tin_chua_doc_khach' => fn (Builder $query) => $query
                    ->where('nguoi_gui_loai', 'staff')
                    ->where('da_doc', false),
                'tinNhans as so_tin_chua_doc_nhan_vien' => fn (Builder $query) => $query
                    ->where('nguoi_gui_loai', 'customer')
                    ->where('da_doc', false),
            ]);
    }

    private function resolveSupportCustomer(Request $request): ?KhachHang
    {
        $user = $request->user();

        if (! $user instanceof KhachHang) {
            $user = Auth::guard('sanctum')->user();
        }

        return $user instanceof KhachHang ? $user : null;
    }

    private function resolveGuestSessionId(Request $request): ?string
    {
        $rawValue = trim((string) ($request->input('guest_session_id') ?: $request->query('guest_session_id')));

        if ($rawValue === '') {
            return null;
        }

        $normalized = preg_replace('/[^a-zA-Z0-9_-]/', '', $rawValue) ?: '';

        return $normalized !== '' ? substr($normalized, 0, 80) : null;
    }

    private function conversationRelations(bool $includeMessages = false): array
    {
        $relations = [
            'khachHang:id_khach_hang,ten_khach_hang,so_dien_thoai,email,avatar',
            'nhanVienPhuTrach:id_nhan_vien,ho_ten,ten_dang_nhap',
            'latestTinNhan',
            'latestTinNhan.khachHang:id_khach_hang,ten_khach_hang',
            'latestTinNhan.nhanVien:id_nhan_vien,ho_ten,ten_dang_nhap',
        ];

        if ($includeMessages) {
            $relations['tinNhans'] = fn ($query) => $query
                ->with([
                    'khachHang:id_khach_hang,ten_khach_hang',
                    'nhanVien:id_nhan_vien,ho_ten,ten_dang_nhap',
                ])
                ->orderBy('thoi_gian')
                ->orderBy('id_tin_nhan');
        }

        return $relations;
    }

    private function markMessagesRead(HoTroHoiThoai $conversation, string $senderType): void
    {
        HoTroTinNhan::query()
            ->where('id_hoi_thoai', $conversation->id_hoi_thoai)
            ->where('nguoi_gui_loai', $senderType)
            ->where('da_doc', false)
            ->update(['da_doc' => true]);
    }

    private function transformConversation(HoTroHoiThoai $conversation, bool $includeMessages, string $viewerType): array
    {
        $unreadCount = $viewerType === 'customer'
            ? (int) ($conversation->so_tin_chua_doc_khach ?? 0)
            : (int) ($conversation->so_tin_chua_doc_nhan_vien ?? 0);

        return [
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
            'trang_thai' => $conversation->trang_thai,
            'thoi_gian_tin_nhan_cuoi' => optional($conversation->thoi_gian_tin_nhan_cuoi)?->toIso8601String(),
            'so_tin_chua_doc' => $unreadCount,
            'khach_hang' => $this->transformConversationCustomer($conversation),
            'nhan_vien_phu_trach' => $conversation->nhanVienPhuTrach ? [
                'id_nhan_vien' => $conversation->nhanVienPhuTrach->id_nhan_vien,
                'ho_ten' => $conversation->nhanVienPhuTrach->ho_ten,
                'ten_dang_nhap' => $conversation->nhanVienPhuTrach->ten_dang_nhap,
            ] : null,
            'tin_nhan_cuoi' => $conversation->latestTinNhan
                ? $this->transformMessage($conversation->latestTinNhan)
                : null,
            'messages' => $includeMessages && $conversation->relationLoaded('tinNhans')
                ? $conversation->tinNhans->map(fn (HoTroTinNhan $message) => $this->transformMessage($message))->values()
                : [],
        ];
    }

    private function transformConversationCustomer(HoTroHoiThoai $conversation): ?array
    {
        if ($conversation->khachHang) {
            return [
                'id_khach_hang' => $conversation->khachHang->id_khach_hang,
                'ten_khach_hang' => $conversation->khachHang->ten_khach_hang,
                'so_dien_thoai' => $conversation->khachHang->so_dien_thoai,
                'email' => $conversation->khachHang->email,
                'avatar_url' => $conversation->khachHang->avatar_url,
                'la_khach_vang_lai' => false,
            ];
        }

        if (! $conversation->guest_session_id) {
            return null;
        }

        return [
            'id_khach_hang' => null,
            'ten_khach_hang' => $conversation->guest_display_name ?: 'Khách Vãng Lai',
            'so_dien_thoai' => null,
            'email' => null,
            'avatar_url' => null,
            'guest_session_id' => $conversation->guest_session_id,
            'la_khach_vang_lai' => true,
        ];
    }

    private function transformMessage(HoTroTinNhan $message): array
    {
        return [
            'id_tin_nhan' => $message->id_tin_nhan,
            'nguoi_gui_loai' => $message->nguoi_gui_loai,
            'noi_dung' => $message->noi_dung,
            'du_lieu_bo_sung' => $message->du_lieu_bo_sung,
            'da_doc' => (bool) $message->da_doc,
            'thoi_gian' => optional($message->thoi_gian)?->toIso8601String(),
            'khach_hang' => $message->khachHang ? [
                'id_khach_hang' => $message->khachHang->id_khach_hang,
                'ten_khach_hang' => $message->khachHang->ten_khach_hang,
            ] : null,
            'nhan_vien' => $message->nhanVien ? [
                'id_nhan_vien' => $message->nhanVien->id_nhan_vien,
                'ho_ten' => $message->nhanVien->ho_ten,
                'ten_dang_nhap' => $message->nhanVien->ten_dang_nhap,
            ] : null,
        ];
    }
}
