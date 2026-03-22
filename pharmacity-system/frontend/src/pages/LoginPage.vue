<template>
  <section class="auth-card">
    <div class="auth-card__hero">
      <div class="soft-badge soft-badge--blue mb-3">
        <i class="bi bi-shield-lock"></i>
        Pharmacity Access
      </div>
      <h1 class="auth-card__title">Dang nhap cho khach hang va quan tri</h1>
      <p class="auth-card__copy">
        Khach hang dang nhap de theo doi mua thuoc sau nay, con admin va nhan vien van bat dau tu homepage nhung co
        them menu quan ly he thong trong ho so.
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
      <div class="auth-switch mb-4">
        <button
          class="auth-switch__button"
          :class="{ active: activeTab === 'customer' }"
          type="button"
          @click="activeTab = 'customer'"
        >
          Khach hang
        </button>
        <button
          class="auth-switch__button"
          :class="{ active: activeTab === 'admin' }"
          type="button"
          @click="activeTab = 'admin'"
        >
          Quan tri
        </button>
      </div>

      <div class="mb-4">
        <h2 class="h4 fw-bold mb-2">{{ activeTab === "customer" ? "Dang nhap khach hang" : "Dang nhap admin / nhan vien" }}</h2>
        <p class="text-secondary mb-0">
          {{ activeTab === "customer" ? "Nhap email hoac so dien thoai da dang ky." : "Nhap ten dang nhap he thong." }}
        </p>
      </div>

      <form class="vstack gap-3" @submit.prevent="handleLogin">
        <div v-if="activeTab === 'customer'">
          <label class="form-label fw-semibold">Email hoac so dien thoai</label>
          <input v-model.trim="customerForm.tai_khoan" class="form-control form-control-lg" placeholder="email@example.com" />
        </div>

        <div v-else>
          <label class="form-label fw-semibold">Ten dang nhap</label>
          <input v-model.trim="adminForm.ten_dang_nhap" class="form-control form-control-lg" placeholder="admin" />
        </div>

        <div>
          <label class="form-label fw-semibold">Mat khau</label>
          <input
            v-model="activePassword"
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
import { computed, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { login } from "../api/authApi";
import { API_BASE_URL } from "../lib/apiClient";
import { setAuthSession } from "../lib/authStorage";

const router = useRouter();
const apiBaseUrl = API_BASE_URL;
const loading = ref(false);
const error = ref("");
const message = ref("");
const activeTab = ref("customer");

const adminForm = reactive({
  ten_dang_nhap: "admin",
  mat_khau: "password",
});

const customerForm = reactive({
  tai_khoan: "",
  mat_khau: "",
});

const activePassword = computed({
  get() {
    return activeTab.value === "customer" ? customerForm.mat_khau : adminForm.mat_khau;
  },
  set(value) {
    if (activeTab.value === "customer") {
      customerForm.mat_khau = value;
    } else {
      adminForm.mat_khau = value;
    }
  },
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
    const payload =
      activeTab.value === "customer"
        ? {
            tai_khoan: customerForm.tai_khoan,
            mat_khau: customerForm.mat_khau,
          }
        : {
            ten_dang_nhap: adminForm.ten_dang_nhap,
            mat_khau: adminForm.mat_khau,
          };

    const response = await login(payload);

    setAuthSession({
      token: response.token,
      user: response.user,
      type: response.type,
    });

    message.value = response.message || "Dang nhap thanh cong.";
    router.push("/");
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.value = false;
  }
}
</script>
