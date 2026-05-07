<?php

namespace App\Services;

use App\Models\HoaDon;
use App\Models\LichSuDonHang;
use App\Models\ThanhToan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayosPaymentSyncService
{
    public function __construct(
        private readonly PayosService $payos,
        private readonly RewardPointService $rewardPoints,
    ) {
    }

    public function syncHoaDon(HoaDon $hoaDon, bool $settleRewards = true): HoaDon
    {
        $hoaDon->loadMissing('thanhToan');

        if (! $hoaDon->thanhToan) {
            return $hoaDon;
        }

        $this->syncThanhToan($hoaDon->thanhToan, $settleRewards);

        return $hoaDon->fresh(['thanhToan']) ?: $hoaDon;
    }

    public function syncThanhToan(ThanhToan $thanhToan, bool $settleRewards = true): ThanhToan
    {
        if ($thanhToan->phuong_thuc !== 'payos'
            || $thanhToan->trang_thai === 'paid'
            || ! $this->payos->isConfigured()) {
            return $thanhToan;
        }

        $payosId = $thanhToan->payos_payment_link_id ?: $thanhToan->payos_order_code;

        if (! filled($payosId)) {
            return $thanhToan;
        }

        try {
            $payosInfo = $this->payos->getPaymentLinkInformation($payosId);
        } catch (\Throwable $exception) {
            Log::warning('Khong the dong bo trang thai PayOS.', [
                'id_hoa_don' => $thanhToan->id_hoa_don,
                'payos_id' => $payosId,
                'error' => $exception->getMessage(),
            ]);

            return $thanhToan;
        }

        $data = is_array($payosInfo['data'] ?? null) ? $payosInfo['data'] : [];
        $internalStatus = $this->resolveInternalStatus($data, $thanhToan);

        if (! $internalStatus) {
            return $thanhToan;
        }

        return DB::transaction(function () use ($thanhToan, $data, $payosInfo, $internalStatus, $settleRewards): ThanhToan {
            $lockedPayment = ThanhToan::query()
                ->with('hoaDon')
                ->whereKey($thanhToan->id_hoa_don)
                ->lockForUpdate()
                ->firstOrFail();

            $wasPaid = $lockedPayment->trang_thai === 'paid';

            if ($wasPaid) {
                return $lockedPayment;
            }

            $paidAt = $this->resolvePaidAt($data);
            $reference = $this->resolveTransactionReference($data);
            $isPaid = $internalStatus === 'paid';

            $lockedPayment->forceFill([
                'trang_thai' => $internalStatus,
                'ma_giao_dich' => $isPaid ? ($reference ?: $lockedPayment->ma_giao_dich) : $lockedPayment->ma_giao_dich,
                'thoi_gian' => $isPaid ? ($paidAt ?: now()) : $lockedPayment->thoi_gian,
                'payos_payment_link_id' => (string) ($data['id'] ?? $lockedPayment->payos_payment_link_id),
                'payos_payload' => $payosInfo['raw'] ?? $data,
                'payos_paid_at' => $isPaid ? ($paidAt ?: now()) : $lockedPayment->payos_paid_at,
            ])->save();

            if ($isPaid) {
                if ($settleRewards && $lockedPayment->hoaDon) {
                    $this->rewardPoints->settle($lockedPayment->hoaDon);
                }

                LichSuDonHang::create([
                    'id_hoa_don' => $lockedPayment->id_hoa_don,
                    'trang_thai' => 'Đã thanh toán',
                    'ghi_chu' => 'PayOS đã xác nhận thanh toán.',
                    'thoi_gian' => $paidAt ?: now(),
                    'id_nhan_vien' => $lockedPayment->hoaDon?->id_nhan_vien,
                ]);
            }

            return $lockedPayment->refresh();
        });
    }

    private function resolveInternalStatus(array $data, ThanhToan $thanhToan): ?string
    {
        $status = strtoupper((string) ($data['status'] ?? ''));
        $amountPaid = (float) ($data['amountPaid'] ?? 0);
        $amountRemaining = (float) ($data['amountRemaining'] ?? 0);
        $expectedAmount = (float) $thanhToan->so_tien;

        if (in_array($status, ['PAID', 'SUCCESS', 'SUCCEEDED', 'COMPLETED'], true)
            || ($expectedAmount > 0 && $amountPaid >= $expectedAmount && $amountRemaining <= 0)) {
            return 'paid';
        }

        if (in_array($status, ['CANCELLED', 'CANCELED'], true)) {
            return 'canceled';
        }

        if (in_array($status, ['EXPIRED'], true)) {
            return 'expired';
        }

        if (in_array($status, ['PENDING', 'PROCESSING'], true)) {
            return 'pending';
        }

        return null;
    }

    private function resolvePaidAt(array $data): ?Carbon
    {
        $transaction = $this->firstTransaction($data);
        $raw = $transaction['transactionDateTime']
            ?? $transaction['paymentTime']
            ?? $data['transactionDateTime']
            ?? $data['paymentTime']
            ?? null;

        if (! filled($raw)) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveTransactionReference(array $data): ?string
    {
        $transaction = $this->firstTransaction($data);

        return $transaction['reference']
            ?? $transaction['transactionReference']
            ?? $data['reference']
            ?? $data['transactionReference']
            ?? null;
    }

    private function firstTransaction(array $data): array
    {
        $transactions = $data['transactions'] ?? [];

        if (! is_array($transactions)) {
            return [];
        }

        foreach ($transactions as $transaction) {
            if (is_array($transaction)) {
                return $transaction;
            }
        }

        return [];
    }
}
