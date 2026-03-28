<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--orange mb-3">
          <i class="bi bi-receipt-cutoff"></i>
          Sales module
        </div>
        <h2 class="page-section-title">Danh sách hóa đơn</h2>
        <p class="page-section-copy mb-0">
          Màn hình đầu tiên để frontend có thể xem doanh thu và danh sách hóa đơn từ backend.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" @click="loadAll" :disabled="loading.list || loading.stats">
          <span v-if="loading.list || loading.stats" class="spinner-border spinner-border-sm me-2"></span>
          Đồng bộ dữ liệu
        </button>
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-6">
        <label class="form-label fw-semibold">Tìm hóa đơn</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Mã hóa đơn, khách hàng, email, nhân viên"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-6">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tìm kiếm
          </button>
          <button class="btn btn-outline-secondary" @click="resetSearch">Xóa lọc</button>
        </div>
      </div>
    </div>

    <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
    <div v-if="error" class="alert alert-danger mt-4 mb-0">{{ error }}</div>
  </section>

  <section class="row g-4">
    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tổng hóa đơn</p>
        <h3 class="metric-card__value mb-3">{{ stats.tong_so_hoa_don }}</h3>
        <span class="metric-card__delta is-positive">Số đơn trong bộ lọc hiện tại</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tổng thanh toán</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.tong_tien_thanh_toan) }}</h3>
        <span class="metric-card__delta is-positive">Doanh thu thực nhận</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tổng giảm giá</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.tong_giam_gia) }}</h3>
        <span class="metric-card__delta is-warning">Giá trị ưu đãi</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Trung bình / đơn</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.gia_tri_trung_binh) }}</h3>
        <span class="metric-card__delta is-positive">Giá trị tham chiếu</span>
      </article>
    </div>
  </section>

  <section class="content-card mt-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h3 class="panel-title">Bảng hóa đơn</h3>
        <p class="panel-subtitle mb-0">Route đang dùng: <code>/api/admin/hoa-dons</code></p>
      </div>
      <span class="soft-badge soft-badge--blue">{{ hoaDons.length }} hóa đơn</span>
    </div>

    <div v-if="hoaDons.length" class="table-responsive">
      <table class="table table-master align-middle mb-0">
        <thead>
          <tr>
            <th>Mã hóa đơn</th>
            <th>Khách hàng</th>
            <th>Nhân viên</th>
            <th>Tổng tiền</th>
            <th>Giảm giá</th>
            <th>Thanh toán</th>
            <th>Ngày bán</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="hoaDon in hoaDons" :key="hoaDon.id_hoa_don">
            <td class="fw-semibold">{{ hoaDon.ma_hoa_don }}</td>
            <td>
              <div>{{ hoaDon.khach_hang?.ten_khach_hang || '-' }}</div>
              <div class="small text-secondary">{{ hoaDon.khach_hang?.so_dien_thoai || '-' }}</div>
            </td>
            <td>
              <div>{{ hoaDon.nhan_vien?.ho_ten || '-' }}</div>
              <div class="small text-secondary">{{ hoaDon.nhan_vien?.ten_dang_nhap || '-' }}</div>
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
      <p class="mb-2 fw-semibold">Chưa có hóa đơn để hiển thị.</p>
      <p class="mb-0 text-secondary">Đăng nhập bằng admin và bấm "Đồng bộ dữ liệu" để FE lấy danh sách.</p>
    </div>
  </section>
</template>

<script>
import { getHoaDons, getHoaDonStatistics, searchHoaDons } from '../../../api/hoaDonApi';

export default {
  name: 'HoaDonAdmin',

  data() {
    return {
      keyword: '',
      hoaDons: [],
      message: '',
      error: '',
      loading: {
        list: false,
        search: false,
        stats: false,
      },
      stats: {
        tong_so_hoa_don: 0,
        tong_tien: 0,
        tong_giam_gia: 0,
        tong_tien_thanh_toan: 0,
        gia_tri_trung_binh: 0,
      },
    };
  },

  mounted() {
    this.loadAll().catch((err) => {
      this.error = this.normalizeError(err);
    });
  },

  methods: {
    normalizeError(err) {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(' | ');
      }

      return err?.message || 'Không thể tải dữ liệu hóa đơn.';
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },

    formatDate(value) {
      if (!value) {
        return '-';
      }

      return new Intl.DateTimeFormat('vi-VN', {
        dateStyle: 'short',
        timeStyle: 'short',
      }).format(new Date(value));
    },

    async loadStatistics() {
      this.loading.stats = true;

      try {
        const response = await getHoaDonStatistics();
        Object.assign(this.stats, response.data?.tong_quan || {});
      } finally {
        this.loading.stats = false;
      }
    },

    async loadHoaDons() {
      this.loading.list = true;
      this.error = '';

      try {
        const response = await getHoaDons();
        this.hoaDons = response.data || [];
        this.message = `Đã tải ${this.hoaDons.length} hóa đơn từ backend.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.list = false;
      }
    },

    async loadAll() {
      await Promise.all([this.loadHoaDons(), this.loadStatistics()]);
    },

    async handleSearch() {
      if (!this.keyword) {
        return;
      }

      this.loading.search = true;
      this.error = '';

      try {
        const response = await searchHoaDons(this.keyword);
        this.hoaDons = response.data || [];
        this.message = `Tìm thấy ${this.hoaDons.length} hóa đơn phù hợp.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.search = false;
      }
    },

    resetSearch() {
      this.keyword = '';
      this.message = '';
      this.error = '';
      this.loadAll();
    },
  },
};
</script>
