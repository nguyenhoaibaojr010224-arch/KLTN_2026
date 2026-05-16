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

  <RevenueCharts />

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
            <DatePickerInput
              v-model="performanceDate"
              size="sm"
              placeholder="dd/mm/yyyy"
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

  <section class="rv-card rv-card--weekly">
    <div class="rv-card__head">
      <div>
        <em>Biểu đồ doanh thu</em>
        <h4>Doanh thu theo tuần</h4>
        <p class="rv-card__sub">{{ formatShortDate(weeklySummary.tu_ngay) }} – {{ formatShortDate(weeklySummary.den_ngay) }}</p>
      </div>

      <div class="weekly-nav">
        <div class="weekly-nav__month">
          <label class="weekly-nav__label" for="weekMonthPicker">Xem tháng</label>
          <input id="weekMonthPicker" v-model="weekMonth" type="month" class="form-control form-control-sm" @change="onWeekMonthChange"/>
        </div>
        <div class="weekly-nav__arrows">
          <button class="weekly-nav__btn" :disabled="weekLoading" title="Tuần trước" @click="shiftWeek(-1)"><i class="bi bi-chevron-left"></i></button>
          <span class="weekly-nav__label">{{ weekOffset === 0 ? 'Tuần này' : weekOffset < 0 ? `${Math.abs(weekOffset)} tuần trước` : `${weekOffset} tuần sau` }}</span>
          <button class="weekly-nav__btn" :disabled="weekLoading || weekOffset >= 0" title="Tuần sau" @click="shiftWeek(1)"><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>

      <div class="rv-card__stat">
        <span>Tổng tuần</span>
        <strong>{{ formatCurrency(weeklySummary.tong_doanh_thu) }}</strong>
        <small>{{ weeklySummary.tong_hoa_don }} hóa đơn</small>
      </div>
    </div>

    <div v-if="weekLoading" class="staff-performance-empty">
      <span class="spinner-border spinner-border-sm me-2"></span>Đang tải...
    </div>
    <div v-else-if="performanceError" class="staff-performance-empty text-danger">{{ performanceError }}</div>
    <div v-else-if="weeklyRevenueRows.length" class="rv-bars">
      <div v-for="day in weeklyRevenueRows" :key="day.ngay" class="rv-bars__col">
        <div class="rv-bars__val">{{ formatCurrency(day.doanh_thu) }}</div>
        <div class="rv-bars__track"><span class="rv-bars__fill rv-bars__fill--gradient" :style="{ height: `${barHeight(day.doanh_thu, weekMaxRevenue)}%` }"></span></div>
        <strong>{{ day.thu }}</strong>
        <small>{{ formatShortDate(day.ngay) }}</small>
      </div>
    </div>
    <div v-else class="staff-performance-empty">Chưa có dữ liệu doanh thu trong tuần này.</div>
  </section>

</template>

<script>
import { getStaffPerformanceChart } from '../../../api/dashboardApi';
import DatePickerInput from '../../Common/DatePickerInput.vue';
import RevenueCharts from './RevenueCharts.vue';
import { authState } from '../../../lib/authStorage';
import { formatDateInputValue, parseDateInputValue } from '../../../lib/dateInput';
import { normalizeApiError } from '../../../lib/errorMessages';

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
  components: {
    DatePickerInput,
    RevenueCharts,
  },

  data() {
    const today = new Date();

    return {
      authState,
      performanceDate: formatDateInputValue(today),
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
      const selectedDate = parseDateInputValue(this.performanceDate);

      if (this.performanceDate && !selectedDate) {
        this.performanceError = 'Ngày hiệu suất phải theo định dạng dd/mm/yyyy.';
        return;
      }

      this.performanceLoading = true;
      this.performanceError = '';

      try {
        const response = await getStaffPerformanceChart({
          date: selectedDate || toDateInputValue(new Date()),
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
        this.performanceError = normalizeApiError(error, 'Không tải được dữ liệu biểu đồ.');
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
        this.performanceError = normalizeApiError(error, 'Không tải được dữ liệu tuần.');
      } finally {
        this.weekLoading = false;
      }
    },

    formatCurrency(value) {
      const n = Number(value || 0);
      if (Math.abs(n) >= 1e9) {
        const ty = n / 1e9;
        return (Math.abs(ty) >= 10 ? Math.round(ty) : ty.toFixed(2).replace('.', ',')) + ' tỷ đ';
      }
      return currencyFormatter.format(n);
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
/* ===== Unified rv-card & rv-bars (matches RevenueCharts.vue) ===== */
.rv-card{border:1px solid rgba(22,82,197,.06);border-radius:22px;padding:24px;background:#fff;box-shadow:0 2px 12px rgba(15,31,79,.04)}
.rv-card--weekly{background:radial-gradient(circle at top right,rgba(20,184,166,.08),transparent 34%),#fff}
.rv-card__head{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:20px}
.rv-card__head em{display:block;font-style:normal;color:#1652c5;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:3px}
.rv-card__head h4{margin:0;color:#0f172a;font-size:1.1rem;font-weight:800}
.rv-card__sub{margin:4px 0 0;color:#64748b;font-size:.84rem;font-weight:700}
.rv-card__stat span{display:block;color:#64748b;font-size:.8rem;font-weight:600;text-align:right}
.rv-card__stat strong{display:block;color:#059669;font-size:1.1rem;font-weight:900}
.rv-card__stat small{display:block;margin-top:2px;color:#64748b;font-size:.8rem;font-weight:700;text-align:right}

.rv-bars{display:flex;gap:14px;align-items:flex-end;overflow-x:auto;padding:4px 0}
.rv-bars__col{flex:1;min-width:56px;text-align:center}
.rv-bars__val{color:#0f172a;font-size:.68rem;font-weight:800;margin-bottom:6px;line-height:1.2}
.rv-bars__track{height:200px;display:flex;align-items:flex-end;justify-content:center}
.rv-bars__fill{width:42px;min-height:4px;border-radius:8px 8px 3px 3px;transition:height .6s cubic-bezier(.4,0,.2,1)}
.rv-bars__fill--gradient{background:linear-gradient(180deg,#60a5fa 0%,#1652c5 100%);box-shadow:0 6px 20px rgba(59,130,246,.3)}
.rv-bars__col:hover .rv-bars__fill--gradient{background:linear-gradient(180deg,#93c5fd 0%,#2563eb 100%);box-shadow:0 8px 28px rgba(59,130,246,.4)}
.rv-bars__col strong{display:block;margin-top:8px;color:#0f172a;font-size:.78rem;font-weight:800}
.rv-bars__col small{color:#94a3b8;font-size:.7rem;font-weight:600}

/* ===== Week navigation ===== */
.weekly-nav{display:flex;flex-wrap:wrap;align-items:center;gap:.75rem;margin-left:auto}
.weekly-nav__month{display:flex;align-items:center;gap:.5rem}
.weekly-nav__label{font-size:.78rem;font-weight:600;color:#3b5bdb;white-space:nowrap;min-width:90px;text-align:center}
.weekly-nav__arrows{display:flex;align-items:center;gap:.35rem;background:#f0f4ff;border-radius:8px;padding:.25rem .5rem}
.weekly-nav__btn{display:flex;align-items:center;justify-content:center;width:30px;height:30px;border:none;background:#fff;border-radius:6px;color:#3b5bdb;font-size:.85rem;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.1);transition:background .15s,box-shadow .15s}
.weekly-nav__btn:hover:not(:disabled){background:#3b5bdb;color:#fff;box-shadow:0 2px 6px rgba(59,91,219,.35)}
.weekly-nav__btn:disabled{opacity:.35;cursor:not-allowed}
</style>
