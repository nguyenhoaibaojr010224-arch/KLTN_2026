<template>
  <div class="pc-page">
    <div class="container-fluid pc-container py-4">
      <section class="pc-search-results">
        <div class="pc-search-results__head">
          <div>
            <p class="pc-search-results__eyebrow">Kết quả tìm kiếm</p>
            <h1>
              {{ keyword ? `Thuốc phù hợp với "${keyword}"` : "Tìm thuốc theo nhu cầu sức khỏe" }}
            </h1>
            <p v-if="keyword">
              Tìm thấy {{ products.length }} thuốc phù hợp với từ khóa khách hàng vừa tìm.
            </p>
            <p v-else>
              Nhập từ khóa như thuốc ho, đau họng, vitamin, tiêu hóa để hệ thống gợi ý đúng nhóm thuốc.
            </p>
          </div>

          <div class="pc-search-results__box">
            <i class="bi bi-search"></i>
            <input
              v-model.trim="localKeyword"
              type="text"
              placeholder="Tìm thuốc, loại thuốc hoặc triệu chứng..."
              @keyup.enter="submitSearch"
            />
            <button class="btn btn-primary rounded-pill px-4" type="button" @click="submitSearch">
              Tìm kiếm
            </button>
          </div>
        </div>

        <div class="pc-search-results__chips">
          <button v-for="item in quickKeywords" :key="item" type="button" @click="pickKeyword(item)">
            {{ item }}
          </button>
        </div>

        <div v-if="catalogError" class="alert alert-danger mt-3">{{ catalogError }}</div>

        <div v-if="loading" class="pc-empty-card pc-empty-card--inline mt-4">
          <i class="bi bi-arrow-repeat"></i>
          <h2>Đang tìm thuốc phù hợp</h2>
          <p>Hệ thống đang tra cứu thuốc theo tên, loại thuốc, triệu chứng và hashtag liên quan.</p>
        </div>

        <div v-else-if="products.length" class="pc-product-grid mt-4">
          <article v-for="product in products" :key="product.ma_thuoc" class="pc-product-card">
            <div v-if="product.co_khuyen_mai" class="pc-product-card__badge">
              {{ product.khuyen_mai?.nhan_hien_thi || "Đang ưu đãi" }}
            </div>
            <div class="pc-product-card__image" :class="thumbTone(product)">
              <div class="pc-product-card__pill">{{ product.loai_thuoc || "Thuốc" }}</div>
              <i :class="thumbIcon(product)"></i>
            </div>
            <h3>{{ product.ten_thuoc }}</h3>
            <p>{{ product.mo_ta }}</p>
            <div class="pc-product-card__price">
              <strong>{{ formatCurrency(product.gia_ban) }}</strong>
              <span v-if="product.co_khuyen_mai">{{ formatCurrency(product.gia_niem_yet) }}</span>
            </div>
            <div class="pc-product-card__stock">Còn {{ product.so_luong_ton }} {{ product.don_vi_tinh }}</div>
            <div class="pc-product-card__actions">
              <button type="button" @click="viewProduct(product)">Xem chi tiết</button>
              <button type="button" class="primary" @click="addProduct(product)">Chọn mua</button>
            </div>
          </article>
        </div>

        <div v-else class="pc-empty-card pc-empty-card--inline mt-4">
          <i class="bi bi-search-heart"></i>
          <h2>Chưa tìm thấy thuốc phù hợp</h2>
          <p>Hãy thử từ khóa khác như thuốc ho, hạ sốt, đau họng, vitamin hoặc tiêu hóa.</p>
        </div>
      </section>
    </div>

    <div v-if="selectedProduct" class="pc-modal-backdrop" @click.self="selectedProduct = null">
      <div class="pc-modal-card">
        <button class="pc-modal-card__close" type="button" @click="selectedProduct = null">
          <i class="bi bi-x-lg"></i>
        </button>

        <div class="pc-modal-card__head">
          <div class="pc-modal-card__preview" :class="thumbTone(selectedProduct)">
            <i :class="thumbIcon(selectedProduct)"></i>
          </div>

          <div>
            <div class="pc-modal-card__code">{{ selectedProduct.ma_thuoc }}</div>
            <h3>{{ selectedProduct.ten_thuoc }}</h3>
            <p>{{ selectedProduct.nha_san_xuat || "Pharmacity Care" }}</p>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Mô tả sản phẩm</div>
              <p>{{ selectedProduct.mo_ta }}</p>
            </div>
          </div>

          <div class="col-md-6">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Thông tin nhanh</div>
              <ul class="pc-modal-card__list">
                <li><strong>Giá bán:</strong> {{ formatCurrency(selectedProduct.gia_ban) }}</li>
                <li><strong>Số lượng tồn:</strong> {{ selectedProduct.so_luong_ton }} {{ selectedProduct.don_vi_tinh }}</li>
                <li><strong>Hàm lượng:</strong> {{ selectedProduct.ham_luong || "Đang cập nhật" }}</li>
              </ul>
            </div>
          </div>

          <div class="col-md-6">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Triệu chứng phù hợp</div>
              <div class="pc-modal-card__chips">
                <span v-for="item in selectedProduct.trieu_chung" :key="item">{{ item }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Hashtag liên quan</div>
              <div class="pc-modal-card__chips">
                <span v-for="item in selectedProduct.hashtags" :key="item">{{ item }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="pc-modal-card__actions">
          <button class="btn btn-outline-primary rounded-pill px-4" type="button" @click="selectedProduct = null">Đóng</button>
          <button class="btn btn-primary rounded-pill px-4" type="button" @click="addProduct(selectedProduct)">Chọn mua</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getCatalogThuoc, getCatalogThuocs } from "../api/catalogApi";
import { useCustomerStore } from "../lib/customerStore";
import { saveRecentSearch } from "../lib/recentSearches";
import { showToast } from "../lib/toast";

const route = useRoute();
const router = useRouter();
const { addToCart } = useCustomerStore();

const keyword = ref((route.query.q || "").trim());
const localKeyword = ref(keyword.value);
const products = ref([]);
const loading = ref(false);
const catalogError = ref("");
const selectedProduct = ref(null);

const quickKeywords = [
  "thuốc ho",
  "đau họng",
  "hạ sốt",
  "tiêu hóa",
  "vitamin",
  "dị ứng",
];

watch(
  () => route.query.q,
  (value) => {
    keyword.value = (value || "").trim();
    localKeyword.value = keyword.value;
    selectedProduct.value = null;
    if (keyword.value) {
      saveRecentSearch(keyword.value);
    }
    void loadProducts();
  },
  { immediate: true }
);

function submitSearch() {
  const normalizedKeyword = localKeyword.value.trim();

  if (!normalizedKeyword) {
    return;
  }

  saveRecentSearch(normalizedKeyword);

  router.push({
    path: "/tim-kiem",
    query: { q: normalizedKeyword },
  });
}

function pickKeyword(nextKeyword) {
  localKeyword.value = nextKeyword;
  submitSearch();
}

async function loadProducts() {
  loading.value = true;
  catalogError.value = "";

  try {
    const response = await getCatalogThuocs(keyword.value);
    products.value = response.data || [];
  } catch (error) {
    catalogError.value = error?.message || "Không tải được kết quả tìm kiếm thuốc.";
    products.value = [];
  } finally {
    loading.value = false;
  }
}

async function viewProduct(product) {
  try {
    const response = await getCatalogThuoc(product.ma_thuoc);
    selectedProduct.value = response.data;
  } catch (error) {
    catalogError.value = error?.message || "Không tải được chi tiết thuốc.";
  }
}

function addProduct(product) {
  addToCart(product);
  selectedProduct.value = null;
  showToast("Bạn vừa thêm vào giỏ hàng thành công");
}

function formatCurrency(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function thumbIcon(product) {
  const group = (product.loai_thuoc || "").toLowerCase();

  if (group.includes("vitamin")) return "bi bi-sun";
  if (group.includes("da")) return "bi bi-droplet-half";
  if (group.includes("mắt")) return "bi bi-eye";
  if (group.includes("hô hấp") || group.includes("ho")) return "bi bi-lungs";

  return "bi bi-capsule-pill";
}

function thumbTone(product) {
  const group = (product.loai_thuoc || "").toLowerCase();

  if (group.includes("vitamin")) return "tone-yellow";
  if (group.includes("da")) return "tone-green";
  if (group.includes("hô hấp") || group.includes("ho")) return "tone-pink";

  return "tone-blue";
}
</script>

<style scoped>
.pc-search-results__head {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(340px, 420px);
  gap: 24px;
  align-items: end;
}

.pc-search-results__eyebrow {
  margin-bottom: 10px;
  color: #1652c5;
  font-size: 0.9rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.pc-search-results__head h1 {
  margin: 0;
  color: #243b5d;
  font-size: clamp(2rem, 3vw, 2.8rem);
  font-weight: 800;
}

.pc-search-results__head p {
  margin: 10px 0 0;
  color: #60738d;
  font-size: 1rem;
}

.pc-search-results__box {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 62px;
  padding: 10px 12px 10px 18px;
  border: 1px solid #d9e4ff;
  border-radius: 24px;
  background: #fff;
  box-shadow: 0 18px 40px rgba(15, 31, 79, 0.06);
}

.pc-search-results__box i {
  color: #6b7a90;
}

.pc-search-results__box input {
  flex: 1;
  border: 0;
  outline: 0;
  background: transparent;
  color: #243b5d;
}

.pc-search-results__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.pc-search-results__chips button {
  padding: 9px 16px;
  border: 1px solid #d9e4ff;
  border-radius: 999px;
  background: #fff;
  color: #1652c5;
  font-weight: 700;
}

@media (max-width: 991.98px) {
  .pc-search-results__head {
    grid-template-columns: 1fr;
  }

  .pc-search-results__box {
    min-height: 56px;
  }
}
</style>
