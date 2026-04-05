<template>
  <section class="auth-card">
    <div class="auth-card__hero">
      <h1 class="auth-card__title">
        Đăng Nhập Hệ Thống
        <span class="auth-card__brand">PHARMAGO</span>
      </h1>
      <p class="auth-card__copy">
        Đăng nhập bằng số điện thoại để tiếp tục mua sắm, theo dõi đơn hàng và quản lý tài khoản trên hệ thống
        PharmaGo.
      </p>
    </div>

    <div class="auth-card__form">
      <div class="mb-4">
        <h1 class="h4 fw-bold mb-2 text-center">Đăng Nhập</h1>

        <div class="auth-benefits">
          <div class="auth-benefit">
            <span class="auth-benefit__icon auth-benefit__icon--blue">
              <i class="fa-solid fa-truck-fast"></i>
            </span>
            <span class="text-secondary">Miễn phí vận chuyển</span>
          </div>

          <div class="auth-benefit">
            <span class="auth-benefit__icon auth-benefit__icon--mint">
              <i class="fa-solid fa-ranking-star"></i>
            </span>
            <span class="text-secondary">Số 1 thuốc kê đơn</span>
          </div>

          <div class="auth-benefit">
            <span class="auth-benefit__icon auth-benefit__icon--violet">
              <i class="fa-solid fa-bolt"></i>
            </span>
            <span class="text-secondary">Giao nhanh trong 1 giờ</span>
          </div>
        </div>
      </div>

      <form class="vstack gap-3" @submit.prevent="dangNhap">
        <div>
          <label class="form-label fw-semibold">Số Điện Thoại</label>
          <input
            v-model.trim="so_dien_thoai"
            type="text"
            class="form-control form-control-lg"
            placeholder="Nhập số điện thoại"
          >
        </div>

        <div>
          <label class="form-label fw-semibold">Mật khẩu</label>
          <input
            v-model="mat_khau"
            type="password"
            class="form-control form-control-lg"
            placeholder="Nhập mật khẩu"
          >
        </div>

        <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          Đăng Nhập
        </button>
      </form>

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
        <RouterLink to="/register" class="text-decoration-none fw-semibold text-primary">
          Chưa có tài khoản? Đăng ký tài khoản
        </RouterLink>
        <RouterLink to="/" class="text-decoration-none text-secondary">Quay lại trang chủ</RouterLink>
      </div>

      <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
      <div v-if="error" class="alert alert-danger mt-3 mb-0">{{ error }}</div>
    </div>
  </section>
</template>

<script>
import { login } from "../../../api/authApi";
import { setAuthSession } from "../../../lib/authStorage";

export default {
  data() {
    return {
      so_dien_thoai: "",
      mat_khau: "",
      loading: false,
      error: "",
      message: "",
    };
  },
  methods: {
    normalizeError(err) {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(" | ");
      }

      return err?.message || "Đăng nhập thất bại.";
    },
    async dangNhap() {
      this.loading = true;
      this.error = "";
      this.message = "";

      try {
        const response = await login({
          so_dien_thoai: this.so_dien_thoai,
          mat_khau: this.mat_khau,
        });

        setAuthSession({
          token: response.token,
          user: response.user,
          type: response.type,
        });

        this.message = response.message || "Đăng nhập thành công.";

        if (["admin", "staff", "nhan_vien", "nhanvien"].includes(response.type)) {
          this.$router.push("/dashboard");
          return;
        }

        this.$router.push("/");
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.auth-card {
  width: min(100%, 1100px);
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  border: 1px solid var(--pc-border);
  border-radius: 32px;
  overflow: hidden;
  background: var(--pc-surface-elevated);
  box-shadow: var(--pc-shadow);
}

.auth-card__hero {
  padding: clamp(1.5rem, 4vw, 3rem);
  color: #fff;
  background:
    radial-gradient(circle at top left, rgba(20, 184, 166, 0.24), transparent 26%),
    linear-gradient(135deg, #0d2b73, #1652c5 56%, #14b8a6);
}

.auth-card__form {
  padding: clamp(1.5rem, 4vw, 3rem);
}

.auth-benefits {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin-top: 18px;
}

.auth-benefit {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 12px 10px;
  border-radius: 18px;
  color: var(--pc-text);
  font-size: 0.9rem;
  font-weight: 600;
  text-align: center;
}

.auth-benefit__icon {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 999px;
  font-size: 1.1rem;
}

.auth-benefit__icon--blue,
.auth-benefit__icon--mint,
.auth-benefit__icon--violet {
  background: #e8f0ff;
  color: #1652c5;
}

.auth-card__title {
  margin: 0 0 12px;
  font-size: clamp(2rem, 3vw, 2.8rem);
  font-weight: 800;
  line-height: 1.05;
}

.auth-card__brand {
  display: block;
  margin-top: 10px;
  color: #23b26d;
}

.auth-card__copy {
  margin: 0;
  max-width: 48ch;
  color: rgba(255, 255, 255, 0.82);
}

@media (max-width: 992px) {
  .auth-card {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px) {
  .auth-benefits {
    grid-template-columns: 1fr;
  }
}
</style>
