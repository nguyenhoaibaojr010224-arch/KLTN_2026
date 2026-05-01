<template>
  <section class="content-card hero-card">
    <div class="row align-items-center g-4">
      <div class="col-xl-7">
        <div class="hero-card__badge mb-3">
          <i class="bi bi-activity"></i>
          Thống kê doanh thu
        </div>
        <h2 class="hero-card__title mb-3">Theo dõi doanh thu và hiệu suất nhân viên</h2>
        <p class="hero-card__lead mb-4">
          Xem nhân viên nào đã đăng nhập, doanh thu bán hàng trong ngày và bảng xếp hạng doanh thu tháng.
        </p>

        <div class="d-flex flex-wrap gap-3">
          <span class="master-topstrip__pill">
            <i class="bi bi-person-badge"></i>
            Quyền hiện tại: {{ roleLabel }}
          </span>
        </div>
      </div>

      <div class="col-xl-5">
        <div class="master-panel p-4 bg-white bg-opacity-10 border border-white border-opacity-10">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
              <p class="small text-white-50 mb-1">Tài khoản đang sử dụng</p>
              <h3 class="display-6 fw-bold mb-0">{{ currentUserName }}</h3>
            </div>
            <span class="soft-badge bg-white text-primary">
              <i class="bi bi-shield-check"></i>
              Đã xác thực
            </span>
          </div>

          <div class="mb-2 d-flex justify-content-between small text-white-50">
            <span>Điều hướng hệ thống</span>
            <span>Sẵn sàng</span>
          </div>
          <div class="progress bg-white bg-opacity-25" style="height: 10px">
            <div class="progress-bar bg-info" style="width: 100%"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-xl-8">
      <article class="content-card staff-performance-card h-100">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 mb-4">
          <div>
            <h3 class="panel-title">Hiệu suất nhân viên hôm nay</h3>
            <p class="panel-subtitle">
              Theo dõi nhân viên đã đăng nhập, số hóa đơn và doanh thu bán hàng trong ngày đã chọn.
            </p>
          </div>
          <div class="staff-performance-filters">
            <input
              v-model="performanceDate"
              type="date"
              class="form-control form-control-sm"
              aria-label="Chọn ngày xem hiệu suất"
              @change="loadStaffPerformance"
            />
            <input
              v-model="performanceMonth"
              type="month"
              class="form-control form-control-sm"
              aria-label="Chọn tháng tổng hợp doanh thu"
              @change="loadStaffPerformance"
            />
          </div>
        </div>

        <div class="staff-performance-summary mb-4">
          <div>
            <span>Đăng nhập</span>
            <strong>{{ todaySummary.so_nhan_vien_dang_nhap }}</strong>
          </div>
          <div>
            <span>Hóa đơn</span>
            <strong>{{ todaySummary.tong_hoa_don }}</strong>
          </div>
          <div>
            <span>Doanh thu ngày</span>
            <strong>{{ formatCurrency(todaySummary.tong_doanh_thu) }}</strong>
          </div>
        </div>

        <div v-if="performanceLoading" class="staff-performance-empty">
          Đang tải dữ liệu hiệu suất...
        </div>
        <div v-else-if="performanceError" class="staff-performance-empty text-danger">
          {{ performanceError }}
        </div>
        <div v-else>
          <div v-if="todayPerformanceRows.length" class="staff-chart-list">
            <div v-for="row in todayPerformanceRows" :key="`today-${row.id_nhan_vien}`" class="staff-chart-row">
              <div class="staff-chart-row__head">
                <div>
                  <strong>{{ row.ho_ten }}</strong>
                  <span>{{ row.vai_tro || "Nhân viên" }} · {{ row.so_lan_dang_nhap }} lần đăng nhập</span>
                </div>
                <div class="text-end">
                  <strong>{{ formatCurrency(row.doanh_thu) }}</strong>
                  <span>{{ row.so_hoa_don }} hóa đơn</span>
                </div>
              </div>
              <div class="staff-chart-row__bar">
                <span :style="{ width: `${revenuePercent(row.doanh_thu, todayMaxRevenue)}%` }"></span>
              </div>
              <div class="staff-chart-row__foot">
                <span>Lần đăng nhập cuối: {{ formatDateTime(row.lan_dang_nhap_cuoi) }}</span>
                <span>@{{ row.ten_dang_nhap }}</span>
              </div>
            </div>
          </div>
          <div v-else class="staff-performance-empty">
            Chưa có nhân viên đăng nhập hoặc bán hàng trong ngày này.
          </div>
        </div>
      </article>
    </div>

    <div class="col-xl-4">
      <article class="content-card staff-performance-card h-100">
        <div class="mb-4">
          <h3 class="panel-title">Xếp hạng doanh thu tháng</h3>
          <p class="panel-subtitle">Tổng hợp nhân viên bán được nhiều doanh thu nhất trong tháng.</p>
        </div>

        <div v-if="monthlyLeader" class="staff-leader-card mb-4">
          <span class="soft-badge soft-badge--blue">
            <i class="bi bi-trophy"></i>
            Dẫn đầu tháng
          </span>
          <h4>{{ monthlyLeader.ho_ten }}</h4>
          <strong>{{ formatCurrency(monthlyLeader.doanh_thu) }}</strong>
          <p>{{ monthlyLeader.so_hoa_don }} hóa đơn · {{ monthlyLeader.so_ngay_dang_nhap }} ngày đăng nhập</p>
        </div>

        <div v-if="performanceLoading" class="staff-performance-empty">
          Đang tải bảng xếp hạng...
        </div>
        <div v-else-if="monthlyPerformanceRows.length">
          <div class="staff-ranking-list">
            <div
              v-for="(row, index) in visibleMonthlyPerformanceRows"
              :key="`month-${row.id_nhan_vien}`"
              class="staff-ranking-item"
            >
              <span class="staff-ranking-item__rank">{{ index + 1 }}</span>
              <div class="staff-ranking-item__body">
                <div class="d-flex justify-content-between gap-2">
                  <strong>{{ row.ho_ten }}</strong>
                  <span>{{ formatCurrency(row.doanh_thu) }}</span>
                </div>
                <div class="staff-ranking-item__bar">
                  <span :style="{ width: `${revenuePercent(row.doanh_thu, monthMaxRevenue)}%` }"></span>
                </div>
                <small>{{ row.so_hoa_don }} hóa đơn · {{ row.so_lan_dang_nhap }} lần đăng nhập</small>
              </div>
            </div>
          </div>

          <button
            v-if="hasMoreMonthlyRows"
            type="button"
            class="staff-ranking-more"
            @click="showAllMonthly = !showAllMonthly"
          >
            {{ showAllMonthly ? "Thu gọn" : `Xem tất cả ${monthlyPerformanceRows.length} nhân viên` }}
          </button>
        </div>
        <div v-else class="staff-performance-empty">
          Chưa có doanh thu trong tháng này.
        </div>
      </article>
    </div>
  </section>

  <section class="content-card weekly-revenue-chart">
    <div class="weekly-revenue-chart__head">
      <div>
        <span class="weekly-revenue-chart__eyebrow">Biểu đồ doanh thu</span>
        <h3>Doanh thu theo tuần</h3>
        <p>{{ formatShortDate(weeklySummary.tu_ngay) }} – {{ formatShortDate(weeklySummary.den_ngay) }}</p>
      </div>

      <!-- Điều hướng tuần + chọn tháng -->
      <div class="weekly-nav">
        <div class="weekly-nav__month">
          <label class="weekly-nav__label" for="weekMonthPicker">Xem tháng</label>
          <input
            id="weekMonthPicker"
            v-model="weekMonth"
            type="month"
            class="form-control form-control-sm"
            aria-label="Chọn tháng xem biểu đồ tuần"
            @change="onWeekMonthChange"
          />
        </div>
        <div class="weekly-nav__arrows">
          <button
            class="weekly-nav__btn"
            :disabled="weekLoading"
            title="Tuần trước"
            @click="shiftWeek(-1)"
          >
            <i class="bi bi-chevron-left"></i>
          </button>
          <span class="weekly-nav__label">
            {{ weekOffset === 0 ? 'Tuần này' : weekOffset < 0 ? `${Math.abs(weekOffset)} tuần trước` : `${weekOffset} tuần sau` }}
          </span>
          <button
            class="weekly-nav__btn"
            :disabled="weekLoading || weekOffset >= 0"
            title="Tuần sau"
            @click="shiftWeek(1)"
          >
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <div class="weekly-revenue-chart__total">
        <span>Tổng tuần</span>
        <strong>{{ formatCurrency(weeklySummary.tong_doanh_thu) }}</strong>
        <small>{{ weeklySummary.tong_hoa_don }} hóa đơn</small>
      </div>
    </div>

    <div v-if="weekLoading" class="staff-performance-empty">
      <span class="spinner-border spinner-border-sm me-2"></span>
      Đang tải biểu đồ doanh thu tuần...
    </div>
    <div v-else-if="performanceError" class="staff-performance-empty text-danger">
      {{ performanceError }}
    </div>
    <div v-else-if="weeklyRevenueRows.length" class="weekly-revenue-chart__canvas">
      <div class="weekly-revenue-chart__columns">
        <div v-for="day in weeklyRevenueRows" :key="day.ngay" class="weekly-revenue-chart__item">
          <div class="weekly-revenue-chart__value">{{ formatCurrency(day.doanh_thu) }}</div>
          <div class="weekly-revenue-chart__bar-area">
            <span
              class="weekly-revenue-chart__bar"
              :style="{ height: `${barHeight(day.doanh_thu, weekMaxRevenue)}%` }"
            ></span>
          </div>
          <strong>{{ day.thu }}</strong>
          <small>{{ formatShortDate(day.ngay) }}</small>
        </div>
      </div>
    </div>
    <div v-else class="staff-performance-empty">
      Chưa có dữ liệu doanh thu trong tuần này.
    </div>
  </section>

</template>

<script>
import { getStaffPerformanceChart } from '../../../api/dashboardApi';
import { authState } from '../../../lib/authStorage';

const currencyFormatter = new Intl.NumberFormat('vi-VN', {
  style: 'currency',
  currency: 'VND',
  maximumFractionDigits: 0,
});

function toDateInputValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

function toMonthInputValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');

  return `${year}-${month}`;
}

export default {
  name: 'DashboardAdmin',

  data() {
    const today = new Date();

    return {
      authState,
      performanceDate: toDateInputValue(today),
      performanceMonth: toMonthInputValue(today),
      performanceLoading: false,
      performanceError: '',
      staffPerformance: null,
      showAllMonthly: false,
      // --- Điều hướng biểu đồ tuần ---
      weekOffset: 0,          // 0 = tuần hiện tại, -1 = tuần trước, ...
      weekRefDate: today,     // ngày tham chiếu (Thứ 2 của tuần đang xem)
      weekMonth: toMonthInputValue(today), // tháng đang chọn để chuyển tuần
      weekLoading: false,
      weekData: null,         // dữ liệu tuần riêng (khi điều hướng)
    };
  },

  created() {
    this.loadStaffPerformance();
  },

  computed: {
    currentUserName() {
      return this.authState.user?.ho_ten || this.authState.user?.ten_khach_hang || 'Tài khoản hệ thống';
    },

    roleLabel() {
      if (this.authState.type === 'admin') {
        return 'Admin';
      }

      if (['staff', 'nhan_vien', 'nhanvien'].includes(this.authState.type)) {
        return 'Nhân viên';
      }

      return 'Tài khoản';
    },

    todaySummary() {
      return this.staffPerformance?.hom_nay || {
        tong_doanh_thu: 0,
        tong_hoa_don: 0,
        so_nhan_vien_dang_nhap: 0,
      };
    },

    todayPerformanceRows() {
      return this.staffPerformance?.hom_nay?.nhan_viens || [];
    },

    monthlyPerformanceRows() {
      return this.staffPerformance?.thang_nay?.nhan_viens || [];
    },

    visibleMonthlyPerformanceRows() {
      return this.showAllMonthly ? this.monthlyPerformanceRows : this.monthlyPerformanceRows.slice(0, 5);
    },

    hasMoreMonthlyRows() {
      return this.monthlyPerformanceRows.length > 5;
    },

    monthlyLeader() {
      return this.staffPerformance?.thang_nay?.nhan_vien_dan_dau || null;
    },

    todayMaxRevenue() {
      return Math.max(...this.todayPerformanceRows.map((row) => Number(row.doanh_thu || 0)), 0);
    },

    monthMaxRevenue() {
      return Math.max(...this.monthlyPerformanceRows.map((row) => Number(row.doanh_thu || 0)), 0);
    },

    weeklySummary() {
      // Nếu đang điều hướng → dùng weekData, ngược lại dùng dữ liệu mặc định từ API chính
      const base = this.weekData ?? this.staffPerformance?.tuan_nay;

      return base || {
        tu_ngay: '',
        den_ngay: '',
        tong_doanh_thu: 0,
        tong_hoa_don: 0,
        doanh_thu_theo_ngay: [],
      };
    },

    weeklyRevenueRows() {
      return this.weeklySummary.doanh_thu_theo_ngay || [];
    },

    weekMaxRevenue() {
      return Math.max(...this.weeklyRevenueRows.map((day) => Number(day.doanh_thu || 0)), 0);
    },
  },

  methods: {
    async loadStaffPerformance() {
      this.performanceLoading = true;
      this.performanceError = '';

      try {
        const response = await getStaffPerformanceChart({
          date: this.performanceDate,
          month: this.performanceMonth,
        });

        this.staffPerformance = response.data;
        this.showAllMonthly = false;
        // Reset về tuần hiện tại khi đổi ngày/tháng nhân viên
        this.weekOffset = 0;
        this.weekData = null;
        this.weekRefDate = new Date();
        this.weekMonth = toMonthInputValue(new Date());
      } catch (error) {
        this.performanceError = error?.data?.message || error?.message || 'Không tải được dữ liệu chart.';
      } finally {
        this.performanceLoading = false;
      }
    },

    // Chuyển tuần: delta = -1 (trước) hoặc +1 (sau)
    async shiftWeek(delta) {
      const newOffset = this.weekOffset + delta;
      // Không cho vượt quá tuần hiện tại
      if (newOffset > 0) return;

      this.weekOffset = newOffset;

      // Tính ngày tham chiếu = hôm nay + offset*7 ngày
      const ref = new Date();
      ref.setDate(ref.getDate() + newOffset * 7);
      this.weekRefDate = ref;
      this.weekMonth = toMonthInputValue(ref);

      if (newOffset === 0) {
        // Quay về tuần hiện tại → dùng data gốc
        this.weekData = null;
        return;
      }

      await this.loadWeekChart(ref);
    },

    // Khi chọn tháng → nhảy đến tuần đầu của tháng đó
    async onWeekMonthChange() {
      const [year, month] = this.weekMonth.split('-').map(Number);
      const firstDay = new Date(year, month - 1, 1);

      // Tính offset so với tuần hiện tại
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const diffMs = firstDay - today;
      const diffWeeks = Math.round(diffMs / (7 * 24 * 60 * 60 * 1000));

      this.weekOffset = Math.min(0, diffWeeks); // Không cho > tuần hiện tại
      this.weekRefDate = firstDay;

      if (this.weekOffset === 0) {
        this.weekData = null;
        this.weekMonth = toMonthInputValue(new Date());
        return;
      }

      await this.loadWeekChart(firstDay);
    },

    // Gọi API lấy dữ liệu tuần theo ngày tham chiếu
    async loadWeekChart(refDate) {
      this.weekLoading = true;
      this.performanceError = '';

      try {
        const response = await getStaffPerformanceChart({
          date: toDateInputValue(refDate),
          month: this.performanceMonth,
        });

        this.weekData = response.data?.tuan_nay || null;
      } catch (error) {
        this.performanceError = error?.data?.message || error?.message || 'Không tải được dữ liệu tuần.';
      } finally {
        this.weekLoading = false;
      }
    },

    formatCurrency(value) {
      return currencyFormatter.format(Number(value || 0));
    },

    formatDateTime(value) {
      if (!value) {
        return 'Chưa ghi nhận';
      }

      const date = new Date(String(value).replace(' ', 'T'));

      if (Number.isNaN(date.getTime())) {
        return 'Chưa ghi nhận';
      }

      return new Intl.DateTimeFormat('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit',
      }).format(date);
    },

    revenuePercent(value, maxValue) {
      const max = Number(maxValue || 0);

      if (max <= 0) {
        return 0;
      }

      return Math.max(6, Math.round((Number(value || 0) / max) * 100));
    },

    barHeight(value, maxValue) {
      const max = Number(maxValue || 0);
      const current = Number(value || 0);

      if (max <= 0 || current <= 0) {
        return 0;
      }

      return Math.max(10, Math.round((current / max) * 100));
    },

    formatShortDate(value) {
      if (!value) {
        return '--/--';
      }

      const date = new Date(String(value).replace(' ', 'T'));

      if (Number.isNaN(date.getTime())) {
        return '--/--';
      }

      return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
      }).format(date);
    },
  },
};
</script>

<style scoped>
/* ===== Điều hướng tuần trên biểu đồ ===== */
.weekly-revenue-chart__head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.weekly-nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-left: auto;
}

.weekly-nav__month {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.weekly-nav__label {
  font-size: 0.8rem;
  color: #6c757d;
  white-space: nowrap;
  font-weight: 500;
}

.weekly-nav__arrows {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: #f0f4ff;
  border-radius: 8px;
  padding: 0.25rem 0.5rem;
}

.weekly-nav__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: none;
  background: white;
  border-radius: 6px;
  color: #3b5bdb;
  font-size: 0.85rem;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: background 0.15s, box-shadow 0.15s;
}

.weekly-nav__btn:hover:not(:disabled) {
  background: #3b5bdb;
  color: white;
  box-shadow: 0 2px 6px rgba(59, 91, 219, 0.35);
}

.weekly-nav__btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.weekly-nav__label {
  min-width: 90px;
  text-align: center;
  font-size: 0.78rem;
  font-weight: 600;
  color: #3b5bdb;
}
</style>
