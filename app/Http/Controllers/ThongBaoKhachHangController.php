<?php

namespace App\Http\Controllers;

use App\Models\KhachHang;
use App\Models\ThongBaoKhachHang;
use App\Models\ThongBaoKhachHangDaDoc;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ThongBaoKhachHangController extends Controller
{
    private const NHOM_HOP_LE = [
        'Ưu đãi',
        'Hệ thống',
        'Thương hiệu',
        'Sức khỏe',
        'Tin tức',
    ];

    public function index(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->string('q'));

        $items = ThongBaoKhachHang::query()
            ->with('nhanVienTao')
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $innerQuery) use ($keyword): void {
                    $innerQuery
                        ->where('nhom', 'like', '%' . $keyword . '%')
                        ->orWhere('tieu_de', 'like', '%' . $keyword . '%')
                        ->orWhere('noi_dung', 'like', '%' . $keyword . '%');
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id_thong_bao')
            ->get();

        return response()->json([
            'message' => 'Lấy danh sách thông báo khách hàng thành công.',
            'data' => $items->map(fn (ThongBaoKhachHang $item) => $this->transformAdminItem($item))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $item = ThongBaoKhachHang::create([
            ...$payload,
            'id_nhan_vien_tao' => $request->user()?->id_nhan_vien,
        ]);

        $item->load('nhanVienTao');

        return response()->json([
            'message' => 'Tạo thông báo khách hàng thành công.',
            'data' => $this->transformAdminItem($item),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ThongBaoKhachHang::query()->find($id);

        if (! $item) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo khách hàng.',
            ], 404);
        }

        $item->update([
            ...$this->validatePayload($request),
            'id_nhan_vien_tao' => $request->user()?->id_nhan_vien,
        ]);

        $item->load('nhanVienTao');

        return response()->json([
            'message' => 'Cập nhật thông báo khách hàng thành công.',
            'data' => $this->transformAdminItem($item),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = ThongBaoKhachHang::query()->find($id);

        if (! $item) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo khách hàng.',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'message' => 'Xóa thông báo khách hàng thành công.',
        ]);
    }

    public function customerList(Request $request): JsonResponse
    {
        $khachHang = $request->user();

        if (! $khachHang instanceof KhachHang) {
            return response()->json([
                'message' => 'Tài khoản này không được phép truy cập thông báo khách hàng.',
            ], 403);
        }

        $items = ThongBaoKhachHang::query()
            ->dangHienThiChoKhachHang()
            ->with([
                'daDocs' => fn (Builder $query) => $query
                    ->where('id_khach_hang', $khachHang->id_khach_hang),
            ])
            ->orderByDesc('ngay_bat_dau')
            ->orderByDesc('created_at')
            ->orderByDesc('id_thong_bao')
            ->get();

        return response()->json([
            'message' => 'Lấy danh sách thông báo khách hàng thành công.',
            'data' => $items->map(
                fn (ThongBaoKhachHang $item) => $this->transformCustomerItem($item, $khachHang)
            )->values(),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $khachHang = $request->user();

        if (! $khachHang instanceof KhachHang) {
            return response()->json([
                'message' => 'Tài khoản này không được phép cập nhật trạng thái thông báo.',
            ], 403);
        }

        $item = ThongBaoKhachHang::query()
            ->dangHienThiChoKhachHang()
            ->find($id);

        if (! $item) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo khách hàng.',
            ], 404);
        }

        ThongBaoKhachHangDaDoc::query()->firstOrCreate(
            [
                'id_thong_bao' => $item->id_thong_bao,
                'id_khach_hang' => $khachHang->id_khach_hang,
            ],
            [
                'da_doc_luc' => Carbon::now(),
            ]
        );

        $item->load([
            'daDocs' => fn (Builder $query) => $query
                ->where('id_khach_hang', $khachHang->id_khach_hang),
        ]);

        return response()->json([
            'message' => 'Đã cập nhật trạng thái đọc thông báo.',
            'data' => $this->transformCustomerItem($item, $khachHang),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $khachHang = $request->user();

        if (! $khachHang instanceof KhachHang) {
            return response()->json([
                'message' => 'Tài khoản này không được phép cập nhật trạng thái thông báo.',
            ], 403);
        }

        $validated = $request->validate([
            'nhom' => ['nullable', 'string', 'max:50'],
        ]);

        $items = ThongBaoKhachHang::query()
            ->dangHienThiChoKhachHang()
            ->when(! empty($validated['nhom']), function (Builder $query) use ($validated): void {
                $query->where('nhom', $validated['nhom']);
            })
            ->pluck('id_thong_bao');

        foreach ($items as $notificationId) {
            ThongBaoKhachHangDaDoc::query()->firstOrCreate(
                [
                    'id_thong_bao' => $notificationId,
                    'id_khach_hang' => $khachHang->id_khach_hang,
                ],
                [
                    'da_doc_luc' => Carbon::now(),
                ]
            );
        }

        return response()->json([
            'message' => 'Đã đánh dấu đọc các thông báo phù hợp.',
        ]);
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'nhom' => ['required', 'string', 'in:' . implode(',', self::NHOM_HOP_LE)],
            'tieu_de' => ['required', 'string', 'min:3', 'max:180'],
            'noi_dung' => ['required', 'string', 'min:5', 'max:4000'],
            'loai_gui' => ['nullable', 'string', 'in:broadcast'],
            'doi_tuong' => ['nullable', 'string', 'in:tat_ca_khach_hang'],
            'ngay_bat_dau' => ['nullable', 'date'],
            'ngay_ket_thuc' => ['nullable', 'date', 'after_or_equal:ngay_bat_dau'],
            'trang_thai' => ['required', 'string', 'in:draft,active,inactive'],
        ], [
            'nhom.required' => 'Vui lòng chọn nhóm thông báo.',
            'tieu_de.required' => 'Vui lòng nhập tiêu đề thông báo.',
            'noi_dung.required' => 'Vui lòng nhập nội dung thông báo.',
            'ngay_ket_thuc.after_or_equal' => 'Thời gian kết thúc phải sau hoặc bằng thời gian bắt đầu.',
        ]);

        return [
            'nhom' => $validated['nhom'],
            'tieu_de' => trim($validated['tieu_de']),
            'noi_dung' => trim($validated['noi_dung']),
            'loai_gui' => $validated['loai_gui'] ?? 'broadcast',
            'doi_tuong' => $validated['doi_tuong'] ?? 'tat_ca_khach_hang',
            'ngay_bat_dau' => $validated['ngay_bat_dau'] ?? null,
            'ngay_ket_thuc' => $validated['ngay_ket_thuc'] ?? null,
            'trang_thai' => $validated['trang_thai'],
        ];
    }

    private function transformAdminItem(ThongBaoKhachHang $item): array
    {
        return [
            'id' => $item->id_thong_bao,
            'nhom' => $item->nhom,
            'tieu_de' => $item->tieu_de,
            'noi_dung' => $item->noi_dung,
            'loai_gui' => $item->loai_gui,
            'doi_tuong' => $item->doi_tuong,
            'ngay_bat_dau' => $item->ngay_bat_dau?->toIso8601String(),
            'ngay_ket_thuc' => $item->ngay_ket_thuc?->toIso8601String(),
            'trang_thai' => $item->trang_thai,
            'nguoi_tao' => $item->nhanVienTao?->ho_ten,
            'id_nhan_vien_tao' => $item->id_nhan_vien_tao,
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    private function transformCustomerItem(ThongBaoKhachHang $item, KhachHang $khachHang): array
    {
        $isRead = $item->daDocs
            ->contains(fn (ThongBaoKhachHangDaDoc $readItem) => $readItem->id_khach_hang === $khachHang->id_khach_hang);

        return [
            'id' => 'broadcast-' . $item->id_thong_bao,
            'remote_id' => $item->id_thong_bao,
            'scope' => 'shared',
            'group' => $item->nhom,
            'tieuDe' => $item->tieu_de,
            'noiDung' => $item->noi_dung,
            'daDoc' => $isRead,
            'createdAt' => ($item->ngay_bat_dau ?? $item->created_at)?->toIso8601String(),
        ];
    }
}
