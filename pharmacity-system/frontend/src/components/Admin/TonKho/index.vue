<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--teal mb-3">
          <i class="bi bi-box-seam"></i>
          Kho va lo thuoc
        </div>
        <h2 class="page-section-title">Theo doi ton kho va lo thuoc</h2>
        <p class="page-section-copy mb-0">
          Nhan vien va admin co the xem tong ton theo thuoc, chi tiet tung lo, han su dung va so luong con lai tu
          database da noi san.
        </p>
      </div>

      <div class="soft-badge">
        <i class="bi bi-person-badge"></i>
        {{ currentUser?.ho_ten || "Tai khoan he thong" }}
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-6">
        <label class="form-label fw-semibold">Tim thuoc hoac so lo</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Nhap ten thuoc, ma thuoc hoac so lo"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-6">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary" @click="loadInventory" :disabled="loading.inventory">
            <span v-if="loading.inventory" class="spinner-border spinner-border-sm me-2"></span>
            Dong bo du lieu
          </button>
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tim kiem
          </button>
          <button class="btn btn-outline-secondary" @click="resetView">Lam moi</button>
        </div>
      </div>
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
    <div class="col-xl-7">
      <article class="content-card h-100">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
          <div>
            <h3 class="panel-title">Tong ton theo thuoc</h3>
            <p class="panel-subtitle mb-0">Chon mot thuoc de xem cac lo dang gan voi no.</p>
          </div>
          <span class="soft-badge soft-badge--blue">{{ thuocRows.length }} thuoc</span>
        </div>

        <div v-if="thuocRows.length" class="table-responsive">
          <table class="table table-master align-middle mb-0">
            <thead>
              <tr>
                <th>Thuoc</th>
                <th>Loai</th>
                <th>Gia ban</th>
                <th>Tong ton</th>
                <th>So lo</th>
                <th class="text-end">Xem lo</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="thuoc in thuocRows"
                :key="thuoc.ma_thuoc"
                :class="{ 'table-active': selectedThuoc?.ma_thuoc === thuoc.ma_thuoc }"
              >
                <td>
                  <div class="fw-semibold">{{ thuoc.ten_thuoc }}</div>
                  <div class="small text-secondary">{{ thuoc.ma_thuoc }}</div>
                </td>
                <td>{{ thuoc.loaiThuoc?.ten_loai || thuoc.loaiThuoc?.ten_loai_thuoc || "-" }}</td>
                <td>{{ formatCurrency(thuoc.gia_ban) }}</td>
                <td>{{ thuoc.tong_ton }}</td>
                <td>{{ thuoc.so_lo_count }}</td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary" @click="selectThuoc(thuoc)">Chi tiet lo</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chua co du lieu ton kho.</p>
          <p class="mb-0 text-secondary">Hay dong bo du lieu hoac tim kiem lai theo ten thuoc / so lo.</p>
        </div>
      </article>
    </div>

    <div class="col-xl-5">
      <article class="content-card h-100">
        <div class="mb-4">
          <h3 class="panel-title">Chi tiet lo thuoc</h3>
          <p class="panel-subtitle mb-0">
            {{ selectedThuoc ? `Dang hien lo cua ${selectedThuoc.ten_thuoc}` : "Chon mot thuoc o bang ben trai." }}
          </p>
        </div>

        <div v-if="selectedThuocLots.length" class="vstack gap-3">
          <article v-for="lo in selectedThuocLots" :key="lo.id_lo" class="inventory-lot-card">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
              <div>
                <div class="fw-bold">So lo {{ lo.so_lo }}</div>
                <div class="small text-secondary">ID lo: {{ lo.id_lo }}</div>
              </div>
              <span class="soft-badge" :class="lotBadgeClass(lo)">
                {{ lotStatus(lo) }}
              </span>
            </div>

            <div class="row g-3 small">
              <div class="col-6">
                <div class="text-secondary mb-1">So luong nhap</div>
                <div class="fw-semibold">{{ lo.so_luong_nhap }}</div>
              </div>
              <div class="col-6">
                <div class="text-secondary mb-1">So luong con</div>
                <div class="fw-semibold">{{ lo.so_luong_con }}</div>
              </div>
              <div class="col-6">
                <div class="text-secondary mb-1">Ngay san xuat</div>
                <div class="fw-semibold">{{ formatDate(lo.ngay_san_xuat) }}</div>
              </div>
              <div class="col-6">
                <div class="text-secondary mb-1">Han su dung</div>
                <div class="fw-semibold">{{ formatDate(lo.han_su_dung) }}</div>
              </div>
              <div class="col-12">
                <div class="text-secondary mb-1">Gia nhap</div>
                <div class="fw-semibold">{{ formatCurrency(lo.gia_nhap) }}</div>
              </div>
            </div>
          </article>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chua co lo nao duoc chon.</p>
          <p class="mb-0 text-secondary">Bam vao mot dong thuoc trong bang de xem danh sach lo va ton kho chi tiet.</p>
        </div>
      </article>
    </div>
  </section>
</template>

<script>
import { getLoThuocs, getThuocs, searchLoThuocs, searchThuocs } from "../../../api/inventoryApi";
import { getStoredUser } from "../../../lib/authStorage";
export default {
  data() {
    return {
      currentUser: getStoredUser(),
      keyword: "",
      thuocs: [],
      loThuocs: [],
      selectedThuoc: null,
      message: "",
      error: "",
      loading: {
        inventory: false,
        search: false,
      },
    };
  },
  computed: {
    thuocRows() {
      return this.thuocs
        .map((thuoc) => {
          const relatedLots = this.loThuocs.filter((lo) => lo.id_thuoc === thuoc.ma_thuoc);

          return {
            ...thuoc,
            tong_ton: relatedLots.reduce((sum, lo) => sum + Number(lo.so_luong_con || 0), 0),
            so_lo_count: relatedLots.length,
          };
        })
        .sort((a, b) => a.tong_ton - b.tong_ton);
    },
    selectedThuocLots() {
      if (!this.selectedThuoc) return [];

      return this.loThuocs
        .filter((lo) => lo.id_thuoc === this.selectedThuoc.ma_thuoc)
        .sort((a, b) => new Date(a.han_su_dung) - new Date(b.han_su_dung));
    },
    metrics() {
      const totalTon = this.loThuocs.reduce((sum, lo) => sum + Number(lo.so_luong_con || 0), 0);
      const sapHetHan = this.loThuocs.filter((lo) => this.lotStatus(lo) === "Sap het han").length;
      const ganHetTon = this.thuocRows.filter((thuoc) => thuoc.tong_ton > 0 && thuoc.tong_ton <= 20).length;

      return [
        {
          label: "Tong thuoc",
          value: this.thuocRows.length,
          note: "Dang co trong kho",
          deltaClass: "is-positive",
          icon: "bi bi-capsule-pill",
          iconClass: "metric-card__icon--blue",
        },
        {
          label: "Tong ton",
          value: totalTon,
          note: "Cong don tu cac lo",
          deltaClass: "is-positive",
          icon: "bi bi-box-seam",
          iconClass: "metric-card__icon--teal",
        },
        {
          label: "Lo sap het han",
          value: sapHetHan,
          note: "Can theo doi som",
          deltaClass: "is-warning",
          icon: "bi bi-exclamation-diamond",
          iconClass: "metric-card__icon--orange",
        },
        {
          label: "Thuoc gan het ton",
          value: ganHetTon,
          note: "Tong ton <= 20",
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

      return err?.message || "Khong the tai du lieu ton kho.";
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    formatDate(value) {
      if (!value) return "-";
      return new Intl.DateTimeFormat("vi-VN", { dateStyle: "short" }).format(new Date(value));
    },
    lotStatus(lo) {
      const today = new Date();
      const expiry = new Date(lo.han_su_dung);
      const diffDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

      if (lo.so_luong_con <= 0) return "Het ton";
      if (diffDays <= 30) return "Sap het han";
      return "On dinh";
    },
    lotBadgeClass(lo) {
      const status = this.lotStatus(lo);
      if (status === "Het ton") return "soft-badge--orange";
      if (status === "Sap het han") return "soft-badge--blue";
      return "soft-badge--teal";
    },
    selectThuoc(thuoc) {
      this.selectedThuoc = thuoc;
      this.message = `Dang hien ${this.selectedThuocLots.length} lo cua ${thuoc.ten_thuoc}.`;
      this.error = "";
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

        this.message = `Da dong bo ${this.thuocs.length} thuoc va ${this.loThuocs.length} lo thuoc.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.inventory = false;
      }
    },
    async handleSearch() {
      if (!this.keyword) return;

      this.loading.search = true;
      this.error = "";

      try {
        const [thuocData, loData] = await Promise.all([searchThuocs(this.keyword), searchLoThuocs(this.keyword)]);
        this.thuocs = thuocData;
        this.loThuocs = loData;
        this.selectedThuoc = null;
        this.message = `Tim thay ${this.thuocs.length} thuoc va ${this.loThuocs.length} lo phu hop.`;
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
    this.loadInventory();
  },
};
</script>

