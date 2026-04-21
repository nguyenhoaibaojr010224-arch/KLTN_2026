<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--orange mb-3">
          <i class="bi bi-receipt-cutoff"></i>
          Hóa đơn
        </div>
        <h2 class="page-section-title">Quản lý hóa đơn</h2>
        <p class="page-section-copy mb-0">
          Đơn hàng được tạo tự động ngay khi khách đặt thành công. Màn này dùng để theo dõi hóa đơn thật,
          VAT và giảm giá đã áp dụng.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" @click="loadAll" :disabled="isReloading">
          <span v-if="isReloading" class="spinner-border spinner-border-sm me-2"></span>
          Đồng bộ dữ liệu
        </button>
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-7">
        <label class="form-label fw-semibold">Tìm hóa đơn</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Mã hóa đơn, khách hàng, số điện thoại, nhân viên"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-5">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tìm kiếm
          </button>
          <button class="btn btn-outline-secondary" @click="resetSearch">Xóa lọc</button>
        </div>
      </div>
    </div>

  </section>

  <section class="row g-4">
    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tổng hóa đơn</p>
        <h3 class="metric-card__value mb-3">{{ stats.tong_so_hoa_don }}</h3>
        <span class="metric-card__delta is-positive">Dữ liệu thật từ checkout</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tổng thanh toán</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.tong_tien_thanh_toan) }}</h3>
        <span class="metric-card__delta is-positive">Đã gồm VAT</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tổng giảm giá</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.tong_giam_gia) }}</h3>
        <span class="metric-card__delta is-warning">Mã giảm giá đã áp dụng</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">VAT đã thu</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.tong_thue_vat) }}</h3>
        <span class="metric-card__delta is-positive">10% trên giá trị sau giảm</span>
      </article>
    </div>
  </section>

  <section class="content-card mt-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h3 class="panel-title">Bảng hóa đơn</h3>
        <p class="panel-subtitle mb-0">
          Route đang dùng: <code>/api/hoa-dons</code> · Tự động cập nhật mỗi 5 giây
        </p>
      </div>
      <span class="soft-badge soft-badge--blue">{{ hoaDons.length }} hóa đơn</span>
    </div>

    <div v-if="hoaDons.length">
      <div class="table-responsive">
        <table class="table table-master align-middle mb-0">
          <thead>
            <tr>
              <th>Mã hóa đơn</th>
              <th>Khách hàng</th>
              <th>Nhân viên</th>
              <th>Trạng thái</th>
              <th>Tổng tiền</th>
              <th>Giảm giá</th>
              <th>VAT</th>
              <th>Thanh toán</th>
              <th>Ngày bán</th>
              <th class="text-end">Xử lý</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="hoaDon in visibleHoaDons" :key="hoaDon.id_hoa_don">
              <td class="fw-semibold">{{ hoaDon.ma_hoa_don }}</td>
              <td>
                <div>{{ hoaDon.khach_hang?.ten_khach_hang || "-" }}</div>
                <div class="small text-secondary">{{ hoaDon.khach_hang?.so_dien_thoai || "-" }}</div>
              </td>
              <td>
                <div>{{ hoaDon.nhan_vien?.ho_ten || "-" }}</div>
                <div class="small text-secondary">{{ hoaDon.nhan_vien?.ten_dang_nhap || "-" }}</div>
              </td>
              <td>
                <span class="soft-badge" :class="statusBadgeClass(currentStatus(hoaDon))">
                  {{ currentStatus(hoaDon) }}
                </span>
              </td>
              <td>{{ formatCurrency(hoaDon.tong_tien) }}</td>
              <td>{{ formatCurrency(hoaDon.giam_gia) }}</td>
              <td>{{ formatCurrency(hoaDon.thue_vat) }}</td>
              <td>{{ formatCurrency(hoaDon.tien_thanh_toan) }}</td>
              <td>{{ formatDate(hoaDon.ngay_ban) }}</td>
              <td class="text-end">
                <span class="small text-secondary">Tự động</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="canLoadMoreHoaDons" class="d-flex justify-content-center mt-4 pt-4 border-top">
        <button class="btn btn-outline-primary px-4" @click="showMoreHoaDons">Xem thêm</button>
      </div>
    </div>

    <div v-else class="master-empty">
      <p class="mb-2 fw-semibold">Chưa có hóa đơn để hiển thị.</p>
      <p class="mb-0 text-secondary">
        Khi khách đặt hàng thành công, hóa đơn sẽ xuất hiện tại đây mà không cần tải lại trang.
      </p>
    </div>
  </section>
</template>

<script>
import { getHoaDons, getHoaDonStatistics, searchHoaDons } from "../../../api/hoaDonApi";
import { showToast } from "../../../lib/toast";

const HOA_DON_TABLE_BATCH_SIZE = 15;

export default {
  name: "HoaDonAdmin",

  data() {
    return {
      keyword: this.$route.query.q || "",
      hoaDons: [],
      visibleHoaDonCount: HOA_DON_TABLE_BATCH_SIZE,
      refreshTimer: null,
      loading: {
        list: false,
        search: false,
        stats: false,
      },
      stats: {
        tong_so_hoa_don: 0,
        tong_tien: 0,
        tong_giam_gia: 0,
        tong_thue_vat: 0,
        tong_tien_thanh_toan: 0,
        gia_tri_trung_binh: 0,
      },
    };
  },

  computed: {
    isReloading() {
      return this.loading.list || this.loading.stats;
    },
    visibleHoaDons() {
      return this.hoaDons.slice(0, this.visibleHoaDonCount);
    },
    canLoadMoreHoaDons() {
      return this.hoaDons.length > this.visibleHoaDonCount;
    },
  },

  mounted() {
    if (this.keyword) {
      this.handleSearch();
      this.loadStatistics();
      this.startAutoRefresh();
      return;
    }

    this.loadAll();
    this.startAutoRefresh();
  },

  beforeUnmount() {
    this.stopAutoRefresh();
  },

  watch: {
    "$route.query.q": {
      immediate: false,
      handler(nextValue) {
        const nextKeyword = String(nextValue || "").trim();

        if (nextKeyword === this.keyword) {
          return;
        }

        this.keyword = nextKeyword;

        if (nextKeyword) {
          this.handleSearch(true);
          return;
        }

        this.loadAll();
      },
    },
  },

  methods: {
    normalizeError(error) {
      if (error?.payload?.errors) {
        return Object.values(error.payload.errors).flat().join(" | ");
      }

      return error?.payload?.message || error?.message || "Không thể tải dữ liệu hóa đơn.";
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    formatDate(value) {
      if (!value) {
        return "-";
      }

      return new Intl.DateTimeFormat("vi-VN", {
        dateStyle: "short",
        timeStyle: "short",
      }).format(new Date(value));
    },
    currentStatus(hoaDon) {
      return hoaDon?.latest_lich_su_don_hang?.trang_thai || "Thành công";
    },
    statusBadgeClass(status) {
      if (status === "Thành công") {
        return "soft-badge--teal";
      }

      if (status === "Đã hủy") {
        return "soft-badge--orange";
      }

      return "soft-badge--blue";
    },
    applyHoaDonResponse(response) {
      this.hoaDons = Array.isArray(response?.data) ? response.data : [];
    },
    applyStatisticsResponse(response) {
      Object.assign(this.stats, response?.data?.tong_quan || {});
    },
    resetHoaDonPagination() {
      this.visibleHoaDonCount = HOA_DON_TABLE_BATCH_SIZE;
    },
    showMoreHoaDons() {
      this.visibleHoaDonCount += HOA_DON_TABLE_BATCH_SIZE;
    },
    async loadStatistics(silent = false) {
      if (!silent) {
        this.loading.stats = true;
      }

      try {
        const response = await getHoaDonStatistics();
        this.applyStatisticsResponse(response);
      } catch (error) {
        if (!silent) {
          showToast(this.normalizeError(error), "error");
        }
      } finally {
        if (!silent) {
          this.loading.stats = false;
        }
      }
    },
    async loadHoaDons(silent = false) {
      if (!silent) {
        this.loading.list = true;
      }

      try {
        const response = await getHoaDons();
        this.applyHoaDonResponse(response);

        if (!silent) {
          this.resetHoaDonPagination();
          showToast(`Đã tải ${this.hoaDons.length} hóa đơn từ backend.`);
        }
      } catch (error) {
        if (!silent) {
          showToast(this.normalizeError(error), "error");
        }
      } finally {
        if (!silent) {
          this.loading.list = false;
        }
      }
    },
    async loadAll() {
      await Promise.all([this.loadHoaDons(), this.loadStatistics()]);
    },
    async handleSearch(silent = false) {
      if (!this.keyword) {
        return;
      }

      if (!silent) {
        this.loading.search = true;
      }

      try {
        const response = await searchHoaDons(this.keyword);
        this.applyHoaDonResponse(response);

        if (!silent) {
          this.resetHoaDonPagination();
          showToast(`Tìm thấy ${this.hoaDons.length} hóa đơn phù hợp.`);
          this.$router.replace({
            path: "/hoa-dons",
            query: this.keyword ? { q: this.keyword } : {},
          });
        }
      } catch (error) {
        if (!silent) {
          showToast(this.normalizeError(error), "error");
        }
      } finally {
        if (!silent) {
          this.loading.search = false;
        }
      }
    },
    async refreshCurrentView() {
      if (this.loading.list || this.loading.search || this.loading.stats) {
        return;
      }

      if (this.keyword) {
        await Promise.all([this.handleSearch(true), this.loadStatistics(true)]);
        return;
      }

      await Promise.all([this.loadHoaDons(true), this.loadStatistics(true)]);
    },
    startAutoRefresh() {
      this.stopAutoRefresh();
      this.refreshTimer = window.setInterval(() => {
        this.refreshCurrentView().catch(() => {});
      }, 5000);
    },
    stopAutoRefresh() {
      if (!this.refreshTimer) {
        return;
      }

      window.clearInterval(this.refreshTimer);
      this.refreshTimer = null;
    },
    resetSearch() {
      this.keyword = "";
      this.$router.replace({
        path: "/hoa-dons",
        query: {},
      });
      this.loadAll();
    },
  },
};
</script>
