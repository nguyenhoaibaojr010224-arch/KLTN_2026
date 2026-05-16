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
                  placeholder="Tìm toàn bộ thuốc liên quan..."
                  @keyup.enter="submitCatalogSearch"
                />
              </div>
            </div>

            <div v-if="activeThuocParentCard" class="pc-catalog-parent-banner">
              <div class="pc-catalog-parent-banner__icon" :class="activeThuocParentCard.tone">
                <i :class="activeThuocParentCard.icon"></i>
              </div>
              <div class="pc-catalog-parent-banner__content">
                <p class="pc-catalog-parent-banner__eyebrow">Nhóm thuốc đang xem</p>
                <h3>{{ activeThuocParentCard.label }}</h3>
                <p>{{ activeThuocParentCard.description }}</p>
              </div>
              <button type="button" class="pc-catalog-parent-banner__back" @click="changeSection('thuoc')">
                Quay lại nhóm thuốc
              </button>
            </div>

            <div
              v-if="visibleCards.length"
              class="pc-catalog-card-grid"
              :class="{ 'pc-catalog-card-grid--three': isThuocChildView }"
            >
              <button
                v-for="card in visibleCards"
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

            <div v-if="showCardToolbar" class="pc-catalog-toolbar">
              <div class="pc-catalog-toolbar__intro">
                <span class="pc-catalog-toolbar__badge">Nhóm thuốc phù hợp</span>
                <h2>{{ activeCard.label }}</h2>
                <p>{{ activeCard.description }}</p>
              </div>
            </div>

            <div v-if="showCardToolbar && activeCard.symptoms && activeCard.symptoms.length" class="pc-catalog-symptoms">
              <div class="pc-catalog-symptoms__head">
                <h3>Triệu chứng phù hợp</h3>
                <p>Chọn đúng nhóm để xem nhanh những thuốc thường dùng cho triệu chứng này.</p>
              </div>
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

            <div v-else-if="filteredProducts.length">
              <div class="pc-product-grid">
                <article
                  v-for="product in displayedProducts"
                  :key="product.ma_thuoc"
                  class="pc-product-card"
                  role="button"
                  tabindex="0"
                  @click="viewProduct(product)"
                  @keydown.enter.prevent="viewProduct(product)"
                  @keydown.space.prevent="viewProduct(product)"
                >
                <div v-if="product.co_khuyen_mai" class="pc-product-card__badge">
                  {{ promotionBadgeLabel(product) }}
                </div>
                <div class="pc-product-card__media-slot">
                  <div class="pc-product-card__image" :class="thumbTone(product)">
                    <div class="pc-product-card__pill">{{ product.loai_thuoc || 'Thuốc' }}</div>
                    <img
                      v-if="product.hinh_anh_url"
                      :src="product.hinh_anh_url"
                      :alt="product.ten_thuoc"
                      style="width: 100%; height: 100%; object-fit: contain; display: block; border-radius: inherit"
                    />
                    <i v-else :class="thumbIcon(product)"></i>
                  </div>
                </div>
                <div class="pc-product-card__body-slot">
                  <h3><span>{{ product.ten_thuoc }}</span></h3>
                  <p><span>{{ product.mo_ta }}</span></p>
                  <div v-if="!isPrescriptionProduct(product)" class="pc-product-card__price">
                    <strong>{{ formatCurrency(product.gia_ban) }}</strong>
                    <span v-if="product.co_khuyen_mai">{{ formatCurrency(product.gia_niem_yet) }}</span>
                  </div>
                  <div class="pc-product-card__stock">Còn {{ resolveProductStock(product) }} {{ resolveProductUnitLabel(product) }}</div>
                  <div v-if="isPrescriptionProduct(product)" class="pc-product-card__stock pc-product-card__stock--prescription">Cần tư vấn dược sĩ</div>
                  <div class="pc-product-card__actions">
                    <button type="button" @click.stop="viewProduct(product)">Xem chi tiết</button>
                    <button type="button" class="primary" @click.stop="isPrescriptionProduct(product) ? requestPharmacistConsult(product) : buyNow(product)">
                      {{ isPrescriptionProduct(product) ? 'Tư vấn ngay' : 'Chọn mua' }}
                    </button>
                  </div>
                </div>
                </article>
              </div>

              <div v-if="shouldShowProductToggle" class="pc-catalog-products__toggle">
                <button type="button" class="pc-catalog-products__toggle-button" @click="toggleProductPreview">
                  {{ showAllProducts ? 'Thu gọn' : 'Xem tất cả' }}
                </button>
              </div>
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

    <div v-if="selectedProduct" class="pc-modal-backdrop" @click.self="closeSelectedProduct()">
      <div class="pc-modal-card">
        <button class="pc-modal-card__close" type="button" @click="closeSelectedProduct()">
          <i class="bi bi-x-lg"></i>
        </button>

        <div class="pc-modal-card__head">
          <div class="pc-modal-card__preview" :class="thumbTone(selectedProduct)">
            <img
              v-if="selectedProduct.hinh_anh_url"
              :src="selectedProduct.hinh_anh_url"
              :alt="selectedProduct.ten_thuoc"
              style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: inherit"
            />
            <i v-else :class="thumbIcon(selectedProduct)"></i>
          </div>

          <div>
            <div class="pc-modal-card__code">{{ selectedProduct.ma_thuoc }}</div>
            <h3>{{ selectedProduct.ten_thuoc }}</h3>
            <p class="mb-1">{{ selectedProduct.nha_san_xuat || 'PharmaGo Care' }}</p>
            <div class="pc-modal-card__meta">
              <div v-if="selectedProductUnitOptions.length > 1" class="pc-modal-card__variant">
                <label class="pc-modal-card__variant-label">Phân loại sản phẩm</label>
                <ProductVariantSelect
                  v-model="selectedProductUnit"
                  :options="selectedProductUnitOptions"
                  class="pc-modal-card__variant-select"
                />
              </div>
              <div v-if="!isPrescriptionProduct(selectedProduct)" class="pc-modal-card__price">
                <div v-if="selectedProduct.co_khuyen_mai" class="pc-modal-card__promo-badge">
                  {{ promotionBadgeLabel(selectedProduct) }}
                </div>
                <div class="pc-modal-card__price-label">Giá bán</div>
                <div class="pc-modal-card__price-value">{{ formatCurrency(selectedProductUnitOption?.gia_ban || selectedProduct.gia_ban) }}</div>
                <div v-if="selectedProductOriginalPrice > selectedProductCurrentPrice" class="pc-modal-card__price-original">
                  {{ formatCurrency(selectedProductOriginalPrice) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Mô tả sản phẩm</div>
              <p :class="['pc-modal-card__description', { 'pc-modal-card__description--expanded': isSelectedProductDescriptionExpanded }]">{{ selectedProduct.mo_ta }}</p>
              <button
                v-if="shouldShowSelectedProductDescriptionToggle(selectedProduct)"
                class="pc-modal-card__description-toggle"
                type="button"
                @click="toggleSelectedProductDescription()"
              >
                {{ isSelectedProductDescriptionExpanded ? 'Thu gọn' : 'Xem tất cả' }}
              </button>
            </div>
          </div>

          <div class="col-md-6">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Thông tin nhanh</div>
              <ul class="pc-modal-card__list">
                <li><strong>Số lượng còn:</strong> {{ resolveProductStock(selectedProduct, selectedProductUnit) }} {{ resolveProductUnitLabel(selectedProduct, selectedProductUnit) }}</li>
                <li><strong>Hàm lượng:</strong> {{ selectedProduct.ham_luong || 'Đang cập nhật' }}</li>
              </ul>
            </div>
          </div>

          <div v-if="isPrescriptionProduct(selectedProduct)" class="col-12">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Thuốc kê đơn</div>
              <p class="mb-0">Cần tư vấn dược sĩ trước khi mua sản phẩm này.</p>
            </div>
          </div>

          <div class="col-12">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Liều lượng</div>
              <ul class="pc-modal-card__list">
                <li v-for="item in selectedProduct.lieu_luong_items" :key="item">{{ item }}</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="pc-modal-card__actions">
          <button class="btn btn-outline-primary rounded-pill px-4" type="button" @click="closeSelectedProduct()">Đóng</button>
          <button
            class="btn btn-primary rounded-pill px-4"
            type="button"
            @click="isPrescriptionProduct(selectedProduct) ? requestPharmacistConsult(selectedProduct) : buyNow(selectedProduct, selectedProductUnit)"
          >
            {{ isPrescriptionProduct(selectedProduct) ? 'Tư vấn ngay' : 'Chọn mua' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getCatalogThuoc, getCatalogThuocs } from '../../../api/catalogApi';
import {
  catalogSections,
  countProductsForCard,
  filterProductsForSection,
  getCatalogCard,
  getCatalogCardsForView,
  getCatalogSection,
} from '../../../data/catalogSections';
import { buildPrescriptionConsultMessage, isPrescriptionProduct as isPrescriptionProductFlag } from '../../../lib/prescriptionProducts';
import { useCustomerStore } from '../../../lib/customerStore';
import { isAuthenticated } from '../../../lib/authStorage';
import { normalizeApiError } from '../../../lib/errorMessages';
import { applyProductUnitSelection, getDefaultProductUnit, getProductUnitLabel, getProductUnitOption, getProductUnitOptions, getProductUnitStock } from '../../../lib/productUnits';
import { openSupportChat } from '../../../lib/supportChatEvents';
import { showToast } from '../../../lib/toast';
import ProductVariantSelect from '../ProductVariantSelect.vue';

export default {
  name: 'DanhMucClient',

  components: {
    ProductVariantSelect,
  },

  data() {
    return {
      catalogSections,
      customerStore: useCustomerStore(),
      keyword: '',
      products: [],
      showAllProducts: false,
      productPreviewLimit: 15,
      loadingProducts: false,
      catalogError: '',
      selectedProduct: null,
      selectedProductUnit: '',
      isSelectedProductDescriptionExpanded: false,
    };
  },

  computed: {
    activeSection() {
      return getCatalogSection(this.$route.params.sectionSlug);
    },

    activeCard() {
      return getCatalogCard(this.activeSection.id, this.$route.params.categorySlug || 'tat-ca');
    },

    activeThuocParentCard() {
      if (this.activeSection.id !== 'thuoc') {
        return null;
      }

      const currentSlug = this.$route.params.categorySlug || 'tat-ca';

      const directParent = this.activeSection.cards.find(
        (card) => card.slug === currentSlug && Array.isArray(card.children) && card.children.length
      );

      if (directParent) {
        return directParent;
      }

      return (
        this.activeSection.cards.find((card) =>
          Array.isArray(card.children) ? card.children.some((child) => child.slug === currentSlug) : false
        ) || null
      );
    },

    isThuocChildView() {
      return Boolean(this.activeThuocParentCard);
    },

    visibleCards() {
      if (this.activeThuocParentCard) {
        return (this.activeThuocParentCard.children || []).filter((card) => card.slug !== 'tat-ca');
      }

      return getCatalogCardsForView(this.activeSection.id, this.$route.params.categorySlug || 'tat-ca').filter(
        (card) => card.slug !== 'tat-ca'
      );
    },

    filteredProducts() {
      return filterProductsForSection(
        this.products,
        this.activeSection.id,
        this.$route.params.categorySlug || 'tat-ca'
      );
    },

    displayedProducts() {
      if (this.showAllProducts) {
        return this.filteredProducts;
      }

      return this.filteredProducts.slice(0, this.productPreviewLimit);
    },

    shouldShowProductToggle() {
      return this.filteredProducts.length > this.productPreviewLimit;
    },

    showCardToolbar() {
      return this.activeCard?.slug !== 'tat-ca';
    },

    selectedProductUnitOptions() {
      return getProductUnitOptions(this.selectedProduct);
    },

    selectedProductUnitOption() {
      return getProductUnitOption(this.selectedProduct, this.selectedProductUnit);
    },

    selectedProductCurrentPrice() {
      return Number(this.selectedProductUnitOption?.gia_ban || this.selectedProduct?.gia_ban || 0);
    },

    selectedProductOriginalPrice() {
      return Number(this.selectedProductUnitOption?.gia_niem_yet || this.selectedProduct?.gia_niem_yet || 0);
    },
  },

  watch: {
    '$route.params.sectionSlug'() {
      this.selectedProduct = null;
      this.showAllProducts = false;
    },
    '$route.params.categorySlug'() {
      this.selectedProduct = null;
      this.showAllProducts = false;
    },
    keyword() {
      this.showAllProducts = false;
    },
  },

  mounted() {
    this.loadProducts();
  },

  methods: {
    countProductsForCard,

    formatCurrency(value) {
      const n = Number(value || 0);
      if (Math.abs(n) >= 1e9) {
        const ty = n / 1e9;
        return (Math.abs(ty) >= 10 ? Math.round(ty) : ty.toFixed(2).replace('.', ',')) + ' tỷ đ';
      }
      return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 }).format(n);
    },

    thumbIcon(product) {
      const group = (product.nhan || product.loai_thuoc || '').toLowerCase();

      if (group.includes('vitamin')) return 'bi bi-sun';
      if (group.includes('da')) return 'bi bi-droplet-half';
      if (group.includes('mẹ') || group.includes('bé') || group.includes('me') || group.includes('be')) return 'bi bi-heart-pulse';

      return 'bi bi-capsule-pill';
    },

    thumbTone(product) {
      const group = (product.nhan || product.loai_thuoc || '').toLowerCase();

      if (group.includes('vitamin')) return 'tone-yellow';
      if (group.includes('da')) return 'tone-green';
      if (group.includes('mẹ') || group.includes('bé') || group.includes('me') || group.includes('be')) return 'tone-pink';

      return 'tone-blue';
    },

    isPrescriptionProduct(product) {
      return isPrescriptionProductFlag(product);
    },

    discountPercent(product) {
      const base = Number(product?.gia_niem_yet || 0);
      const current = Number(product?.gia_ban || 0);

      if (!base || current >= base) {
        return 0;
      }

      return Math.round(((base - current) / base) * 100);
    },

    promotionBadgeLabel(product) {
      const customLabel = String(product?.khuyen_mai?.nhan_hien_thi || product?.khuyen_mai_nhan_hien_thi || '').trim();

      if (customLabel) {
        return customLabel;
      }

      const promotionType = String(product?.khuyen_mai?.loai_ap_dung || '').trim();
      const promotionValue = Number(product?.khuyen_mai?.gia_tri || 0);

      if (promotionType === 'phan_tram' && promotionValue > 0) {
        return `-${promotionValue}%`;
      }

      const discount = this.discountPercent(product);
      return discount > 0 ? `-${discount}%` : 'Đang ưu đãi';
    },

    changeSection(sectionId) {
      this.$router.push(`/danh-muc/${sectionId}`);
    },

    changeCard(cardSlug) {
      if (this.activeSection.id === 'thuoc' && cardSlug === 'tat-ca') {
        this.$router.push('/danh-muc/thuoc');
        return;
      }

      if (cardSlug === 'tat-ca') {
        this.$router.push(`/danh-muc/${this.activeSection.id}`);
        return;
      }

      this.$router.push(`/danh-muc/${this.activeSection.id}/${cardSlug}`);
    },

    submitCatalogSearch() {
      const normalizedKeyword = String(this.keyword || '').trim();

      if (!normalizedKeyword) {
        this.loadProducts();
        return;
      }

      this.$router.push({
        path: '/tim-kiem',
        query: { q: normalizedKeyword },
      });
    },

    toggleProductPreview() {
      this.showAllProducts = !this.showAllProducts;
    },

    async loadProducts() {
      this.loadingProducts = true;
      this.catalogError = '';
      this.showAllProducts = false;

      try {
        const response = await getCatalogThuocs(this.keyword);
        this.products = response.data || [];
      } catch (error) {
        this.catalogError = normalizeApiError(error, 'Không tải được danh mục thuốc.');
      } finally {
        this.loadingProducts = false;
      }
    },

    async viewProduct(product) {
      try {
        const response = await getCatalogThuoc(product.ma_thuoc);
        this.selectedProduct = response.data;
        this.selectedProductUnit = getDefaultProductUnit(response.data);
        this.isSelectedProductDescriptionExpanded = false;
      } catch (error) {
        this.catalogError = normalizeApiError(error, 'Không tải được chi tiết sản phẩm.');
      }
    },

    closeSelectedProduct() {
      this.selectedProduct = null;
      this.selectedProductUnit = '';
      this.isSelectedProductDescriptionExpanded = false;
    },

    shouldShowSelectedProductDescriptionToggle(product) {
      return String(product?.mo_ta || '').trim().length > 160;
    },

    toggleSelectedProductDescription() {
      this.isSelectedProductDescriptionExpanded = !this.isSelectedProductDescriptionExpanded;
    },

    resolveProductStock(product, selectedUnit = '') {
      return getProductUnitStock(product, selectedUnit);
    },

    resolveProductUnitLabel(product, selectedUnit = '') {
      return getProductUnitLabel(product, selectedUnit);
    },

    resolveProductForCart(product, selectedUnit = '') {
      return applyProductUnitSelection(product, selectedUnit || getDefaultProductUnit(product));
    },

    requireLoginForPurchase() {
      if (isAuthenticated()) {
        return false;
      }

      const redirectPath = this.$route.fullPath;
      this.selectedProduct = null;
      this.selectedProductUnit = '';
      this.isSelectedProductDescriptionExpanded = false;
      this.$router.push({
        path: '/login',
        query: { redirect: redirectPath },
      });

      return true;
    },

    requestPharmacistConsult(product) {
      if (!product) {
        return;
      }

      openSupportChat({
        message: buildPrescriptionConsultMessage(product),
        ma_thuoc: product.ma_thuoc,
      });
    },

    buyNow(product, selectedUnit = '') {
      if (this.isPrescriptionProduct(product)) {
        this.requestPharmacistConsult(product);
        return;
      }

      if (this.requireLoginForPurchase()) {
        return;
      }

      this.customerStore.addToCart(this.resolveProductForCart(product, selectedUnit));
      this.closeSelectedProduct();
      showToast('Bạn vừa thêm vào giỏ hàng thành công');
    },
  },
};
</script>

<style scoped>
.pc-modal-card__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 16px 22px;
  margin-top: 14px;
}

.pc-modal-card__variant {
  min-width: 220px;
}

.pc-modal-card__variant-label,
.pc-modal-card__price-label {
  display: block;
  margin-bottom: 8px;
  color: #6980a7;
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.pc-modal-card__variant-select {
  width: 100%;
}

.pc-modal-card__price {
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  min-height: 52px;
}

.pc-modal-card__price-value {
  color: #162a49;
  font-size: clamp(1.55rem, 2vw, 2rem);
  font-weight: 800;
  line-height: 1.05;
}

.pc-modal-card__promo-badge {
  align-self: flex-start;
  margin-bottom: 8px;
  padding: 7px 12px;
  border-radius: 999px;
  background: #eb3030;
  color: #fff;
  font-size: 0.82rem;
  font-weight: 800;
  line-height: 1;
}

.pc-modal-card__price-original {
  margin-top: 8px;
  color: #8b9ab5;
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.1;
  text-decoration: line-through;
}

.pc-modal-card__section p,
.pc-product-card p {
  white-space: pre-line;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.pc-modal-card__description {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  overflow: hidden;
  margin-bottom: 0;
  white-space: pre-line;
  word-break: break-word;
  overflow-wrap: anywhere;
}

.pc-modal-card__description--expanded {
  display: block;
  overflow: visible;
  white-space: pre-line;
}

.pc-modal-card__description-toggle {
  margin-top: 10px;
  padding: 0;
  border: 0;
  background: transparent;
  color: #1652c5;
  font-size: 0.92rem;
  font-weight: 700;
}

.pc-catalog-head__eyebrow,
.pc-catalog-head > div > p {
  display: none;
}

.pc-catalog-parent-banner {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 16px;
  margin-bottom: 18px;
  padding: 18px 20px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 20px;
  background: #f8fbff;
}

.pc-catalog-parent-banner__icon {
  width: 64px;
  height: 64px;
  display: grid;
  place-items: center;
  border-radius: 20px;
  font-size: 1.6rem;
}

.pc-catalog-parent-banner__content {
  min-width: 0;
}

.pc-catalog-parent-banner__eyebrow {
  margin: 0 0 6px;
  color: #6e84aa;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.pc-catalog-parent-banner__content h3 {
  margin: 0 0 6px;
  color: #243b5d;
  font-size: 1.15rem;
  font-weight: 800;
}

.pc-catalog-parent-banner__content p:last-child {
  margin: 0;
  color: #5b7397;
}

.pc-catalog-parent-banner__back {
  padding: 10px 16px;
  border: 1px solid rgba(22, 82, 197, 0.18);
  border-radius: 999px;
  background: #fff;
  color: #1652c5;
  font-size: 0.88rem;
  font-weight: 700;
}

.pc-catalog-card-grid--three {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.pc-catalog-products__toggle {
  display: flex;
  justify-content: center;
  margin-top: 18px;
}

.pc-catalog-products__toggle-button {
  padding: 10px 18px;
  border: 1px solid rgba(22, 82, 197, 0.18);
  border-radius: 999px;
  background: #fff;
  color: #1652c5;
  font-size: 0.92rem;
  font-weight: 700;
}

@media (max-width: 1199px) {
  .pc-catalog-card-grid--three {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .pc-modal-card__variant,
  .pc-modal-card__price {
    width: 100%;
  }

  .pc-catalog-parent-banner {
    grid-template-columns: 1fr;
  }

  .pc-catalog-parent-banner__back {
    width: 100%;
  }

  .pc-catalog-card-grid--three {
    grid-template-columns: 1fr;
  }
}
</style>
