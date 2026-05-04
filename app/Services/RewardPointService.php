<?php

namespace App\Services;

use App\Models\HoaDon;
use App\Models\KhachHang;

class RewardPointService
{
    public function settle(HoaDon $hoaDon): HoaDon
    {
        $lockedHoaDon = HoaDon::query()
            ->whereKey($hoaDon->id_hoa_don)
            ->lockForUpdate()
            ->firstOrFail();

        if ((bool) $lockedHoaDon->diem_thuong_da_xu_ly) {
            return $lockedHoaDon;
        }

        $khachHang = KhachHang::query()
            ->whereKey($lockedHoaDon->id_khach_hang)
            ->lockForUpdate()
            ->first();

        if ($khachHang) {
            $khachHang->forceFill([
                'diem_tich_luy' => max(
                    0,
                    (int) $khachHang->diem_tich_luy
                    - (int) ($lockedHoaDon->diem_da_su_dung ?? 0)
                    + (int) ($lockedHoaDon->diem_da_cong ?? 0)
                ),
            ])->save();
        }

        $lockedHoaDon->forceFill([
            'diem_thuong_da_xu_ly' => true,
        ])->save();

        return $lockedHoaDon;
    }

    public function restore(HoaDon $hoaDon): HoaDon
    {
        $lockedHoaDon = HoaDon::query()
            ->whereKey($hoaDon->id_hoa_don)
            ->lockForUpdate()
            ->firstOrFail();

        if (! (bool) $lockedHoaDon->diem_thuong_da_xu_ly) {
            return $lockedHoaDon;
        }

        $khachHang = KhachHang::query()
            ->whereKey($lockedHoaDon->id_khach_hang)
            ->lockForUpdate()
            ->first();

        if ($khachHang) {
            $khachHang->forceFill([
                'diem_tich_luy' => max(
                    0,
                    (int) $khachHang->diem_tich_luy
                    + (int) ($lockedHoaDon->diem_da_su_dung ?? 0)
                    - (int) ($lockedHoaDon->diem_da_cong ?? 0)
                ),
            ])->save();
        }

        $lockedHoaDon->forceFill([
            'diem_thuong_da_xu_ly' => false,
        ])->save();

        return $lockedHoaDon;
    }
}
