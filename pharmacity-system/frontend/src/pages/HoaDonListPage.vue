<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--orange mb-3">
          <i class="bi bi-receipt-cutoff"></i>
          Sales module
        </div>
        <h2 class="page-section-title">Danh sach hoa don</h2>
        <p class="page-section-copy mb-0">
          Man hinh dau tien de frontend co the xem doanh thu va danh sach hoa don tu backend.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" @click="loadAll" :disabled="loading.list || loading.stats">
          <span v-if="loading.list || loading.stats" class="spinner-border spinner-border-sm me-2"></span>
          Dong bo du lieu
        </button>
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-6">
        <label class="form-label fw-semibold">Tim hoa don</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Ma hoa don, khach hang, email, nhan vien"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-6">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tim kiem
          </button>
          <button class="btn btn-outline-secondary" @click="resetSearch">Xoa loc</button>
        </div>
      </div>
    </div>

    <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
    <div v-if="error" class="alert alert-danger mt-4 mb-0">{{ error }}</div>
  </section>

  <section class="row g-4">
    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tong hoa don</p>
        <h3 class="metric-card__value mb-3">{{ stats.tong_so_hoa_don }}</h3>
        <span class="metric-card__delta is-positive">So don trong bo loc hien tai</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tong thanh toan</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.tong_tien_thanh_toan) }}</h3>
        <span class="metric-card__delta is-positive">Doanh thu thuc nhan</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tong giam gia</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.tong_giam_gia) }}</h3>
        <span class="metric-card__delta is-warning">Gia tri uu dai</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Trung binh / don</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.gia_tri_trung_binh) }}</h3>
        <span class="metric-card__delta is-positive">Gia tri tham chieu</span>
      </article>
    </div>
  </section>

  <section class="content-card mt-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h3 class="panel-title">Bang hoa don</h3>
        <p class="panel-subtitle mb-0">Route dang dung: <code>/api/admin/hoa-dons</code></p>
      </div>
      <span class="soft-badge soft-badge--blue">{{ hoaDons.length }} hoa don</span>
    </div>

    <div v-if="hoaDons.length" class="table-responsive">
      <table class="table table-master align-middle mb-0">
        <thead>
          <tr>
            <th>Ma hoa don</th>
            <th>Khach hang</th>
            <th>Nhan vien</th>
            <th>Tong tien</th>
            <th>Giam gia</th>
            <th>Thanh toan</th>
            <th>Ngay ban</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="hoaDon in hoaDons" :key="hoaDon.id_hoa_don">
            <td class="fw-semibold">{{ hoaDon.ma_hoa_don }}</td>
            <td>
              <div>{{ hoaDon.khach_hang?.ten_khach_hang || "-" }}</div>
              <div class="small text-secondary">{{ hoaDon.khach_hang?.so_dien_thoai || "-" }}</div>
            </td>
            <td>
              <div>{{ hoaDon.nhan_vien?.ho_ten || "-" }}</div>
              <div class="small text-secondary">{{ hoaDon.nhan_vien?.ten_dang_nhap || "-" }}</div>
            </td>
            <td>{{ formatCurrency(hoaDon.tong_tien) }}</td>
            <td>{{ formatCurrency(hoaDon.giam_gia) }}</td>
            <td>{{ formatCurrency(hoaDon.tien_thanh_toan) }}</td>
            <td>{{ formatDate(hoaDon.ngay_ban) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="master-empty">
      <p class="mb-2 fw-semibold">Chua co hoa don de hien thi.</p>
      <p class="mb-0 text-secondary">Dang nhap bang admin va bam "Dong bo du lieu" de FE lay danh sach.</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { getHoaDons, getHoaDonStatistics, searchHoaDons } from "../api/hoaDonApi";

const keyword = ref("");
const hoaDons = ref([]);
const message = ref("");
const error = ref("");

const loading = reactive({
  list: false,
  search: false,
  stats: false,
});

const stats = reactive({
  tong_so_hoa_don: 0,
  tong_tien: 0,
  tong_giam_gia: 0,
  tong_tien_thanh_toan: 0,
  gia_tri_trung_binh: 0,
});

function normalizeError(err) {
  if (err?.payload?.errors) {
    return Object.values(err.payload.errors).flat().join(" | ");
  }

  return err?.message || "Khong the tai du lieu hoa don.";
}

function formatCurrency(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function formatDate(value) {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("vi-VN", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(new Date(value));
}

async function loadStatistics() {
  loading.stats = true;

  try {
    const response = await getHoaDonStatistics();
    Object.assign(stats, response.data?.tong_quan || {});
  } finally {
    loading.stats = false;
  }
}

async function loadHoaDons() {
  loading.list = true;
  error.value = "";

  try {
    const response = await getHoaDons();
    hoaDons.value = response.data || [];
    message.value = `Da tai ${hoaDons.value.length} hoa don tu backend.`;
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.list = false;
  }
}

async function loadAll() {
  await Promise.all([loadHoaDons(), loadStatistics()]);
}

async function handleSearch() {
  if (!keyword.value) {
    return;
  }

  loading.search = true;
  error.value = "";

  try {
    const response = await searchHoaDons(keyword.value);
    hoaDons.value = response.data || [];
    message.value = `Tim thay ${hoaDons.value.length} hoa don phu hop.`;
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.search = false;
  }
}

function resetSearch() {
  keyword.value = "";
  message.value = "";
  error.value = "";
  loadAll();
}

onMounted(() => {
  loadAll().catch((err) => {
    error.value = normalizeError(err);
  });
});
</script>
