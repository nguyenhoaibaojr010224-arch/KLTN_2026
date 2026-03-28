<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--blue mb-3">
          <i class="bi bi-tags"></i>
          Gia va khuyen mai
        </div>
        <h2 class="page-section-title">Quan ly gia ban va chuong trinh uu dai</h2>
        <p class="page-section-copy mb-0">
          Admin va nhan vien co the doi gia thuoc, tao khuyen mai rieng va de trang khach hang tu dong hien dung gia.
        </p>
      </div>

      <div class="soft-badge">
        <i class="bi bi-person-workspace"></i>
        {{ currentUser?.ho_ten || "Tai khoan he thong" }}
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-6">
        <label class="form-label fw-semibold">Tim thuoc hoac khuyen mai</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Nhap ten thuoc, ma thuoc, ten khuyen mai"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-6">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary" @click="loadData" :disabled="loading.sync">
            <span v-if="loading.sync" class="spinner-border spinner-border-sm me-2"></span>
            Dong bo du lieu
          </button>
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tim kiem
          </button>
        </div>
      </div>
    </div>

    <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
    <div v-if="error" class="alert alert-danger mt-4 mb-0">{{ error }}</div>
  </section>

  <section class="row g-4">
    <div class="col-xl-7">
      <article class="content-card h-100">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
          <div>
            <h3 class="panel-title">Bang gia thuoc</h3>
            <p class="panel-subtitle mb-0">Chon mot thuoc de cap nhat gia ban va tao chuong trinh khuyen mai.</p>
          </div>
          <span class="soft-badge soft-badge--teal">{{ thuocs.length }} thuoc</span>
        </div>

        <div class="table-responsive">
          <table class="table table-master align-middle mb-0">
            <thead>
              <tr>
                <th>Thuoc</th>
                <th>Loai</th>
                <th>Gia niem yet</th>
                <th>Khuyen mai</th>
                <th class="text-end">Tac vu</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="thuoc in thuocs"
                :key="thuoc.ma_thuoc"
                :class="{ 'table-active': selectedThuoc?.ma_thuoc === thuoc.ma_thuoc }"
              >
                <td>
                  <div class="fw-semibold">{{ thuoc.ten_thuoc }}</div>
                  <div class="small text-secondary">{{ thuoc.ma_thuoc }}</div>
                </td>
                <td>{{ thuoc.loaiThuoc?.ten_loai || "-" }}</td>
                <td>{{ formatCurrency(thuoc.gia_ban) }}</td>
                <td>
                  <span v-if="latestPromotionLabel(thuoc)" class="soft-badge soft-badge--orange">
                    {{ latestPromotionLabel(thuoc) }}
                  </span>
                  <span v-else class="text-secondary">Chua co</span>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary" @click="selectThuoc(thuoc)">Chon</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>

    <div class="col-xl-5">
      <article class="content-card h-100">
        <div class="mb-4">
          <h3 class="panel-title">Cap nhat gia ban</h3>
          <p class="panel-subtitle mb-0">
            {{ selectedThuoc ? `Dang chinh gia cho ${selectedThuoc.ten_thuoc}` : "Chon thuoc tu bang ben trai." }}
          </p>
        </div>

        <div v-if="selectedThuoc" class="vstack gap-3">
          <div class="inventory-lot-card">
            <div class="small text-secondary mb-1">Ma thuoc</div>
            <div class="fw-semibold">{{ selectedThuoc.ma_thuoc }}</div>
          </div>

          <div>
            <label class="form-label fw-semibold">Gia ban moi</label>
            <input v-model.number="priceForm.gia_ban" type="number" min="1000" class="form-control" />
          </div>

          <button class="btn btn-primary" @click="savePrice" :disabled="loading.price">
            <span v-if="loading.price" class="spinner-border spinner-border-sm me-2"></span>
            Luu gia ban
          </button>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chua chon thuoc.</p>
          <p class="mb-0 text-secondary">Hay chon mot thuoc de mo form chinh gia ban.</p>
        </div>
      </article>
    </div>
  </section>

  <section class="row g-4 mt-1">
    <div class="col-xl-5">
      <article class="content-card h-100">
        <div class="mb-4">
          <h3 class="panel-title">Tao / sua khuyen mai</h3>
          <p class="panel-subtitle mb-0">Khuyen mai chi hien ben khach hang khi dang active va con trong thoi gian ap dung.</p>
        </div>

        <div class="vstack gap-3">
          <div>
            <label class="form-label fw-semibold">Thuoc ap dung</label>
            <select v-model="promoForm.ma_thuoc" class="form-select">
              <option value="">Chon thuoc</option>
              <option v-for="thuoc in thuocs" :key="thuoc.ma_thuoc" :value="thuoc.ma_thuoc">
                {{ thuoc.ma_thuoc }} - {{ thuoc.ten_thuoc }}
              </option>
            </select>
          </div>

          <div>
            <label class="form-label fw-semibold">Ten khuyen mai</label>
            <input v-model.trim="promoForm.ten_khuyen_mai" class="form-control" placeholder="Vi du: Deal hot cuoi tuan" />
          </div>

          <div>
            <label class="form-label fw-semibold">Nhan hien thi</label>
            <input v-model.trim="promoForm.nhan_hien_thi" class="form-control" placeholder="Vi du: Giam 15%" />
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Loai ap dung</label>
              <select v-model="promoForm.loai_ap_dung" class="form-select">
                <option value="phan_tram">Phan tram</option>
                <option value="so_tien">So tien</option>
                <option value="gia_co_dinh">Gia co dinh</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Gia tri</label>
              <input v-model.number="promoForm.gia_tri" type="number" min="1" class="form-control" />
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ngay bat dau</label>
              <input v-model="promoForm.ngay_bat_dau" type="datetime-local" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ngay ket thuc</label>
              <input v-model="promoForm.ngay_ket_thuc" type="datetime-local" class="form-control" />
            </div>
          </div>

          <div>
            <label class="form-label fw-semibold">Trang thai</label>
            <select v-model="promoForm.trang_thai" class="form-select">
              <option value="draft">Draft</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <div>
            <label class="form-label fw-semibold">Mo ta</label>
            <textarea v-model.trim="promoForm.mo_ta" rows="3" class="form-control" placeholder="Mo ta ngan cho chuong trinh"></textarea>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" @click="savePromotion" :disabled="loading.promo">
              <span v-if="loading.promo" class="spinner-border spinner-border-sm me-2"></span>
              {{ promoForm.id ? "Cap nhat khuyen mai" : "Tao khuyen mai" }}
            </button>
            <button class="btn btn-outline-secondary" @click="resetPromotionForm">Lam moi form</button>
          </div>
        </div>
      </article>
    </div>

    <div class="col-xl-7">
      <article class="content-card h-100">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
          <div>
            <h3 class="panel-title">Danh sach khuyen mai</h3>
            <p class="panel-subtitle mb-0">Danh sach nay la nguon hien thi cho gia va badge o trang khach hang.</p>
          </div>
          <span class="soft-badge soft-badge--blue">{{ promotions.length }} khuyen mai</span>
        </div>

        <div v-if="promotions.length" class="vstack gap-3">
          <article v-for="item in promotions" :key="item.id" class="inventory-lot-card">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
              <div>
                <div class="fw-bold">{{ item.ten_khuyen_mai }}</div>
                <div class="small text-secondary">{{ item.ma_thuoc }} - {{ item.ten_thuoc }}</div>
              </div>
              <span class="soft-badge" :class="promotionBadgeClass(item)">
                {{ item.trang_thai }}
              </span>
            </div>

            <div class="row g-3 small mb-3">
              <div class="col-md-6">
                <div class="text-secondary mb-1">Nhan hien thi</div>
                <div class="fw-semibold">{{ item.nhan_hien_thi || "-" }}</div>
              </div>
              <div class="col-md-6">
                <div class="text-secondary mb-1">Gia sau giam</div>
                <div class="fw-semibold">{{ formatCurrency(item.gia_sau_giam) }}</div>
              </div>
              <div class="col-md-6">
                <div class="text-secondary mb-1">Thoi gian</div>
                <div class="fw-semibold">{{ formatDateTime(item.ngay_bat_dau) }}</div>
              </div>
              <div class="col-md-6">
                <div class="text-secondary mb-1">Ket thuc</div>
                <div class="fw-semibold">{{ item.ngay_ket_thuc ? formatDateTime(item.ngay_ket_thuc) : "Khong gioi han" }}</div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <button class="btn btn-sm btn-outline-primary" @click="editPromotion(item)">Sua</button>
              <button class="btn btn-sm btn-outline-danger" @click="removePromotion(item.id)" :disabled="loading.deleteId === item.id">
                <span v-if="loading.deleteId === item.id" class="spinner-border spinner-border-sm me-2"></span>
                Xoa
              </button>
            </div>
          </article>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chua co khuyen mai nao.</p>
          <p class="mb-0 text-secondary">Tao mot khuyen mai o form ben trai de trang khach hang hien uu dai that.</p>
        </div>
      </article>
    </div>
  </section>
</template>

<script>
import { getThuocs, searchThuocs } from "../../../api/inventoryApi";
import { createKhuyenMai, deleteKhuyenMai, getKhuyenMais, updateKhuyenMai, updateThuocPrice } from "../../../api/pricingApi";
import { getStoredUser } from "../../../lib/authStorage";
export default {
  data() {
    return {
      currentUser: getStoredUser(),
      keyword: "",
      thuocs: [],
      promotions: [],
      selectedThuoc: null,
      message: "",
      error: "",
      loading: {
        sync: false,
        search: false,
        price: false,
        promo: false,
        deleteId: null,
      },
      priceForm: {
        gia_ban: null,
      },
      promoForm: {
        id: null,
        ma_thuoc: "",
        ten_khuyen_mai: "",
        mo_ta: "",
        loai_ap_dung: "phan_tram",
        gia_tri: 10,
        nhan_hien_thi: "",
        ngay_bat_dau: "",
        ngay_ket_thuc: "",
        trang_thai: "active",
      },
    };
  },
  methods: {
    toInputDateTime(value) {
      const date = new Date(value);
      const year = date.getFullYear();
      const month = `${date.getMonth() + 1}`.padStart(2, "0");
      const day = `${date.getDate()}`.padStart(2, "0");
      const hours = `${date.getHours()}`.padStart(2, "0");
      const minutes = `${date.getMinutes()}`.padStart(2, "0");

      return `${year}-${month}-${day}T${hours}:${minutes}`;
    },
    normalizeError(err) {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(" | ");
      }

      return err?.message || "Khong the xu ly du lieu gia va khuyen mai.";
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    formatDateTime(value) {
      if (!value) return "-";

      return new Intl.DateTimeFormat("vi-VN", {
        dateStyle: "short",
        timeStyle: "short",
      }).format(new Date(value));
    },
    latestPromotionLabel(thuoc) {
      const latest = (thuoc.khuyen_mais || [])[0];
      return latest?.nhan_hien_thi || latest?.ten_khuyen_mai || "";
    },
    selectThuoc(thuoc) {
      this.selectedThuoc = thuoc;
      this.priceForm.gia_ban = Number(thuoc.gia_ban || 0);
      this.promoForm.ma_thuoc = thuoc.ma_thuoc;
    },
    resetPromotionForm() {
      this.promoForm.id = null;
      this.promoForm.ma_thuoc = this.selectedThuoc?.ma_thuoc || "";
      this.promoForm.ten_khuyen_mai = "";
      this.promoForm.mo_ta = "";
      this.promoForm.loai_ap_dung = "phan_tram";
      this.promoForm.gia_tri = 10;
      this.promoForm.nhan_hien_thi = "";
      this.promoForm.ngay_bat_dau = this.toInputDateTime(new Date());
      this.promoForm.ngay_ket_thuc = "";
      this.promoForm.trang_thai = "active";
    },
    async loadData() {
      this.loading.sync = true;
      this.error = "";

      try {
        const [thuocData, promotionResponse] = await Promise.all([getThuocs(), getKhuyenMais()]);
        this.thuocs = thuocData;
        this.promotions = promotionResponse.data || [];
        this.message = `Da dong bo ${this.thuocs.length} thuoc va ${this.promotions.length} khuyen mai.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.sync = false;
      }
    },
    async handleSearch() {
      if (!this.keyword) return;

      this.loading.search = true;
      this.error = "";

      try {
        const [thuocData, promotionResponse] = await Promise.all([
          searchThuocs(this.keyword),
          getKhuyenMais(this.keyword),
        ]);

        this.thuocs = thuocData;
        this.promotions = promotionResponse.data || [];
        this.message = `Tim thay ${this.thuocs.length} thuoc va ${this.promotions.length} khuyen mai.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.search = false;
      }
    },
    async savePrice() {
      if (!this.selectedThuoc) return;

      this.loading.price = true;
      this.error = "";

      try {
        await updateThuocPrice(this.selectedThuoc.ma_thuoc, { gia_ban: this.priceForm.gia_ban });
        await this.loadData();
        this.message = `Da cap nhat gia ban cho ${this.selectedThuoc.ten_thuoc}.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.price = false;
      }
    },
    async savePromotion() {
      this.loading.promo = true;
      this.error = "";

      try {
        const payload = {
          ma_thuoc: this.promoForm.ma_thuoc,
          ten_khuyen_mai: this.promoForm.ten_khuyen_mai,
          mo_ta: this.promoForm.mo_ta,
          loai_ap_dung: this.promoForm.loai_ap_dung,
          gia_tri: this.promoForm.gia_tri,
          nhan_hien_thi: this.promoForm.nhan_hien_thi,
          ngay_bat_dau: new Date(this.promoForm.ngay_bat_dau).toISOString().slice(0, 19).replace("T", " "),
          ngay_ket_thuc: this.promoForm.ngay_ket_thuc
            ? new Date(this.promoForm.ngay_ket_thuc).toISOString().slice(0, 19).replace("T", " ")
            : null,
          trang_thai: this.promoForm.trang_thai,
        };

        if (this.promoForm.id) {
          await updateKhuyenMai(this.promoForm.id, payload);
          this.message = "Da cap nhat khuyen mai.";
        } else {
          await createKhuyenMai(payload);
          this.message = "Da tao khuyen mai moi.";
        }

        this.resetPromotionForm();
        await this.loadData();
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.promo = false;
      }
    },
    editPromotion(item) {
      this.promoForm.id = item.id;
      this.promoForm.ma_thuoc = item.ma_thuoc;
      this.promoForm.ten_khuyen_mai = item.ten_khuyen_mai;
      this.promoForm.mo_ta = item.mo_ta || "";
      this.promoForm.loai_ap_dung = item.loai_ap_dung;
      this.promoForm.gia_tri = item.gia_tri;
      this.promoForm.nhan_hien_thi = item.nhan_hien_thi || "";
      this.promoForm.ngay_bat_dau = item.ngay_bat_dau ? this.toInputDateTime(item.ngay_bat_dau) : this.toInputDateTime(new Date());
      this.promoForm.ngay_ket_thuc = item.ngay_ket_thuc ? this.toInputDateTime(item.ngay_ket_thuc) : "";
      this.promoForm.trang_thai = item.trang_thai;
    },
    async removePromotion(id) {
      this.loading.deleteId = id;
      this.error = "";

      try {
        await deleteKhuyenMai(id);
        await this.loadData();
        this.message = "Da xoa khuyen mai.";
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.deleteId = null;
      }
    },
    promotionBadgeClass(item) {
      if (item.trang_thai === "active") return "soft-badge--teal";
      if (item.trang_thai === "draft") return "soft-badge--blue";
      return "soft-badge--orange";
    },
  },
  mounted() {
    this.promoForm.ngay_bat_dau = this.toInputDateTime(new Date());
    this.loadData();
  },
};
</script>

