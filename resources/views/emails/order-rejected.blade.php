<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông báo từ chối đơn hàng {{ $hoaDon->ma_hoa_don }}</title>
</head>
<body style="margin:0;background:#fff7ed;color:#183153;font-family:Arial,'Helvetica Neue',sans-serif;line-height:1.55;">
    @php
        $currency = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
        $customerName = $khachHang->ten_khach_hang ?? 'Quý khách';
    @endphp

    <div style="width:100%;padding:28px 12px;">
        <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #fed7aa;box-shadow:0 18px 45px rgba(154,52,18,.12);">
            <div style="padding:26px 30px;background:#c2410c;color:#ffffff;">
                <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Nhà thuốc</div>
                <div style="font-size:30px;font-weight:900;color:#fff7ad;line-height:1;">PharmaGo</div>
                <div style="margin-top:18px;font-size:22px;font-weight:800;">Đơn hàng chưa thể xác nhận</div>
            </div>

            <div style="padding:28px 30px;">
                <p style="margin:0 0 10px;font-size:18px;font-weight:700;">Xin chào, {{ $customerName }}</p>
                <p style="margin:0 0 18px;font-size:16px;">
                    PharmaGo rất tiếc phải từ chối đơn hàng <strong>{{ $hoaDon->ma_hoa_don }}</strong>.
                </p>

                <div style="padding:16px 18px;border-radius:18px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;margin-bottom:22px;">
                    <strong>Lý do từ chối:</strong>
                    <div style="margin-top:8px;">{{ $reason }}</div>
                </div>

                <h3 style="margin:0 0 12px;font-size:18px;color:#0f2654;">Sản phẩm trong đơn</h3>
                <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;border:1px solid #fed7aa;border-radius:14px;overflow:hidden;">
                    <thead>
                        <tr style="background:#fff7ed;color:#7c2d12;">
                            <th align="left" style="padding:12px;border-bottom:1px solid #fed7aa;">Sản phẩm</th>
                            <th align="left" style="padding:12px;border-bottom:1px solid #fed7aa;">Đơn vị</th>
                            <th align="right" style="padding:12px;border-bottom:1px solid #fed7aa;">SL</th>
                            <th align="right" style="padding:12px;border-bottom:1px solid #fed7aa;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $quantity = (float) ($item['soLuong'] ?? 0);
                                $lineTotal = (float) ($item['thanhTien'] ?? 0);
                            @endphp
                            <tr>
                                <td style="padding:12px;border-bottom:1px solid #ffedd5;font-weight:700;">{{ $item['ten'] ?? 'Sản phẩm' }}</td>
                                <td style="padding:12px;border-bottom:1px solid #ffedd5;">{{ $item['donVi'] ?? '-' }}</td>
                                <td align="right" style="padding:12px;border-bottom:1px solid #ffedd5;">{{ number_format($quantity, $quantity == (int) $quantity ? 0 : 2, ',', '.') }}</td>
                                <td align="right" style="padding:12px;border-bottom:1px solid #ffedd5;font-weight:800;">{{ $currency($lineTotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p style="margin:24px 0 0;color:#64748b;">
                    Nếu cần hỗ trợ thêm, bạn có thể liên hệ PharmaGo để được tư vấn lại đơn hàng phù hợp hơn.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
