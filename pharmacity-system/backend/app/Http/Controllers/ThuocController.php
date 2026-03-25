<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateThuocPriceRequest;
use App\Models\Thuoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ThuocController extends Controller
{
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
        $validated = $request->validate([
            'ma_thuoc' => 'required|string|size:10|regex:/^TH\d{8}$/|unique:thuocs,ma_thuoc',
            'ten_thuoc' => 'required|string|min:5|max:100',
            'ham_luong' => 'nullable|string|max:50',
            'don_vi_tinh' => 'required|string|max:50',
            'gia_ban' => 'required|numeric|min:1',
            'trang_thai' => ['nullable', Rule::in(['còn bán', 'ngừng bán'])],
            'hinh_anh' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'id_loai_thuoc' => 'required|exists:loai_thuocs,id',
            'id_nha_san_xuat' => 'required|exists:nha_san_xuats,id',
        ]);

        if ($request->hasFile('hinh_anh')) {
            $validated['hinh_anh'] = $request->file('hinh_anh')->store('thuocs', 'public');
        }

        $thuoc = Thuoc::create([
            ...$validated,
            'gia_ban' => (int) $validated['gia_ban'],
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

        $validated = $request->validate([
            'ma_thuoc' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('thuocs', 'ma_thuoc')->ignore($thuoc->ma_thuoc, 'ma_thuoc')],
            'ten_thuoc' => 'sometimes|required|string|min:5|max:100',
            'ham_luong' => 'nullable|string|max:50',
            'don_vi_tinh' => 'sometimes|required|string|max:50',
            'gia_ban' => 'sometimes|required|numeric|min:1',
            'trang_thai' => ['nullable', Rule::in(['còn bán', 'ngừng bán'])],
            'hinh_anh' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'id_loai_thuoc' => 'sometimes|required|exists:loai_thuocs,id',
            'id_nha_san_xuat' => 'sometimes|required|exists:nha_san_xuats,id',
        ]);

        if (array_key_exists('gia_ban', $validated)) {
            $validated['gia_ban'] = (int) $validated['gia_ban'];
        }

        if ($request->hasFile('hinh_anh')) {
            if ($thuoc->hinh_anh) {
                Storage::disk('public')->delete($thuoc->hinh_anh);
            }

            $validated['hinh_anh'] = $request->file('hinh_anh')->store('thuocs', 'public');
        }

        $thuoc->update($validated);

        return response()->json([
            'message' => 'Cập nhật thuốc thành công.',
            'data' => $this->baseQuery()->findOrFail($thuoc->fresh()->ma_thuoc),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $thuoc = Thuoc::query()->find($id);

        if (! $thuoc) {
            return response()->json(['message' => 'Không tìm thấy thuốc.'], 404);
        }

        if ($thuoc->hinh_anh) {
            Storage::disk('public')->delete($thuoc->hinh_anh);
        }

        $thuoc->delete();

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
                        ->orWhereHas('loaiThuoc', fn ($loaiQuery) => $loaiQuery->where('ten_loai', 'like', '%' . $keyword . '%'))
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
                'loaiThuoc',
                'nhaSanXuat',
                'khuyenMais' => fn ($query) => $query->latest(),
            ])
            ->orderBy('ten_thuoc');
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
}
