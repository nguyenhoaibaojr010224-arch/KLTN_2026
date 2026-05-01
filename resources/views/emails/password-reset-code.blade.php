<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Mã đặt lại mật khẩu PharmaGo</title>
</head>
<body style="margin:0;background:#f3f7fd;font-family:Arial,sans-serif;color:#183153;">
    <div style="max-width:560px;margin:0 auto;padding:28px 16px;">
        <div style="background:#ffffff;border-radius:18px;padding:28px;border:1px solid #dbe7fb;">
            <h1 style="margin:0 0 12px;font-size:24px;color:#0d2b73;">Đặt lại mật khẩu PharmaGo</h1>
            <p style="margin:0 0 16px;line-height:1.6;">Xin chào {{ $khachHang->ten_khach_hang }},</p>
            <p style="margin:0 0 18px;line-height:1.6;">Nhập mã bên dưới vào màn hình quên mật khẩu để tạo mật khẩu mới.</p>
            <div style="margin:22px 0;padding:18px;border-radius:14px;background:#eef5ff;text-align:center;font-size:34px;font-weight:800;letter-spacing:8px;color:#1652c5;">
                {{ $code }}
            </div>
            <p style="margin:0;line-height:1.6;color:#60748f;">Mã đặt lại mật khẩu có hiệu lực trong 15 phút. Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
        </div>
    </div>
</body>
</html>
