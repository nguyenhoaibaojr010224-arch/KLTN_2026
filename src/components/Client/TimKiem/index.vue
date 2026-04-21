<template>
  <div class="pc-page">
    <div class="container-fluid pc-container py-4">
      <section class="pc-search-results">
        <div class="pc-search-results__head">
          <div>
            <p class="pc-search-results__eyebrow">Kết quả tìm kiếm</p>
            <h1>
              {{ keyword ? `Thuốc phù hợp với "${keyword}"` : 'Tìm thuốc theo nhu cầu sức khỏe' }}
            </h1>
            <p v-if="keyword">
              {{
                loading
                  ? 'Đang tìm thuốc phù hợp với từ khóa khách hàng vừa tìm.'
                  : `Tìm thấy ${products.length} thuốc phù hợp với từ khóa khách hàng vừa tìm.`
              }}
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

        <div v-if="matchedHashtags.length" class="pc-search-results__chips pc-search-results__chips--matched">
          <span
            v-for="item in matchedHashtags"
            :key="item.tag"
            class="pc-search-results__matched-chip"
          >
            {{ item.tag }} · {{ item.so_luong_thuoc }} thuốc
          </span>
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
              {{ product.khuyen_mai?.nhan_hien_thi || 'Đang ưu đãi' }}
            </div>
            <div class="pc-product-card__image" :class="thumbTone(product)">
              <div class="pc-product-card__pill">{{ product.loai_thuoc || 'Thuốc' }}</div>
              <img
                v-if="product.hinh_anh_url"
                :src="product.hinh_anh_url"
                :alt="product.ten_thuoc"
                style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: inherit"
              />
              <i v-else :class="thumbIcon(product)"></i>
            </div>
            <h3>{{ product.ten_thuoc }}</h3>
            <p>{{ product.mo_ta }}</p>
            <div v-if="!isPrescriptionProduct(product)" class="pc-product-card__price">
              <strong>{{ formatCurrency(product.gia_ban) }}</strong>
              <span v-if="product.co_khuyen_mai">{{ formatCurrency(product.gia_niem_yet) }}</span>
            </div>
            <div class="pc-product-card__stock">Còn {{ resolveProductStock(product) }} {{ resolveProductUnitLabel(product) }}</div>
            <div v-if="isPrescriptionProduct(product)" class="pc-product-card__stock pc-product-card__stock--prescription">Cần tư vấn dược sĩ</div>
            <div class="pc-product-card__actions">
              <button type="button" @click="viewProduct(product)">Xem chi tiết</button>
              <button type="button" class="primary" @click="isPrescriptionProduct(product) ? requestPharmacistConsult(product) : addProduct(product)">
                {{ isPrescriptionProduct(product) ? 'Tư vấn ngay' : 'Chọn mua' }}
              </button>
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
                <div class="pc-modal-card__price-label">Giá bán</div>
                <div class="pc-modal-card__price-value">{{ formatCurrency(selectedProductUnitOption?.gia_ban || selectedProduct.gia_ban) }}</div>
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
            @click="isPrescriptionProduct(selectedProduct) ? requestPharmacistConsult(selectedProduct) : addProduct(selectedProduct, selectedProductUnit)"
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
import { buildPrescriptionConsultMessage, isPrescriptionProduct as isPrescriptionProductFlag } from '../../../lib/prescriptionProducts';
import { useCustomerStore } from '../../../lib/customerStore';
import { applyProductUnitSelection, getDefaultProductUnit, getProductUnitLabel, getProductUnitOption, getProductUnitOptions, getProductUnitStock } from '../../../lib/productUnits';
import { saveRecentSearch } from '../../../lib/recentSearches';
import { openSupportChat } from '../../../lib/supportChatEvents';
import { showToast } from '../../../lib/toast';
import ProductVariantSelect from '../ProductVariantSelect.vue';

export default {
  name: 'TimKiemClient',

  components: {
    ProductVariantSelect,
  },

  data() {
    return {
      customerStore: useCustomerStore(),
      keyword: (this.$route.query.q || '').trim(),
      localKeyword: (this.$route.query.q || '').trim(),
      products: [],
      matchedHashtags: [],
      loading: false,
      searchRequestId: 0,
      catalogError: '',
      selectedProduct: null,
      selectedProductUnit: '',
      isSelectedProductDescriptionExpanded: false,
      quickKeywords: ['thuốc ho', 'đau họng', 'hạ sốt', 'tiêu hóa', 'vitamin', 'dị ứng'],
    };
  },

  watch: {
    '$route.query.q': {
      immediate: true,
      handler(value) {
        this.keyword = (value || '').trim();
        this.localKeyword = this.keyword;
        this.selectedProduct = null;
        if (this.keyword) {
          saveRecentSearch(this.keyword);
        }
        this.loadProducts();
      },
    },
  },

  computed: {
    selectedProductUnitOptions() {
      return getProductUnitOptions(this.selectedProduct);
    },

    selectedProductUnitOption() {
      return getProductUnitOption(this.selectedProduct, this.selectedProductUnit);
    },
  },

  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },

    thumbIcon(product) {
      const group = (product?.nhan || product?.loai_thuoc || '').toLowerCase();

      if (group.includes('vitamin')) return 'bi bi-sun';
      if (group.includes('da')) return 'bi bi-droplet-half';
      if (group.includes('mẹ') || group.includes('bé')) return 'bi bi-heart-pulse';

      return 'bi bi-capsule-pill';
    },

    thumbTone(product) {
      const group = (product?.nhan || product?.loai_thuoc || '').toLowerCase();

      if (group.includes('vitamin')) return 'tone-yellow';
      if (group.includes('da')) return 'tone-green';
      if (group.includes('mẹ') || group.includes('bé')) return 'tone-pink';

      return 'tone-blue';
    },

    isPrescriptionProduct(product) {
      return isPrescriptionProductFlag(product);
    },

    submitSearch() {
      const normalizedKeyword = this.localKeyword.trim();

      if (!normalizedKeyword) {
        return;
      }

      saveRecentSearch(normalizedKeyword);

      this.$router.push({
        path: '/tim-kiem',
        query: { q: normalizedKeyword },
      });
    },

    pickKeyword(nextKeyword) {
      this.localKeyword = nextKeyword;
      this.submitSearch();
    },

    async loadProducts() {
      const requestId = ++this.searchRequestId;
      this.loading = true;
      this.catalogError = '';

      try {
        const response = await getCatalogThuocs(this.keyword);

        if (requestId !== this.searchRequestId) {
          return;
        }

        this.products = response.data || [];
        this.matchedHashtags = Array.isArray(response.meta?.matched_hashtags) ? response.meta.matched_hashtags : [];
      } catch (error) {
        if (requestId !== this.searchRequestId) {
          return;
        }

        this.catalogError = error?.message || 'Không tải được kết quả tìm kiếm thuốc.';
        this.products = [];
        this.matchedHashtags = [];
      } finally {
        if (requestId === this.searchRequestId) {
          this.loading = false;
        }
      }
    },

    async viewProduct(product) {
      try {
        const response = await getCatalogThuoc(product.ma_thuoc);
        this.selectedProduct = response.data;
        this.selectedProductUnit = getDefaultProductUnit(response.data);
        this.isSelectedProductDescriptionExpanded = false;
      } catch (error) {
        this.catalogError = error?.message || 'Không tải được chi tiết thuốc.';
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

    requestPharmacistConsult(product) {
      if (!product) {
        return;
      }

      openSupportChat({
        message: buildPrescriptionConsultMessage(product),
        ma_thuoc: product.ma_thuoc,
      });
    },

    addProduct(product, selectedUnit = '') {
      if (this.isPrescriptionProduct(product)) {
        this.requestPharmacistConsult(product);
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

.pc-search-results__chips--matched {
  margin-top: 12px;
}

.pc-search-results__matched-chip {
  padding: 9px 16px;
  border-radius: 999px;
  background: #eef4ff;
  color: #24467f;
  font-weight: 700;
}

@media (max-width: 991.98px) {
  .pc-modal-card__variant,
  .pc-modal-card__price {
    width: 100%;
  }

  .pc-search-results__head {
    grid-template-columns: 1fr;
  }

  .pc-search-results__box {
    min-height: 56px;
  }
}
</style>
