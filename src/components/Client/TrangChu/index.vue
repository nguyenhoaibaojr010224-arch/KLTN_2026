<template>
  <div class="pc-homepage">
    <section class="pc-hero">
      <div class="container-fluid pc-container">
        <div class="pc-hero__banner" :class="currentSlide.bannerClass">
          <button class="pc-slider-button pc-slider-button--left" type="button" @click="prevSlide">
            <i class="bi bi-chevron-left"></i>
          </button>

          <Transition name="pc-hero-fade" mode="out-in">
            <div :key="currentSlide.id" class="pc-hero__content">
              <div class="pc-hero__copy">
                <div class="pc-hero__eyebrow">{{ currentSlide.eyebrow }}</div>
                <h1>{{ currentSlide.title }}</h1>
                <p>{{ currentSlide.description }}</p>
              </div>

              <div class="pc-hero__visual">
                <div class="pc-phone-card" :class="currentSlide.phoneClass">
                  <div class="pc-phone-card__camera"></div>
                  <div class="pc-phone-card__screen">
                    <div class="pc-phone-card__badge">{{ currentSlide.badge }}</div>
                    <div class="pc-phone-card__price">{{ currentSlide.price }}</div>
                    <div class="pc-phone-card__chips">
                      <span v-for="chip in currentSlide.chips" :key="chip">{{ chip }}</span>
                    </div>
                  </div>
                </div>

                <div class="pc-ticket-stack">
                  <div
                    v-for="ticket in currentSlide.tickets"
                    :key="ticket.label"
                    class="pc-ticket"
                    :class="ticket.className"
                  >
                    {{ ticket.label }}
                  </div>
                </div>
              </div>
            </div>
          </Transition>

          <div class="pc-hero__dots">
            <button
              v-for="(slide, index) in heroSlides"
              :key="slide.id"
              type="button"
              class="pc-hero__dot"
              :class="{ active: index === currentSlideIndex }"
              @click="goToSlide(index)"
            ></button>
          </div>

          <button class="pc-slider-button pc-slider-button--right" type="button" @click="nextSlide">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>

        <div class="pc-promo-row">
          <article class="pc-promo-card pc-promo-card--aqua">
            <div>
              <div class="pc-promo-card__label">Chăm sóc gia đình</div>
              <h3>Cùng nâng sức khỏe trên mọi hành trình</h3>
              <button class="btn btn-light rounded-pill px-4" type="button">Xem ngay</button>
            </div>
          </article>

          <article class="pc-promo-card pc-promo-card--pink">
            <div>
              <div class="pc-promo-card__label">Ưu đãi nổi bật</div>
              <h3>Quà tặng bạn mới và các combo mua nhiều tiết kiệm hơn</h3>
              <button class="btn btn-primary rounded-pill px-4" type="button">Mua ngay</button>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="pc-categories">
      <div class="container-fluid pc-container">
        <div class="pc-section-head">
          <h2>Vấn đề sức khỏe</h2>
        </div>

        <div class="pc-category-grid">
          <button
            v-for="item in symptomCategories"
            :key="item.slug"
            type="button"
            class="pc-category-card"
            @click="openSymptomCategory(item.slug)"
          >
            <div class="pc-category-card__icon-slot">
              <span class="pc-category-card__icon"><i :class="item.icon"></i></span>
            </div>
            <div class="pc-category-card__label-slot">
              <span class="pc-category-card__label">{{ item.label }}</span>
            </div>
          </button>
        </div>

        <div class="pc-banner-strip">
          <div class="pc-banner-strip__title">Ưu đãi thả ga, mua là có quà</div>
          <div class="pc-banner-strip__tag">Mua 1 tặng 1</div>
          <div class="pc-banner-strip__tag">Miễn phí vận chuyển</div>
        </div>
      </div>
    </section>

    <section class="pc-products">
      <div class="container-fluid pc-container">
        <div v-if="catalogError" class="alert alert-danger">{{ catalogError }}</div>

        <div v-for="section in visibleHomeProductSections" :key="section.slug" class="pc-product-section">
          <div class="pc-section-head">
            <div>
              <h2>{{ section.label }}</h2>
            </div>
            <button type="button" @click="openSymptomCategory(section.slug)">
              Xem tất cả <i class="bi bi-arrow-right"></i>
            </button>
          </div>

          <div v-if="section.products.length" class="pc-product-grid">
            <article
              v-for="product in section.products"
              :key="`${section.slug}-${product.ma_thuoc}`"
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

          <div v-else class="pc-home-empty-section">
            Chưa có sản phẩm trong danh mục này.
          </div>
        </div>

        <div class="pc-home-pager-wrap">
          <div class="pc-home-pager">
            <button type="button" @click="prevHomeCategoryPage" :disabled="homeCategoryPageCount <= 1">
              <i class="bi bi-chevron-left"></i>
            </button>
            <span>Trang {{ homeCategoryPageIndex + 1 }}/{{ homeCategoryPageCount }}</span>
            <button type="button" @click="nextHomeCategoryPage" :disabled="homeCategoryPageCount <= 1">
              <i class="bi bi-chevron-right"></i>
            </button>
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
import { filterProductsForSection, getCatalogSection } from '../../../data/catalogSections';
import { buildPrescriptionConsultMessage, isPrescriptionProduct as isPrescriptionProductFlag } from '../../../lib/prescriptionProducts';
import { useCustomerStore } from '../../../lib/customerStore';
import { isAuthenticated } from '../../../lib/authStorage';
import { applyProductUnitSelection, getDefaultProductUnit, getProductUnitLabel, getProductUnitOption, getProductUnitOptions, getProductUnitStock } from '../../../lib/productUnits';
import { openSupportChat } from '../../../lib/supportChatEvents';
import { showToast } from '../../../lib/toast';
import ProductVariantSelect from '../ProductVariantSelect.vue';

export default {
  name: 'TrangChuClient',

  components: {
    ProductVariantSelect,
  },

  data() {
    return {
      thuocs: [],
      catalogError: '',
      selectedProduct: null,
      selectedProductUnit: '',
      isSelectedProductDescriptionExpanded: false,
      currentSlideIndex: 0,
      heroTimer: null,
      homeCategoryPageIndex: 0,
      homeCategoryPageSize: 2,
      customerStore: useCustomerStore(),
      heroSlides: [
        {
          id: 'welcome',
          eyebrow: 'Quà tặng bạn mới',
          title: 'Gói ưu đãi lên đến 150.000đ cho khách hàng lần đầu trải nghiệm',
          description:
            'Giao diện mới bám sát tinh thần trang bán thuốc hiện đại: nổi bật, dễ tìm sản phẩm, dễ chọn mua và vẫn gắn chặt với dữ liệu thuốc thực tế từ backend.',
          badge: 'Mã QR ưu đãi',
          price: '150.000đ',
          chips: ['Miễn phí vận chuyển', 'Quà tặng thuốc thiết yếu', 'Mã giảm cho đơn đầu tiên'],
          tickets: [
            { label: 'Phiếu mua hàng 30.000đ', className: 'pc-ticket--blue' },
            { label: 'Tặng 20.000đ P-Xu', className: 'pc-ticket--cyan' },
            { label: 'Giao nhanh trong ngày', className: 'pc-ticket--yellow' },
          ],
          bannerClass: 'pc-hero__banner--sky',
          phoneClass: 'pc-phone-card--blue',
        },
        {
          id: 'family-care',
          eyebrow: 'Sống khỏe mỗi ngày',
          title: 'Ưu đãi chăm sóc gia đình với các nhóm thuốc thiết yếu dễ tìm, dễ mua',
          description:
            'Từ thuốc cảm, tiêu hóa đến vitamin thiết yếu, hệ thống gợi ý nhanh đúng nhu cầu để khách hàng chọn sản phẩm phù hợp chỉ trong vài giây.',
          badge: 'Gợi ý theo nhu cầu',
          price: 'Mua nhanh',
          chips: ['Thuốc ho, cảm, sốt', 'Tiêu hóa, dạ dày', 'Vitamin và khoáng chất'],
          tickets: [
            { label: 'Mua nhiều tiết kiệm hơn', className: 'pc-ticket--pink' },
            { label: 'Ưu đãi theo mùa', className: 'pc-ticket--cyan' },
            { label: 'Tư vấn dễ hiểu', className: 'pc-ticket--green' },
          ],
          bannerClass: 'pc-hero__banner--mint',
          phoneClass: 'pc-phone-card--violet',
        },
        {
          id: 'fast-delivery',
          eyebrow: 'Nhà thuốc online',
          title: 'Đặt thuốc nhanh, theo dõi đơn dễ và nhận hàng mượt trong cùng ngày',
          description:
            'Kết nối từ trang chủ, giỏ hàng đến thanh toán trong một trải nghiệm liền mạch, giúp khách hàng mua thuốc thuận tiện hơn trên mọi thiết bị.',
          badge: 'Giao nhanh 1 giờ',
          price: 'Nhanh - gọn',
          chips: ['Theo dõi đơn hàng', 'Thanh toán linh hoạt', 'Thông báo khuyến mãi mới'],
          tickets: [
            { label: 'Miễn phí vận chuyển', className: 'pc-ticket--blue' },
            { label: 'Thanh toán MoMo, ZaloPay', className: 'pc-ticket--purple' },
            { label: 'Hỗ trợ khách hàng 24/7', className: 'pc-ticket--yellow' },
          ],
          bannerClass: 'pc-hero__banner--violet',
          phoneClass: 'pc-phone-card--cyan',
        },
      ],
    };
  },

  computed: {
    symptomCategories() {
      return getCatalogSection('tra-cuu-benh').cards.filter((card) => card.slug !== 'tat-ca');
    },

    homeProductCategories() {
      return this.symptomCategories.map((card) => {
        const products = filterProductsForSection(this.thuocs, 'tra-cuu-benh', card.slug);

        return {
          ...card,
          total: products.length,
          products: products.slice(0, 4),
        };
      });
    },

    homeCategoryPageCount() {
      return Math.max(Math.ceil(this.homeProductCategories.length / this.homeCategoryPageSize), 1);
    },

    visibleHomeProductSections() {
      const startIndex = this.homeCategoryPageIndex * this.homeCategoryPageSize;
      return this.homeProductCategories.slice(startIndex, startIndex + this.homeCategoryPageSize);
    },

    currentSlide() {
      return this.heroSlides[this.currentSlideIndex] || this.heroSlides[0];
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

  mounted() {
    this.loadThuocs();
    this.startHeroTimer();
  },

  beforeUnmount() {
    this.stopHeroTimer();
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
      const group = (product.nhan || product.loai_thuoc || '').toLowerCase();

      if (group.includes('vitamin')) return 'bi bi-sun';
      if (group.includes('da')) return 'bi bi-droplet-half';
      if (group.includes('mẹ') || group.includes('bé')) return 'bi bi-heart-pulse';

      return 'bi bi-capsule-pill';
    },

    thumbTone(product) {
      const group = (product.nhan || product.loai_thuoc || '').toLowerCase();

      if (group.includes('vitamin')) return 'tone-yellow';
      if (group.includes('da')) return 'tone-green';
      if (group.includes('mẹ') || group.includes('bé')) return 'tone-pink';

      return 'tone-blue';
    },

    isPrescriptionProduct(product) {
      return isPrescriptionProductFlag(product);
    },

    discountPercent(product) {
      const base = Number(product.gia_niem_yet || 0);
      const current = Number(product.gia_ban || 0);
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

    openSymptomCategory(slug) {
      this.$router.push(`/danh-muc/tra-cuu-benh/${slug}`);
    },

    nextHomeCategoryPage() {
      if (this.homeCategoryPageCount <= 1) {
        return;
      }

      this.homeCategoryPageIndex = (this.homeCategoryPageIndex + 1) % this.homeCategoryPageCount;
    },

    prevHomeCategoryPage() {
      if (this.homeCategoryPageCount <= 1) {
        return;
      }

      this.homeCategoryPageIndex =
        (this.homeCategoryPageIndex - 1 + this.homeCategoryPageCount) % this.homeCategoryPageCount;
    },

    nextSlide() {
      this.currentSlideIndex = (this.currentSlideIndex + 1) % this.heroSlides.length;
      this.restartHeroTimer();
    },

    prevSlide() {
      this.currentSlideIndex = (this.currentSlideIndex - 1 + this.heroSlides.length) % this.heroSlides.length;
      this.restartHeroTimer();
    },

    goToSlide(index) {
      this.currentSlideIndex = index;
      this.restartHeroTimer();
    },

    startHeroTimer() {
      this.stopHeroTimer();
      this.heroTimer = window.setInterval(() => {
        this.currentSlideIndex = (this.currentSlideIndex + 1) % this.heroSlides.length;
      }, 6500);
    },

    stopHeroTimer() {
      if (this.heroTimer) {
        clearInterval(this.heroTimer);
        this.heroTimer = null;
      }
    },

    restartHeroTimer() {
      this.startHeroTimer();
    },

    async loadThuocs() {
      this.catalogError = '';

      try {
        const response = await getCatalogThuocs('');
        this.thuocs = response.data || [];
      } catch (error) {
        this.catalogError = error?.message || 'Không tải được danh mục thuốc.';
      }
    },

    async viewProduct(product) {
      try {
        const response = await getCatalogThuoc(product.ma_thuoc);
        this.selectedProduct = response.data;
        this.selectedProductUnit = getDefaultProductUnit(response.data);
        this.isSelectedProductDescriptionExpanded = false;
      } catch (error) {
        this.catalogError = error?.message || 'Không tải được chi tiết sản phẩm.';
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

    addProduct(product, selectedUnit = '') {
      if (this.isPrescriptionProduct(product)) {
        this.requestPharmacistConsult(product);
        return;
      }

      if (this.requireLoginForPurchase()) {
        return;
      }

      this.customerStore.addToCart(this.resolveProductForCart(product, selectedUnit));
      showToast('Bạn vừa thêm vào giỏ hàng thành công');
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

.pc-home-pager {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.pc-home-pager button {
  width: 40px;
  height: 40px;
  border: 1px solid #d8e4ff;
  border-radius: 12px;
  background: #ffffff;
  color: #1f5eff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.pc-home-pager button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.pc-home-pager button:not(:disabled):hover {
  background: #f4f8ff;
}

.pc-home-pager span {
  min-width: 92px;
  text-align: center;
  font-size: 0.95rem;
  font-weight: 600;
  color: #5e6f91;
}

.pc-home-pager-wrap {
  display: flex;
  justify-content: center;
  margin-top: 24px;
}

.pc-home-section-copy {
  color: #7082a6;
  font-size: 0.95rem;
}

.pc-home-empty-section {
  padding: 28px;
  border: 1px dashed #d8e4ff;
  border-radius: 24px;
  background: #f8fbff;
  color: #7082a6;
  font-size: 0.98rem;
}

@media (max-width: 768px) {
  .pc-modal-card__variant,
  .pc-modal-card__price {
    width: 100%;
  }

  .pc-home-pager {
    justify-content: space-between;
  }
}
</style>
