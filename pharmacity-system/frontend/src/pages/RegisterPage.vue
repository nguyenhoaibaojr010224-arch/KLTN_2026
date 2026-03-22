<template>
  <section class="auth-card">
    <div class="auth-card__hero">
      <div class="soft-badge soft-badge--teal mb-3">
        <i class="bi bi-person-plus"></i>
        Tao tai khoan moi
      </div>
      <h1 class="auth-card__title">Dang ky khach hang de mua thuoc va theo doi nhu cau</h1>
      <p class="auth-card__copy">
        Khach hang co the tao tai khoan de bat dau trai nghiem mua thuoc. Tai khoan admin va nhan vien van duoc he
        thong cap rieng.
      </p>
    </div>

    <div class="auth-card__form">
      <div class="auth-switch mb-4">
        <button class="auth-switch__button active" type="button">Khach hang</button>
        <button class="auth-switch__button" type="button" disabled>Quan tri</button>
      </div>

      <div class="mb-4">
        <h2 class="h4 fw-bold mb-2">Dang ky khach hang</h2>
        <p class="text-secondary mb-0">
          Sau dang ky, he thong se tra token va link xac thuc email de FE tiep tuc mo rong sang ho so khach hang.
        </p>
      </div>

      <form class="row g-3" @submit.prevent="handleRegister">
        <div class="col-12">
          <label class="form-label fw-semibold">Ho va ten</label>
          <input v-model.trim="form.ten_khach_hang" class="form-control form-control-lg" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">So dien thoai</label>
          <input v-model.trim="form.so_dien_thoai" class="form-control form-control-lg" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Email</label>
          <input v-model.trim="form.email" class="form-control form-control-lg" />
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Dia chi</label>
          <input v-model.trim="form.dia_chi" class="form-control form-control-lg" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Mat khau</label>
          <input v-model="form.mat_khau" type="password" class="form-control form-control-lg" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Xac nhan mat khau</label>
          <input v-model="form.mat_khau_confirmation" type="password" class="form-control form-control-lg" />
        </div>
        <div class="col-12">
          <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            Dang ky tai khoan
          </button>
        </div>
      </form>

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
        <RouterLink to="/login" class="text-decoration-none fw-semibold text-primary">
          Da co tai khoan? Dang nhap ngay
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

  return err?.message || "Dang ky that bai.";
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

    message.value = response.message || "Dang ky thanh cong.";
    router.push("/");
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.value = false;
  }
}
</script>
