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
          <label class="form-label fw-semibold">Email hoặc số điện thoại</label>
          <input
            v-model.trim="tai_khoan"
            type="text"
            class="form-control form-control-lg"
            placeholder="Nhập email hoặc số điện thoại"
          >
        </div>

        <div>
          <label class="form-label fw-semibold">Mật khẩu</label>
          <div class="password-field">
            <input
              v-model="mat_khau"
              :type="showLoginPassword ? 'text' : 'password'"
              class="form-control form-control-lg password-field__input"
              placeholder="Nhập mật khẩu"
            >
            <button
              class="password-field__toggle"
              type="button"
              :aria-label="showLoginPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
              @click="showLoginPassword = !showLoginPassword"
            >
              <i :class="showLoginPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
            </button>
          </div>
          <button class="auth-link-button mt-2" type="button" @click="openForgotPassword">
            Quên mật khẩu?
          </button>
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

    <div v-if="showForgotPasswordModal" class="forgot-modal" role="dialog" aria-modal="true">
      <div class="forgot-modal__backdrop"></div>
      <div class="forgot-modal__panel">
        <button class="forgot-modal__close" type="button" aria-label="Đóng" @click="closeForgotPassword">
          <i class="bi bi-x-lg"></i>
        </button>

        <div class="forgot-modal__icon">
          <i class="bi bi-shield-lock"></i>
        </div>

        <p class="forgot-modal__eyebrow">Khôi phục tài khoản</p>
        <h2 class="forgot-modal__title">Quên mật khẩu</h2>
        <p class="forgot-modal__copy">
          Nhập Gmail đã đăng ký để nhận mã 6 chữ số, sau đó đặt mật khẩu mới cho tài khoản.
        </p>

        <form class="forgot-form" @submit.prevent="forgotStep === 'email' ? guiMaDatLaiMatKhau() : datLaiMatKhau()">
          <div>
            <label class="form-label fw-semibold" for="forgot-email">Email</label>
            <input
              id="forgot-email"
              v-model.trim="forgotForm.email"
              type="email"
              class="form-control form-control-lg"
              :class="{ 'is-invalid': forgotErrors.email }"
              placeholder="Nhập Gmail đã đăng ký"
              :disabled="forgotStep === 'reset'"
            >
            <div v-if="forgotErrors.email" class="field-error">{{ forgotErrors.email }}</div>
          </div>

          <template v-if="forgotStep === 'reset'">
            <div>
              <label class="form-label fw-semibold" for="forgot-code">Mã xác minh</label>
              <input
                id="forgot-code"
                v-model="forgotForm.code"
                class="form-control form-control-lg forgot-form__code"
                :class="{ 'is-invalid': forgotErrors.code }"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="000000"
                @input="sanitizeForgotCode"
              >
              <div v-if="forgotErrors.code" class="field-error">{{ forgotErrors.code }}</div>
            </div>

            <div>
              <label class="form-label fw-semibold" for="forgot-password">Mật khẩu mới</label>
              <div class="password-field">
                <input
                  id="forgot-password"
                  v-model="forgotForm.password"
                  :type="forgotShowPassword ? 'text' : 'password'"
                  class="form-control form-control-lg password-field__input"
                  :class="{ 'is-invalid': forgotErrors.password }"
                  placeholder="Nhập mật khẩu mới"
                >
                <button
                  class="password-field__toggle"
                  type="button"
                  :aria-label="forgotShowPassword ? 'Ẩn mật khẩu mới' : 'Hiện mật khẩu mới'"
                  @click="forgotShowPassword = !forgotShowPassword"
                >
                  <i :class="forgotShowPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>
              <div v-if="forgotErrors.password" class="field-error">{{ forgotErrors.password }}</div>
            </div>

            <div>
              <label class="form-label fw-semibold" for="forgot-password-confirmation">Xác nhận mật khẩu mới</label>
              <div class="password-field">
                <input
                  id="forgot-password-confirmation"
                  v-model="forgotForm.password_confirmation"
                  :type="forgotShowPasswordConfirmation ? 'text' : 'password'"
                  class="form-control form-control-lg password-field__input"
                  :class="{ 'is-invalid': forgotErrors.password_confirmation }"
                  placeholder="Nhập lại mật khẩu mới"
                >
                <button
                  class="password-field__toggle"
                  type="button"
                  :aria-label="forgotShowPasswordConfirmation ? 'Ẩn xác nhận mật khẩu' : 'Hiện xác nhận mật khẩu'"
                  @click="forgotShowPasswordConfirmation = !forgotShowPasswordConfirmation"
                >
                  <i :class="forgotShowPasswordConfirmation ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>
              <div v-if="forgotErrors.password_confirmation" class="field-error">
                {{ forgotErrors.password_confirmation }}
              </div>
            </div>
          </template>

          <div v-if="forgotMessage" class="alert alert-info mb-0">{{ forgotMessage }}</div>
          <div v-if="forgotError" class="alert alert-danger mb-0">{{ forgotError }}</div>

          <div class="forgot-form__actions">
            <button class="btn btn-primary btn-lg forgot-form__submit" type="submit" :disabled="forgotLoading">
              <span v-if="forgotLoading" class="spinner-border spinner-border-sm me-2"></span>
              {{ forgotStep === "email" ? "Gửi mã về Gmail" : "Đặt lại mật khẩu" }}
            </button>

            <button
              v-if="forgotStep === 'reset'"
              class="btn btn-outline-primary btn-lg"
              type="button"
              :disabled="forgotLoading"
              @click="guiMaDatLaiMatKhau"
            >
              Gửi lại mã
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
import { login, requestPasswordReset, resetPasswordWithCode } from "../../../api/authApi";
import { setAuthSession } from "../../../lib/authStorage";

export default {
  data() {
    return {
      tai_khoan: "",
      mat_khau: "",
      loading: false,
      showLoginPassword: false,
      forgotLoading: false,
      forgotShowPassword: false,
      forgotShowPasswordConfirmation: false,
      showForgotPasswordModal: false,
      forgotStep: "email",
      error: "",
      message: "",
      forgotMessage: "",
      forgotError: "",
      forgotForm: {
        email: "",
        code: "",
        password: "",
        password_confirmation: "",
      },
      forgotErrors: {
        email: "",
        code: "",
        password: "",
        password_confirmation: "",
      },
    };
  },
  methods: {
    normalizeError(err) {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(" | ");
      }

      return err?.message || "Đăng nhập thất bại.";
    },
    normalizeForgotError(err, fallback = "Yêu cầu thất bại.") {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(" | ");
      }

      return err?.message || fallback;
    },
    openForgotPassword() {
      this.showForgotPasswordModal = true;
      this.forgotStep = "email";
      this.forgotMessage = "";
      this.forgotError = "";
      this.forgotShowPassword = false;
      this.forgotShowPasswordConfirmation = false;
      this.forgotErrors = {
        email: "",
        code: "",
        password: "",
        password_confirmation: "",
      };
      this.forgotForm = {
        email: "",
        code: "",
        password: "",
        password_confirmation: "",
      };
    },
    closeForgotPassword() {
      this.showForgotPasswordModal = false;
    },
    sanitizeForgotCode() {
      this.forgotForm.code = String(this.forgotForm.code || "").replace(/\D/g, "").slice(0, 6);
      this.forgotErrors.code = "";
    },
    validateForgotEmail() {
      this.forgotErrors.email = "";

      if (!this.forgotForm.email) {
        this.forgotErrors.email = "Vui lòng nhập Gmail đã đăng ký.";
        return false;
      }

      if (!/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(this.forgotForm.email)) {
        this.forgotErrors.email = "Email chưa đúng định dạng.";
        return false;
      }

      return true;
    },
    validateResetPasswordForm() {
      let isValid = this.validateForgotEmail();

      this.forgotErrors.code = "";
      this.forgotErrors.password = "";
      this.forgotErrors.password_confirmation = "";

      if (this.forgotForm.code.length !== 6) {
        this.forgotErrors.code = "Vui lòng nhập đủ 6 chữ số trong Gmail.";
        isValid = false;
      }

      if (!this.forgotForm.password || this.forgotForm.password.length < 6) {
        this.forgotErrors.password = "Mật khẩu mới phải có ít nhất 6 ký tự.";
        isValid = false;
      }

      if (this.forgotForm.password_confirmation !== this.forgotForm.password) {
        this.forgotErrors.password_confirmation = "Xác nhận mật khẩu mới không khớp.";
        isValid = false;
      }

      return isValid;
    },
    async guiMaDatLaiMatKhau() {
      if (!this.validateForgotEmail()) {
        return;
      }

      this.forgotLoading = true;
      this.forgotError = "";
      this.forgotMessage = "";

      try {
        const response = await requestPasswordReset({
          email: this.forgotForm.email,
        });

        this.forgotStep = "reset";
        this.forgotForm.code = "";
        this.forgotMessage = response.message || "Mã đặt lại mật khẩu đã được gửi đến Gmail.";
      } catch (err) {
        this.forgotError = this.normalizeForgotError(err, "Không thể gửi mã đặt lại mật khẩu.");
      } finally {
        this.forgotLoading = false;
      }
    },
    async datLaiMatKhau() {
      this.sanitizeForgotCode();

      if (!this.validateResetPasswordForm()) {
        return;
      }

      this.forgotLoading = true;
      this.forgotError = "";
      this.forgotMessage = "";

      try {
        const response = await resetPasswordWithCode({
          email: this.forgotForm.email,
          token: this.forgotForm.code,
          password: this.forgotForm.password,
          password_confirmation: this.forgotForm.password_confirmation,
        });

        this.forgotMessage = response.message || "Đặt lại mật khẩu thành công.";
        this.message = "Đặt lại mật khẩu thành công. Bạn có thể đăng nhập bằng mật khẩu mới.";
        window.setTimeout(() => {
          this.showForgotPasswordModal = false;
        }, 900);
      } catch (err) {
        this.forgotError = this.normalizeForgotError(err, "Không thể đặt lại mật khẩu.");
      } finally {
        this.forgotLoading = false;
      }
    },
    async dangNhap() {
      this.loading = true;
      this.error = "";
      this.message = "";

      try {
        const response = await login({
          tai_khoan: this.tai_khoan,
          mat_khau: this.mat_khau,
        });

        setAuthSession({
          token: response.token,
          user: response.user,
          type: response.type,
        });

        this.message = response.message || "Đăng nhập thành công.";

        if (["admin", "staff", "nhan_vien", "nhanvien"].includes(response.type)) {
          this.$router.push("/thong-ke");
          return;
        }

        const redirectPath = Array.isArray(this.$route.query.redirect)
          ? this.$route.query.redirect[0]
          : this.$route.query.redirect;
        const safeRedirectPath =
          typeof redirectPath === "string" && redirectPath.startsWith("/") ? redirectPath : "/";

        this.$router.push(safeRedirectPath);
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

.auth-link-button {
  border: 0;
  padding: 0;
  background: transparent;
  color: #1652c5;
  font-weight: 700;
  text-align: left;
}

.auth-link-button:hover {
  color: #0d2b73;
  text-decoration: underline;
}

.field-error {
  margin-top: 6px;
  color: #dc3545;
  font-size: 0.92rem;
  font-weight: 500;
}

.password-field {
  position: relative;
}

.password-field__input {
  padding-right: 52px;
}

.password-field__toggle {
  position: absolute;
  top: 50%;
  right: 12px;
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: #64748b;
  padding: 0;
  transform: translateY(-50%);
}

.password-field__toggle:hover {
  background: #eef5ff;
  color: #1652c5;
}

.forgot-modal {
  position: fixed;
  inset: 0;
  z-index: 1080;
  display: grid;
  place-items: center;
  padding: 20px;
}

.forgot-modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.58);
  backdrop-filter: blur(5px);
}

.forgot-modal__panel {
  position: relative;
  z-index: 1;
  width: min(520px, 100%);
  max-height: min(720px, calc(100vh - 32px));
  overflow: auto;
  padding: 28px;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 24px;
  background: #fff;
  box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
}

.forgot-modal__close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 999px;
  background: #fff;
  color: #475569;
  padding: 0;
}

.forgot-modal__icon {
  width: 58px;
  height: 58px;
  display: grid;
  place-items: center;
  border-radius: 18px;
  background: #e8f0ff;
  color: #1652c5;
  font-size: 1.65rem;
}

.forgot-modal__eyebrow {
  margin: 18px 0 8px;
  color: #1652c5;
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.forgot-modal__title {
  margin: 0;
  color: #111827;
  font-size: 1.55rem;
  font-weight: 800;
}

.forgot-modal__copy {
  margin: 10px 0 0;
  color: #475569;
}

.forgot-form {
  display: grid;
  gap: 16px;
  margin-top: 20px;
}

.forgot-form__code {
  font-size: 1.45rem;
  font-weight: 800;
  letter-spacing: 0.28em;
  text-align: center;
}

.forgot-form__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.forgot-form__submit {
  flex: 1 1 220px;
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

  .forgot-modal__panel {
    padding: 22px;
    border-radius: 22px;
  }

  .forgot-form__actions > .btn {
    width: 100%;
  }
}
</style>
