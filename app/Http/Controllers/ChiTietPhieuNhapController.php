<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StoreChiTietPhieuNhapRequest;
use App\Http\Requests\UpdateChiTietPhieuNhapRequest;
use App\Models\ChiTietPhieuNhap;
use App\Models\LoThuoc;
use App\Models\PhieuNhap;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChiTietPhieuNhapController extends Controller
{
    public function indexByPhieuNhap(int $idPhieuNhap): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach chi tiet phieu nhap thanh cong.',
            'data' => $this->baseQuery()->where('id_phieu_nhap', $idPhieuNhap)->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->baseQuery()->find($id);

        if (! $record) {
            return response()->json(['message' => 'Khong tim thay chi tiet phieu nhap.'], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet phieu nhap thanh cong.',
            'data' => $record,
        ]);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $records = $this->baseQuery()
            ->where(function ($query) use ($keyword): void {
                $query->where('id', 'like', '%' . $keyword . '%')
                    ->orWhere('id_phieu_nhap', 'like', '%' . $keyword . '%')
                    ->orWhereHas('loThuoc', function ($loQuery) use ($keyword): void {
                        $loQuery->where('so_lo', 'like', '%' . $keyword . '%')
                            ->orWhere('id_thuoc', 'like', '%' . $keyword . '%')
                            ->orWhereHas('thuoc', function ($thuocQuery) use ($keyword): void {
                                $thuocQuery->where('ten_thuoc', 'like', '%' . $keyword . '%');
                            });
                    });
            })
            ->get();

        return response()->json([
            'message' => 'Tim kiem chi tiet phieu nhap thanh cong.',
            'data' => $records,
        ]);
    }

    public function store(StoreChiTietPhieuNhapRequest $request, int $idPhieuNhap): JsonResponse
    {
        $phieuNhap = PhieuNhap::find($idPhieuNhap);

        if (! $phieuNhap) {
            return response()->json(['message' => 'Khong tim thay phieu nhap.'], 404);
        }

        $detail = DB::transaction(function () use ($request, $idPhieuNhap) {
            $validated = $request->validated();
            $loThuoc = LoThuoc::lockForUpdate()->findOrFail($validated['id_lo']);

            $detail = ChiTietPhieuNhap::create([
                'id_phieu_nhap' => $idPhieuNhap,
                'id_lo' => $validated['id_lo'],
                'don_vi_nhap' => $loThuoc->don_vi_nhap,
                'don_vi_co_so' => $loThuoc->don_vi_co_so,
                'so_luong_nhap_goc' => $validated['so_luong'],
                'he_so_quy_doi_nhap' => 1,
                'so_luong' => $validated['so_luong'],
                'gia_nhap' => $validated['gia_nhap'],
                'gia_nhap_quy_doi' => $validated['gia_nhap'],
                'thanh_tien' => $validated['so_luong'] * $validated['gia_nhap'],
            ]);

            $loThuoc->so_luong_nhap += $validated['so_luong'];
            $loThuoc->so_luong_con += $validated['so_luong'];
            $loThuoc->gia_nhap = $validated['gia_nhap'];
            $loThuoc->save();

            $this->recalculatePhieuNhap($idPhieuNhap);

            return $detail;
        });

        $detail->load(['phieuNhap', 'loThuoc.thuoc']);

        return response()->json([
            'message' => 'Them chi tiet phieu nhap thanh cong.',
            'data' => $detail,
        ], 201);
    }

    public function update(UpdateChiTietPhieuNhapRequest $request, int $id): JsonResponse
    {
        $detail = ChiTietPhieuNhap::find($id);

        if (! $detail) {
            return response()->json(['message' => 'Khong tim thay chi tiet phieu nhap.'], 404);
        }

        DB::transaction(function () use ($request, $detail): void {
            $validated = $request->validated();
            $oldLoId = $detail->id_lo;
            $oldSoLuong = $detail->so_luong;
            $newLoId = $validated['id_lo'] ?? $oldLoId;
            $newSoLuong = $validated['so_luong'] ?? $oldSoLuong;
            $newGiaNhap = $validated['gia_nhap'] ?? $detail->gia_nhap;

            $oldLo = LoThuoc::lockForUpdate()->findOrFail($oldLoId);

            if ($newLoId === $oldLoId) {
                $diff = $newSoLuong - $oldSoLuong;

                if ($oldLo->so_luong_con + $diff < 0 || $oldLo->so_luong_nhap + $diff < 0) {
                    throw ValidationException::withMessages([
                        'so_luong' => ['Khong the giam so luong nhap vuot qua ton hien co cua lo thuoc.'],
                    ]);
                }

                $oldLo->so_luong_nhap += $diff;
                $oldLo->so_luong_con += $diff;
                $oldLo->gia_nhap = $newGiaNhap;
                $oldLo->save();
            } else {
                if ($oldLo->so_luong_con - $oldSoLuong < 0 || $oldLo->so_luong_nhap - $oldSoLuong < 0) {
                    throw ValidationException::withMessages([
                        'id_lo' => ['Khong the chuyen lo thuoc vi lo hien tai da duoc su dung.'],
                    ]);
                }

                $oldLo->so_luong_nhap -= $oldSoLuong;
                $oldLo->so_luong_con -= $oldSoLuong;
                $oldLo->save();

                $newLo = LoThuoc::lockForUpdate()->findOrFail($newLoId);
                $newLo->so_luong_nhap += $newSoLuong;
                $newLo->so_luong_con += $newSoLuong;
                $newLo->gia_nhap = $newGiaNhap;
                $newLo->save();
            }

            $currentLo = $newLoId === $oldLoId ? $oldLo : $newLo;

            $detail->update([
                'id_lo' => $newLoId,
                'don_vi_nhap' => $currentLo->don_vi_nhap,
                'don_vi_co_so' => $currentLo->don_vi_co_so,
                'so_luong_nhap_goc' => $newSoLuong,
                'he_so_quy_doi_nhap' => 1,
                'so_luong' => $newSoLuong,
                'gia_nhap' => $newGiaNhap,
                'gia_nhap_quy_doi' => $newGiaNhap,
                'thanh_tien' => $newSoLuong * $newGiaNhap,
            ]);

            $this->recalculatePhieuNhap($detail->id_phieu_nhap);
        });

        $detail->load(['phieuNhap', 'loThuoc.thuoc']);

        return response()->json([
            'message' => 'Cap nhat chi tiet phieu nhap thanh cong.',
            'data' => $detail,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $detail = ChiTietPhieuNhap::find($id);

        if (! $detail) {
            return response()->json(['message' => 'Khong tim thay chi tiet phieu nhap.'], 404);
        }

        DB::transaction(function () use ($detail): void {
            $loThuoc = LoThuoc::lockForUpdate()->findOrFail($detail->id_lo);

            if ($loThuoc->so_luong_con - $detail->so_luong < 0 || $loThuoc->so_luong_nhap - $detail->so_luong < 0) {
                throw ValidationException::withMessages([
                    'id_lo' => ['Khong the xoa chi tiet vi lo thuoc da duoc su dung.'],
                ]);
            }

            $loThuoc->so_luong_nhap -= $detail->so_luong;
            $loThuoc->so_luong_con -= $detail->so_luong;
            $loThuoc->save();

            $phieuNhapId = $detail->id_phieu_nhap;
            $detail->delete();
            $this->recalculatePhieuNhap($phieuNhapId);
        });

        return response()->json(['message' => 'Xoa chi tiet phieu nhap thanh cong.']);
    }

    private function recalculatePhieuNhap(int $idPhieuNhap): void
    {
        $tongTien = ChiTietPhieuNhap::query()
            ->where('id_phieu_nhap', $idPhieuNhap)
            ->selectRaw('coalesce(sum(coalesce(thanh_tien, so_luong * gia_nhap)), 0) as tong_tien')
            ->value('tong_tien');

        PhieuNhap::query()
            ->where('id_phieu_nhap', $idPhieuNhap)
            ->update(['tong_tien' => $tongTien]);
    }

    private function baseQuery()
    {
        return ChiTietPhieuNhap::query()
            ->with([
                'phieuNhap:id_phieu_nhap,id_nha_san_xuat,id_nhan_vien,tong_tien,ngay_nhap',
                'loThuoc:id_lo,so_lo,id_thuoc,so_luong_nhap,so_luong_con,gia_nhap,don_vi_nhap,don_vi_co_so,so_luong_nhap_goc,he_so_quy_doi_nhap,gia_nhap_quy_doi',
                'loThuoc.thuoc:ma_thuoc,ten_thuoc',
            ])
            ->orderByDesc('id');
    }
}
