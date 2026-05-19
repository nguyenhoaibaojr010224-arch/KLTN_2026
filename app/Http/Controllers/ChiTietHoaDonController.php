<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StoreChiTietHoaDonRequest;
use App\Http\Requests\UpdateChiTietHoaDonRequest;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\LoThuoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChiTietHoaDonController extends Controller
{
    public function indexByHoaDon(int $idHoaDon): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach chi tiet hoa don thanh cong.',
            'data' => $this->baseQuery()->where('id_hoa_don', $idHoaDon)->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->baseQuery()->find($id);

        if (! $record) {
            return response()->json(['message' => 'Khong tim thay chi tiet hoa don.'], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet hoa don thanh cong.',
            'data' => $record,
        ]);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $records = $this->baseQuery()
            ->where(function ($query) use ($keyword): void {
                $query->where('id', 'like', '%' . $keyword . '%')
                    ->orWhere('id_hoa_don', 'like', '%' . $keyword . '%')
                    ->orWhereHas('hoaDon', function ($hoaDonQuery) use ($keyword): void {
                        $hoaDonQuery->where('ma_hoa_don', 'like', '%' . $keyword . '%');
                    })
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
            'message' => 'Tim kiem chi tiet hoa don thanh cong.',
            'data' => $records,
        ]);
    }

    public function store(StoreChiTietHoaDonRequest $request, int $idHoaDon): JsonResponse
    {
        $hoaDon = HoaDon::find($idHoaDon);

        if (! $hoaDon) {
            return response()->json(['message' => 'Khong tim thay hoa don.'], 404);
        }

        $detail = DB::transaction(function () use ($request, $idHoaDon) {
            $validated = $request->validated();
            $loThuoc = LoThuoc::with('thuoc')->lockForUpdate()->findOrFail($validated['id_lo']);

            if (Carbon::parse($loThuoc->han_su_dung)->startOfDay()->lt(Carbon::now()->startOfDay())) {
                throw ValidationException::withMessages([
                    'id_lo' => ['Lo thuoc nay da het han, khong duoc ban.'],
                ]);
            }

            if ($loThuoc->so_luong_con < $validated['so_luong']) {
                throw ValidationException::withMessages([
                    'so_luong' => ['So luong vuot qua ton kho cua lo thuoc.'],
                ]);
            }

            $giaBan = (float) ($validated['gia_ban'] ?? $loThuoc->thuoc?->gia_ban ?? 0);

            if ($giaBan <= 0) {
                throw ValidationException::withMessages([
                    'gia_ban' => ['Gia ban khong hop le.'],
                ]);
            }

            $detail = ChiTietHoaDon::create([
                'id_hoa_don' => $idHoaDon,
                'id_lo' => $validated['id_lo'],
                'so_luong' => $validated['so_luong'],
                'gia_ban' => $giaBan,
                'thanh_tien' => $validated['so_luong'] * $giaBan,
            ]);

            $loThuoc->so_luong_con -= $validated['so_luong'];
            $loThuoc->save();

            $this->recalculateHoaDon($idHoaDon);

            return $detail;
        });

        $detail->load(['hoaDon', 'loThuoc.thuoc']);

        return response()->json([
            'message' => 'Them chi tiet hoa don thanh cong.',
            'data' => $detail,
        ], 201);
    }

    public function update(UpdateChiTietHoaDonRequest $request, int $id): JsonResponse
    {
        $detail = ChiTietHoaDon::find($id);

        if (! $detail) {
            return response()->json(['message' => 'Khong tim thay chi tiet hoa don.'], 404);
        }

        DB::transaction(function () use ($request, $detail): void {
            $validated = $request->validated();
            $oldLoId = $detail->id_lo;
            $oldSoLuong = $detail->so_luong;
            $newLoId = $validated['id_lo'] ?? $oldLoId;
            $newSoLuong = $validated['so_luong'] ?? $oldSoLuong;

            $oldLo = LoThuoc::with('thuoc')->lockForUpdate()->findOrFail($oldLoId);

            if ($newLoId === $oldLoId) {
                $diff = $newSoLuong - $oldSoLuong;

                if ($diff > 0 && Carbon::parse($oldLo->han_su_dung)->startOfDay()->lt(Carbon::now()->startOfDay())) {
                    throw ValidationException::withMessages([
                        'id_lo' => ['Lo thuoc nay da het han, khong duoc tang so luong ban.'],
                    ]);
                }

                if ($oldLo->so_luong_con - $diff < 0) {
                    throw ValidationException::withMessages([
                        'so_luong' => ['So luong cap nhat vuot qua ton kho cua lo thuoc.'],
                    ]);
                }

                $oldLo->so_luong_con -= $diff;
                $oldLo->save();
                $giaBan = (float) ($validated['gia_ban'] ?? $detail->gia_ban);
            } else {
                $oldLo->so_luong_con += $oldSoLuong;
                $oldLo->save();

                $newLo = LoThuoc::with('thuoc')->lockForUpdate()->findOrFail($newLoId);

                if (Carbon::parse($newLo->han_su_dung)->startOfDay()->lt(Carbon::now()->startOfDay())) {
                    throw ValidationException::withMessages([
                        'id_lo' => ['Lo thuoc moi da het han, khong duoc ban.'],
                    ]);
                }

                if ($newLo->so_luong_con < $newSoLuong) {
                    throw ValidationException::withMessages([
                        'id_lo' => ['Lo thuoc moi khong du ton kho.'],
                    ]);
                }

                $newLo->so_luong_con -= $newSoLuong;
                $newLo->save();

                $giaBan = (float) ($validated['gia_ban'] ?? $newLo->thuoc?->gia_ban ?? 0);
            }

            if ($giaBan <= 0) {
                throw ValidationException::withMessages([
                    'gia_ban' => ['Gia ban khong hop le.'],
                ]);
            }

            $detail->update([
                'id_lo' => $newLoId,
                'so_luong' => $newSoLuong,
                'gia_ban' => $giaBan,
                'thanh_tien' => $newSoLuong * $giaBan,
            ]);

            $this->recalculateHoaDon($detail->id_hoa_don);
        });

        $detail->load(['hoaDon', 'loThuoc.thuoc']);

        return response()->json([
            'message' => 'Cap nhat chi tiet hoa don thanh cong.',
            'data' => $detail,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $detail = ChiTietHoaDon::find($id);

        if (! $detail) {
            return response()->json(['message' => 'Khong tim thay chi tiet hoa don.'], 404);
        }

        DB::transaction(function () use ($detail): void {
            $loThuoc = LoThuoc::lockForUpdate()->findOrFail($detail->id_lo);
            $loThuoc->so_luong_con += $detail->so_luong;
            $loThuoc->save();

            $hoaDonId = $detail->id_hoa_don;
            $detail->delete();
            $this->recalculateHoaDon($hoaDonId);
        });

        return response()->json(['message' => 'Xoa chi tiet hoa don thanh cong.']);
    }

    private function recalculateHoaDon(int $idHoaDon): void
    {
        $tongTien = (float) ChiTietHoaDon::query()
            ->where('id_hoa_don', $idHoaDon)
            ->selectRaw('coalesce(sum(thanh_tien), 0) as tong_tien')
            ->value('tong_tien');

        $hoaDon = HoaDon::findOrFail($idHoaDon);
        $tienThanhToan = max($tongTien - (float) $hoaDon->giam_gia, 0);

        $hoaDon->update([
            'tong_tien' => $tongTien,
            'tien_thanh_toan' => $tienThanhToan,
        ]);
    }

    private function baseQuery()
    {
        return ChiTietHoaDon::query()
            ->with([
                'hoaDon:id_hoa_don,ma_hoa_don,tong_tien,giam_gia,tien_thanh_toan',
                'loThuoc:id_lo,so_lo,id_thuoc,so_luong_con',
                'loThuoc.thuoc:ma_thuoc,ten_thuoc,gia_ban',
            ])
            ->orderByDesc('id');
    }
}
