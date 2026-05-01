<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StoreKhuyenMaiRequest;
use App\Http\Requests\UpdateKhuyenMaiRequest;
use App\Models\KhuyenMai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class KhuyenMaiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->string('q'));

        $khuyenMais = KhuyenMai::query()
            ->with(['thuoc', 'nhanVien'])
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($innerQuery) use ($keyword): void {
                    $innerQuery
                        ->where('ten_khuyen_mai', 'like', '%' . $keyword . '%')
                        ->orWhere('ma_thuoc', 'like', '%' . $keyword . '%')
                        ->orWhere('ma_khuyen_mai', 'like', '%' . $keyword . '%')
                        ->orWhere('nhan_hien_thi', 'like', '%' . $keyword . '%')
                        ->orWhereHas('thuoc', fn ($thuocQuery) => $thuocQuery->where('ten_thuoc', 'like', '%' . $keyword . '%'));
                });
            })
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Lay danh sach khuyen mai thanh cong.',
            'data' => $khuyenMais->map(fn (KhuyenMai $khuyenMai) => $this->transformKhuyenMai($khuyenMai)),
        ]);
    }

    public function store(StoreKhuyenMaiRequest $request): JsonResponse
    {
        $payload = $this->normalizeDatePayload($request->validated());

        $khuyenMai = KhuyenMai::create([
            ...$payload,
            'ma_khuyen_mai' => $this->normalizePromotionCode($payload['ma_khuyen_mai'] ?? null),
            'id_nhan_vien' => $request->user()?->id_nhan_vien,
        ]);

        $khuyenMai->load(['thuoc', 'nhanVien']);

        return response()->json([
            'message' => 'Tao khuyen mai thanh cong.',
            'data' => $this->transformKhuyenMai($khuyenMai),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $khuyenMai = KhuyenMai::with(['thuoc', 'nhanVien'])->find($id);

        if (! $khuyenMai) {
            return response()->json(['message' => 'Khong tim thay khuyen mai.'], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet khuyen mai thanh cong.',
            'data' => $this->transformKhuyenMai($khuyenMai),
        ]);
    }

    public function update(UpdateKhuyenMaiRequest $request, int $id): JsonResponse
    {
        $khuyenMai = KhuyenMai::find($id);

        if (! $khuyenMai) {
            return response()->json(['message' => 'Khong tim thay khuyen mai.'], 404);
        }

        $payload = $this->normalizeDatePayload($request->validated());

        if (array_key_exists('ma_khuyen_mai', $payload)) {
            $payload['ma_khuyen_mai'] = $this->normalizePromotionCode($payload['ma_khuyen_mai']);
        }

        $payload['id_nhan_vien'] = $request->user()?->id_nhan_vien;

        $khuyenMai->update($payload);
        $khuyenMai->load(['thuoc', 'nhanVien']);

        return response()->json([
            'message' => 'Cap nhat khuyen mai thanh cong.',
            'data' => $this->transformKhuyenMai($khuyenMai),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $khuyenMai = KhuyenMai::find($id);

        if (! $khuyenMai) {
            return response()->json(['message' => 'Khong tim thay khuyen mai.'], 404);
        }

        $khuyenMai->delete();

        return response()->json(['message' => 'Xoa khuyen mai thanh cong.']);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        return $this->index($request);
    }

    private function transformKhuyenMai(KhuyenMai $khuyenMai): array
    {
        $giaNiemYet = (int) ($khuyenMai->thuoc?->gia_ban ?? 0);
        $giaSauGiam = $giaNiemYet > 0 ? $khuyenMai->tinhGiaSauGiam($giaNiemYet) : 0;

        return [
            'id' => $khuyenMai->id,
            'ma_thuoc' => $khuyenMai->ma_thuoc,
            'ma_khuyen_mai' => $khuyenMai->ma_khuyen_mai,
            'ten_thuoc' => $khuyenMai->thuoc?->ten_thuoc,
            'ten_khuyen_mai' => $khuyenMai->ten_khuyen_mai,
            'mo_ta' => $khuyenMai->mo_ta,
            'loai_ap_dung' => $khuyenMai->loai_ap_dung,
            'gia_tri' => (int) $khuyenMai->gia_tri,
            'nhan_hien_thi' => $khuyenMai->nhan_hien_thi,
            'trang_thai' => $khuyenMai->trang_thai,
            'ngay_bat_dau' => optional($khuyenMai->ngay_bat_dau)->toDateTimeString(),
            'ngay_ket_thuc' => optional($khuyenMai->ngay_ket_thuc)->toDateTimeString(),
            'gia_niem_yet' => $giaNiemYet,
            'gia_sau_giam' => $giaSauGiam,
            'nhan_vien_cap_nhat' => $khuyenMai->nhanVien?->ho_ten,
        ];
    }

    private function normalizePromotionCode(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Str::upper(trim($value));
    }

    private function normalizeDatePayload(array $payload): array
    {
        foreach (['ngay_bat_dau', 'ngay_ket_thuc'] as $field) {
            if (! filled($payload[$field] ?? null)) {
                $payload[$field] = null;
                continue;
            }

            $payload[$field] = Carbon::parse(
                (string) $payload[$field],
                'Asia/Ho_Chi_Minh'
            )->setTimezone(config('app.timezone'));
        }

        return $payload;
    }
}
