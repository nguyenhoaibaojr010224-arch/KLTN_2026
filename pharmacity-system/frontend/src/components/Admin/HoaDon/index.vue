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
          Nhân viên xác nhận đơn hàng tại đây. Khi xác nhận xong, khách hàng sẽ thấy trạng thái thành công trong lịch sử đơn hàng.
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

    <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
    <div v-if="error" class="alert alert-danger mt-4 mb-0">{{ error }}</div>
  </section>

  <section class="row g-4">
    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tổng hóa đơn</p>
        <h3 class="metric-card__value mb-3">{{ stats.tong_so_hoa_don }}</h3>
        <span class="metric-card__delta is-positive">Số đơn có dữ liệu thật</span>
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
        <span class="metric-card__delta is-warning">Ưu đãi đã áp dụng</span>
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
        <p class="panel-subtitle mb-0">Route đang dùng: <code>/api/hoa-dons</code></p>
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
            <th>Trạng thái</th>
            <th>Tổng tiền</th>
            <th>Giảm giá</th>
            <th>Thanh toán</th>
            <th>Ngày bán</th>
            <th class="text-end">Thao tác</th>
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
            <td>
              <span class="soft-badge" :class="statusBadgeClass(currentStatus(hoaDon))">
                {{ currentStatus(hoaDon) }}
              </span>
            </td>
            <td>{{ formatCurrency(hoaDon.tong_tien) }}</td>
            <td>{{ formatCurrency(hoaDon.giam_gia) }}</td>
            <td>{{ formatCurrency(hoaDon.tien_thanh_toan) }}</td>
            <td>{{ formatDate(hoaDon.ngay_ban) }}</td>
            <td class="text-end">
              <button
                v-if="currentStatus(hoaDon) === 'Chờ xác nhận'"
                class="btn btn-sm btn-primary"
                type="button"
                :disabled="confirmingIds.includes(hoaDon.id_hoa_don)"
                @click="handleConfirm(hoaDon)"
              >
                <span
                  v-if="confirmingIds.includes(hoaDon.id_hoa_don)"
                  class="spinner-border spinner-border-sm me-2"
                ></span>
                Xác nhận
              </button>
              <span v-else class="small text-secondary">Đã xử lý</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="master-empty">
      <p class="mb-2 fw-semibold">Chưa có hóa đơn để hiển thị.</p>
      <p class="mb-0 text-secondary">Đăng nhập bằng nhân viên hoặc admin rồi đồng bộ lại dữ liệu.</p>
    </div>
  </section>
</template>

<script>
import { confirmHoaDon, getHoaDons, getHoaDonStatistics, searchHoaDons } from "../../../api/hoaDonApi";
import { showToast } from "../../../lib/toast";

export default {
  name: "HoaDonAdmin",

  data() {
    return {
      keyword: this.$route.query.q || "",
      hoaDons: [],
      confirmingIds: [],
      message: "",
      error: "",
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

  computed: {
    isReloading() {
      return this.loading.list || this.loading.stats;
    },
  },

  mounted() {
    if (this.keyword) {
      this.handleSearch();
      this.loadStatistics();
      return;
    }

    this.loadAll().catch((error) => {
      this.error = this.normalizeError(error);
    });
  },

  watch: {
    "$route.query.q": {
      immediate: false,
      handler(nextValue) {
        const nextKeyword = String(nextValue || "").trim();
        this.keyword = nextKeyword;

        if (nextKeyword) {
          this.handleSearch();
          return;
        }

        this.resetSearch();
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
      return hoaDon?.latest_lich_su_don_hang?.trang_thai || "Chờ xác nhận";
    },
    statusBadgeClass(status) {
      if (status === "Thành công") {
        return "soft-badge--teal";
      }

      if (status === "Chờ xác nhận") {
        return "soft-badge--orange";
      }

      return "soft-badge--blue";
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
      this.error = "";

      try {
        const response = await getHoaDons();
        this.hoaDons = Array.isArray(response.data) ? response.data : [];
        this.message = `Đã tải ${this.hoaDons.length} hóa đơn từ backend.`;
      } catch (error) {
        this.error = this.normalizeError(error);
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
      this.error = "";

      try {
        const response = await searchHoaDons(this.keyword);
        this.hoaDons = Array.isArray(response.data) ? response.data : [];
        this.message = `Tìm thấy ${this.hoaDons.length} hóa đơn phù hợp.`;
        this.$router.replace({
          path: "/hoa-dons",
          query: this.keyword ? { q: this.keyword } : {},
        });
      } catch (error) {
        this.error = this.normalizeError(error);
      } finally {
        this.loading.search = false;
      }
    },
    resetSearch() {
      this.keyword = "";
      this.message = "";
      this.error = "";
      this.$router.replace({
        path: "/hoa-dons",
        query: {},
      });
      this.loadAll();
    },
    async handleConfirm(hoaDon) {
      if (!hoaDon?.id_hoa_don) {
        return;
      }

      this.confirmingIds = [...this.confirmingIds, hoaDon.id_hoa_don];
      this.error = "";

      try {
        const response = await confirmHoaDon(hoaDon.id_hoa_don);
        const updatedHoaDon = response?.data || null;

        this.hoaDons = this.hoaDons.map((item) =>
          item.id_hoa_don === hoaDon.id_hoa_don ? { ...item, ...updatedHoaDon } : item
        );

        await this.loadStatistics();
        showToast(`Đã xác nhận hóa đơn ${hoaDon.ma_hoa_don}.`);
      } catch (error) {
        const message = this.normalizeError(error);
        this.error = message;
        showToast(message, "error");
      } finally {
        this.confirmingIds = this.confirmingIds.filter((id) => id !== hoaDon.id_hoa_don);
      }
    },
  },
};
</script>
