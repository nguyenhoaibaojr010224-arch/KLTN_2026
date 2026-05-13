<?php

namespace App\Http\Controllers;

use App\Models\CounterSalePayosSession;
use App\Models\HoaDon;
use App\Models\LichSuDonHang;
use App\Models\ThanhToan;
use App\Services\PayosService;
use App\Services\OrderMailService;
use App\Services\RewardPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayosWebhookController extends Controller
{
    public function __invoke(Request $request, PayosService $payos, RewardPointService $rewardPoints, OrderMailService $orderMailService): JsonResponse
    {
        $payload = $request->all();

        if (! $payos->verifyWebhook($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'PayOS signature không hợp lệ.',
            ], 400);
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $orderCode = (int) ($data['orderCode'] ?? 0);
        $paymentLinkId = (string) ($data['paymentLinkId'] ?? $data['id'] ?? '');
        $success = (bool) ($payload['success'] ?? false);
        $code = (string) ($payload['code'] ?? '');

        if ($orderCode <= 0 && $paymentLinkId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu mã đơn hàng PayOS.',
            ], 422);
        }

        $confirmedHoaDon = null;

        DB::transaction(function () use ($data, $orderCode, $paymentLinkId, $success, $code, $payload, $rewardPoints, &$confirmedHoaDon): void {
            $thanhToan = ThanhToan::query()
                ->where(function ($query) use ($orderCode, $paymentLinkId): void {
                    if ($orderCode > 0) {
                        $query->where('payos_order_code', $orderCode);
                    }

                    if ($paymentLinkId !== '') {
                        $query->orWhere('payos_payment_link_id', $paymentLinkId);
                    }
                })
                ->lockForUpdate()
                ->first();

            $status = strtoupper((string) ($data['status'] ?? $payload['status'] ?? ''));
            $isCanceled = in_array($status, ['CANCELLED', 'CANCELED'], true)
                || filter_var($data['cancel'] ?? $payload['cancel'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $isPaid = $success
                && $code === '00'
                && ! $isCanceled
                && ($status === '' || in_array($status, ['PAID', 'SUCCESS', 'SUCCEEDED'], true));
            $paidAt = $this->resolvePaidAt($data);

            if (! $thanhToan) {
                $counterSession = CounterSalePayosSession::query()
                    ->where(function ($query) use ($orderCode, $paymentLinkId): void {
                        if ($orderCode > 0) {
                            $query->where('payos_order_code', $orderCode);
                        }

                        if ($paymentLinkId !== '') {
                            $query->orWhere('payos_payment_link_id', $paymentLinkId);
                        }
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $counterSession) {
                    return;
                }

                if ($isPaid) {
                    app(CounterSaleController::class)->completePayosSessionFromWebhook(
                        $counterSession,
                        $data,
                        $payload,
                        $paidAt
                    );

                    return;
                }

                if ($counterSession->status !== 'paid') {
                    $counterSession->forceFill([
                        'status' => $isCanceled ? 'canceled' : 'failed',
                        'payos_payment_link_id' => $paymentLinkId ?: $counterSession->payos_payment_link_id,
                        'payos_payload' => $data ?: $payload,
                        'error_message' => $isCanceled ? null : 'PayOS chưa xác nhận thanh toán.',
                    ])->save();
                }

                return;
            }

            $wasPaid = $thanhToan->trang_thai === 'paid';

            $thanhToan->forceFill([
                'trang_thai' => $isPaid ? 'paid' : ($isCanceled ? 'canceled' : 'failed'),
                'ma_giao_dich' => $data['reference'] ?? $data['transactionReference'] ?? $thanhToan->ma_giao_dich,
                'thoi_gian' => $isPaid ? ($paidAt ?: now()) : $thanhToan->thoi_gian,
                'payos_payment_link_id' => $paymentLinkId ?: $thanhToan->payos_payment_link_id,
                'payos_payload' => $data,
                'payos_paid_at' => $isPaid ? ($paidAt ?: now()) : $thanhToan->payos_paid_at,
            ])->save();

            if ($isPaid && ! $wasPaid) {
                $hoaDon = $thanhToan->hoaDon
                    ? HoaDon::query()
                        ->whereKey($thanhToan->hoaDon->id_hoa_don)
                        ->lockForUpdate()
                        ->first()
                    : null;

                if ($hoaDon && in_array($hoaDon->trang_thai_xu_ly, ['cho_thanh_toan', 'cho_xac_nhan'], true)) {
                    $hoaDon->forceFill([
                        'trang_thai_xu_ly' => 'da_xac_nhan',
                        'ly_do_tu_choi' => null,
                    ])->save();

                    LichSuDonHang::create([
                        'id_hoa_don' => $hoaDon->id_hoa_don,
                        'trang_thai' => 'Đã xác nhận',
                        'ghi_chu' => 'PayOS đã thanh toán thành công, hệ thống tự xác nhận đơn hàng.',
                        'thoi_gian' => $paidAt ?: now(),
                        'id_nhan_vien' => $hoaDon->id_nhan_vien,
                    ]);

                    $confirmedHoaDon = $hoaDon;
                }

                if ($hoaDon) {
                    $rewardPoints->settle($hoaDon);
                }

                LichSuDonHang::create([
                    'id_hoa_don' => $thanhToan->id_hoa_don,
                    'trang_thai' => 'Đã thanh toán',
                    'ghi_chu' => 'PayOS đã xác nhận thanh toán.',
                    'thoi_gian' => $paidAt ?: now(),
                    'id_nhan_vien' => $hoaDon?->id_nhan_vien,
                ]);
            }
        });

        if ($confirmedHoaDon) {
            $orderMailService->sendConfirmed($confirmedHoaDon);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    private function resolvePaidAt(array $data): ?Carbon
    {
        $raw = $data['transactionDateTime'] ?? $data['paymentTime'] ?? null;

        if (! filled($raw)) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
