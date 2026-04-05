<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--teal mb-3">
          <i class="bi bi-box-seam"></i>
          Kho và lô thuốc
        </div>
        <h2 class="page-section-title">Theo dõi tồn kho và lô thuốc</h2>
        <p class="page-section-copy mb-0">
          Theo dõi tổng tồn theo thuốc, danh sách lô, hạn sử dụng và số lượng còn lại. Có thể nhập lô mới trực tiếp
          tại đây.
        </p>
      </div>

      <div class="soft-badge">
        <i class="bi bi-person-badge"></i>
        {{ currentUser?.ho_ten || "Tài khoản hệ thống" }}
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-6">
        <label class="form-label fw-semibold">Tìm thuốc hoặc số lô</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Nhập tên thuốc, mã thuốc hoặc số lô"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-6">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary" @click="loadInventory" :disabled="loading.inventory">
            <span v-if="loading.inventory" class="spinner-border spinner-border-sm me-2"></span>
            Đồng bộ dữ liệu
          </button>
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tìm kiếm
          </button>
          <button class="btn btn-outline-secondary" @click="resetView">Làm mới</button>
          <button class="btn btn-success" @click="openLotForm()" :disabled="!isAdminUser">
            <i class="bi bi-plus-circle me-2"></i>
            Nhập lô thuốc
          </button>
        </div>
      </div>
    </div>

    <div v-if="!isAdminUser" class="alert alert-warning mt-4 mb-0">
      Tài khoản nhân viên chỉ có quyền xem. Thêm hoặc chỉnh sửa lô thuốc yêu cầu quyền quản trị.
    </div>
    <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
    <div v-if="error" class="alert alert-danger mt-4 mb-0">{{ error }}</div>
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

  <section class="row g-4 mt-1">
    <div class="col-12">
      <article class="content-card h-100">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
          <div>
            <h3 class="panel-title">Tổng tồn theo thuốc</h3>
            <p class="panel-subtitle mb-0">Bấm vào nút chi tiết để xem danh sách lô của từng thuốc.</p>
          </div>
          <span class="soft-badge soft-badge--blue">{{ thuocRows.length }} thuốc</span>
        </div>

        <div v-if="thuocRows.length" class="table-responsive">
          <table class="table table-master align-middle mb-0">
            <thead>
              <tr>
                <th>Thuốc</th>
                <th>Loại</th>
                <th>Giá bán</th>
                <th>Số lượng còn</th>
                <th class="text-end">Tác vụ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="thuoc in thuocRows" :key="thuoc.ma_thuoc">
                <td>
                  <div class="fw-semibold">{{ thuoc.ten_thuoc }}</div>
                  <div class="small text-secondary">{{ thuoc.ma_thuoc }}</div>
                </td>
                <td>
                  {{
                    thuoc.loaiThuoc?.ten_loai ||
                    thuoc.loaiThuoc?.ten_loai_thuoc ||
                    thuoc.loai_thuoc?.ten_loai ||
                    thuoc.loai_thuoc?.ten_loai_thuoc ||
                    thuoc.loai_thuoc ||
                    "-"
                  }}
                </td>
                <td>{{ formatCurrency(thuoc.gia_ban) }}</td>
                <td>{{ thuoc.so_luong_con }}</td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-sm btn-outline-primary" @click="openLotModal(thuoc)">Chi tiết lô</button>
                    <button
                      class="btn btn-sm btn-outline-success"
                      :disabled="!isAdminUser"
                      @click="openLotForm({ id_thuoc: thuoc.ma_thuoc })"
                    >
                      Nhập lô
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chưa có dữ liệu tồn kho.</p>
          <p class="mb-0 text-secondary">Hãy đồng bộ dữ liệu hoặc thử lại với từ khóa khác.</p>
        </div>
      </article>
    </div>
  </section>

  <!-- Modal: Danh sách lô -->
  <div ref="lotModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Chi tiết lô thuốc</h5>
            <p class="mb-0 text-secondary small">
              {{ selectedThuoc ? `${selectedThuoc.ten_thuoc} - ${selectedThuoc.ma_thuoc}` : "Chưa chọn thuốc" }}
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div v-if="selectedThuocLots.length" class="row g-3">
            <div class="col-md-6 col-xl-4" v-for="lo in selectedThuocLots" :key="lo.id_lo">
              <article class="inventory-lot-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                  <div>
                    <div class="fw-bold">Số lô {{ lo.so_lo }}</div>
                    <div class="small text-secondary">Mã lô: {{ lo.id_lo }}</div>
                  </div>
                  <span class="soft-badge" :class="lotBadgeClass(lo)">
                    {{ lotStatus(lo) }}
                  </span>
                </div>

                <div class="row g-3 small">
                  <div class="col-6">
                    <div class="text-secondary mb-1">Số lượng nhập</div>
                    <div class="fw-semibold">{{ lo.so_luong_nhap }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Số lượng còn</div>
                    <div class="fw-semibold">{{ lo.so_luong_con }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Ngày sản xuất</div>
                    <div class="fw-semibold">{{ formatDate(lo.ngay_san_xuat) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Hạn sử dụng</div>
                    <div class="fw-semibold">{{ formatDate(lo.han_su_dung) }}</div>
                  </div>
                  <div class="col-12">
                    <div class="text-secondary mb-1">Giá nhập</div>
                    <div class="fw-semibold">{{ formatCurrency(lo.gia_nhap) }}</div>
                  </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                  <button class="btn btn-sm btn-outline-primary" @click="openLotForm(lo)">Sửa lô</button>
                </div>
              </article>
            </div>
          </div>

          <div v-else class="master-empty">
            <p class="mb-2 fw-semibold">Thuốc này hiện chưa có lô nào.</p>
            <p class="mb-0 text-secondary">Hệ thống chưa ghi nhận lô nhập cho thuốc đang chọn.</p>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Nhập/Sửa lô -->
  <div ref="lotFormModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">{{ lotForm.id_lo ? "Cập nhật lô thuốc" : "Nhập lô thuốc" }}</h5>
            <p class="mb-0 text-secondary small">
              {{ lotForm.id_lo ? "Cập nhật số lượng hoặc thông tin lô." : "Nhập lô thuốc mới vào kho." }}
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Thuốc áp dụng</label>
              <select v-model="lotForm.id_thuoc" class="form-select" :disabled="lockThuocSelect">
                <option value="">Chọn thuốc</option>
                <option v-for="thuoc in thuocRows" :key="thuoc.ma_thuoc" :value="thuoc.ma_thuoc">
                  {{ thuoc.ten_thuoc }} ({{ thuoc.ma_thuoc }})
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Số lô</label>
              <input v-model.trim="lotForm.so_lo" class="form-control" placeholder="Ví dụ: LO-2026-001" />
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ngày sản xuất</label>
              <input v-model="lotForm.ngay_san_xuat" type="date" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Hạn sử dụng</label>
              <input v-model="lotForm.han_su_dung" type="date" class="form-control" />
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4" v-if="!lotForm.id_lo">
              <label class="form-label fw-semibold">Số lượng nhập</label>
              <input v-model.number="lotForm.so_luong_nhap" type="number" min="1" class="form-control" />
              <div class="form-text">Số lượng còn sẽ tự bằng số lượng nhập.</div>
            </div>
            <div class="col-md-4" v-if="!lotForm.id_lo">
              <label class="form-label fw-semibold">Số lượng còn (tự động)</label>
              <input :value="lotForm.so_luong_nhap || 0" type="number" class="form-control" disabled />
            </div>
            <div class="col-md-4" v-if="lotForm.id_lo">
              <label class="form-label fw-semibold">Số lượng còn (tự động)</label>
              <input :value="lotForm.so_luong_con ?? 0" type="number" class="form-control" disabled />
            </div>
            <div class="col-md-4" v-if="lotForm.id_lo">
              <label class="form-label fw-semibold">Số lượng nhập thêm</label>
              <input v-model.number="lotForm.so_luong_nhap_them" type="number" min="1" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Giá nhập</label>
              <input v-model.number="lotForm.gia_nhap" type="number" min="1" class="form-control" />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="btn btn-primary" @click="saveLot" :disabled="loading.saveLot || !isAdminUser">
            <span v-if="loading.saveLot" class="spinner-border spinner-border-sm me-2"></span>
            {{ lotForm.id_lo ? "Lưu thay đổi" : "Nhập lô" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from "bootstrap";
import { createLoThuoc, getLoThuocs, getThuocs, searchLoThuocs, searchThuocs, updateLoThuoc } from "../../../api/inventoryApi";
import { getStoredUser, isAdminUser } from "../../../lib/authStorage";
import { showToast } from "../../../lib/toast";

export default {
  data() {
    return {
      currentUser: getStoredUser(),
      keyword: "",
      thuocs: [],
      loThuocs: [],
      selectedThuoc: null,
      lotModal: null,
      lotFormModal: null,
      message: "",
      error: "",
      loading: {
        inventory: false,
        search: false,
        saveLot: false,
      },
      lotForm: {
        id_lo: null,
        id_thuoc: "",
        so_lo: "",
        ngay_san_xuat: "",
        han_su_dung: "",
        so_luong_nhap: null,
        so_luong_con: null,
        so_luong_nhap_them: null,
        gia_nhap: null,
      },
      lockThuocSelect: false,
    };
  },
  computed: {
    isAdminUser() {
      return isAdminUser();
    },
    thuocRows() {
      return this.thuocs
        .map((thuoc) => {
          const relatedLots = this.loThuocs.filter((lo) => lo.id_thuoc === thuoc.ma_thuoc);

          return {
            ...thuoc,
            so_luong_con: relatedLots.reduce((sum, lo) => sum + Number(lo.so_luong_con || 0), 0),
          };
        })
        .sort((a, b) => a.so_luong_con - b.so_luong_con);
    },
    selectedThuocLots() {
      if (!this.selectedThuoc) {
        return [];
      }

      return this.loThuocs
        .filter((lo) => lo.id_thuoc === this.selectedThuoc.ma_thuoc)
        .sort((a, b) => new Date(a.han_su_dung) - new Date(b.han_su_dung));
    },
    metrics() {
      const totalTon = this.loThuocs.reduce((sum, lo) => sum + Number(lo.so_luong_con || 0), 0);
      const sapHetHan = this.loThuocs.filter((lo) => this.lotStatus(lo) === "Sắp hết hạn").length;
      const ganHetTon = this.thuocRows.filter((thuoc) => thuoc.so_luong_con > 0 && thuoc.so_luong_con <= 20).length;

      return [
        {
          label: "Tổng thuốc",
          value: this.thuocRows.length,
          note: "Đang có trong kho",
          deltaClass: "is-positive",
          icon: "bi bi-capsule-pill",
          iconClass: "metric-card__icon--blue",
        },
        {
          label: "Tổng tồn",
          value: totalTon,
          note: "Cộng dồn từ các lô",
          deltaClass: "is-positive",
          icon: "bi bi-box-seam",
          iconClass: "metric-card__icon--teal",
        },
        {
          label: "Lô sắp hết hạn",
          value: sapHetHan,
          note: "Cần theo dõi sớm",
          deltaClass: "is-warning",
          icon: "bi bi-exclamation-diamond",
          iconClass: "metric-card__icon--orange",
        },
        {
          label: "Thuốc gần hết tồn",
          value: ganHetTon,
          note: "Tổng tồn dưới hoặc bằng 20",
          deltaClass: "is-warning",
          icon: "bi bi-clipboard2-pulse",
          iconClass: "metric-card__icon--red",
        },
      ];
    },
  },
  methods: {
    normalizeError(err) {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(" | ");
      }

      return err?.message || "Không thể tải dữ liệu tồn kho.";
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

      return new Intl.DateTimeFormat("vi-VN", { dateStyle: "short" }).format(new Date(value));
    },
    lotStatus(lo) {
      const today = new Date();
      const expiry = new Date(lo.han_su_dung);
      const diffDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

      if (Number(lo.so_luong_con || 0) <= 0) {
        return "Hết tồn";
      }

      if (diffDays <= 30) {
        return "Sắp hết hạn";
      }

      return "Ổn định";
    },
    lotBadgeClass(lo) {
      const status = this.lotStatus(lo);

      if (status === "Hết tồn") {
        return "soft-badge--orange";
      }

      if (status === "Sắp hết hạn") {
        return "soft-badge--blue";
      }

      return "soft-badge--teal";
    },
    ensureModal() {
      if (!this.lotModal && this.$refs.lotModalEl) {
        this.lotModal = new Modal(this.$refs.lotModalEl);
      }
      if (!this.lotFormModal && this.$refs.lotFormModalEl) {
        this.lotFormModal = new Modal(this.$refs.lotFormModalEl);
      }
    },
    openLotModal(thuoc) {
      this.selectedThuoc = thuoc;
      this.message = `Đang hiển thị ${this.selectedThuocLots.length} lô của ${thuoc.ten_thuoc}.`;
      this.error = "";
      this.ensureModal();

      if (this.lotModal) {
        this.lotModal.show();
      }
    },
    openLotForm(lot = {}) {
      this.lotForm = {
        id_lo: lot.id_lo || null,
        id_thuoc: lot.id_thuoc || this.selectedThuoc?.ma_thuoc || "",
        so_lo: lot.so_lo || "",
        ngay_san_xuat: lot.ngay_san_xuat || "",
        han_su_dung: lot.han_su_dung || "",
        so_luong_nhap: lot.so_luong_nhap || null,
        so_luong_con: lot.so_luong_con ?? lot.so_luong_nhap ?? null,
        so_luong_nhap_them: null,
        gia_nhap: lot.gia_nhap || null,
      };
      this.lockThuocSelect = Boolean(lot.id_thuoc || lot.id_lo);
      this.ensureModal();
      if (this.lotFormModal) {
        this.lotFormModal.show();
      }
    },
    async saveLot() {
      if (!this.isAdminUser) {
        showToast("Bạn không có quyền thao tác lô thuốc.", "error");
        return;
      }

      if (!this.lotForm.id_thuoc) {
        showToast("Vui lòng chọn thuốc.", "error");
        return;
      }

      if (!this.lotForm.so_lo) {
        showToast("Vui lòng nhập số lô.", "error");
        return;
      }

      if (!this.lotForm.ngay_san_xuat || !this.lotForm.han_su_dung) {
        showToast("Vui lòng nhập ngày sản xuất và hạn sử dụng.", "error");
        return;
      }

      if (!this.lotForm.gia_nhap) {
        showToast("Vui lòng nhập giá nhập.", "error");
        return;
      }

      if (!this.lotForm.id_lo && !this.lotForm.so_luong_nhap) {
        showToast("Vui lòng nhập số lượng nhập.", "error");
        return;
      }

      if (this.lotForm.id_lo && !this.lotForm.so_luong_nhap_them) {
        showToast("Vui lòng nhập số lượng nhập thêm.", "error");
        return;
      }

      const payload = {
        id_thuoc: this.lotForm.id_thuoc,
        so_lo: this.lotForm.so_lo,
        ngay_san_xuat: this.lotForm.ngay_san_xuat,
        han_su_dung: this.lotForm.han_su_dung,
        so_luong_nhap: this.lotForm.so_luong_nhap,
        so_luong_con:
          this.lotForm.id_lo
            ? this.lotForm.so_luong_con
            : this.lotForm.so_luong_nhap,
        so_luong_nhap_them: this.lotForm.so_luong_nhap_them,
        gia_nhap: this.lotForm.gia_nhap,
      };

      if (!this.lotForm.id_lo || !this.lotForm.so_luong_nhap_them) {
        delete payload.so_luong_nhap_them;
      }

      this.loading.saveLot = true;
      this.error = "";

      try {
        if (this.lotForm.id_lo) {
          await updateLoThuoc(this.lotForm.id_lo, payload);
          showToast("Đã cập nhật lô thuốc.", "success");
        } else {
          await createLoThuoc(payload);
          showToast("Đã nhập lô thuốc mới.", "success");
        }

        await this.loadInventory();
        if (this.lotFormModal) {
          this.lotFormModal.hide();
        }
        this.lockThuocSelect = false;
      } catch (err) {
        this.error = this.normalizeError(err);
        showToast(this.error, "error");
      } finally {
        this.loading.saveLot = false;
      }
    },
    async loadInventory() {
      this.loading.inventory = true;
      this.error = "";

      try {
        const [thuocData, loData] = await Promise.all([getThuocs(), getLoThuocs()]);
        this.thuocs = thuocData;
        this.loThuocs = loData;

        if (this.selectedThuoc) {
          const refreshed = thuocData.find((item) => item.ma_thuoc === this.selectedThuoc.ma_thuoc);
          this.selectedThuoc = refreshed || null;
        }

        this.message = `Đã đồng bộ ${this.thuocs.length} thuốc và ${this.loThuocs.length} lô thuốc.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.inventory = false;
      }
    },
    async handleSearch() {
      if (!this.keyword) {
        return;
      }

      this.loading.search = true;
      this.error = "";

      try {
        const [thuocData, loData] = await Promise.all([searchThuocs(this.keyword), searchLoThuocs(this.keyword)]);
        this.thuocs = thuocData;
        this.loThuocs = loData;
        this.selectedThuoc = null;
        this.message = `Tìm thấy ${this.thuocs.length} thuốc và ${this.loThuocs.length} lô phù hợp.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.search = false;
      }
    },
    resetView() {
      this.keyword = "";
      this.selectedThuoc = null;
      this.message = "";
      this.error = "";
      this.loadInventory();
    },
  },
  mounted() {
    this.ensureModal();
    this.loadInventory();
  },
  beforeUnmount() {
    if (this.lotModal) {
      this.lotModal.dispose();
    }
    if (this.lotFormModal) {
      this.lotFormModal.dispose();
    }
  },
};
</script>
