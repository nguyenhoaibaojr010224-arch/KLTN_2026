<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacity API Docs</title>
    <style>
        :root {
            --bg: #f4f7f2;
            --panel: rgba(255, 255, 255, 0.92);
            --line: rgba(19, 51, 60, 0.12);
            --ink: #14313a;
            --muted: #62717a;
            --blue: #0f6cbd;
            --green: #157f5b;
            --orange: #d96c06;
            --shadow: 0 20px 50px rgba(20, 49, 58, 0.12);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(15, 108, 189, 0.16), transparent 28%),
                radial-gradient(circle at bottom right, rgba(21, 127, 91, 0.14), transparent 26%),
                linear-gradient(180deg, #fbfdf9 0%, var(--bg) 100%);
        }
        .shell { max-width: 1220px; margin: 0 auto; padding: 28px 18px 72px; }
        .hero {
            padding: 30px;
            border-radius: 28px;
            color: #fff;
            background: linear-gradient(135deg, rgba(20,49,58,.98), rgba(15,108,189,.9));
            box-shadow: var(--shadow);
        }
        .hero h1 { margin: 0 0 8px; font-size: clamp(2rem, 4vw, 3.4rem); }
        .hero p { margin: 0; max-width: 860px; color: rgba(255,255,255,.88); }
        .hero-grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }
        .hero-card, .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 18px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }
        .hero-card strong { color: var(--ink); display: block; margin-bottom: 6px; }
        .layout {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 20px;
            margin-top: 22px;
            align-items: start;
        }
        .stack { display: grid; gap: 18px; }
        .toc a {
            display: block;
            padding: 10px 0;
            border-bottom: 1px solid var(--line);
            color: var(--blue);
            text-decoration: none;
        }
        .toc a:last-child { border-bottom: none; }
        .section-title { margin: 0 0 10px; font-size: 1.2rem; }
        .endpoint {
            display: grid;
            gap: 14px;
            border-radius: 24px;
            border: 1px solid var(--line);
            background: var(--panel);
            padding: 22px;
            box-shadow: var(--shadow);
        }
        .head {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .method {
            color: #fff;
            font-weight: 700;
            border-radius: 999px;
            min-width: 76px;
            text-align: center;
            padding: 8px 12px;
        }
        .get { background: var(--green); }
        .post, .put, .delete { background: var(--orange); }
        .path {
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(15,108,189,.08);
            border: 1px solid rgba(15,108,189,.12);
            font-family: Consolas, monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }
        th, td {
            padding: 11px 13px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }
        th { background: rgba(20,49,58,.06); }
        tr:last-child td { border-bottom: none; }
        pre {
            margin: 0;
            padding: 14px;
            border-radius: 16px;
            overflow-x: auto;
            background: #10212b;
            color: #edf7ff;
            font-size: .92rem;
        }
        code { font-family: Consolas, monospace; }
        .muted { color: var(--muted); }
        .chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(20,49,58,.06);
            border: 1px solid var(--line);
            font-size: .92rem;
        }
        .role {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .03em;
            border: 1px solid transparent;
        }
        .role.admin {
            background: rgba(15,108,189,.12);
            color: #0f6cbd;
            border-color: rgba(15,108,189,.18);
        }
        .role.staff {
            background: rgba(21,127,91,.12);
            color: #157f5b;
            border-color: rgba(21,127,91,.18);
        }
        .role.customer {
            background: rgba(217,108,6,.12);
            color: #b85b04;
            border-color: rgba(217,108,6,.18);
        }
        .role.public {
            background: rgba(98,113,122,.12);
            color: #4a5962;
            border-color: rgba(98,113,122,.18);
        }
        .ownership {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
        }
        .ownership-card {
            padding: 14px;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.72);
        }
        .ownership-card strong {
            display: block;
            margin-bottom: 8px;
        }
        ul { margin: 0; padding-left: 18px; }
        @media (max-width: 920px) {
            .layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="shell">
    <section class="hero">
        <h1>Pharmacity API docs</h1>
        <p>Trang nay tong hop cac API quan trong da lam cho nhan vien, hoa don, nhap kho, thanh toan, lich su don hang, xac thuc email va reset password. Frontend co the dung truc tiep de map request body, query param va response.</p>
        <div class="hero-grid">
            <div class="hero-card">
                <strong>Base URL</strong>
                <div><code>{{ url('/api') }}</code></div>
            </div>
            <div class="hero-card">
                <strong>Auth</strong>
                <div>Bearer token qua Sanctum</div>
            </div>
            <div class="hero-card">
                <strong>Tai khoan mau</strong>
                <div><code>admin / password</code><br><code>staff / password</code></div>
            </div>
            <div class="hero-card">
                <strong>Seed command</strong>
                <div><code>php artisan pharmacity:seed-samples 5|10|20|40 --fresh</code></div>
            </div>
        </div>
    </section>

    <div class="layout">
        <aside class="stack">
            <section class="panel toc">
                <h2 class="section-title">Muc luc</h2>
                <a href="#auth">Dang nhap</a>
                <a href="#nv-info">Thong tin nhan vien</a>
                <a href="#hoa-don">Hoa don va chi tiet hoa don</a>
                <a href="#thanh-toan">Thanh toan</a>
                <a href="#lich-su">Lich su don hang</a>
                <a href="#phieu-nhap">Phieu nhap va chi tiet</a>
                <a href="#email-verification">Email verification</a>
                <a href="#password-reset">Password reset</a>
                <a href="#postman">Postman va test cases</a>
            </section>

            <section class="panel">
                <h2 class="section-title">Phan quyen</h2>
                <div class="chips">
                    <span class="role admin">Admin</span>
                    <span class="role staff">Nhan vien</span>
                    <span class="role customer">Khach hang</span>
                    <span class="role public">Public</span>
                </div>
            </section>

            <section class="panel">
                <h2 class="section-title">Quy uoc response</h2>
                <div class="chips">
                    <span class="chip"><code>message</code>: thong bao</span>
                    <span class="chip"><code>data</code>: payload chinh</span>
                    <span class="chip">401/403: auth, role</span>
                    <span class="chip">404: khong tim thay</span>
                    <span class="chip">422: validate/nghiep vu</span>
                </div>
            </section>

            <section class="panel">
                <h2 class="section-title">Client vs Server</h2>
                <div class="chips">
                    <span class="chip">Client: field frontend gui len API</span>
                    <span class="chip">Server: field backend tu xu ly</span>
                </div>
            </section>
        </aside>

        <main class="stack">
            <section class="endpoint" id="auth">
                <div class="head"><span class="method post">POST</span><span class="path">/api/login</span><span class="role public">Public</span></div>
                <p>Dang nhap cho nhan vien. Token duoc dung cho toan bo API admin va staff.</p>
                <div class="ownership">
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>ten_dang_nhap</code></li>
                            <li><code>password</code></li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>kiem tra password bcrypt</li>
                            <li>xac dinh role <code>admin</code> hay <code>staff</code></li>
                            <li>tao Sanctum token</li>
                        </ul>
                    </div>
                </div>
<pre>{
  "ten_dang_nhap": "admin",
  "password": "password"
}</pre>
<pre>{
  "token": "1|token",
  "message": "Dang nhap thanh cong.",
  "type": "admin"
}</pre>
            </section>

            <section class="endpoint" id="nv-info">
                <div class="head"><span class="method get">GET</span><span class="path">/api/admin/thong-tin-nhan-viens</span><span class="role admin">Admin</span></div>
                <p>Admin xem danh sach thong tin chi tiet cua nhan vien. Cac endpoint chinh:</p>
                <div class="ownership">
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>id_nhan_vien</code> khi admin tao moi</li>
                            <li><code>so_dien_thoai</code></li>
                            <li><code>email</code></li>
                            <li><code>dia_chi</code></li>
                            <li><code>ngay_sinh</code></li>
                            <li><code>ngay_vao_lam</code></li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>validate tuoi >= 18</li>
                            <li>validate email, SDT unique</li>
                            <li>route <code>/me</code> tu lay <code>id_nhan_vien</code> tu token</li>
                        </ul>
                    </div>
                </div>
                <table>
                    <thead><tr><th>Role</th><th>Method</th><th>URL</th><th>Mo ta</th></tr></thead>
                    <tbody>
                        <tr><td><span class="role admin">Admin</span></td><td>POST</td><td><code>/api/admin/thong-tin-nhan-viens</code></td><td>Tao thong tin nhan vien</td></tr>
                        <tr><td><span class="role admin">Admin</span></td><td>PUT</td><td><code>/api/admin/thong-tin-nhan-viens/{id}</code></td><td>Cap nhat toan bo</td></tr>
                        <tr><td><span class="role admin">Admin</span></td><td>DELETE</td><td><code>/api/admin/thong-tin-nhan-viens/{id}</code></td><td>Xoa</td></tr>
                        <tr><td><span class="role admin">Admin</span></td><td>GET</td><td><code>/api/admin/thong-tin-nhan-viens/search?q=...</code></td><td>Tim theo SDT, email, dia chi</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>GET</td><td><code>/api/thong-tin-nhan-vien/me</code></td><td>Nhan vien xem ho so cua minh</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>PUT</td><td><code>/api/thong-tin-nhan-vien/me</code></td><td>Nhan vien cap nhat gioi han</td></tr>
                    </tbody>
                </table>
<pre>{
  "id_nhan_vien": 2,
  "so_dien_thoai": "0912345678",
  "email": "staff@example.com",
  "dia_chi": "123 Duong ABC, Quan 1",
  "ngay_sinh": "1996-03-18",
  "ngay_vao_lam": "2023-03-18"
}</pre>
            </section>

            <section class="endpoint" id="hoa-don">
                <div class="head"><span class="method get">GET</span><span class="path">/api/admin/hoa-dons</span><span class="role admin">Admin</span><span class="role staff">Nhan vien</span></div>
                <p>Module ban hang hien co 2 lop: hoa don tong va chi tiet hoa don. Backend tu tinh <code>thanh_tien</code>, <code>tong_tien</code>, <code>tien_thanh_toan</code> va tru ton kho lo thuoc.</p>
                <div class="ownership">
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>id_khach_hang</code></li>
                            <li><code>giam_gia</code></li>
                            <li><code>ngay_ban</code> neu muon custom</li>
                            <li><code>ma_hoa_don</code> la tuy chon</li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>tu lay <code>id_nhan_vien</code> tu token</li>
                            <li>tu sinh <code>ma_hoa_don</code> neu client khong gui</li>
                            <li>tu tinh <code>tong_tien</code> va <code>tien_thanh_toan</code> tu chi tiet</li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>id_lo</code></li>
                            <li><code>so_luong</code></li>
                            <li><code>gia_ban</code> la tuy chon</li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>kiem tra ton kho <code>so_luong_con</code></li>
                            <li>tu lay gia ban mac dinh tu thuoc neu can</li>
                            <li>tu tinh <code>thanh_tien</code></li>
                            <li>tu tru ton kho lo thuoc</li>
                        </ul>
                    </div>
                </div>
                <table>
                    <thead><tr><th>Role</th><th>Method</th><th>URL</th><th>Mo ta</th></tr></thead>
                    <tbody>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>POST</td><td><code>/api/hoa-dons</code></td><td>Tao hoa don</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>GET</td><td><code>/api/hoa-dons</code></td><td>Danh sach hoa don</td></tr>
                        <tr><td><span class="role admin">Admin</span></td><td>GET</td><td><code>/api/admin/hoa-dons/statistics</code></td><td>Thong ke doanh thu</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>POST</td><td><code>/api/hoa-dons/{id_hoa_don}/chi-tiets</code></td><td>Them chi tiet hoa don</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>PUT</td><td><code>/api/chi-tiet-hoa-dons/{id}</code></td><td>Sua chi tiet hoa don</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>DELETE</td><td><code>/api/chi-tiet-hoa-dons/{id}</code></td><td>Xoa chi tiet hoa don</td></tr>
                        <tr><td><span class="role admin">Admin</span></td><td>GET</td><td><code>/api/admin/chi-tiet-hoa-dons/search?q=...</code></td><td>Tim chi tiet hoa don</td></tr>
                    </tbody>
                </table>
<pre>{
  "id_khach_hang": 1,
  "tong_tien": 0,
  "giam_gia": 0
}</pre>
<pre>{
  "id_lo": 5,
  "so_luong": 2,
  "gia_ban": 50000
}</pre>
            </section>

            <section class="endpoint" id="thanh-toan">
                <div class="head"><span class="method post">POST</span><span class="path">/api/thanh-toans</span><span class="role admin">Admin</span><span class="role staff">Nhan vien</span></div>
                <p>Moi hoa don chi co mot ban ghi thanh toan. <code>so_tien</code> phai bang <code>hoa_don.tien_thanh_toan</code>. Neu <code>phuong_thuc != tien_mat</code> thi phai co <code>ma_giao_dich</code>.</p>
                <div class="ownership">
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>id_hoa_don</code></li>
                            <li><code>phuong_thuc</code></li>
                            <li><code>so_tien</code></li>
                            <li><code>thoi_gian</code> la tuy chon</li>
                            <li><code>ma_giao_dich</code> neu khong phai tien mat</li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>kiem tra moi hoa don chi co 1 thanh toan</li>
                            <li>doi chieu <code>so_tien</code> voi <code>hoa_don.tien_thanh_toan</code></li>
                            <li>validate bat buoc <code>ma_giao_dich</code> theo phuong thuc</li>
                        </ul>
                    </div>
                </div>
<pre>{
  "id_hoa_don": 10,
  "phuong_thuc": "tien_mat",
  "so_tien": 100000
}</pre>
                <table>
                    <thead><tr><th>Role</th><th>Method</th><th>URL</th><th>Mo ta</th></tr></thead>
                    <tbody>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>POST</td><td><code>/api/thanh-toans</code></td><td>Tao thanh toan</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>GET</td><td><code>/api/thanh-toans/{id_hoa_don}</code></td><td>Chi tiet thanh toan</td></tr>
                        <tr><td><span class="role admin">Admin</span></td><td>GET</td><td><code>/api/admin/thanh-toans</code></td><td>Admin xem toan bo</td></tr>
                        <tr><td><span class="role admin">Admin</span></td><td>GET</td><td><code>/api/admin/thanh-toans/search?q=...</code></td><td>Tim theo ma giao dich, phuong thuc, ma hoa don</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="endpoint" id="lich-su">
                <div class="head"><span class="method post">POST</span><span class="path">/api/hoa-dons/{id_hoa_don}/lich-su</span><span class="role admin">Admin</span><span class="role staff">Nhan vien</span></div>
                <p>Nhan vien them lich su xu ly don hang. <code>id_nhan_vien</code> lay tu token dang nhap.</p>
                <div class="ownership">
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>trang_thai</code></li>
                            <li><code>ghi_chu</code></li>
                            <li><code>thoi_gian</code> la tuy chon</li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>tu lay <code>id_nhan_vien</code> tu token</li>
                            <li>kiem tra hoa don ton tai</li>
                            <li>gan <code>thoi_gian = now()</code> neu client khong gui</li>
                        </ul>
                    </div>
                </div>
<pre>{
  "trang_thai": "dang_xu_ly",
  "ghi_chu": "Da xac nhan thong tin giao dich"
}</pre>
            </section>

            <section class="endpoint" id="phieu-nhap">
                <div class="head"><span class="method post">POST</span><span class="path">/api/phieu-nhaps</span><span class="role admin">Admin</span><span class="role staff">Nhan vien</span></div>
                <p>Module nhap kho tu dong cap nhat <code>tong_tien</code> cua phieu nhap khi them, sua, xoa chi tiet. Backend dong thoi cap nhat lai <code>so_luong_nhap</code> va <code>so_luong_con</code> cua lo thuoc.</p>
                <div class="ownership">
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>id_nha_san_xuat</code></li>
                            <li><code>ngay_nhap</code> la tuy chon</li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>tu lay <code>id_nhan_vien</code> tu token</li>
                            <li>khoi tao <code>tong_tien = 0</code></li>
                            <li>tu cap nhat lai <code>tong_tien</code> sau moi thay doi chi tiet</li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>id_lo</code></li>
                            <li><code>so_luong</code></li>
                            <li><code>gia_nhap</code></li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>tu cong <code>so_luong_nhap</code> va <code>so_luong_con</code> cua lo</li>
                            <li>tu tinh tong tien phieu nhap</li>
                            <li>kiem tra xoa/sua khong lam du lieu ton kho am</li>
                        </ul>
                    </div>
                </div>
                <table>
                    <thead><tr><th>Role</th><th>Method</th><th>URL</th><th>Mo ta</th></tr></thead>
                    <tbody>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>POST</td><td><code>/api/phieu-nhaps</code></td><td>Tao phieu nhap</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>POST</td><td><code>/api/phieu-nhaps/{id_phieu_nhap}/chi-tiets</code></td><td>Them chi tiet phieu nhap</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>PUT</td><td><code>/api/chi-tiet-phieu-nhaps/{id}</code></td><td>Sua chi tiet</td></tr>
                        <tr><td><span class="role staff">Nhan vien</span></td><td>DELETE</td><td><code>/api/chi-tiet-phieu-nhaps/{id}</code></td><td>Xoa chi tiet</td></tr>
                        <tr><td><span class="role admin">Admin</span></td><td>GET</td><td><code>/api/admin/phieu-nhaps/statistics</code></td><td>Thong ke tong tien nhap</td></tr>
                    </tbody>
                </table>
<pre>{
  "id_nha_san_xuat": 1,
  "ngay_nhap": "2026-03-18 08:30:00"
}</pre>
<pre>{
  "id_lo": 12,
  "so_luong": 30,
  "gia_nhap": 22000
}</pre>
            </section>

            <section class="endpoint" id="email-verification">
                <div class="head"><span class="method post">POST</span><span class="path">/api/email-verifications/request</span><span class="role public">Public</span><span class="role customer">Khach hang</span><span class="role admin">Admin</span></div>
                <p>API public de tao va xac thuc token email. Trong moi truong dev, response tra ve token de frontend/Postman test nhanh.</p>
                <div class="ownership">
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>email</code> khi request/resend</li>
                            <li><code>token</code> tren URL khi verify</li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>tu sinh token</li>
                            <li>tu gan <code>expired_at</code></li>
                            <li>tu cap nhat <code>khach_hangs.email_verified</code></li>
                        </ul>
                    </div>
                </div>
                <table>
                    <thead><tr><th>Role</th><th>Method</th><th>URL</th><th>Mo ta</th></tr></thead>
                    <tbody>
                        <tr><td><span class="role public">Public</span></td><td>POST</td><td><code>/api/email-verifications/request</code></td><td>Tao token moi</td></tr>
                        <tr><td><span class="role public">Public</span></td><td>POST</td><td><code>/api/email-verifications/resend</code></td><td>Gui lai token</td></tr>
                        <tr><td><span class="role public">Public</span></td><td>GET</td><td><code>/api/email-verifications/verify/{token}</code></td><td>Xac thuc email</td></tr>
                        <tr><td><span class="role admin">Admin</span></td><td>GET</td><td><code>/api/admin/email-verifications</code></td><td>Admin xem lich su token</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="endpoint" id="password-reset">
                <div class="head"><span class="method post">POST</span><span class="path">/api/password-resets/request</span><span class="role public">Public</span><span class="role customer">Khach hang</span><span class="role admin">Admin</span></div>
                <p>Flow reset password public gom 3 buoc: request token, validate token, reset password. Token chi dung duoc 1 lan.</p>
                <div class="ownership">
                    <div class="ownership-card">
                        <strong>Client</strong>
                        <ul>
                            <li><code>email</code> khi request</li>
                            <li><code>token</code> khi validate/reset</li>
                            <li><code>password</code> va <code>password_confirmation</code> khi reset</li>
                        </ul>
                    </div>
                    <div class="ownership-card">
                        <strong>Server</strong>
                        <ul>
                            <li>tu sinh token reset</li>
                            <li>tu gan han su dung</li>
                            <li>tu hash password bcrypt khi reset thanh cong</li>
                            <li>tu danh dau token da dung</li>
                        </ul>
                    </div>
                </div>
<pre>{
  "email": "khach@example.com"
}</pre>
<pre>{
  "email": "khach@example.com",
  "token": "raw-token",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}</pre>
            </section>

            <section class="endpoint" id="postman">
                <div class="head"><span class="method get">FILE</span><span class="path">pharmacity-modules-postman.json</span><span class="role admin">Admin</span><span class="role staff">Nhan vien</span><span class="role customer">Khach hang</span><span class="role public">Public</span></div>
                <p>Bo Postman moi va file matrix test case duoc tao san:</p>
                <ul>
                    <li><code>pharmacity-modules-postman.json</code>: collection de import vao Postman.</li>
                    <li><code>POSTMAN_TEST_CASES.md</code>: danh sach case thanh cong, validate loi, khong co quyen, khong tim thay.</li>
                </ul>
            </section>
        </main>
    </div>
</div>
</body>
</html>
