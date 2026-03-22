<template>
  <section class="auth-card">
    <div class="auth-card__hero">
      <div class="soft-badge soft-badge--blue mb-3">
        <i class="bi bi-shield-lock"></i>
        Pharmacity Access
      </div>
      <h1 class="auth-card__title">Dang nhap he thong Pharmacity</h1>
      <p class="auth-card__copy">
        Su dung email, so dien thoai hoac ten dang nhap. Neu dang nhap bang tai khoan admin hoac nhan vien, giao dien
        se tu dong hien menu he thong va mo duoc dashboard quan tri.
      </p>

      <div class="d-flex flex-wrap gap-2 mt-4">
        <span class="master-topstrip__pill">
          <i class="bi bi-hdd-network"></i>
          {{ apiBaseUrl }}
        </span>
        <span class="master-topstrip__pill">
          <i class="bi bi-person-workspace"></i>
          Admin mau: admin / password
        </span>
      </div>
    </div>

    <div class="auth-card__form">
      <div class="mb-4">
        <h2 class="h4 fw-bold mb-2">Dang nhap</h2>
        <p class="text-secondary mb-0">
          Nhap tai khoan va mat khau. He thong se tu nhan biet ban la khach hang hay quan tri.
        </p>
      </div>

      <form class="vstack gap-3" @submit.prevent="handleLogin">
        <div>
          <label class="form-label fw-semibold">Tai khoan</label>
          <input
            v-model.trim="form.tai_khoan"
            class="form-control form-control-lg"
            placeholder="Email, so dien thoai hoac ten dang nhap"
          />
        </div>

        <div>
          <label class="form-label fw-semibold">Mat khau</label>
          <input
            v-model="form.mat_khau"
            type="password"
            class="form-control form-control-lg"
            placeholder="password"
          />
        </div>

        <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          Dang nhap
        </button>
      </form>

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
        <RouterLink to="/register" class="text-decoration-none fw-semibold text-primary">
          Chua co tai khoan? Dang ky khach hang
        </RouterLink>
        <RouterLink to="/" class="text-decoration-none text-secondary">Quay ve trang chu</RouterLink>
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
