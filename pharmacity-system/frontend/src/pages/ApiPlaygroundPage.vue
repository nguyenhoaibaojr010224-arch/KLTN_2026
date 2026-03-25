<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--blue mb-3">
          <i class="bi bi-plug"></i>
          FE ket noi BE
        </div>
        <h2 class="page-section-title">Trang test API Laravel tu frontend Vue</h2>
        <p class="page-section-copy mb-0">
          Dung trang nay de dang nhap, luu token va goi API admin ngay trong FE.
        </p>
      </div>

      <div class="soft-badge">
        <i class="bi bi-hdd-network"></i>
        {{ apiBaseUrl }}
      </div>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-xl-4">
      <article class="content-card h-100">
        <div class="mb-4">
          <h3 class="panel-title">Dang nhap</h3>
          <p class="panel-subtitle">Dung tai khoan admin hoac staff de lay token Sanctum.</p>
        </div>

        <form class="vstack gap-3" @submit.prevent="handleLogin">
          <div>
            <label class="form-label fw-semibold">Ten dang nhap</label>
            <input v-model.trim="form.ten_dang_nhap" class="form-control" placeholder="admin" />
          </div>

          <div>
            <label class="form-label fw-semibold">Mat khau</label>
            <input v-model="form.mat_khau" type="password" class="form-control" placeholder="password" />
          </div>

          <button class="btn btn-primary" type="submit" :disabled="loading.login">
            <span v-if="loading.login" class="spinner-border spinner-border-sm me-2"></span>
            Dang nhap
          </button>
        </form>

        <div class="vstack gap-2 mt-4 small">
          <div><strong>Token:</strong> {{ tokenPreview }}</div>
          <div><strong>User:</strong> {{ currentUser?.ho_ten || "Chua dang nhap" }}</div>
          <div><strong>Role:</strong> {{ currentRole }}</div>
        </div>

        <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
        <div v-if="error" class="alert alert-danger mt-3 mb-0">{{ error }}</div>
      </article>
    </div>

    <div class="col-xl-8">
      <article class="content-card h-100">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
          <div>
            <h3 class="panel-title">Nhan vien tu API</h3>
            <p class="panel-subtitle mb-0">
              Goi <code>/api/admin/nhan-viens</code> bang token dang luu trong frontend.
            </p>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" @click="loadNhanViens" :disabled="loading.list">
              <span v-if="loading.list" class="spinner-border spinner-border-sm me-2"></span>
              Tai danh sach
            </button>
            <button class="btn btn-outline-secondary" @click="handleLogout">Xoa token</button>
          </div>
        </div>

        <div v-if="nhanViens.length" class="table-responsive">
          <table class="table table-master align-middle mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Ten dang nhap</th>
                <th>Ho ten</th>
                <th>Trang thai</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="nhanVien in nhanViens" :key="nhanVien.id_nhan_vien">
                <td>{{ nhanVien.id_nhan_vien }}</td>
                <td>{{ nhanVien.ten_dang_nhap }}</td>
                <td>{{ nhanVien.ho_ten }}</td>
                <td>
                  <span class="soft-badge" :class="nhanVien.trang_thai === 'active' ? 'soft-badge--teal' : 'soft-badge--orange'">
                    {{ nhanVien.trang_thai }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chua co du lieu hien thi.</p>
          <p class="mb-0 text-secondary">
            Dang nhap truoc, sau do bam "Tai danh sach" de frontend goi API backend.
          </p>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { login } from "../api/authApi";
import { getNhanViens } from "../api/nhanVienApi";
import { API_BASE_URL } from "../lib/apiClient";
import { clearAuthSession, getAccessToken, getStoredUser, setAuthSession } from "../lib/authStorage";

const form = reactive({
  ten_dang_nhap: "admin",
  mat_khau: "password",
});

const loading = reactive({
  login: false,
  list: false,
});

const message = ref("");
const error = ref("");
const nhanViens = ref([]);
const currentUser = ref(getStoredUser());

const apiBaseUrl = API_BASE_URL;

const tokenPreview = computed(() => {
  const token = getAccessToken();

  if (!token) {
    return "Chua co token";
  }

  return `${token.slice(0, 18)}...`;
});

const currentRole = computed(() => currentUser.value?.vai_tro?.ten_vai_tro || "Chua xac dinh");

function normalizeError(err) {
  if (err?.payload?.errors) {
    return Object.values(err.payload.errors).flat().join(" | ");
  }

  return err?.message || "Da xay ra loi.";
}

async function handleLogin() {
  loading.login = true;
  error.value = "";
  message.value = "";

  try {
    const response = await login({
      ten_dang_nhap: form.ten_dang_nhap,
      mat_khau: form.mat_khau,
    });

    setAuthSession({
      token: response.token,
      user: response.user,
    });

    currentUser.value = response.user;
    message.value = response.message || "Dang nhap thanh cong.";
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.login = false;
  }
}

async function loadNhanViens() {
  loading.list = true;
  error.value = "";

  try {
    const response = await getNhanViens();
    nhanViens.value = Array.isArray(response) ? response : response.data || [];
    message.value = `Da tai ${nhanViens.value.length} nhan vien tu backend.`;
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.list = false;
  }
}

function handleLogout() {
  clearAuthSession();
  currentUser.value = null;
  nhanViens.value = [];
  message.value = "Da xoa token khoi frontend.";
  error.value = "";
}

onMounted(() => {
  if (getAccessToken()) {
    message.value = "Frontend da phat hien token da luu trong localStorage.";
  }
});
</script>
