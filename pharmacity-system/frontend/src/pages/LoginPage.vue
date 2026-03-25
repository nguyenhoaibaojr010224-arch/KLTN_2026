<template>
  <section class="auth-card">
    <div class="auth-card__hero">
      <h1 class="auth-card__title">Đăng Nhập Hệ Thống <p class="text-success">PHARMAGO</p>
      </h1>
      <p class="auth-card__copy">
        Đăng nhập bằng email, số điện thoại hoặc tên đăng nhập để tiếp tục mua sắm và quản lý đơn hàng. <b>PharmaGo</b>
        cam kết
        cung cấp sản phẩm chính hãng, thông tin rõ ràng và dịch vụ chăm sóc sức khỏe đáng tin cậy.
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

      <form class="vstack gap-3" @submit.prevent="handleLogin">
        <div>
          <label class="form-label fw-semibold">Tài Khoản</label>
          <input v-model.trim="form.tai_khoan" class="form-control form-control-lg"
            placeholder="Email, so dien thoai hoac ten dang nhap" />
        </div>

        <div>
          <label class="form-label fw-semibold">Mật Khẩu</label>
          <input v-model="form.mat_khau" type="password" class="form-control form-control-lg" placeholder="password" />
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

<script setup>
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { login } from "../api/authApi";
import { API_BASE_URL } from "../lib/apiClient";
import { setAuthSession } from "../lib/authStorage";

const router = useRouter();
const apiBaseUrl = API_BASE_URL;
const loading = ref(false);
const error = ref("");
const message = ref("");

const form = reactive({
  tai_khoan: "admin",
  mat_khau: "password",
});

function normalizeError(err) {
  if (err?.payload?.errors) {
    return Object.values(err.payload.errors).flat().join(" | ");
  }

  return err?.message || "Dang nhap that bai.";
}

async function handleLogin() {
  loading.value = true;
  error.value = "";
  message.value = "";

  try {
    const response = await login({
      tai_khoan: form.tai_khoan,
      mat_khau: form.mat_khau,
    });

    setAuthSession({
      token: response.token,
      user: response.user,
      type: response.type,
    });

    message.value = response.message || "Dang nhap thanh cong.";

    if (["admin", "staff", "nhan_vien", "nhanvien"].includes(response.type)) {
      router.push("/dashboard");
    } else {
      router.push("/");
    }
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.value = false;
  }
}
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

.auth-benefit__icon--blue {
  background: #e8f0ff;
  color: #1652c5;
}

.auth-benefit__icon--mint {
  background: #e8f0ff;
  color: #1652c5;
}

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
