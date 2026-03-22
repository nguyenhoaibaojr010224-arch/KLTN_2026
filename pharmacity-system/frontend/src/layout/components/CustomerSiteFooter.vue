<template>
  <footer class="pc-footer">
    <div class="container-fluid pc-container">
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="pc-logo pc-logo--footer">
            <span class="pc-logo__brand">NHÀ THUỐC</span>
            <span class="pc-logo__name">Pharmacity</span>
          </div>
          <p class="pc-footer__copy">
            Giao diện mô phỏng sát trải nghiệm của một trang thương mại thuốc hiện đại, đồng thời vẫn dùng dữ liệu thuốc
            và đăng nhập từ backend Laravel hiện có.
          </p>
        </div>

        <div class="col-sm-6 col-lg-2">
          <h3 class="pc-footer__title">Mua sắm</h3>
          <ul class="pc-footer__links">
            <li><RouterLink to="/">Trang chủ</RouterLink></li>
            <li><RouterLink to="/gio-hang">Giỏ hàng</RouterLink></li>
            <li><RouterLink to="/thanh-toan">Thanh toán</RouterLink></li>
          </ul>
        </div>

        <div class="col-sm-6 col-lg-3">
          <h3 class="pc-footer__title">Tài khoản</h3>
          <ul class="pc-footer__links">
            <li><RouterLink to="/tai-khoan/thong-tin">Thông tin cá nhân</RouterLink></li>
            <li><RouterLink to="/tai-khoan/dia-chi">Sổ địa chỉ</RouterLink></li>
            <li><RouterLink to="/tai-khoan/lich-su-don-hang">Lịch sử đơn hàng</RouterLink></li>
          </ul>
        </div>

        <div class="col-lg-3">
          <h3 class="pc-footer__title">Liên hệ</h3>
          <ul class="pc-footer__links">
            <li>Hotline 1800 6821</li>
            <li>CSKH toàn quốc 24/7</li>
            <li>Hệ thống nhà thuốc và hỗ trợ doanh nghiệp</li>
          </ul>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
</script>
