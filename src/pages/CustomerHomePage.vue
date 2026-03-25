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

    <section class="pc-services">
      <div class="container-fluid pc-container">
        <div class="pc-service-chip" v-for="service in quickServices" :key="service.label" :class="service.tone">
          <span class="pc-service-chip__icon"><i :class="service.icon"></i></span>
          <span>{{ service.label }}</span>
        </div>
      </div>
    </section>

    <section class="pc-categories">
      <div class="container-fluid pc-container">
        <div class="pc-section-head">
          <h2>Danh mục tủ thuốc</h2>
        </div>

        <div class="pc-category-grid">
          <button
            v-for="item in symptomCategories"
            :key="item.slug"
            type="button"
            class="pc-category-card"
            @click="openSymptomCategory(item.slug)"
          >
            <span class="pc-category-card__icon"><i :class="item.icon"></i></span>
            <span>{{ item.label }}</span>
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

        <div class="pc-product-section">
          <div class="pc-section-head">
            <h2>Thực phẩm bảo vệ sức khỏe mẹ và bé</h2>
            <button type="button">Xem tất cả <i class="bi bi-arrow-right"></i></button>
          </div>

          <div class="pc-product-grid">
            <article v-for="product in featuredProducts" :key="`featured-${product.ma_thuoc}`" class="pc-product-card">
              <div v-if="product.co_khuyen_mai" class="pc-product-card__badge">
                {{ product.khuyen_mai?.nhan_hien_thi || `Giảm ${discountPercent(product)}%` }}
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
        </div>

        <div class="pc-product-section">
          <div class="pc-section-head">
            <h2>Sản phẩm mới tại Pharmacity</h2>
            <button type="button">Xem tất cả <i class="bi bi-arrow-right"></i></button>
          </div>

          <div class="pc-product-grid">
            <article v-for="product in newestProducts" :key="`new-${product.ma_thuoc}`" class="pc-product-card">
              <div class="pc-product-card__badge">
                {{ product.co_khuyen_mai ? (product.khuyen_mai?.nhan_hien_thi || "Deal hot") : "Mới" }}
              </div>
              <div class="pc-product-card__image" :class="thumbTone(product)">
                <div class="pc-product-card__pill">{{ product.nha_san_xuat || "Pharmacity" }}</div>
                <i :class="thumbIcon(product)"></i>
              </div>
              <h3>{{ product.ten_thuoc }}</h3>
              <p>{{ product.mo_ta }}</p>
              <div class="pc-product-card__price">
                <strong>{{ formatCurrency(product.gia_ban) }}</strong>
                <span v-if="product.co_khuyen_mai">{{ formatCurrency(product.gia_niem_yet) }}</span>
              </div>
              <div class="pc-product-card__stock">Thuộc nhóm {{ product.loai_thuoc || "Thuốc" }}</div>
              <div class="pc-product-card__actions">
                <button type="button" @click="viewProduct(product)">Xem chi tiết</button>
                <button type="button" class="primary" @click="addProduct(product)">Chọn mua</button>
              </div>
            </article>
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
              <div class="pc-modal-card__label">Thuốc này thường dùng cho triệu chứng</div>
              <div class="pc-modal-card__chips">
                <span v-for="item in selectedProduct.trieu_chung" :key="item">{{ item }}</span>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="pc-modal-card__section">
              <div class="pc-modal-card__label">Tác dụng phụ thường gặp</div>
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
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { getCatalogThuoc, getCatalogThuocs } from "../api/catalogApi";
import { getCatalogSection } from "../data/catalogSections";
import { useCustomerStore } from "../lib/customerStore";
import { showToast } from "../lib/toast";

const router = useRouter();
const { addToCart } = useCustomerStore();

const thuocs = ref([]);
const catalogError = ref("");
const selectedProduct = ref(null);
const currentSlideIndex = ref(0);

const heroSlides = [
  {
    id: "welcome",
    eyebrow: "Quà tặng bạn mới",
    title: "Gói ưu đãi lên đến 150.000đ cho khách hàng lần đầu trải nghiệm",
    description:
      "Giao diện mới bám sát tinh thần trang bán thuốc hiện đại: nổi bật, dễ tìm sản phẩm, dễ chọn mua và vẫn gắn chặt với dữ liệu thuốc thực tế từ backend.",
    badge: "Mã QR ưu đãi",
    price: "150.000đ",
    chips: ["Miễn phí vận chuyển", "Quà tặng thuốc thiết yếu", "Mã giảm cho đơn đầu tiên"],
    tickets: [
      { label: "Phiếu mua hàng 30.000đ", className: "pc-ticket--blue" },
      { label: "Tặng 20.000đ P-Xu", className: "pc-ticket--cyan" },
      { label: "Giao nhanh trong ngày", className: "pc-ticket--yellow" },
    ],
    bannerClass: "pc-hero__banner--sky",
    phoneClass: "pc-phone-card--blue",
  },
  {
    id: "family-care",
    eyebrow: "Sống khỏe mỗi ngày",
    title: "Ưu đãi chăm sóc gia đình với các nhóm thuốc thiết yếu dễ tìm, dễ mua",
    description:
      "Từ thuốc cảm, tiêu hóa đến vitamin thiết yếu, hệ thống gợi ý nhanh đúng nhu cầu để khách hàng chọn sản phẩm phù hợp chỉ trong vài giây.",
    badge: "Gợi ý theo nhu cầu",
    price: "Mua nhanh",
    chips: ["Thuốc ho, cảm, sốt", "Tiêu hóa, dạ dày", "Vitamin và khoáng chất"],
    tickets: [
      { label: "Mua nhiều tiết kiệm hơn", className: "pc-ticket--pink" },
      { label: "Ưu đãi theo mùa", className: "pc-ticket--cyan" },
      { label: "Tư vấn dễ hiểu", className: "pc-ticket--green" },
    ],
    bannerClass: "pc-hero__banner--mint",
    phoneClass: "pc-phone-card--violet",
  },
  {
    id: "fast-delivery",
    eyebrow: "Nhà thuốc online",
    title: "Đặt thuốc nhanh, theo dõi đơn dễ và nhận hàng mượt trong cùng ngày",
    description:
      "Kết nối từ trang chủ, giỏ hàng đến thanh toán trong một trải nghiệm liền mạch, giúp khách hàng mua thuốc thuận tiện hơn trên mọi thiết bị.",
    badge: "Giao nhanh 1 giờ",
    price: "Nhanh - gọn",
    chips: ["Theo dõi đơn hàng", "Thanh toán linh hoạt", "Thông báo khuyến mãi mới"],
    tickets: [
      { label: "Miễn phí vận chuyển", className: "pc-ticket--blue" },
      { label: "Thanh toán MoMo, ZaloPay", className: "pc-ticket--purple" },
      { label: "Hỗ trợ khách hàng 24/7", className: "pc-ticket--yellow" },
    ],
    bannerClass: "pc-hero__banner--violet",
    phoneClass: "pc-phone-card--cyan",
  },
];

let heroTimer = null;

const quickServices = [
  { label: "Tư vấn mua thuốc", icon: "bi bi-capsule", tone: "tone-pink" },
  { label: "Liên hệ dược sĩ", icon: "bi bi-person-badge", tone: "tone-blue" },
  { label: "Hệ thống nhà thuốc", icon: "bi bi-shop", tone: "tone-green" },
  { label: "Mã giảm giá riêng", icon: "bi bi-ticket-perforated", tone: "tone-orange" },
  { label: "Kiểm tra sức khỏe", icon: "bi bi-heart-pulse", tone: "tone-cream" },
];

const symptomCategories = computed(() =>
  getCatalogSection("tu-thuoc").cards.filter((card) => card.slug !== "tat-ca")
);

const featuredProducts = computed(() => thuocs.value.slice(0, 5));
const newestProducts = computed(() => thuocs.value.slice(5, 10));
const currentSlide = computed(() => heroSlides[currentSlideIndex.value]);

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
  if (group.includes("mẹ") || group.includes("bé")) return "bi bi-heart-pulse";

  return "bi bi-capsule-pill";
}

function thumbTone(product) {
  const group = (product.loai_thuoc || "").toLowerCase();

  if (group.includes("vitamin")) return "tone-yellow";
  if (group.includes("da")) return "tone-green";
  if (group.includes("mẹ") || group.includes("bé")) return "tone-pink";

  return "tone-blue";
}

function discountPercent(product) {
  const base = Number(product.gia_niem_yet || 0);
  const current = Number(product.gia_ban || 0);
  if (!base || current >= base) return 0;

  return Math.round(((base - current) / base) * 100);
}

function openSymptomCategory(slug) {
  router.push(`/danh-muc/tu-thuoc/${slug}`);
}

function nextSlide() {
  currentSlideIndex.value = (currentSlideIndex.value + 1) % heroSlides.length;
  restartHeroTimer();
}

function prevSlide() {
  currentSlideIndex.value =
    (currentSlideIndex.value - 1 + heroSlides.length) % heroSlides.length;
  restartHeroTimer();
}

function goToSlide(index) {
  currentSlideIndex.value = index;
  restartHeroTimer();
}

function startHeroTimer() {
  stopHeroTimer();
  heroTimer = window.setInterval(() => {
    currentSlideIndex.value = (currentSlideIndex.value + 1) % heroSlides.length;
  }, 6500);
}

function stopHeroTimer() {
  if (heroTimer) {
    clearInterval(heroTimer);
    heroTimer = null;
  }
}

function restartHeroTimer() {
  startHeroTimer();
}

async function loadThuocs() {
  catalogError.value = "";

  try {
    const response = await getCatalogThuocs("");
    thuocs.value = response.data || [];
  } catch (error) {
    catalogError.value = error?.message || "Không tải được danh mục thuốc.";
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

function addProduct(product) {
  addToCart(product);
  showToast("Bạn vừa thêm vào giỏ hàng thành công");
}

function buyNow(product) {
  addToCart(product);
  selectedProduct.value = null;
  showToast("Bạn vừa thêm vào giỏ hàng thành công");
}

onMounted(() => {
  void loadThuocs();
  startHeroTimer();
});

onBeforeUnmount(() => {
  stopHeroTimer();
});
</script>
