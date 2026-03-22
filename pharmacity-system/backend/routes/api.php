<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BangCapController;
use App\Http\Controllers\CatalogThuocController;
use App\Http\Controllers\ChiTietHoaDonController;
use App\Http\Controllers\ChiTietPhieuNhapController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\HoaDonController;
use App\Http\Controllers\KhachHangController;
use App\Http\Controllers\KhuyenMaiController;
use App\Http\Controllers\LichSuDonHangController;
use App\Http\Controllers\LoaiThuocController;
use App\Http\Controllers\LoThuocController;
use App\Http\Controllers\NhanVienController;
use App\Http\Controllers\NhaSanXuatController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PhieuNhapController;
use App\Http\Controllers\ThanhToanController;
use App\Http\Controllers\ThongTinNhanVienController;
use App\Http\Controllers\ThuocController;
use App\Http\Controllers\VaiTroController;
use Illuminate\Support\Facades\Route;

// =========================
// SERVER
// Public endpoints: client goi truc tiep, server xu ly trong controller/request/model
// =========================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/catalog/thuocs', [CatalogThuocController::class, 'index']);
Route::get('/catalog/thuocs/{ma_thuoc}', [CatalogThuocController::class, 'show']);
Route::get('/email/verify/{token}', [AuthController::class, 'verifyEmail']);
Route::post('/email-verifications/request', [EmailVerificationController::class, 'request']);
Route::get('/email-verifications/verify/{token}', [EmailVerificationController::class, 'verify']);
Route::post('/email-verifications/resend', [EmailVerificationController::class, 'resend']);
Route::post('/password-resets/request', [PasswordResetController::class, 'request']);
Route::post('/password-resets/validate-token', [PasswordResetController::class, 'validateToken']);
Route::post('/password-resets/reset', [PasswordResetController::class, 'reset']);

// =========================
// SERVER
// Admin endpoints: client admin goi, server xu ly
// =========================
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin'])
    ->group(function () {
        // Admin - Thong tin nhan vien
        Route::get('/thong-tin-nhan-viens/search', [ThongTinNhanVienController::class, 'search']);
        Route::get('/thong-tin-nhan-viens', [ThongTinNhanVienController::class, 'index']);
        Route::post('/thong-tin-nhan-viens', [ThongTinNhanVienController::class, 'store']);
        Route::get('/thong-tin-nhan-viens/{id}', [ThongTinNhanVienController::class, 'show']);
        Route::put('/thong-tin-nhan-viens/{id}', [ThongTinNhanVienController::class, 'update']);
        Route::delete('/thong-tin-nhan-viens/{id}', [ThongTinNhanVienController::class, 'destroy']);

        // Admin - Hoa don / chi tiet / thong ke / lich su
        Route::get('/hoa-dons/search', [HoaDonController::class, 'search']);
        Route::get('/hoa-dons/statistics', [HoaDonController::class, 'statistics']);
        Route::get('/hoa-dons', [HoaDonController::class, 'index']);
        Route::get('/hoa-dons/{id}', [HoaDonController::class, 'show']);
        Route::get('/hoa-dons/{id_hoa_don}/chi-tiets', [ChiTietHoaDonController::class, 'indexByHoaDon']);
        Route::get('/chi-tiet-hoa-dons/search', [ChiTietHoaDonController::class, 'search']);
        Route::get('/chi-tiet-hoa-dons/{id}', [ChiTietHoaDonController::class, 'show']);
        Route::get('/hoa-dons/{id_hoa_don}/lich-su', [LichSuDonHangController::class, 'indexByHoaDon']);

        // Admin - Lo thuoc
        Route::get('/lo-thuocs/search', [LoThuocController::class, 'search']);
        Route::get('/lo-thuocs/expiring', [LoThuocController::class, 'expiring']);
        Route::get('/lo-thuocs', [LoThuocController::class, 'index']);
        Route::post('/lo-thuocs', [LoThuocController::class, 'store']);
        Route::get('/lo-thuocs/{id}', [LoThuocController::class, 'show']);
        Route::put('/lo-thuocs/{id}', [LoThuocController::class, 'update']);
        Route::delete('/lo-thuocs/{id}', [LoThuocController::class, 'destroy']);

        // Admin - Khach hang
        Route::get('/khach-hangs/search', [KhachHangController::class, 'search']);
        Route::get('/khach-hangs', [KhachHangController::class, 'index']);
        Route::get('/khach-hangs/{id}', [KhachHangController::class, 'show']);
        Route::put('/khach-hangs/{id}', [KhachHangController::class, 'update']);
        Route::delete('/khach-hangs/{id}', [KhachHangController::class, 'destroy']);

        // Admin - Nhan vien
        Route::get('/nhan-viens/search', [NhanVienController::class, 'search']);
        Route::get('/nhan-viens', [NhanVienController::class, 'index']);
        Route::post('/nhan-viens', [NhanVienController::class, 'store']);
        Route::get('/nhan-viens/{id}', [NhanVienController::class, 'show']);
        Route::put('/nhan-viens/{id}', [NhanVienController::class, 'update']);
        Route::delete('/nhan-viens/{id}', [NhanVienController::class, 'destroy']);
        Route::put('/nhan-viens/{id}/change-password', [NhanVienController::class, 'changePassword']);
        Route::put('/nhan-viens/{id}/role', [NhanVienController::class, 'changeRole']);

        // Admin - Vai tro
        Route::get('/vai-tros/search', [VaiTroController::class, 'search']);
        Route::get('/vai-tros', [VaiTroController::class, 'index']);
        Route::post('/vai-tros', [VaiTroController::class, 'store']);
        Route::get('/vai-tros/{id}', [VaiTroController::class, 'show']);
        Route::put('/vai-tros/{id}', [VaiTroController::class, 'update']);
        Route::delete('/vai-tros/{id}', [VaiTroController::class, 'destroy']);

        // Admin - Bang cap
        Route::get('/bang-caps/search', [BangCapController::class, 'search']);
        Route::get('/bang-caps', [BangCapController::class, 'index']);
        Route::post('/bang-caps', [BangCapController::class, 'store']);
        Route::get('/bang-caps/{id}', [BangCapController::class, 'show']);
        Route::put('/bang-caps/{id}', [BangCapController::class, 'update']);
        Route::delete('/bang-caps/{id}', [BangCapController::class, 'destroy']);

        // Admin - Thuoc
        Route::get('/thuocs/search', [ThuocController::class, 'search']);
        Route::put('/thuocs/{id}/status', [ThuocController::class, 'updateStatus']);
        Route::get('/thuocs', [ThuocController::class, 'index']);
        Route::post('/thuocs', [ThuocController::class, 'store']);
        Route::get('/thuocs/{id}', [ThuocController::class, 'show']);
        Route::put('/thuocs/{id}', [ThuocController::class, 'update']);
        Route::delete('/thuocs/{id}', [ThuocController::class, 'destroy']);

        // Admin - Nha san xuat
        Route::get('/nha-san-xuats/search', [NhaSanXuatController::class, 'search']);
        Route::get('/nha-san-xuats', [NhaSanXuatController::class, 'index']);
        Route::post('/nha-san-xuats', [NhaSanXuatController::class, 'store']);
        Route::get('/nha-san-xuats/{id}', [NhaSanXuatController::class, 'show']);
        Route::put('/nha-san-xuats/{id}', [NhaSanXuatController::class, 'update']);
        Route::delete('/nha-san-xuats/{id}', [NhaSanXuatController::class, 'destroy']);

        // Admin - Phieu nhap / chi tiet / thong ke
        Route::get('/phieu-nhaps/search', [PhieuNhapController::class, 'search']);
        Route::get('/phieu-nhaps/statistics', [PhieuNhapController::class, 'statistics']);
        Route::get('/phieu-nhaps', [PhieuNhapController::class, 'index']);
        Route::get('/phieu-nhaps/{id}', [PhieuNhapController::class, 'show']);
        Route::get('/phieu-nhaps/{id_phieu_nhap}/chi-tiets', [ChiTietPhieuNhapController::class, 'indexByPhieuNhap']);
        Route::get('/chi-tiet-phieu-nhaps/search', [ChiTietPhieuNhapController::class, 'search']);
        Route::get('/chi-tiet-phieu-nhaps/{id}', [ChiTietPhieuNhapController::class, 'show']);

        // Admin - Thanh toan
        Route::get('/thanh-toans/search', [ThanhToanController::class, 'search']);
        Route::get('/thanh-toans', [ThanhToanController::class, 'index']);
        Route::get('/thanh-toans/{id_hoa_don}', [ThanhToanController::class, 'show']);

        // Admin - Lich su don hang
        Route::get('/lich-su-don-hangs/search', [LichSuDonHangController::class, 'search']);
        Route::get('/lich-su-don-hangs', [LichSuDonHangController::class, 'index']);

        // Admin - Email verification logs
        Route::get('/email-verifications/search', [EmailVerificationController::class, 'search']);
        Route::get('/email-verifications', [EmailVerificationController::class, 'index']);
        Route::get('/email-verifications/{id}', [EmailVerificationController::class, 'show']);

        // Admin - Password reset logs
        Route::get('/password-resets/search', [PasswordResetController::class, 'search']);
        Route::get('/password-resets', [PasswordResetController::class, 'index']);
        Route::get('/password-resets/{id}', [PasswordResetController::class, 'show']);

        // Admin - Loai thuoc
        Route::get('/loai-thuocs/search', [LoaiThuocController::class, 'search']);
        Route::get('/loai-thuocs', [LoaiThuocController::class, 'index']);
        Route::post('/loai-thuocs', [LoaiThuocController::class, 'store']);
        Route::get('/loai-thuocs/{id}', [LoaiThuocController::class, 'show']);
        Route::put('/loai-thuocs/{id}', [LoaiThuocController::class, 'update']);
        Route::delete('/loai-thuocs/{id}', [LoaiThuocController::class, 'destroy']);
    });

// =========================
// SERVER
// Staff/Admin endpoints: client nhan vien hoac admin goi, server xu ly
// =========================
Route::prefix('hoa-dons')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Hoa don
        Route::get('/search', [HoaDonController::class, 'search']);
        Route::get('/', [HoaDonController::class, 'index']);
        Route::post('/', [HoaDonController::class, 'store']);
        Route::get('/{id}', [HoaDonController::class, 'show']);

        // Nhan vien/Admin - Chi tiet hoa don
        Route::get('/{id_hoa_don}/chi-tiets', [ChiTietHoaDonController::class, 'indexByHoaDon']);
        Route::post('/{id_hoa_don}/chi-tiets', [ChiTietHoaDonController::class, 'store']);

        // Nhan vien/Admin - Lich su don hang theo hoa don
        Route::get('/{id_hoa_don}/lich-su', [LichSuDonHangController::class, 'indexByHoaDon']);
        Route::post('/{id_hoa_don}/lich-su', [LichSuDonHangController::class, 'store']);
    });

Route::prefix('chi-tiet-hoa-dons')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Sua/xoa chi tiet hoa don
        Route::put('/{id}', [ChiTietHoaDonController::class, 'update']);
        Route::delete('/{id}', [ChiTietHoaDonController::class, 'destroy']);
    });

Route::prefix('phieu-nhaps')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Phieu nhap
        Route::get('/search', [PhieuNhapController::class, 'search']);
        Route::get('/', [PhieuNhapController::class, 'index']);
        Route::post('/', [PhieuNhapController::class, 'store']);
        Route::get('/{id}', [PhieuNhapController::class, 'show']);

        // Nhan vien/Admin - Chi tiet phieu nhap theo phieu
        Route::get('/{id_phieu_nhap}/chi-tiets', [ChiTietPhieuNhapController::class, 'indexByPhieuNhap']);
        Route::post('/{id_phieu_nhap}/chi-tiets', [ChiTietPhieuNhapController::class, 'store']);
    });

Route::prefix('chi-tiet-phieu-nhaps')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Sua/xoa chi tiet phieu nhap
        Route::put('/{id}', [ChiTietPhieuNhapController::class, 'update']);
        Route::delete('/{id}', [ChiTietPhieuNhapController::class, 'destroy']);
    });

Route::prefix('thanh-toans')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Thanh toan
        Route::post('/', [ThanhToanController::class, 'store']);
        Route::get('/{id_hoa_don}', [ThanhToanController::class, 'show']);
    });

Route::prefix('thuocs')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Thuoc va gia ban
        Route::get('/search', [ThuocController::class, 'search']);
        Route::get('/', [ThuocController::class, 'index']);
        Route::get('/{id}', [ThuocController::class, 'show']);
        Route::put('/{id}/price', [ThuocController::class, 'updatePrice']);
    });

Route::prefix('khuyen-mais')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Khuyen mai
        Route::get('/search', [KhuyenMaiController::class, 'search']);
        Route::get('/', [KhuyenMaiController::class, 'index']);
        Route::post('/', [KhuyenMaiController::class, 'store']);
        Route::get('/{id}', [KhuyenMaiController::class, 'show']);
        Route::put('/{id}', [KhuyenMaiController::class, 'update']);
        Route::delete('/{id}', [KhuyenMaiController::class, 'destroy']);
    });

Route::prefix('lo-thuocs')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Lo thuoc (read only o nhom nay)
        Route::get('/search', [LoThuocController::class, 'search']);
        Route::get('/', [LoThuocController::class, 'index']);
        Route::get('/{id}', [LoThuocController::class, 'show']);
    });

// =========================
// SERVER
// Profile endpoints: client user da dang nhap goi, server xu ly
// =========================
Route::prefix('profile')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // Profile ca nhan cua user dang nhap
        Route::get('/', [AuthController::class, 'profile']);
        Route::put('/', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

Route::prefix('thong-tin-nhan-vien')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Ho so chi tiet cua chinh minh
        Route::get('/me', [ThongTinNhanVienController::class, 'me']);
        Route::put('/me', [ThongTinNhanVienController::class, 'updateMe']);
    });

Route::prefix('vai-tros')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Vai tro (read only)
        Route::get('/', [VaiTroController::class, 'index']);
        Route::get('/{id}', [VaiTroController::class, 'show']);
    });

Route::prefix('bang-caps')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Bang cap (read only)
        Route::get('/', [BangCapController::class, 'index']);
        Route::get('/{id}', [BangCapController::class, 'show']);
    });

Route::prefix('nha-san-xuats')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Nha san xuat (read only)
        Route::get('/', [NhaSanXuatController::class, 'index']);
        Route::get('/{id}', [NhaSanXuatController::class, 'show']);
    });

Route::prefix('loai-thuocs')
    ->middleware(['auth:sanctum', 'nhan_vien.role:admin,staff'])
    ->group(function () {
        // Nhan vien/Admin - Loai thuoc (read only)
        Route::get('/', [LoaiThuocController::class, 'index']);
        Route::get('/{id}', [LoaiThuocController::class, 'show']);
    });
