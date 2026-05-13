<?php

namespace App\Services;

use App\Mail\OrderConfirmedMail;
use App\Mail\OrderRejectedMail;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\LichSuDonHang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderMailService
{
    public function sendConfirmed(HoaDon $hoaDon): void
    {
        $hoaDon->loadMissing([
            'khachHang',
            'thanhToan',
            'chiTiets.loThuoc.thuoc',
            'lichSuDonHangs',
        ]);

        if (! filled($hoaDon->khachHang?->email)) {
            return;
        }

        try {
            Mail::to($hoaDon->khachHang->email)->send(new OrderConfirmedMail(
                $hoaDon->khachHang,
                $hoaDon,
                $this->buildItems($hoaDon),
                [
                    'payment_method_label' => $this->paymentLabel($hoaDon->thanhToan?->phuong_thuc),
                    'shipping_address' => $this->resolveShippingAddress($hoaDon),
                ]
            ));
        } catch (\Throwable $exception) {
            Log::warning('Khong the gui mail xac nhan don hang.', [
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function sendRejected(HoaDon $hoaDon): void
    {
        $hoaDon->loadMissing([
            'khachHang',
            'thanhToan',
            'chiTiets.loThuoc.thuoc',
            'lichSuDonHangs',
        ]);

        if (! filled($hoaDon->khachHang?->email)) {
            return;
        }

        try {
            Mail::to($hoaDon->khachHang->email)->send(new OrderRejectedMail(
                $hoaDon->khachHang,
                $hoaDon,
                (string) $hoaDon->ly_do_tu_choi,
                $this->buildItems($hoaDon),
                [
                    'payment_method_label' => $this->paymentLabel($hoaDon->thanhToan?->phuong_thuc),
                    'shipping_address' => $this->resolveShippingAddress($hoaDon),
                ]
            ));
        } catch (\Throwable $exception) {
            Log::warning('Khong the gui mail tu choi don hang.', [
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function buildItems(HoaDon $hoaDon): array
    {
        return $hoaDon->chiTiets
            ->map(function (ChiTietHoaDon $detail): array {
                $thuoc = $detail->loThuoc?->thuoc;
                $heSo = max(1, (int) ($detail->he_so_quy_doi_ban ?? 1));

                return [
                    'ten' => $thuoc?->ten_thuoc ?: 'Sản phẩm',
                    'donVi' => $detail->don_vi_ban ?: $thuoc?->don_vi_tinh ?: '-',
                    'soLuong' => (float) $detail->so_luong / $heSo,
                    'gia' => (float) $detail->gia_ban,
                    'thanhTien' => (float) $detail->thanh_tien,
                    'hinhAnh' => $thuoc?->hinh_anh_url,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveShippingAddress(HoaDon $hoaDon): ?string
    {
        $note = $hoaDon->lichSuDonHangs
            ->sortBy('thoi_gian')
            ->first(fn (LichSuDonHang $history) => filled($history->ghi_chu) && Str::contains($history->ghi_chu, 'Dia chi giao hang:'))
            ?->ghi_chu;

        if (filled($note)) {
            foreach (preg_split('/\R/u', (string) $note) ?: [] as $line) {
                $line = trim((string) $line);

                if (Str::startsWith($line, 'Dia chi giao hang:')) {
                    return trim(Str::after($line, 'Dia chi giao hang:')) ?: null;
                }
            }
        }

        return $hoaDon->khachHang?->dia_chi;
    }

    private function paymentLabel(?string $method): string
    {
        return match ((string) $method) {
            'payos' => 'PayOS',
            default => 'Tiền mặt',
        };
    }
}
