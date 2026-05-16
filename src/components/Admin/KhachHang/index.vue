<template>
  <div class="vstack gap-4">
    <section class="content-card">
      <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
        <div>
          <div class="soft-badge soft-badge--blue mb-3">
            <i class="bi bi-person-lines-fill"></i>
            Khách hàng
          </div>
          <h2 class="page-section-title mb-2">Quản lý khách hàng</h2>
          <p class="page-section-copy mb-0">Xem thông tin khách hàng và lịch sử đơn hàng đã mua.</p>
        </div>

        <button class="btn btn-primary align-self-start" type="button" @click="loadKhachHangs" :disabled="loading.list">
          <span v-if="loading.list" class="spinner-border spinner-border-sm me-2"></span>
          Tải danh sách
        </button>
      </div>
    </section>

    <section class="content-card">
      <div class="row g-3 align-items-end">
        <div class="col-lg-7">
          <label class="form-label fw-semibold">Tìm khách hàng</label>
          <input v-model.trim="keyword" class="form-control" placeholder="Nhập tên, số điện thoại, email hoặc địa chỉ" @keyup.enter="handleSearch" />
        </div>
        <div class="col-lg-5">
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary" type="button" @click="handleSearch" :disabled="loading.search || !keyword">
              <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
              Tìm kiếm
            </button>
            <button class="btn btn-outline-secondary" type="button" @click="resetSearch">Làm mới</button>
          </div>
        </div>
      </div>
    </section>

    <section class="row g-4">
      <div class="col-md-6 col-xl-3" v-for="metric in metrics" :key="metric.label">
        <article class="metric-card h-100">
          <div class="d-flex justify-content-between gap-3">
            <div>
              <p class="metric-card__label mb-2">{{ metric.label }}</p>
              <h3 class="metric-card__value mb-1">{{ metric.value }}</h3>
              <span class="metric-card__delta" :class="metric.deltaClass">{{ metric.note }}</span>
            </div>
            <div class="metric-card__icon" :class="metric.iconClass">
              <i :class="metric.icon"></i>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="row g-4">
      <div class="col-xl-5">
        <article class="content-card h-100">
          <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
            <div>
              <h3 class="panel-title mb-1">Danh sách khách hàng</h3>
              <p class="panel-subtitle mb-0">Chọn một khách hàng để xem lịch sử mua hàng.</p>
            </div>
            <span class="soft-badge soft-badge--blue">{{ khachHangs.length }} khách</span>
          </div>

          <div v-if="khachHangs.length" class="khach-hang-list">
            <button
              v-for="khachHang in khachHangs"
              :key="khachHang.id_khach_hang"
              type="button"
              class="khach-hang-row"
              :class="{ active: selectedId === khachHang.id_khach_hang }"
              @click="selectKhachHang(khachHang)"
            >
              <span class="khach-hang-row__avatar">{{ initials(khachHang.ten_khach_hang) }}</span>
              <span class="khach-hang-row__body">
                <strong>{{ khachHang.ten_khach_hang }}</strong>
                <small>{{ khachHang.so_dien_thoai || "-" }} - {{ khachHang.email || "-" }}</small>
              </span>
              <span class="khach-hang-row__meta">
                <strong>{{ khachHang.tong_so_don_hang || 0 }}</strong>
                <small>đơn</small>
              </span>
            </button>
          </div>

          <div v-else class="master-empty">
            <p class="mb-2 fw-semibold">Chưa có khách hàng để hiển thị.</p>
            <p class="mb-0 text-secondary">Bấm tải danh sách để lấy dữ liệu từ backend.</p>
          </div>
        </article>
      </div>

      <div class="col-xl-7">
        <article class="content-card h-100">
          <div v-if="selectedCustomer">
            <div class="khach-hang-profile">
              <div class="khach-hang-profile__avatar">{{ initials(selectedCustomer.ten_khach_hang) }}</div>
              <div class="min-w-0">
                <h3>{{ selectedCustomer.ten_khach_hang }}</h3>
                <p>{{ selectedCustomer.so_dien_thoai || "-" }} - {{ selectedCustomer.email || "-" }}</p>
                <p class="mb-0">{{ selectedCustomer.dia_chi || "Chưa cập nhật địa chỉ" }}</p>
              </div>
            </div>

            <div class="khach-hang-detail-grid">
              <div>
                <span>Tổng đơn hàng</span>
                <strong>{{ selectedCustomer.tong_so_don_hang || 0 }}</strong>
              </div>
              <div>
                <span>Tổng tiền đã mua</span>
                <strong>{{ formatCurrency(selectedCustomer.tong_tien_da_mua) }}</strong>
              </div>
              <div>
                <span>Điểm tích lũy</span>
                <strong>{{ selectedCustomer.diem_tich_luy || 0 }}</strong>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-3 mt-4 mb-3">
              <div>
                <h3 class="panel-title mb-1">Lịch sử đơn hàng</h3>
                <p class="panel-subtitle mb-0">Các đơn hàng khách hàng này đã mua.</p>
              </div>
              <span v-if="loading.detail" class="spinner-border spinner-border-sm text-primary"></span>
            </div>

            <div v-if="selectedCustomer.lich_su_don_hang?.length" class="khach-hang-order-list">
              <article v-for="order in selectedCustomer.lich_su_don_hang" :key="order.id_hoa_don" class="khach-hang-order">
                <div class="khach-hang-order__head">
                  <div>
                    <strong>{{ order.ma_hoa_don }}</strong>
                    <span>{{ formatDate(order.ngay_ban) }}</span>
                  </div>
                  <div class="text-end">
                    <strong>{{ formatCurrency(order.tien_thanh_toan) }}</strong>
                    <span>{{ order.trang_thai || "Thành công" }}</span>
                  </div>
                </div>

                <div class="khach-hang-order__items">
                  <div v-for="item in order.items" :key="item.id" class="khach-hang-order-item">
                    <div>
                      <strong>{{ item.ten_thuoc }}</strong>
                      <span>{{ item.so_luong }} {{ item.don_vi || "đơn vị" }} x {{ formatCurrency(item.gia_ban) }}</span>
                    </div>
                    <strong>{{ formatCurrency(item.thanh_tien) }}</strong>
                  </div>
                </div>
              </article>
            </div>

            <div v-else class="master-empty">
              <p class="mb-0 text-secondary">Khách hàng này chưa có đơn hàng.</p>
            </div>
          </div>

          <div v-else class="master-empty">
            <p class="mb-2 fw-semibold">Chọn khách hàng</p>
            <p class="mb-0 text-secondary">Thông tin chi tiết và lịch sử đơn hàng sẽ hiển thị tại đây.</p>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script>
import { getKhachHang, getKhachHangs, searchKhachHangs } from "../../../api/khachHangApi";
import { normalizeApiError } from "../../../lib/errorMessages";
import { showToast } from "../../../lib/toast";

export default {
  name: "KhachHangAdmin",

  data() {
    return {
      keyword: "",
      khachHangs: [],
      selectedId: null,
      selectedCustomer: null,
      loading: {
        list: false,
        search: false,
        detail: false,
      },
    };
  },

  computed: {
    metrics() {
      const totalCustomers = this.khachHangs.length;
      const totalOrders = this.khachHangs.reduce((sum, item) => sum + Number(item.tong_so_don_hang || 0), 0);
      const totalRevenue = this.khachHangs.reduce((sum, item) => sum + Number(item.tong_tien_da_mua || 0), 0);
      const verified = this.khachHangs.filter((item) => item.email_verified).length;

      return [
        { label: "Tổng khách hàng", value: totalCustomers, note: "Đang hiển thị", deltaClass: "is-positive", icon: "bi bi-people", iconClass: "metric-card__icon--blue" },
        { label: "Tổng đơn đã mua", value: totalOrders, note: "Theo danh sách khách", deltaClass: "is-positive", icon: "bi bi-bag-check", iconClass: "metric-card__icon--teal" },
        { label: "Tổng chi tiêu", value: this.formatCurrency(totalRevenue), note: "Đã thanh toán", deltaClass: "is-positive", icon: "bi bi-cash-coin", iconClass: "metric-card__icon--orange" },
        { label: "Email xác thực", value: verified, note: "Tài khoản đã xác minh", deltaClass: "is-positive", icon: "bi bi-envelope-check", iconClass: "metric-card__icon--red" },
      ];
    },
  },

  mounted() {
    const routeKeyword = String(this.$route.query.q || "").trim();
    if (routeKeyword) {
      this.keyword = routeKeyword;
      this.handleSearch(true);
      return;
    }

    this.loadKhachHangs();
  },

  watch: {
    "$route.query.q": {
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

        this.selectedId = null;
        this.selectedCustomer = null;
        this.loadKhachHangs();
      },
    },
  },

  methods: {
    normalizeError(error, fallback = "Không thể tải dữ liệu khách hàng.") {
      return normalizeApiError(error, fallback);
    },
    formatCurrency(value) {
      const n = Number(value || 0);
      if (Math.abs(n) >= 1e9) {
        const ty = n / 1e9;
        return (Math.abs(ty) >= 10 ? Math.round(ty) : ty.toFixed(2).replace('.', ',')) + ' tỷ đ';
      }
      return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 }).format(n);
    },
    formatDate(value) {
      if (!value) return "-";
      return new Intl.DateTimeFormat("vi-VN", { dateStyle: "short", timeStyle: "short" }).format(new Date(value));
    },
    initials(name) {
      return String(name || "KH").trim().split(/\s+/).slice(-2).map((part) => part.charAt(0).toUpperCase()).join("") || "KH";
    },
    async loadKhachHangs() {
      this.loading.list = true;

      try {
        const response = await getKhachHangs();
        this.khachHangs = Array.isArray(response?.data) ? response.data : [];
      } catch (error) {
        showToast(this.normalizeError(error), "error");
      } finally {
        this.loading.list = false;
      }
    },
    async handleSearch(silent = false) {
      if (!this.keyword) {
        await this.loadKhachHangs();
        return;
      }

      this.loading.search = true;

      try {
        const response = await searchKhachHangs(this.keyword);
        this.khachHangs = Array.isArray(response?.data) ? response.data : [];
        this.selectedId = null;
        this.selectedCustomer = null;
        if (!silent) {
          showToast(`Tìm thấy ${this.khachHangs.length} khách hàng phù hợp.`);
          this.$router.replace({
            path: "/khach-hangs",
            query: this.keyword ? { q: this.keyword } : {},
          });
        }
      } catch (error) {
        if (!silent) {
          showToast(this.normalizeError(error, "Không thể tìm kiếm khách hàng."), "error");
        }
      } finally {
        this.loading.search = false;
      }
    },
    async selectKhachHang(khachHang) {
      this.selectedId = khachHang.id_khach_hang;
      this.selectedCustomer = { ...khachHang, lich_su_don_hang: [] };
      this.loading.detail = true;

      try {
        const response = await getKhachHang(khachHang.id_khach_hang);
        this.selectedCustomer = response?.data || this.selectedCustomer;
      } catch (error) {
        showToast(this.normalizeError(error, "Không thể tải lịch sử đơn hàng."), "error");
      } finally {
        this.loading.detail = false;
      }
    },
    resetSearch() {
      this.keyword = "";
      this.selectedId = null;
      this.selectedCustomer = null;
      this.$router.replace({
        path: "/khach-hangs",
        query: {},
      });
      this.loadKhachHangs();
    },
  },
};
</script>

<style scoped>
.khach-hang-list,
.khach-hang-order-list {
  display: grid;
  gap: 12px;
}

.khach-hang-row {
  width: 100%;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  padding: 14px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 18px;
  background: #fff;
  color: #19335e;
  text-align: left;
}

.khach-hang-row.active {
  border-color: rgba(22, 82, 197, 0.34);
  background: #f1f6ff;
}

.khach-hang-row__avatar,
.khach-hang-profile__avatar {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 16px;
  background: linear-gradient(135deg, #1652c5, #14b8a6);
  color: #fff;
  font-weight: 900;
}

.khach-hang-row__body {
  min-width: 0;
}

.khach-hang-row__body strong,
.khach-hang-row__body small,
.khach-hang-row__meta strong,
.khach-hang-row__meta small,
.khach-hang-order__head strong,
.khach-hang-order__head span,
.khach-hang-order-item strong,
.khach-hang-order-item span {
  display: block;
}

.khach-hang-row__body strong {
  overflow: hidden;
  color: #19335e;
  font-weight: 900;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.khach-hang-row__body small,
.khach-hang-row__meta small,
.khach-hang-profile p,
.khach-hang-detail-grid span,
.khach-hang-order__head span,
.khach-hang-order-item span {
  color: #64748b;
  font-size: 0.86rem;
}

.khach-hang-row__meta {
  text-align: center;
}

.khach-hang-row__meta strong {
  color: #1652c5;
  font-size: 1.25rem;
  font-weight: 900;
}

.khach-hang-profile {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-bottom: 18px;
  border-bottom: 1px solid rgba(22, 82, 197, 0.1);
}

.khach-hang-profile__avatar {
  width: 64px;
  height: 64px;
  border-radius: 20px;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.khach-hang-profile h3 {
  margin: 0 0 4px;
  color: #19335e;
  font-weight: 900;
}

.khach-hang-detail-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 18px;
}

.khach-hang-detail-grid > div {
  padding: 14px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 18px;
  background: #f8fbff;
}

.khach-hang-detail-grid strong {
  display: block;
  margin-top: 6px;
  color: #19335e;
  font-size: 1.1rem;
  font-weight: 900;
}

.khach-hang-order {
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 20px;
  background: #fff;
  overflow: hidden;
}

.khach-hang-order__head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 16px;
  background: #f8fbff;
}

.khach-hang-order__head strong {
  color: #19335e;
  font-weight: 900;
}

.khach-hang-order__items {
  display: grid;
}

.khach-hang-order-item {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 12px 16px;
  border-top: 1px solid rgba(22, 82, 197, 0.08);
}

@media (max-width: 767.98px) {
  .khach-hang-detail-grid {
    grid-template-columns: 1fr;
  }

  .khach-hang-order__head,
  .khach-hang-order-item {
    flex-direction: column;
  }
}
</style>
