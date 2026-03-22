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

<script setup>
import { onMounted, reactive, ref } from "vue";
import { getThuocs, searchThuocs } from "../api/inventoryApi";
import { createKhuyenMai, deleteKhuyenMai, getKhuyenMais, updateKhuyenMai, updateThuocPrice } from "../api/pricingApi";
import { getStoredUser } from "../lib/authStorage";

const currentUser = ref(getStoredUser());
const keyword = ref("");
const thuocs = ref([]);
const promotions = ref([]);
const selectedThuoc = ref(null);
const message = ref("");
const error = ref("");

const loading = reactive({
  sync: false,
  search: false,
  price: false,
  promo: false,
  deleteId: null,
});

const priceForm = reactive({
  gia_ban: null,
});

const promoForm = reactive({
  id: null,
  ma_thuoc: "",
  ten_khuyen_mai: "",
  mo_ta: "",
  loai_ap_dung: "phan_tram",
  gia_tri: 10,
  nhan_hien_thi: "",
  ngay_bat_dau: toInputDateTime(new Date()),
  ngay_ket_thuc: "",
  trang_thai: "active",
});

function toInputDateTime(value) {
  const date = new Date(value);
  const year = date.getFullYear();
  const month = `${date.getMonth() + 1}`.padStart(2, "0");
  const day = `${date.getDate()}`.padStart(2, "0");
  const hours = `${date.getHours()}`.padStart(2, "0");
  const minutes = `${date.getMinutes()}`.padStart(2, "0");

  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function normalizeError(err) {
  if (err?.payload?.errors) {
    return Object.values(err.payload.errors).flat().join(" | ");
  }

  return err?.message || "Khong the xu ly du lieu gia va khuyen mai.";
}

function formatCurrency(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function formatDateTime(value) {
  if (!value) return "-";

  return new Intl.DateTimeFormat("vi-VN", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(new Date(value));
}

function latestPromotionLabel(thuoc) {
  const latest = (thuoc.khuyen_mais || [])[0];
  return latest?.nhan_hien_thi || latest?.ten_khuyen_mai || "";
}

function selectThuoc(thuoc) {
  selectedThuoc.value = thuoc;
  priceForm.gia_ban = Number(thuoc.gia_ban || 0);
  promoForm.ma_thuoc = thuoc.ma_thuoc;
}

function resetPromotionForm() {
  promoForm.id = null;
  promoForm.ma_thuoc = selectedThuoc.value?.ma_thuoc || "";
  promoForm.ten_khuyen_mai = "";
  promoForm.mo_ta = "";
  promoForm.loai_ap_dung = "phan_tram";
  promoForm.gia_tri = 10;
  promoForm.nhan_hien_thi = "";
  promoForm.ngay_bat_dau = toInputDateTime(new Date());
  promoForm.ngay_ket_thuc = "";
  promoForm.trang_thai = "active";
}

async function loadData() {
  loading.sync = true;
  error.value = "";

  try {
    const [thuocData, promotionResponse] = await Promise.all([getThuocs(), getKhuyenMais()]);
    thuocs.value = thuocData;
    promotions.value = promotionResponse.data || [];
    message.value = `Da dong bo ${thuocs.value.length} thuoc va ${promotions.value.length} khuyen mai.`;
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.sync = false;
  }
}

async function handleSearch() {
  if (!keyword.value) return;

  loading.search = true;
  error.value = "";

  try {
    const [thuocData, promotionResponse] = await Promise.all([
      searchThuocs(keyword.value),
      getKhuyenMais(keyword.value),
    ]);

    thuocs.value = thuocData;
    promotions.value = promotionResponse.data || [];
    message.value = `Tim thay ${thuocs.value.length} thuoc va ${promotions.value.length} khuyen mai.`;
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.search = false;
  }
}

async function savePrice() {
  if (!selectedThuoc.value) return;

  loading.price = true;
  error.value = "";

  try {
    await updateThuocPrice(selectedThuoc.value.ma_thuoc, { gia_ban: priceForm.gia_ban });
    await loadData();
    message.value = `Da cap nhat gia ban cho ${selectedThuoc.value.ten_thuoc}.`;
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.price = false;
  }
}

async function savePromotion() {
  loading.promo = true;
  error.value = "";

  try {
    const payload = {
      ma_thuoc: promoForm.ma_thuoc,
      ten_khuyen_mai: promoForm.ten_khuyen_mai,
      mo_ta: promoForm.mo_ta,
      loai_ap_dung: promoForm.loai_ap_dung,
      gia_tri: promoForm.gia_tri,
      nhan_hien_thi: promoForm.nhan_hien_thi,
      ngay_bat_dau: new Date(promoForm.ngay_bat_dau).toISOString().slice(0, 19).replace("T", " "),
      ngay_ket_thuc: promoForm.ngay_ket_thuc
        ? new Date(promoForm.ngay_ket_thuc).toISOString().slice(0, 19).replace("T", " ")
        : null,
      trang_thai: promoForm.trang_thai,
    };

    if (promoForm.id) {
      await updateKhuyenMai(promoForm.id, payload);
      message.value = "Da cap nhat khuyen mai.";
    } else {
      await createKhuyenMai(payload);
      message.value = "Da tao khuyen mai moi.";
    }

    resetPromotionForm();
    await loadData();
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.promo = false;
  }
}

function editPromotion(item) {
  promoForm.id = item.id;
  promoForm.ma_thuoc = item.ma_thuoc;
  promoForm.ten_khuyen_mai = item.ten_khuyen_mai;
  promoForm.mo_ta = item.mo_ta || "";
  promoForm.loai_ap_dung = item.loai_ap_dung;
  promoForm.gia_tri = item.gia_tri;
  promoForm.nhan_hien_thi = item.nhan_hien_thi || "";
  promoForm.ngay_bat_dau = item.ngay_bat_dau ? toInputDateTime(item.ngay_bat_dau) : toInputDateTime(new Date());
  promoForm.ngay_ket_thuc = item.ngay_ket_thuc ? toInputDateTime(item.ngay_ket_thuc) : "";
  promoForm.trang_thai = item.trang_thai;
}

async function removePromotion(id) {
  loading.deleteId = id;
  error.value = "";

  try {
    await deleteKhuyenMai(id);
    await loadData();
    message.value = "Da xoa khuyen mai.";
  } catch (err) {
    error.value = normalizeError(err);
  } finally {
    loading.deleteId = null;
  }
}

function promotionBadgeClass(item) {
  if (item.trang_thai === "active") return "soft-badge--teal";
  if (item.trang_thai === "draft") return "soft-badge--blue";
  return "soft-badge--orange";
}

onMounted(loadData);
</script>
