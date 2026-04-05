<template>
  <footer class="pc-footer">
    <div class="pc-container">
      <div class="pc-footer__grid">
        <div class="pc-footer__brand">
          <RouterLink to="/" class="pc-footer__logo">
            <span class="pc-footer__brand-line">NHÀ THUỐC</span>
            <span class="pc-footer__name">PharmaGo</span>
          </RouterLink>

          <p class="pc-footer__copy">
            PharmaGo là nhà thuốc trực tuyến hiện đại, cung cấp sản phẩm chính hãng và giải pháp chăm sóc sức khỏe tiện lợi.
            Tìm kiếm nhanh, tư vấn tận tâm, giao hàng tận nơi giúp bạn an tâm mỗi ngày.
          </p>
        </div>

        <div class="pc-footer__column">
          <h3 class="pc-footer__title">Mua sắm</h3>
          <ul class="pc-footer__links">
            <li><RouterLink to="/">Trang chủ</RouterLink></li>
            <li><RouterLink to="/gio-hang">Giỏ hàng</RouterLink></li>
            <li><RouterLink to="/thanh-toan">Thanh toán</RouterLink></li>
          </ul>
        </div>

        <div class="pc-footer__column">
          <h3 class="pc-footer__title">Tài khoản</h3>
          <ul class="pc-footer__links">
            <li><RouterLink to="/tai-khoan/thong-tin">Thông tin cá nhân</RouterLink></li>
            <li><RouterLink to="/tai-khoan/dia-chi">Sổ địa chỉ</RouterLink></li>
            <li><RouterLink to="/tai-khoan/lich-su-don-hang">Lịch sử đơn hàng</RouterLink></li>
          </ul>
        </div>

        <div class="pc-footer__column">
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

<style scoped>
.pc-footer {
  padding: 52px 0 58px;
  background: #1f3f92;
  color: #fff;
}

.pc-footer__grid {
  display: grid;
  grid-template-columns: 1.45fr 0.7fr 0.8fr 1fr;
  gap: 42px;
}

.pc-footer__logo {
  display: inline-flex;
  flex-direction: column;
  line-height: 1;
  color: #fff;
  text-decoration: none;
}

.pc-footer__brand-line {
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.pc-footer__name {
  margin-top: 4px;
  color: #9be14a;
  font-size: 2.15rem;
  font-weight: 800;
}

.pc-footer__copy {
  max-width: 31rem;
  margin: 28px 0 0;
  color: rgba(255, 255, 255, 0.82);
  font-size: 1rem;
  line-height: 1.6;
}

.pc-footer__title {
  margin: 0 0 18px;
  font-size: 1.05rem;
  font-weight: 800;
}

.pc-footer__links {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 14px;
  color: rgba(255, 255, 255, 0.86);
}

.pc-footer__links a {
  color: inherit;
  text-decoration: none;
}

.pc-footer__links li {
  font-size: 1rem;
  line-height: 1.45;
}

@media (max-width: 1199.98px) {
  .pc-footer__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 32px;
  }
}

@media (max-width: 767.98px) {
  .pc-footer {
    padding: 40px 0 44px;
  }

  .pc-footer__grid {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .pc-footer__copy {
    margin-top: 20px;
  }
}
</style>
