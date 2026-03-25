<template>
  <section class="register-shell">
    <div class="register-card">
      <div class="register-card__hero">
        <div class="soft-badge soft-badge--teal mb-3">
          <i class="bi bi-person-plus"></i>
          Tạo tài khoản mới
        </div>
        <h1 class="register-card__title">Tạo tài khoản khách hàng</h1>
        <p class="register-card__copy">
          Đăng ký để mua thuốc, lưu thông tin giao hàng và theo dõi ưu đãi nhanh hơn trên hệ thống.
        </p>
      </div>

      <div class="register-card__form">
        <div class="register-card__intro mb-4">
          <h2 class="h4 fw-bold mb-2">Thông tin đăng ký</h2>
          <p class="text-secondary mb-0">
            Điền đầy đủ thông tin bên dưới để tạo tài khoản mới.
          </p>
        </div>

        <form class="row g-3" @submit.prevent="handleRegister">
          <div class="col-12">
            <label class="form-label fw-semibold">Họ và tên</label>
            <input v-model.trim="form.ten_khach_hang" class="form-control form-control-lg" />
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Số điện thoại</label>
            <input v-model.trim="form.so_dien_thoai" class="form-control form-control-lg" />
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input v-model.trim="form.email" class="form-control form-control-lg" />
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Địa chỉ</label>
            <input v-model.trim="form.dia_chi" class="form-control form-control-lg" />
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Mật khẩu</label>
            <input v-model="form.mat_khau" type="password" class="form-control form-control-lg" />
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Xác nhận mật khẩu</label>
            <input v-model="form.mat_khau_confirmation" type="password" class="form-control form-control-lg" />
          </div>
          <div class="col-12 register-card__actions">
            <button class="btn btn-primary btn-lg register-card__submit" type="submit" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              Tạo tài khoản
            </button>
          </div>
        </form>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
          <RouterLink to="/login" class="text-decoration-none fw-semibold text-primary">
            Đã có tài khoản? Đăng nhập ngay
          </RouterLink>
          <RouterLink to="/" class="text-decoration-none text-secondary">Quay về trang chủ</RouterLink>
        </div>

        <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
        <div v-if="error" class="alert alert-danger mt-3 mb-0">{{ error }}</div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { register } from "../api/authApi";
import { setAuthSession } from "../lib/authStorage";

const router = useRouter();
const loading = ref(false);
const error = ref("");
const message = ref("");

const form = reactive({
  ten_khach_hang: "",
  so_dien_thoai: "",
  email: "",
  dia_chi: "",
  mat_khau: "",
  mat_khau_confirmation: "",
});

function normalizeError(err) {
  if (err?.payload?.errors) {
    return Object.values(err.payload.errors).flat().join(" | ");
  }

  return err?.message || "Đăng ký thất bại.";
}

async function handleRegister() {
  loading.value = true;
  error.value = "";
  message.value = "";

  try {
    const response = await register(form);

    setAuthSession({
      token: response.token,
      user: response.user,
      type: response.type,
    });

    message.value = response.message || "Đăng ký thành công.";
    router.push("/");
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.register-shell {
  width: min(1120px, 100%);
}

.register-card {
  padding: 28px;
  border: 1px solid rgba(22, 82, 197, 0.08);
  border-radius: 32px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(235, 250, 255, 0.96));
  box-shadow: 0 24px 60px rgba(17, 44, 99, 0.08);
}

.register-card__hero {
  max-width: 760px;
  margin-bottom: 28px;
}

.register-card__title {
  margin: 0;
  color: #1f2937;
  font-size: clamp(2.35rem, 4vw, 3.25rem);
  font-weight: 800;
  line-height: 1.08;
}

.register-card__copy {
  max-width: 62ch;
  margin: 14px 0 0;
  color: #475569;
  font-size: 1.1rem;
}

.register-card__form {
  padding: 28px;
  border: 1px solid rgba(22, 82, 197, 0.08);
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.76);
  backdrop-filter: blur(10px);
}

.register-card__intro {
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(22, 82, 197, 0.08);
}

.register-card__actions {
  padding-top: 8px;
}

.register-card__submit {
  min-width: 220px;
  border-radius: 18px;
}

.form-control {
  border-radius: 18px;
  border-color: rgba(148, 163, 184, 0.32);
  box-shadow: none;
}

.form-control:focus {
  border-color: rgba(22, 82, 197, 0.4);
  box-shadow: 0 0 0 0.22rem rgba(22, 82, 197, 0.12);
}

@media (max-width: 767.98px) {
  .register-card {
    padding: 20px;
    border-radius: 24px;
  }

  .register-card__form {
    padding: 20px;
    border-radius: 22px;
  }

  .register-card__submit {
    width: 100%;
  }
}
</style>
