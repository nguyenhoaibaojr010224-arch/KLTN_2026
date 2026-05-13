<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Từ chối đơn hàng {{ $hoaDon->ma_hoa_don }}</title>
</head>
<body style="margin:0;background:#fff5f5;color:#17233d;font-family:'Times New Roman',Times,serif;line-height:1.55;">
    @php
        $currency = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
        $quantityText = function ($value): string {
            $quantity = (float) $value;
            return number_format($quantity, abs($quantity - round($quantity)) < 0.0001 ? 0 : 2, ',', '.');
        };
        $paymentLabel = $meta['payment_method_label'] ?? 'Tiền mặt';
        $shippingAddress = $meta['shipping_address'] ?? $khachHang->dia_chi ?? null;
        $customerName = $khachHang->ten_khach_hang ?? 'Quý khách';
        $orderDate = optional($hoaDon->ngay_ban)->format('d/m/Y - H:i') ?: 'Chưa cập nhật';
        $rejectDate = optional($hoaDon->latestLichSuDonHang?->thoi_gian)->format('d/m/Y - H:i') ?: now()->format('d/m/Y - H:i');
    @endphp

    <div style="width:100%;padding:18px 0 28px;">
        <div style="max-width:860px;margin:0 auto;background:#ffffff;border:1px solid #ffd7d7;box-shadow:0 14px 40px rgba(153,27,27,.12);">
            <div style="background:linear-gradient(135deg,#c90f17,#e11d27);color:#ffffff;padding:32px 42px;border-radius:12px 12px 0 0;">
                <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td>
                            <div style="font-size:38px;font-weight:900;letter-spacing:-.5px;">
                                <span style="color:#ffffff;">Pharma</span><span style="color:#72d33f;">Go</span>
                            </div>
                            <div style="font-size:15px;font-weight:700;margin-top:4px;">Nhà thuốc uy tín - Sức khỏe vững bền</div>
                        </td>
                        <td align="right" style="font-size:13px;color:#fee2e2;">Thông báo xử lý đơn hàng</td>
                    </tr>
                </table>
            </div>

            <div style="padding:18px 34px 28px;">
                <div style="border:1px solid #ffd7d7;border-radius:12px;background:#ffffff;padding:24px 28px;margin-bottom:22px;">
                    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="width:92px;vertical-align:middle;">
                                <div style="width:70px;height:70px;border:4px solid #dc2626;border-radius:50%;color:#dc2626;font-size:50px;line-height:66px;text-align:center;font-weight:400;">×</div>
                            </td>
                            <td style="vertical-align:middle;border-right:1px solid #f3d4d4;padding-right:22px;">
                                <div style="font-size:24px;font-weight:900;color:#dc2626;letter-spacing:.02em;">ĐƠN HÀNG ĐÃ BỊ TỪ CHỐI</div>
                                <div style="font-size:16px;color:#526789;margin-top:4px;">Đơn hàng của bạn đã bị từ chối. Nếu có thắc mắc, vui lòng liên hệ hỗ trợ.</div>
                            </td>
                            <td style="width:245px;vertical-align:middle;padding-left:28px;">
                                <div style="font-size:15px;color:#526789;">Mã đơn hàng</div>
                                <div style="font-size:23px;font-weight:900;color:#17233d;margin:4px 0;">#{{ $hoaDon->ma_hoa_don }}</div>
                                <div style="font-size:14px;color:#526789;">Ngày đặt hàng: {{ $orderDate }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="border:1px solid #ffd7d7;border-radius:12px;background:#ffffff;padding:22px 24px;margin-bottom:22px;">
                    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="width:33.33%;vertical-align:top;padding-right:22px;border-right:1px solid #f3d4d4;">
                                <div style="font-size:15px;font-weight:900;color:#dc2626;margin-bottom:16px;">THÔNG TIN KHÁCH HÀNG</div>
                                <div style="color:#526789;margin-bottom:10px;">Khách hàng: <strong style="color:#17233d;">{{ mb_strtoupper($customerName) }}</strong></div>
                                <div style="color:#526789;margin-bottom:10px;">Số điện thoại: <strong style="color:#17233d;">{{ $khachHang->so_dien_thoai ?? 'Chưa cung cấp' }}</strong></div>
                                <div style="color:#526789;">Địa chỉ: <strong style="color:#17233d;">{{ $shippingAddress ?: 'Chưa cung cấp' }}</strong></div>
                            </td>
                            <td style="width:33.33%;vertical-align:top;padding:0 22px;border-right:1px solid #f3d4d4;">
                                <div style="font-size:15px;font-weight:900;color:#dc2626;margin-bottom:16px;">THÔNG TIN GIAO HÀNG</div>
                                <div style="color:#526789;margin-bottom:10px;">Phương thức giao hàng</div>
                                <div style="font-weight:800;color:#17233d;margin-bottom:16px;">Giao theo địa chỉ đã chọn</div>
                                <div style="color:#526789;margin-bottom:10px;">Thời gian giao dự kiến</div>
                                <div style="font-weight:800;color:#17233d;">Chưa cập nhật</div>
                            </td>
                            <td style="width:33.33%;vertical-align:top;padding-left:22px;">
                                <div style="font-size:15px;font-weight:900;color:#dc2626;margin-bottom:16px;">THÔNG TIN THANH TOÁN</div>
                                <div style="color:#526789;margin-bottom:10px;">Phương thức thanh toán</div>
                                <div style="font-weight:800;color:#17233d;margin-bottom:16px;">{{ $paymentLabel }}</div>
                                <div style="color:#526789;margin-bottom:10px;">Tổng thanh toán</div>
                                <div style="font-size:20px;font-weight:900;color:#dc2626;">{{ $currency($hoaDon->tien_thanh_toan) }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="font-size:16px;font-weight:900;color:#dc2626;margin:0 0 12px;">CHI TIẾT SẢN PHẨM</div>
                <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;border:1px solid #ffd7d7;border-radius:12px;overflow:hidden;">
                    <thead>
                        <tr style="background:#fff7f7;color:#526789;">
                            <th align="left" style="padding:14px 18px;border-bottom:1px solid #ffd7d7;">Sản phẩm</th>
                            <th align="center" style="padding:14px 12px;border-bottom:1px solid #ffd7d7;width:100px;">Đơn vị</th>
                            <th align="center" style="padding:14px 12px;border-bottom:1px solid #ffd7d7;width:90px;">Số lượng</th>
                            <th align="right" style="padding:14px 12px;border-bottom:1px solid #ffd7d7;width:120px;">Đơn giá</th>
                            <th align="right" style="padding:14px 18px;border-bottom:1px solid #ffd7d7;width:130px;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $quantity = (float) ($item['soLuong'] ?? 0);
                                $unitPrice = (float) ($item['gia'] ?? 0);
                                $lineTotal = (float) ($item['thanhTien'] ?? ($unitPrice * $quantity));
                                $imageUrl = $item['hinhAnh'] ?? null;
                            @endphp
                            <tr>
                                <td style="padding:16px 18px;border-bottom:1px solid #ffeded;">
                                    <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                        <tr>
                                            <td style="width:72px;vertical-align:middle;">
                                                @if($imageUrl)
                                                    <img src="{{ $imageUrl }}" alt="{{ $item['ten'] ?? 'Sản phẩm' }}" style="width:58px;height:58px;border-radius:10px;object-fit:cover;border:1px solid #fde2e2;">
                                                @else
                                                    <div style="width:58px;height:58px;border-radius:10px;background:#fff1f1;border:1px solid #fde2e2;"></div>
                                                @endif
                                            </td>
                                            <td style="vertical-align:middle;">
                                                <div style="font-weight:900;color:#102a56;">{{ $item['ten'] ?? 'Sản phẩm' }}</div>
                                                <div style="display:inline-block;margin-top:8px;padding:4px 10px;border-radius:999px;background:#eaf3ff;color:#0863d6;font-size:12px;font-weight:800;">{{ $item['donVi'] ?? '-' }}</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td align="center" style="padding:16px 12px;border-bottom:1px solid #ffeded;color:#526789;">{{ $item['donVi'] ?? '-' }}</td>
                                <td align="center" style="padding:16px 12px;border-bottom:1px solid #ffeded;">
                                    <span style="display:inline-block;min-width:28px;height:28px;line-height:28px;border-radius:50%;background:#fee2e2;color:#dc2626;font-weight:800;">{{ $quantityText($quantity) }}</span>
                                </td>
                                <td align="right" style="padding:16px 12px;border-bottom:1px solid #ffeded;color:#102a56;">{{ $currency($unitPrice) }}</td>
                                <td align="right" style="padding:16px 18px;border-bottom:1px solid #ffeded;font-size:16px;font-weight:900;color:#dc2626;">{{ $currency($lineTotal) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" style="padding:0;border-bottom:0;"></td>
                            <td colspan="2" style="padding:16px 18px;">
                                <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                                    <tr>
                                        <td style="padding:6px 0;color:#526789;">Tạm tính</td>
                                        <td align="right" style="padding:6px 0;font-weight:800;">{{ $currency($hoaDon->tong_tien) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#526789;">Giảm giá</td>
                                        <td align="right" style="padding:6px 0;font-weight:800;color:#16a34a;">-{{ $currency($hoaDon->giam_gia) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#526789;">VAT (10%)</td>
                                        <td align="right" style="padding:6px 0;font-weight:800;">{{ $currency($hoaDon->thue_vat) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:14px 0 0;border-top:1px solid #ffd7d7;font-size:16px;font-weight:900;color:#dc2626;">TỔNG THANH TOÁN</td>
                                        <td align="right" style="padding:14px 0 0;border-top:1px solid #ffd7d7;font-size:26px;font-weight:900;color:#dc2626;">{{ $currency($hoaDon->tien_thanh_toan) }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="border:1px solid #ffcaca;border-radius:12px;background:#fffafa;margin-top:22px;padding:18px 22px;">
                    <div style="font-size:15px;font-weight:900;color:#dc2626;margin-bottom:12px;">THÔNG TIN TỪ CHỐI ĐƠN HÀNG</div>
                    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="width:140px;padding:5px 0;color:#526789;">Lý do từ chối:</td>
                            <td style="padding:5px 0;color:#17233d;">{{ $reason ?: 'Chưa cung cấp' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:5px 0;color:#526789;">Thời gian từ chối:</td>
                            <td style="padding:5px 0;color:#17233d;">{{ $rejectDate }}</td>
                        </tr>
                        <tr>
                            <td style="padding:5px 0;color:#526789;">Ghi chú:</td>
                            <td style="padding:5px 0;color:#17233d;">Rất mong bạn thông cảm. Vui lòng đặt lại đơn hàng sau hoặc liên hệ hỗ trợ để được tư vấn.</td>
                        </tr>
                    </table>
                </div>

                <div style="border:1px solid #ffd7d7;border-radius:12px;background:#ffffff;margin-top:22px;padding:18px 24px;">
                    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="width:50%;border-right:1px solid #f3d4d4;padding-right:24px;">
                                <div style="font-weight:900;color:#102a56;">Cần hỗ trợ?</div>
                                <div style="color:#526789;">Liên hệ ngay với chúng tôi qua <strong style="color:#dc2626;">1900 1234</strong></div>
                            </td>
                            <td style="padding-left:24px;">
                                <div style="font-weight:900;color:#102a56;">PharmaGo luôn đồng hành cùng bạn</div>
                                <div style="color:#526789;">Cảm ơn bạn đã tin tưởng và lựa chọn PharmaGo.</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div style="background:#f8fafc;color:#526789;padding:16px 34px;border-radius:0 0 12px 12px;font-size:13px;">
                <table cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td align="center">100% Hàng chính hãng</td>
                        <td align="center">Dược sĩ tư vấn 24/7</td>
                        <td align="center">Giao hàng nhanh chóng</td>
                        <td align="center">Đổi trả dễ dàng</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
