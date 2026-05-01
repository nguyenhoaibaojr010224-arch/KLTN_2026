<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn điện tử {{ $hoaDon->ma_hoa_don }}</title>
</head>
<body style="margin:0;background:#f3f7fd;color:#183153;font-family:Arial,'Helvetica Neue',sans-serif;line-height:1.55;">
    @php
        $currency = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
        $paymentLabel = $meta['payment_method_label'] ?? 'Tiền mặt';
        $shippingAddress = $meta['shipping_address'] ?? null;
        $customerName = $khachHang->ten_khach_hang ?? 'Quý khách';
    @endphp

    <div style="width:100%;padding:28px 12px;">
        <div style="max-width:760px;margin:0 auto;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #dce7fb;box-shadow:0 18px 45px rgba(15,49,103,.12);">
            <div style="padding:26px 30px;background:#1652c5;color:#ffffff;">
                <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Nhà thuốc</div>
                <div style="font-size:30px;font-weight:900;color:#9ef23c;line-height:1;">PharmaGo</div>
                <div style="margin-top:18px;font-size:22px;font-weight:800;">Đặt hàng thành công</div>
            </div>

            <div style="padding:28px 30px;">
                <p style="margin:0 0 10px;font-size:18px;font-weight:700;">Xin chào, {{ $customerName }}</p>
                <p style="margin:0 0 20px;font-size:16px;">
                    Đơn hàng của bạn đã được đặt hàng thành công, kèm theo hóa đơn điện tử bên dưới.
                </p>

                <div style="padding:16px 18px;border-radius:18px;background:#eef5ff;border:1px solid #d7e6ff;margin-bottom:22px;">
                    <div style="display:inline-block;padding:7px 12px;border-radius:999px;background:#ffffff;color:#1652c5;font-weight:800;font-size:13px;margin-bottom:12px;">
                        Hóa đơn điện tử
                    </div>
                    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="padding:6px 0;color:#64748b;width:180px;">Mã hóa đơn</td>
                            <td style="padding:6px 0;font-weight:800;">{{ $hoaDon->ma_hoa_don }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Ngày đặt</td>
                            <td style="padding:6px 0;font-weight:700;">{{ optional($hoaDon->ngay_ban)->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Khách hàng</td>
                            <td style="padding:6px 0;font-weight:700;">{{ $customerName }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Số điện thoại</td>
                            <td style="padding:6px 0;font-weight:700;">{{ $khachHang->so_dien_thoai ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Phương thức thanh toán</td>
                            <td style="padding:6px 0;font-weight:700;">{{ $paymentLabel }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Địa chỉ giao hàng</td>
                            <td style="padding:6px 0;font-weight:700;">{{ $shippingAddress ?: 'Chưa cung cấp' }}</td>
                        </tr>
                    </table>
                </div>

                <h3 style="margin:0 0 12px;font-size:18px;color:#0f2654;">Chi tiết sản phẩm</h3>
                <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;border:1px solid #dbe5ff;border-radius:14px;overflow:hidden;">
                    <thead>
                        <tr style="background:#f3f7ff;color:#0f2654;">
                            <th align="left" style="padding:12px;border-bottom:1px solid #dbe5ff;">Sản phẩm</th>
                            <th align="left" style="padding:12px;border-bottom:1px solid #dbe5ff;">Phân loại</th>
                            <th align="right" style="padding:12px;border-bottom:1px solid #dbe5ff;">SL</th>
                            <th align="right" style="padding:12px;border-bottom:1px solid #dbe5ff;">Đơn giá</th>
                            <th align="right" style="padding:12px;border-bottom:1px solid #dbe5ff;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $quantity = (float) ($item['soLuong'] ?? 0);
                                $unitPrice = (float) ($item['gia'] ?? 0);
                                $lineTotal = $unitPrice * $quantity;
                            @endphp
                            <tr>
                                <td style="padding:12px;border-bottom:1px solid #edf2ff;font-weight:700;">{{ $item['ten'] ?? 'Sản phẩm' }}</td>
                                <td style="padding:12px;border-bottom:1px solid #edf2ff;">{{ $item['donVi'] ?? '-' }}</td>
                                <td align="right" style="padding:12px;border-bottom:1px solid #edf2ff;">{{ number_format($quantity, $quantity == (int) $quantity ? 0 : 2, ',', '.') }}</td>
                                <td align="right" style="padding:12px;border-bottom:1px solid #edf2ff;">{{ $currency($unitPrice) }}</td>
                                <td align="right" style="padding:12px;border-bottom:1px solid #edf2ff;font-weight:800;">{{ $currency($lineTotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="max-width:420px;margin:24px 0 0 auto;">
                    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="padding:8px 0;color:#64748b;">Tạm tính</td>
                            <td align="right" style="padding:8px 0;font-weight:700;">{{ $currency($hoaDon->tong_tien) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#64748b;">Giảm giá</td>
                            <td align="right" style="padding:8px 0;font-weight:700;">-{{ $currency($hoaDon->giam_gia) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#64748b;">VAT (10%)</td>
                            <td align="right" style="padding:8px 0;font-weight:700;">{{ $currency($hoaDon->thue_vat) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:12px 0;border-top:1px solid #dbe5ff;font-size:18px;font-weight:900;">Tổng thanh toán</td>
                            <td align="right" style="padding:12px 0;border-top:1px solid #dbe5ff;font-size:20px;font-weight:900;color:#1652c5;">{{ $currency($hoaDon->tien_thanh_toan) }}</td>
                        </tr>
                    </table>
                </div>

                @if(!empty($meta['note']))
                    <div style="margin-top:18px;padding:14px 16px;border-radius:16px;background:#fff7ed;color:#9a3412;">
                        <strong>Ghi chú:</strong> {{ $meta['note'] }}
                    </div>
                @endif

                <p style="margin:24px 0 0;color:#64748b;">
                    PharmaGo sẽ xử lý đơn hàng và liên hệ nếu cần xác nhận thêm thông tin.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
