<template>
  <div class="pc-homepage">
    <section class="pc-catalog-page">
      <div class="container-fluid pc-container">
        <div class="pc-catalog-shell">
          <aside class="pc-catalog-sidebar">
            <button
              v-for="section in catalogSections"
              :key="section.id"
              type="button"
              class="pc-catalog-sidebar__item"
              :class="{ active: activeSection.id === section.id }"
              @click="changeSection(section.id)"
            >
              {{ section.label }}
            </button>
          </aside>

          <div class="pc-catalog-content">
            <div class="pc-catalog-head">
              <div>
                <p class="pc-catalog-head__eyebrow">Danh mục khách hàng</p>
                <h1>{{ activeSection.label }}</h1>
                <p>{{ activeSection.description }}</p>
              </div>

              <div class="pc-catalog-head__search">
                <i class="bi bi-search"></i>
                <input
                  v-model.trim="keyword"
                  type="text"
                  placeholder="Tìm thuốc trong danh mục này..."
                  @keyup.enter="loadProducts"
                />
              </div>
            </div>

            <div class="pc-catalog-card-grid">
              <button
                v-for="card in activeSection.cards"
                :key="card.slug"
                type="button"
                class="pc-catalog-link-card"
                :class="{ active: activeCard.slug === card.slug }"
                @click="changeCard(card.slug)"
              >
                <span class="pc-catalog-link-card__icon" :class="card.tone">
                  <i :class="card.icon"></i>
                </span>
                <span class="pc-catalog-link-card__content">
                  <strong>{{ card.label }}</strong>
                  <small>{{ countProductsForCard(products, activeSection.id, card.slug) }} sản phẩm</small>
                </span>
              </button>
            </div>

            <div class="pc-catalog-toolbar">
              <div>
                <h2>{{ activeCard.label }}</h2>
                <p>{{ activeCard.description }}</p>
              </div>

              <button class="btn btn-outline-primary rounded-pill px-4" type="button" @click="loadProducts">
                Làm mới dữ liệu
              </button>
            </div>

            <div v-if="activeCard.symptoms?.length" class="pc-catalog-symptoms">
              <div class="pc-catalog-symptoms__label">Triệu chứng phù hợp</div>
              <div class="pc-catalog-symptoms__chips">
                <span v-for="symptom in activeCard.symptoms" :key="symptom">{{ symptom }}</span>
              </div>
            </div>

            <div v-if="catalogError" class="alert alert-danger mb-4">{{ catalogError }}</div>

            <div v-if="loadingProducts" class="pc-empty-card pc-empty-card--inline">
              <i class="bi bi-arrow-repeat"></i>
              <h2>Đang tải dữ liệu thuốc</h2>
              <p>Hệ thống đang đồng bộ danh sách thuốc từ cơ sở dữ liệu.</p>
            </div>

            <div v-else-if="filteredProducts.length" class="pc-product-grid">
              <article v-for="product in filteredProducts" :key="product.ma_thuoc" class="pc-product-card">
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
                  <button type="button" class="primary" @click="buyNow(product)">Chọn mua</button>
                </div>
              </article>
            </div>

            <div v-else class="pc-empty-card pc-empty-card--inline">
              <i class="bi bi-clipboard-x"></i>
              <h2>Chưa có sản phẩm phù hợp</h2>
              <p>Hãy thử đổi nhóm danh mục hoặc từ khóa tìm kiếm để xem thêm thuốc.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

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
              <div class="pc-modal-card__label">Triệu chứng thường gặp</div>
              <div class="pc-modal-card__chips">
                <span v-for="item in selectedProduct.trieu_chung" :key="item">{{ item }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Tác dụng phụ tham khảo</div>
              <ul class="pc-modal-card__list">
                <li v-for="item in selectedProduct.tac_dung_phu" :key="item">{{ item }}</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="pc-modal-card__actions">
          <button class="btn btn-outline-primary rounded-pill px-4" type="button" @click="selectedProduct = null">Đóng</button>
          <button class="btn btn-primary rounded-pill px-4" type="button" @click="buyNow(selectedProduct)">Chọn mua</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getCatalogThuoc, getCatalogThuocs } from "../api/catalogApi";
import {
  catalogSections,
  countProductsForCard,
  filterProductsForSection,
  getCatalogCard,
  getCatalogSection,
} from "../data/catalogSections";
import { useCustomerStore } from "../lib/customerStore";
import { showToast } from "../lib/toast";

const route = useRoute();
const router = useRouter();
const { addToCart } = useCustomerStore();

const keyword = ref("");
const products = ref([]);
const loadingProducts = ref(false);
const catalogError = ref("");
const selectedProduct = ref(null);

const activeSection = computed(() => getCatalogSection(route.params.sectionSlug));
const activeCard = computed(() => getCatalogCard(activeSection.value.id, route.params.categorySlug || "tat-ca"));
const filteredProducts = computed(() =>
  filterProductsForSection(products.value, activeSection.value.id, route.params.categorySlug || "tat-ca")
);

watch(
  () => [route.params.sectionSlug, route.params.categorySlug],
  () => {
    selectedProduct.value = null;
  }
);

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
  if (group.includes("mẹ") || group.includes("bé") || group.includes("me") || group.includes("be")) return "bi bi-heart-pulse";

  return "bi bi-capsule-pill";
}

function thumbTone(product) {
  const group = (product.loai_thuoc || "").toLowerCase();

  if (group.includes("vitamin")) return "tone-yellow";
  if (group.includes("da")) return "tone-green";
  if (group.includes("mẹ") || group.includes("bé") || group.includes("me") || group.includes("be")) return "tone-pink";

  return "tone-blue";
}

function changeSection(sectionId) {
  router.push(`/danh-muc/${sectionId}`);
}

function changeCard(cardSlug) {
  if (cardSlug === "tat-ca") {
    router.push(`/danh-muc/${activeSection.value.id}`);
    return;
  }

  router.push(`/danh-muc/${activeSection.value.id}/${cardSlug}`);
}

async function loadProducts() {
  loadingProducts.value = true;
  catalogError.value = "";

  try {
    const response = await getCatalogThuocs(keyword.value);
    products.value = response.data || [];
  } catch (error) {
    catalogError.value = error?.message || "Không tải được danh mục thuốc.";
  } finally {
    loadingProducts.value = false;
  }
}

async function viewProduct(product) {
  try {
    const response = await getCatalogThuoc(product.ma_thuoc);
    selectedProduct.value = response.data;
  } catch (error) {
    catalogError.value = error?.message || "Không tải được chi tiết sản phẩm.";
  }
}

function buyNow(product) {
  addToCart(product);
  selectedProduct.value = null;
  showToast("Bạn vừa thêm vào giỏ hàng thành công");
}

onMounted(loadProducts);
</script>
