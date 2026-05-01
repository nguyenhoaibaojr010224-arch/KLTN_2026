<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateThuocPriceRequest;
use App\Models\Thuoc;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ThuocController extends Controller
{
    private const DANH_MUC_CHA_ALLOWED_SLUGS = [
        'thuoc-khong-ke-don' => [
            'thuoc-khong-ke-don',
            'khong-ke-don',
            'khong-ke-don-ngua-thai',
            'khong-ke-don-khang-di-ung',
            'khong-ke-don-khang-viem',
            'khong-ke-don-cam-lanh',
            'khong-ke-don-giam-can',
            'khong-ke-don-mat-tai-mui',
            'khong-ke-don-tieu-hoa',
            'khong-ke-don-giam-dau-ha-sot',
            'khong-ke-don-da-lieu',
            'khong-ke-don-danh-cho-nam',
            'khong-ke-don-danh-cho-nu',
            'khong-ke-don-than-kinh',
            'khong-ke-don-xuong-khop',
            'khong-ke-don-dau-cao-xoa-bop',
        ],
        'thuoc-ke-don' => [
            'thuoc-ke-don',
            'ke-don',
            'ke-don-khang-sinh',
            'ke-don-ung-thu',
            'ke-don-tiet-nieu',
            'ke-don-danh-cho-nam',
            'ke-don-danh-cho-nu',
            'ke-don-mat-tai-mui',
            'ke-don-da-lieu',
            'ke-don-thuoc-ho-cam-lanh',
            'ke-don-ngua-thai',
            'ke-don-tim-mach-huyet-ap',
            'ke-don-tieu-hoa',
            'ke-don-tieu-duong',
            'ke-don-khang-di-ung',
            'ke-don-khang-viem',
            'ke-don-than-kinh',
            'ke-don-giam-dau-ha-sot',
            'ke-don-he-ho-hap',
            'ke-don-co-xuong-khop',
        ],
        'vitamin-thuc-pham-chuc-nang' => [
            'vitamin-thuc-pham-chuc-nang',
        ],
        'tra-cuu-benh' => [
            'suc-khoe-sinh-san',
            'tai-mui-hong',
            'co-xuong-khop',
            'truyen-nhiem',
            'than-tiet-nieu',
            'mau',
            'mat',
            'ho-hap',
            'tam-than',
            'di-ung',
            'tim-mach',
            'vitamin-khoang-chat',
        ],
        'cham-soc-sac-dep' => [
            'cham-soc-da',
            'cham-soc-toc',
            'chong-nang',
            've-sinh-ca-nhan',
        ],
        'khac' => [
            'khac',
            'thuoc-khac',
            'tat-ca-thuoc-khac',
        ],
    ];

    private const DANH_MUC_THUOC_SLUGS = [
        'khong-ke-don',
        'ke-don',
        'thuoc-khac',
        'thuoc-khong-ke-don',
        'thuoc-ke-don',
        'vitamin-thuc-pham-chuc-nang',
        'tra-cuu-benh',
        'cham-soc-sac-dep',
        'khac',
        'tat-ca-thuoc-khac',
        'khong-ke-don-ngua-thai',
        'khong-ke-don-khang-di-ung',
        'khong-ke-don-khang-viem',
        'khong-ke-don-cam-lanh',
        'khong-ke-don-giam-can',
        'khong-ke-don-mat-tai-mui',
        'khong-ke-don-tieu-hoa',
        'khong-ke-don-giam-dau-ha-sot',
        'khong-ke-don-da-lieu',
        'khong-ke-don-danh-cho-nam',
        'khong-ke-don-danh-cho-nu',
        'khong-ke-don-than-kinh',
        'khong-ke-don-xuong-khop',
        'khong-ke-don-dau-cao-xoa-bop',
        'ke-don-khang-sinh',
        'ke-don-ung-thu',
        'ke-don-tiet-nieu',
        'ke-don-danh-cho-nam',
        'ke-don-danh-cho-nu',
        'ke-don-mat-tai-mui',
        'ke-don-da-lieu',
        'ke-don-thuoc-ho-cam-lanh',
        'ke-don-ngua-thai',
        'ke-don-tim-mach-huyet-ap',
        'ke-don-tieu-hoa',
        'ke-don-tieu-duong',
        'ke-don-khang-di-ung',
        'ke-don-khang-viem',
        'ke-don-than-kinh',
        'ke-don-giam-dau-ha-sot',
        'ke-don-he-ho-hap',
        'ke-don-co-xuong-khop',
        'suc-khoe-sinh-san',
        'tai-mui-hong',
        'co-xuong-khop',
        'truyen-nhiem',
        'than-tiet-nieu',
        'mau',
        'mat',
        'ho-hap',
        'tam-than',
        'di-ung',
        'tim-mach',
        'vitamin-khoang-chat',
        'cham-soc-da',
        'cham-soc-toc',
        'chong-nang',
        've-sinh-ca-nhan',
    ];

    public function index(): JsonResponse
    {
        return response()->json($this->baseQuery()->get());
    }

    public function nextCode(): JsonResponse
    {
        return response()->json([
            'ma_thuoc' => $this->generateMaThuoc(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rulesForStore());
        $this->assertDanhMucBucketConsistency($validated, $request);
        $validated['nhan'] = $this->normalizeNhanPayload($validated['nhan'] ?? '');
        $this->assertNhanIsPresent($validated['nhan']);

        $validated['quy_cach_don_vi'] = $this->parseQuyCachDonViPayload(
            $request->input('quy_cach_don_vi'),
            $validated['don_vi_tinh'],
            $validated['don_vi_co_so']
        );

        $this->applyImagePayload($request, $validated);

        $thuoc = Thuoc::create([
            ...$validated,
            'gia_ban' => (int) $validated['gia_ban'],
            'he_so_quy_doi' => max(1, (int) $validated['he_so_quy_doi']),
            'trang_thai' => $validated['trang_thai'] ?? 'còn bán',
        ]);

        return response()->json([
            'message' => 'Tạo thuốc thành công.',
            'data' => $this->baseQuery()->findOrFail($thuoc->ma_thuoc),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $thuoc = $this->baseQuery()->find($id);

        if (! $thuoc) {
            return response()->json(['message' => 'Không tìm thấy thuốc.'], 404);
        }

        return response()->json($thuoc);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $thuoc = Thuoc::query()->find($id);

        if (! $thuoc) {
            return response()->json(['message' => 'Không tìm thấy thuốc.'], 404);
        }

        $validated = $request->validate($this->rulesForUpdate($thuoc));
        $this->assertDanhMucBucketConsistency($validated, $request);

        if (array_key_exists('nhan', $validated)) {
            $validated['nhan'] = $this->normalizeNhanPayload($validated['nhan']);
            $this->assertNhanIsPresent($validated['nhan']);
        }

        if (array_key_exists('gia_ban', $validated)) {
            $validated['gia_ban'] = (int) $validated['gia_ban'];
        }

        if (array_key_exists('he_so_quy_doi', $validated)) {
            $validated['he_so_quy_doi'] = max(1, (int) $validated['he_so_quy_doi']);
        }

        if ($request->has('quy_cach_don_vi')) {
            $validated['quy_cach_don_vi'] = $this->parseQuyCachDonViPayload(
                $request->input('quy_cach_don_vi'),
                (string) ($validated['don_vi_tinh'] ?? $thuoc->don_vi_tinh),
                (string) ($validated['don_vi_co_so'] ?? $thuoc->don_vi_co_so ?? $thuoc->don_vi_tinh)
            );
        }

        $this->applyImagePayload($request, $validated, $thuoc);

        $thuoc->update($validated);

        return response()->json([
            'message' => 'Cập nhật thuốc thành công.',
            'data' => $this->baseQuery()->findOrFail($thuoc->fresh()->ma_thuoc),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $thuoc = Thuoc::query()
            ->with([
                'loThuocs' => fn ($query) => $query->withCount(['chiTietHoaDons', 'chiTietPhieuNhaps']),
            ])
            ->find($id);

        if (! $thuoc) {
            return response()->json(['message' => 'Không tìm thấy thuốc.'], 404);
        }

        $soLoGanHoaDon = $thuoc->loThuocs
            ->filter(fn ($loThuoc) => (int) $loThuoc->chi_tiet_hoa_dons_count > 0)
            ->count();
        $soLoGanPhieuNhap = $thuoc->loThuocs
            ->filter(fn ($loThuoc) => (int) $loThuoc->chi_tiet_phieu_nhaps_count > 0)
            ->count();

        if ($soLoGanHoaDon > 0 || $soLoGanPhieuNhap > 0) {
            return response()->json([
                'message' => $this->buildDeleteBlockedMessage($soLoGanHoaDon, $soLoGanPhieuNhap),
                'data' => [
                    'so_lo_gan_hoa_don' => $soLoGanHoaDon,
                    'so_lo_gan_phieu_nhap' => $soLoGanPhieuNhap,
                ],
            ], 409);
        }

        $imagePath = $thuoc->hinh_anh;

        try {
            $thuoc->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Không thể xóa thuốc này vì đã phát sinh dữ liệu liên quan. Hãy chuyển sang trạng thái ngừng bán.',
            ], 409);
        }

        $this->deleteStoredImage($imagePath);

        return response()->json(['message' => 'Xóa thuốc thành công.']);
    }

    public function search(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->get('q', ''));

        $thuocs = $this->baseQuery()
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($innerQuery) use ($keyword): void {
                    $innerQuery->where('ten_thuoc', 'like', '%' . $keyword . '%')
                        ->orWhere('ma_thuoc', 'like', '%' . $keyword . '%')
                        ->orWhere('ham_luong', 'like', '%' . $keyword . '%')
                        ->orWhere('don_vi_tinh', 'like', '%' . $keyword . '%')
                        ->orWhere('don_vi_co_so', 'like', '%' . $keyword . '%')
                        ->orWhere('nhan', 'like', '%' . $keyword . '%')
                        ->orWhere('mo_ta', 'like', '%' . $keyword . '%')
                        ->orWhere('lieu_luong', 'like', '%' . $keyword . '%')
                        ->orWhereHas('nhaSanXuat', fn ($nhaSanXuatQuery) => $nhaSanXuatQuery->where('ten_nha_san_xuat', 'like', '%' . $keyword . '%'));
                });
            })
            ->get();

        return response()->json($thuocs);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $thuoc = Thuoc::query()->find($id);

        if (! $thuoc) {
            return response()->json(['message' => 'Không tìm thấy thuốc.'], 404);
        }

        $request->validate([
            'trang_thai' => ['required', Rule::in(['còn bán', 'ngừng bán'])],
        ]);

        $thuoc->update(['trang_thai' => $request->trang_thai]);

        return response()->json([
            'message' => 'Cập nhật trạng thái thuốc thành công.',
            'data' => $this->baseQuery()->findOrFail($thuoc->ma_thuoc),
        ]);
    }

    public function updatePrice(UpdateThuocPriceRequest $request, string $id): JsonResponse
    {
        $thuoc = Thuoc::query()->find($id);

        if (! $thuoc) {
            return response()->json(['message' => 'Khong tim thay thuoc'], 404);
        }

        $thuoc->update([
            'gia_ban' => (int) $request->validated('gia_ban'),
        ]);

        return response()->json([
            'message' => 'Cap nhat gia ban thanh cong.',
            'data' => $this->baseQuery()->findOrFail($thuoc->ma_thuoc),
        ]);
    }

    private function baseQuery()
    {
        return Thuoc::query()
            ->with([
                'nhaSanXuat',
                'khuyenMais' => fn ($query) => $query->latest(),
            ])
            ->orderBy('ten_thuoc');
    }

    private function rulesForStore(): array
    {
        return [
            'ma_thuoc' => 'required|string|size:10|regex:/^TH\d{8}$/|unique:thuocs,ma_thuoc',
            'ten_thuoc' => 'required|string|min:5|max:100',
            'ham_luong' => 'nullable|string|max:50',
            'don_vi_tinh' => 'required|string|max:50',
            'don_vi_co_so' => 'required|string|max:50',
            'he_so_quy_doi' => 'required|integer|min:1',
            'gia_ban' => 'required|numeric|min:1',
            'mo_ta' => 'nullable|string|max:5000',
            'lieu_luong' => 'nullable|string|max:5000',
            'nhan' => 'required|string|min:2|max:500',
            'trang_thai' => ['nullable', Rule::in(['còn bán', 'ngừng bán'])],
            'hinh_anh' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'hinh_anh_url' => ['nullable', 'string', 'max:2048', 'url', 'regex:/^https?:\/\//i'],
            'id_nha_san_xuat' => 'required|exists:nha_san_xuats,id',
            'danh_muc_cha_slug' => 'nullable|string|max:100',
            'danh_muc_thuoc_slug' => ['nullable', Rule::in(self::DANH_MUC_THUOC_SLUGS)],
        ];
    }

    private function rulesForUpdate(Thuoc $thuoc): array
    {
        return [
            'ma_thuoc' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('thuocs', 'ma_thuoc')->ignore($thuoc->ma_thuoc, 'ma_thuoc')],
            'ten_thuoc' => 'sometimes|required|string|min:5|max:100',
            'ham_luong' => 'nullable|string|max:50',
            'don_vi_tinh' => 'sometimes|required|string|max:50',
            'don_vi_co_so' => 'sometimes|required|string|max:50',
            'he_so_quy_doi' => 'sometimes|required|integer|min:1',
            'gia_ban' => 'sometimes|required|numeric|min:1',
            'mo_ta' => 'nullable|string|max:5000',
            'lieu_luong' => 'nullable|string|max:5000',
            'nhan' => 'sometimes|required|string|min:2|max:500',
            'trang_thai' => ['nullable', Rule::in(['còn bán', 'ngừng bán'])],
            'hinh_anh' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'hinh_anh_url' => ['nullable', 'string', 'max:2048', 'url', 'regex:/^https?:\/\//i'],
            'id_nha_san_xuat' => 'sometimes|required|exists:nha_san_xuats,id',
            'danh_muc_cha_slug' => 'nullable|string|max:100',
            'danh_muc_thuoc_slug' => ['nullable', Rule::in(self::DANH_MUC_THUOC_SLUGS)],
        ];
    }

    private function assertDanhMucBucketConsistency(array $validated, Request $request): void
    {
        $danhMucSlug = trim((string) ($validated['danh_muc_thuoc_slug'] ?? ''));
        $danhMucChaSlug = trim((string) $request->input('danh_muc_cha_slug', ''));

        if ($danhMucSlug === '' || $danhMucChaSlug === '') {
            return;
        }

        $allowedSlugs = self::DANH_MUC_CHA_ALLOWED_SLUGS[$danhMucChaSlug] ?? null;

        if ($allowedSlugs === null) {
            return;
        }

        if (! in_array($danhMucSlug, $allowedSlugs, true)) {
            throw ValidationException::withMessages([
                'danh_muc_thuoc_slug' => 'Thuốc chỉ được lưu trong đúng mục cha đã chọn.',
            ]);
        }
    }

    private function generateMaThuoc(): string
    {
        $latestCode = Thuoc::query()
            ->where('ma_thuoc', 'like', 'TH%')
            ->orderByDesc('ma_thuoc')
            ->value('ma_thuoc');

        $nextNumber = 1;

        if (is_string($latestCode) && preg_match('/^TH(\d{8})$/', $latestCode, $matches) === 1) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = 'TH' . str_pad((string) $nextNumber, 8, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Thuoc::query()->where('ma_thuoc', $candidate)->exists());

        return $candidate;
    }

    private function applyImagePayload(Request $request, array &$validated, ?Thuoc $thuoc = null): void
    {
        $imageUrl = isset($validated['hinh_anh_url'])
            ? trim((string) $validated['hinh_anh_url'])
            : null;

        unset($validated['hinh_anh_url']);

        if ($request->hasFile('hinh_anh')) {
            if ($thuoc) {
                $this->deleteStoredImage($thuoc->hinh_anh);
            }

            $validated['hinh_anh'] = $request->file('hinh_anh')->store('thuocs', 'public');
            return;
        }

        if ($imageUrl !== null && $imageUrl !== '') {
            if ($thuoc && $thuoc->hinh_anh !== $imageUrl) {
                $this->deleteStoredImage($thuoc->hinh_anh);
            }

            $validated['hinh_anh'] = $imageUrl;
        }
    }

    private function parseQuyCachDonViPayload(mixed $rawPayload, string $donViChinh, string $donViCoSo): ?array
    {
        if ($rawPayload === null) {
            return null;
        }

        if (is_string($rawPayload) && trim($rawPayload) === '') {
            return null;
        }

        $decoded = is_array($rawPayload) ? $rawPayload : json_decode((string) $rawPayload, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'quy_cach_don_vi' => ['Dữ liệu đơn vị thêm không hợp lệ.'],
            ]);
        }

        $daDungDonVi = [$this->normalizeDonViLookupKey($donViChinh) => true];
        $normalized = [];

        foreach ($decoded as $index => $item) {
            if (! is_array($item)) {
                throw ValidationException::withMessages([
                    'quy_cach_don_vi' => ['Đơn vị thêm ở dòng ' . ($index + 1) . ' không hợp lệ.'],
                ]);
            }

            $tenDonVi = $this->normalizeDonViName($item['ten_don_vi'] ?? null);
            $giaBan = $item['gia_ban'] ?? null;
            $soLuongQuyDoi = $item['so_luong_quy_doi'] ?? null;

            if ($tenDonVi === '') {
                throw ValidationException::withMessages([
                    'quy_cach_don_vi' => ['Vui lòng nhập tên đơn vị thêm ở dòng ' . ($index + 1) . '.'],
                ]);
            }

            if (isset($daDungDonVi[$this->normalizeDonViLookupKey($tenDonVi)])) {
                throw ValidationException::withMessages([
                    'quy_cach_don_vi' => ["Đơn vị {$tenDonVi} đang bị trùng."],
                ]);
            }

            if (! is_numeric($giaBan) || (int) $giaBan < 1) {
                throw ValidationException::withMessages([
                    'quy_cach_don_vi' => ["Giá bán của đơn vị {$tenDonVi} phải lớn hơn 0."],
                ]);
            }

            if (! is_numeric($soLuongQuyDoi) || (int) $soLuongQuyDoi < 1) {
                throw ValidationException::withMessages([
                    'quy_cach_don_vi' => ["Số lượng quy đổi của đơn vị {$tenDonVi} phải lớn hơn 0."],
                ]);
            }

            $daDungDonVi[$this->normalizeDonViLookupKey($tenDonVi)] = true;

            $normalized[] = [
                'ten_don_vi' => $tenDonVi,
                'gia_ban' => (int) $giaBan,
                'so_luong_quy_doi' => (int) $soLuongQuyDoi,
                'don_vi_co_so' => $this->normalizeDonViName($donViCoSo),
            ];
        }

        return $normalized === [] ? null : $normalized;
    }

    private function normalizeDonViName(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) ($value ?? '')));
    }

    private function normalizeDonViLookupKey(mixed $value): string
    {
        return mb_strtolower($this->normalizeDonViName($value));
    }

    private function normalizeNhanPayload(string $value): string
    {
        return collect(preg_split('/[\r\n,;|]+/u', $value) ?: [])
            ->map(fn (string $item): string => trim(preg_replace('/\s+/u', ' ', $item)))
            ->filter()
            ->unique(fn (string $item): string => mb_strtolower($item))
            ->implode(', ');
    }

    private function assertNhanIsPresent(string $value): void
    {
        if (trim($value) === '') {
            throw ValidationException::withMessages([
                'nhan' => 'Vui lòng nhập ít nhất một nhãn hợp lệ cho thuốc.',
            ]);
        }
    }

    private function deleteStoredImage(?string $imagePath): void
    {
        if (! $imagePath || $this->isExternalImageReference($imagePath)) {
            return;
        }

        Storage::disk('public')->delete($imagePath);
    }

    private function isExternalImageReference(?string $imagePath): bool
    {
        if (! is_string($imagePath) || $imagePath === '') {
            return false;
        }

        return preg_match('/^https?:\/\//i', $imagePath) === 1;
    }

    private function buildDeleteBlockedMessage(int $soLoGanHoaDon, int $soLoGanPhieuNhap): string
    {
        $reasons = [];

        if ($soLoGanHoaDon > 0) {
            $reasons[] = "{$soLoGanHoaDon} lô đã xuất hiện trong hóa đơn";
        }

        if ($soLoGanPhieuNhap > 0) {
            $reasons[] = "{$soLoGanPhieuNhap} lô đã gắn với phiếu nhập";
        }

        $reasonText = implode(' và ', $reasons);

        return "Không thể xóa thuốc này vì {$reasonText}. Hãy chuyển thuốc sang trạng thái ngừng bán nếu không muốn tiếp tục kinh doanh.";
    }
}
