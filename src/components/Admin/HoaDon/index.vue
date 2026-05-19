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
          Đơn online cần nhân viên hệ thống xác nhận trước khi tính doanh thu. Đơn tại quầy được hoàn thành ngay khi thanh toán.
        </p>
      </div>

      <button class="btn btn-primary" type="button" :disabled="isReloading" @click="loadAll">
        <span v-if="isReloading" class="spinner-border spinner-border-sm me-2"></span>
        Tải lại dữ liệu
      </button>
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
        >
      </div>

      <div class="col-lg-5">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-outline-primary" type="button" :disabled="loading.search || !keyword" @click="handleSearch">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tìm kiếm
          </button>
          <button class="btn btn-outline-secondary" type="button" @click="resetSearch">Xóa lọc</button>
        </div>
      </div>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Tổng hóa đơn</p>
        <h3 class="metric-card__value mb-3">{{ stats.tong_so_hoa_don }}</h3>
        <span class="metric-card__delta is-positive">Chỉ tính đơn đã xác nhận</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Doanh thu hệ thống</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.doanh_thu_he_thong) }}</h3>
        <span class="metric-card__delta is-positive">{{ stats.hoa_don_he_thong }} hóa đơn</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">Doanh thu tại quầy</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.doanh_thu_tai_quay) }}</h3>
        <span class="metric-card__delta is-positive">{{ stats.hoa_don_tai_quay }} hóa đơn</span>
      </article>
    </div>

    <div class="col-md-6 col-xl-3">
      <article class="metric-card h-100">
        <p class="metric-card__label mb-2">VAT đã thu</p>
        <h3 class="metric-card__value mb-3">{{ formatCurrency(stats.tong_thue_vat) }}</h3>
        <span class="metric-card__delta is-positive">10% sau giảm giá</span>
      </article>
    </div>
  </section>

  <section class="content-card mt-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
      <div>
        <h3 class="panel-title">Danh sách hóa đơn</h3>
        <p class="panel-subtitle mb-0">Đơn chờ xác nhận sẽ có nút xác nhận hoặc từ chối.</p>
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
            <th>Kênh</th>
            <th>Trạng thái</th>
            <th>Thanh toán</th>
            <th>Ngày bán</th>
            <th class="text-end">Xử lý</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="hoaDon in visibleHoaDons"
            :key="hoaDon.id_hoa_don"
            class="invoice-row"
            title="Click để xem sản phẩm trong hóa đơn"
            @click="openDetailModal(hoaDon)"
          >
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
              <span class="soft-badge" :class="hoaDon.kenh_ban === 'tai_quay' ? 'soft-badge--teal' : 'soft-badge--blue'">
                {{ channelLabel(hoaDon.kenh_ban) }}
              </span>
            </td>
            <td>
              <span class="soft-badge" :class="statusBadgeClass(hoaDon.trang_thai_xu_ly)">
                {{ statusLabel(hoaDon) }}
              </span>
              <div v-if="hoaDon.ly_do_tu_choi" class="small text-danger mt-1">
                {{ hoaDon.ly_do_tu_choi }}
              </div>
            </td>
            <td>
              <strong>{{ formatCurrency(hoaDon.tien_thanh_toan) }}</strong>
              <div class="small text-secondary">
                VAT {{ formatCurrency(hoaDon.thue_vat) }}
              </div>
              <div class="small text-secondary">
                {{ paymentMethodLabel(hoaDon) }} - {{ paymentStatusLabel(hoaDon) }}
              </div>
              <div v-if="hasRewardUsage(hoaDon)" class="invoice-reward-note">
                <span>Điểm dùng: {{ formatNumber(usedRewardPoints(hoaDon)) }} điểm</span>
                <span>Giảm điểm: -{{ formatCurrency(rewardPointDiscount(hoaDon)) }}</span>
              </div>
            </td>
            <td>{{ formatDate(hoaDon.ngay_ban) }}</td>
            <td class="text-end">
              <div v-if="isPending(hoaDon)" class="d-flex justify-content-end gap-2">
                <button
                  class="btn btn-sm btn-success"
                  type="button"
                  :disabled="processingId === hoaDon.id_hoa_don"
                  @click.stop="confirmOrder(hoaDon)"
                >
                  <span v-if="processingId === hoaDon.id_hoa_don" class="spinner-border spinner-border-sm me-1"></span>
                  Xác nhận
                </button>
                <button
                  class="btn btn-sm btn-outline-danger"
                  type="button"
                  :disabled="processingId === hoaDon.id_hoa_don"
                  @click.stop="openRejectModal(hoaDon)"
                >
                  Từ chối
                </button>
              </div>
              <div v-else-if="isAwaitingCashPayment(hoaDon)" class="d-flex justify-content-end gap-2">
                <button
                  class="btn btn-sm btn-outline-danger"
                  type="button"
                  :disabled="processingId === hoaDon.id_hoa_don"
                  @click.stop="openRejectModal(hoaDon)"
                >
                  Từ chối
                </button>
                <button
                  class="btn btn-sm btn-primary"
                  type="button"
                  :disabled="processingId === hoaDon.id_hoa_don"
                  @click.stop="markOrderPaid(hoaDon)"
                >
                  <span v-if="processingId === hoaDon.id_hoa_don" class="spinner-border spinner-border-sm me-1"></span>
                  Đã thu tiền
                </button>
              </div>
              <span v-else class="small text-secondary">{{ actionLabel(hoaDon) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="master-empty">
      <p class="mb-2 fw-semibold">Chưa có hóa đơn để hiển thị.</p>
      <p class="mb-0 text-secondary">Khi khách đặt hàng hoặc nhân viên bán tại quầy, hóa đơn sẽ xuất hiện tại đây.</p>
    </div>

    <div v-if="canLoadMoreHoaDons" class="d-flex justify-content-center mt-4 pt-4 border-top">
      <button class="btn btn-outline-primary px-4" type="button" @click="showMoreHoaDons">Xem thêm</button>
    </div>
  </section>

  <div v-if="detailModal.open" class="order-modal" role="dialog" aria-modal="true">
    <div class="order-modal__backdrop" @click="closeDetailModal"></div>
    <div class="order-modal__panel order-modal__panel--wide">
      <button class="order-modal__close" type="button" aria-label="Đóng" @click="closeDetailModal">
        <i class="bi bi-x-lg"></i>
      </button>

      <div class="soft-badge soft-badge--blue mb-3">
        <i class="bi bi-bag-check"></i>
        Chi tiết hóa đơn
      </div>
      <h3>{{ detailModal.order?.ma_hoa_don }}</h3>
      <p>Danh sách sản phẩm khách đã mua trong hóa đơn này.</p>

      <div class="invoice-detail-summary">
        <div>
          <span>Khách hàng</span>
          <strong>{{ detailModal.order?.khach_hang?.ten_khach_hang || "-" }}</strong>
        </div>
        <div>
          <span>Kênh bán</span>
          <strong>{{ channelLabel(detailModal.order?.kenh_ban) }}</strong>
        </div>
        <div>
          <span>Ngày bán</span>
          <strong>{{ formatDate(detailModal.order?.ngay_ban) }}</strong>
        </div>
        <div>
          <span>Thanh toán</span>
          <strong>{{ formatCurrency(detailModal.order?.tien_thanh_toan) }}</strong>
        </div>
      </div>

      <div class="invoice-reward-panel">
        <div class="invoice-reward-panel__header">
          <div>
            <span>Điểm thưởng</span>
            <strong>{{ hasRewardUsage(detailModal.order) ? "Khách có sử dụng điểm" : "Không sử dụng điểm" }}</strong>
          </div>
          <span class="soft-badge" :class="hasRewardUsage(detailModal.order) ? 'soft-badge--teal' : 'soft-badge--blue'">
            {{ formatNumber(earnedRewardPoints(detailModal.order)) }} điểm được cộng
          </span>
        </div>
        <div class="invoice-reward-panel__grid">
          <div>
            <span>Điểm đã sử dụng</span>
            <strong>{{ formatNumber(usedRewardPoints(detailModal.order)) }} điểm</strong>
          </div>
          <div>
            <span>Số tiền giảm bằng điểm</span>
            <strong class="text-success">-{{ formatCurrency(rewardPointDiscount(detailModal.order)) }}</strong>
          </div>
          <div>
            <span>Điểm được cộng sau đơn</span>
            <strong class="text-primary">+{{ formatNumber(earnedRewardPoints(detailModal.order)) }} điểm</strong>
          </div>
        </div>
      </div>

      <div v-if="detailModal.loading" class="master-empty">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Đang tải sản phẩm trong hóa đơn...
      </div>

      <div v-else-if="detailModal.items.length" class="table-responsive invoice-detail-table">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th>Số lô</th>
              <th class="text-center">Số lượng</th>
              <th class="text-end">Giá bán</th>
              <th class="text-end">Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in detailModal.items" :key="item.id">
              <td>
                <div class="fw-semibold">{{ orderItemProductName(item) }}</div>
                <div class="small text-secondary">{{ orderItemProductCode(item) }}</div>
              </td>
              <td>{{ item.lo_thuoc?.so_lo || "-" }}</td>
              <td class="text-center">
                {{ item.so_luong }}
                <span v-if="item.don_vi_ban">{{ item.don_vi_ban }}</span>
              </td>
              <td class="text-end">{{ formatCurrency(item.gia_ban) }}</td>
              <td class="text-end fw-semibold">{{ formatCurrency(item.thanh_tien) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="4" class="text-end">Tổng tiền sản phẩm</th>
              <th class="text-end">{{ formatCurrency(detailSubtotal) }}</th>
            </tr>
          </tfoot>
        </table>
      </div>

      <div v-else class="master-empty">
        <p class="mb-0 fw-semibold">Hóa đơn này chưa có sản phẩm.</p>
      </div>
    </div>
  </div>

  <div v-if="rejectModal.open" class="order-modal" role="dialog" aria-modal="true">
    <div class="order-modal__backdrop" @click="closeRejectModal"></div>
    <div class="order-modal__panel">
      <button class="order-modal__close" type="button" aria-label="Đóng" @click="closeRejectModal">
        <i class="bi bi-x-lg"></i>
      </button>

      <div class="soft-badge soft-badge--orange mb-3">
        <i class="bi bi-exclamation-triangle"></i>
        Từ chối đơn hàng
      </div>
      <h3>{{ rejectModal.order?.ma_hoa_don }}</h3>
      <p>Nhập lý do từ chối. Nội dung này sẽ được gửi về email khách hàng.</p>

      <textarea
        v-model.trim="rejectModal.reason"
        class="form-control"
        :class="{ 'is-invalid': rejectModal.reasonError }"
        rows="5"
        placeholder="Ví dụ: Sản phẩm trong đơn hiện không đủ tồn kho."
        @input="rejectModal.reasonError = ''"
      ></textarea>
      <div v-if="rejectModal.reasonError" class="invalid-feedback d-block">
        {{ rejectModal.reasonError }}
      </div>

      <div class="order-modal__actions">
        <button class="btn btn-outline-secondary" type="button" :disabled="rejectModal.loading" @click="closeRejectModal">
          Đóng
        </button>
        <button class="btn btn-danger" type="button" :disabled="rejectSubmitDisabled" @click="submitReject">
          <span v-if="rejectModal.loading" class="spinner-border spinner-border-sm me-2"></span>
          Gửi từ chối
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import {
  confirmHoaDon,
  getHoaDonChiTiets,
  getHoaDons,
  getHoaDonStatistics,
  markHoaDonPaid,
  rejectHoaDon,
  searchHoaDons,
} from "../../../api/hoaDonApi";
import { normalizeApiError } from "../../../lib/errorMessages";
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
      processingId: null,
      loading: {
        list: false,
        search: false,
        stats: false,
      },
      stats: {
        tong_so_hoa_don: 0,
        tong_giam_gia: 0,
        tong_thue_vat: 0,
        tong_tien_thanh_toan: 0,
        doanh_thu_he_thong: 0,
        doanh_thu_tai_quay: 0,
        hoa_don_he_thong: 0,
        hoa_don_tai_quay: 0,
      },
      rejectModal: {
        open: false,
        order: null,
        reason: "",
        reasonError: "",
        loading: false,
      },
      detailModal: {
        open: false,
        order: null,
        items: [],
        loading: false,
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
    rejectSubmitDisabled() {
      return this.rejectModal.loading;
    },
    detailSubtotal() {
      return this.detailModal.items.reduce((total, item) => total + Number(item.thanh_tien || 0), 0);
    },
  },

  mounted() {
    if (this.keyword) {
      this.handleSearch(true);
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
      return normalizeApiError(error, "Không thể tải dữ liệu hóa đơn.", {
        ly_do_tu_choi: "lý do từ chối",
      });
    },
    formatCurrency(value) {
      const n = Number(value || 0);
      if (Math.abs(n) >= 1e9) {
        const ty = n / 1e9;
        return (Math.abs(ty) >= 10 ? Math.round(ty) : ty.toFixed(2).replace('.', ',')) + ' tỷ đ';
      }
      return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 }).format(n);
    },
    formatNumber(value) {
      return new Intl.NumberFormat("vi-VN", {
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    usedRewardPoints(hoaDon) {
      return Number(hoaDon?.diem_da_su_dung || 0);
    },
    rewardPointDiscount(hoaDon) {
      return Number(hoaDon?.giam_gia_diem || 0);
    },
    earnedRewardPoints(hoaDon) {
      return Number(hoaDon?.diem_da_cong || 0);
    },
    hasRewardUsage(hoaDon) {
      return this.usedRewardPoints(hoaDon) > 0 || this.rewardPointDiscount(hoaDon) > 0;
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
    orderItemProductName(item) {
      return item?.lo_thuoc?.thuoc?.ten_thuoc || item?.thuoc?.ten_thuoc || "Sản phẩm chưa có tên";
    },
    orderItemProductCode(item) {
      return item?.lo_thuoc?.thuoc?.ma_thuoc || item?.lo_thuoc?.id_thuoc || "-";
    },
    channelLabel(channel) {
      return channel === "tai_quay" ? "Tại quầy" : "Hệ thống";
    },
    paymentInfo(hoaDon) {
      return hoaDon?.thanh_toan || hoaDon?.thanhToan || {};
    },
    paymentMethodLabel(hoaDon) {
      return this.paymentInfo(hoaDon)?.phuong_thuc === "payos" ? "PayOS" : "Tiền mặt";
    },
    paymentStatusLabel(hoaDon) {
      const payment = this.paymentInfo(hoaDon);
      const status = payment?.trang_thai || "";

      if (status === "paid") {
        return "Đã thanh toán";
      }

      if (["canceled", "cancelled", "expired", "failed"].includes(status)) {
        return "Thanh toán không thành công";
      }

      return payment?.phuong_thuc === "payos" ? "Chờ thanh toán" : "Chờ thu tiền";
    },
    statusLabel(hoaDon) {
      const status = hoaDon?.trang_thai_xu_ly;

      if (status === "cho_thanh_toan") {
        return "Chờ thanh toán";
      }

      if (["cho_xac_nhan", "cho_thanh_toan"].includes(status)) {
        return "Chờ xác nhận";
      }

      if (status === "tu_choi") {
        return "Từ chối";
      }

      if (status === "hoan_thanh") {
        return "Hoàn thành tại quầy";
      }

      return "Đã xác nhận";
    },
    statusBadgeClass(status) {
      if (["cho_xac_nhan", "cho_thanh_toan"].includes(status)) {
        return "soft-badge--orange";
      }

      if (status === "tu_choi") {
        return "soft-badge--red";
      }

      if (status === "hoan_thanh") {
        return "soft-badge--teal";
      }

      return "soft-badge--blue";
    },
    isPending(hoaDon) {
      return hoaDon?.trang_thai_xu_ly === "cho_xac_nhan";
    },
    isAwaitingCashPayment(hoaDon) {
      const payment = this.paymentInfo(hoaDon);

      return hoaDon?.trang_thai_xu_ly === "da_xac_nhan"
        && payment?.phuong_thuc !== "payos"
        && payment?.trang_thai !== "paid";
    },
    actionLabel(hoaDon) {
      if (hoaDon?.trang_thai_xu_ly === "cho_thanh_toan") {
        return "Chờ khách thanh toán";
      }

      if (hoaDon?.trang_thai_xu_ly === "tu_choi") {
        return "Đã gửi lý do";
      }

      if (hoaDon?.kenh_ban === "tai_quay") {
        return "Tự hoàn thành";
      }

      return "Đã xử lý";
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
          showToast(`Đã tải ${this.hoaDons.length} hóa đơn.`);
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
      if (this.rejectModal.open || this.detailModal.open || this.loading.list || this.loading.search || this.loading.stats) {
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
    async openDetailModal(hoaDon) {
      this.detailModal.open = true;
      this.detailModal.order = hoaDon;
      this.detailModal.items = [];
      this.detailModal.loading = true;

      try {
        const response = await getHoaDonChiTiets(hoaDon.id_hoa_don);
        this.detailModal.items = Array.isArray(response?.data) ? response.data : [];
      } catch (error) {
        showToast(this.normalizeError(error, "Không thể tải sản phẩm trong hóa đơn."), "error");
      } finally {
        this.detailModal.loading = false;
      }
    },
    closeDetailModal() {
      if (this.detailModal.loading) {
        return;
      }

      this.detailModal.open = false;
      this.detailModal.order = null;
      this.detailModal.items = [];
    },
    async confirmOrder(hoaDon) {
      this.processingId = hoaDon.id_hoa_don;

      try {
        await confirmHoaDon(hoaDon.id_hoa_don);
        showToast("Xác nhận đơn hàng thành công.");
        await this.refreshCurrentView();
      } catch (error) {
        showToast(this.normalizeError(error), "error");
      } finally {
        this.processingId = null;
      }
    },
    async markOrderPaid(hoaDon) {
      this.processingId = hoaDon.id_hoa_don;

      try {
        await markHoaDonPaid(hoaDon.id_hoa_don);
        showToast("Đã xác nhận thu tiền.");
        await this.refreshCurrentView();
      } catch (error) {
        showToast(this.normalizeError(error), "error");
      } finally {
        this.processingId = null;
      }
    },
    openRejectModal(hoaDon) {
      this.rejectModal.open = true;
      this.rejectModal.order = hoaDon;
      this.rejectModal.reason = "";
      this.rejectModal.reasonError = "";
      this.rejectModal.loading = false;
    },
    closeRejectModal() {
      if (this.rejectModal.loading) {
        return;
      }

      this.rejectModal.open = false;
      this.rejectModal.order = null;
      this.rejectModal.reason = "";
      this.rejectModal.reasonError = "";
    },
    async submitReject() {
      if (this.rejectModal.loading || !this.rejectModal.order) {
        return;
      }

      if (this.rejectModal.reason.trim().length < 5) {
        this.rejectModal.reasonError = "Vui lòng nhập lý do từ chối ít nhất 5 ký tự.";
        showToast(this.rejectModal.reasonError, "error");
        return;
      }

      this.rejectModal.loading = true;
      this.processingId = this.rejectModal.order.id_hoa_don;

      try {
        await rejectHoaDon(this.rejectModal.order.id_hoa_don, {
          ly_do_tu_choi: this.rejectModal.reason,
        });
        showToast("Đã từ chối đơn hàng.");
        this.rejectModal.open = false;
        await this.refreshCurrentView();
      } catch (error) {
        if (error?.payload?.errors?.ly_do_tu_choi) {
          this.rejectModal.reasonError = this.normalizeError(error);
          showToast(this.rejectModal.reasonError, "error");
        } else {
          showToast(this.normalizeError(error), "error");
        }
      } finally {
        this.rejectModal.loading = false;
        this.processingId = null;
      }
    },
  },
};
</script>

<style scoped>
.soft-badge--red {
  background: #fee2e2;
  color: #b91c1c;
}

.invoice-row {
  cursor: pointer;
}

.invoice-row:hover > td {
  background: #f8fbff;
}

.invoice-reward-note {
  display: grid;
  gap: 2px;
  margin-top: 4px;
  color: #0f766e;
  font-size: 0.78rem;
  font-weight: 700;
}

.order-modal {
  position: fixed;
  inset: 0;
  z-index: 1090;
  display: grid;
  place-items: center;
  padding: 20px;
}

.order-modal__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.58);
  backdrop-filter: blur(4px);
}

.order-modal__panel {
  position: relative;
  z-index: 1;
  width: min(560px, 100%);
  padding: 24px;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 22px;
  background: #fff;
  box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
}

.order-modal__panel--wide {
  width: min(980px, 100%);
  max-height: min(86vh, 760px);
  overflow: auto;
}

.order-modal__close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 999px;
  background: #fff;
  color: #475569;
}

.order-modal__panel h3 {
  margin: 0 0 8px;
  color: #111827;
  font-size: 1.35rem;
  font-weight: 800;
}

.order-modal__panel p {
  margin: 0 0 16px;
  color: #64748b;
}

.order-modal__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}

.invoice-detail-summary {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 18px;
}

.invoice-detail-summary > div {
  padding: 14px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 16px;
  background: #f8fbff;
}

.invoice-detail-summary span,
.invoice-detail-summary strong {
  display: block;
}

.invoice-detail-summary span {
  color: #64748b;
  font-size: 0.84rem;
}

.invoice-detail-summary strong {
  margin-top: 4px;
  color: #0f172a;
}

.invoice-reward-panel {
  margin-bottom: 18px;
  padding: 16px;
  border: 1px solid rgba(20, 184, 166, 0.2);
  border-radius: 18px;
  background: #f0fdfa;
}

.invoice-reward-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.invoice-reward-panel__header span,
.invoice-reward-panel__grid span {
  display: block;
  color: #64748b;
  font-size: 0.84rem;
}

.invoice-reward-panel__header strong,
.invoice-reward-panel__grid strong {
  display: block;
  margin-top: 4px;
  color: #0f172a;
}

.invoice-reward-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.invoice-reward-panel__grid > div {
  padding: 12px;
  border: 1px solid rgba(20, 184, 166, 0.16);
  border-radius: 14px;
  background: #fff;
}

.invoice-detail-table {
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 16px;
  overflow: hidden;
}

.invoice-detail-table thead,
.invoice-detail-table tfoot {
  background: #f8fbff;
}

@media (max-width: 767.98px) {
  .order-modal__actions > .btn {
    width: 100%;
  }

  .invoice-detail-summary {
    grid-template-columns: 1fr;
  }

  .invoice-reward-panel__header {
    align-items: flex-start;
    flex-direction: column;
  }

  .invoice-reward-panel__grid {
    grid-template-columns: 1fr;
  }
}
</style>
