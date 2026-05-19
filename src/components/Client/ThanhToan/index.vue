<template>
  <div class="pc-page">
    <div class="container-fluid pc-container">
      <div class="row g-4 align-items-start">
        <div class="col-xl-8">
          <div class="pc-page__title">
            <h1>Danh sách sản phẩm</h1>
          </div>

          <section class="pc-order-card">
            <article v-for="item in selectedItems" :key="item.id" class="pc-checkout-item">
              <div class="pc-checkout-item__thumb" :class="item.imageTone || 'pink'">
                <img
                  v-if="item.hinhAnhUrl"
                  :src="item.hinhAnhUrl"
                  :alt="item.ten"
                  style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: inherit"
                />
              </div>
              <div class="pc-checkout-item__content">
                <h3>{{ item.ten }}</h3>
                <p>Phân loại: {{ item.donVi }}</p>
                <button type="button" @click="openProductInfo(item)">
                  <i class="bi bi-info-circle"></i> Thông tin sản phẩm
                </button>
                <div v-if="item.promoTags && item.promoTags.length" class="pc-cart-item__tags">
                  <span v-for="tag in item.promoTags" :key="tag">{{ tag }}</span>
                </div>
              </div>
              <div class="pc-checkout-item__qty">x{{ item.soLuong }}</div>
              <div class="pc-checkout-item__price">{{ formatCurrency(item.gia * item.soLuong) }}</div>
            </article>
          </section>

          <section class="pc-order-card">
            <div class="pc-order-card__sectiontitle">Hình thức nhận hàng</div>
            <div class="pc-delivery-body">
              <div class="pc-delivery-header">
                <div>
                  <strong>Chọn vị trí giao hàng</strong>
                  <p>Chọn địa chỉ giao hàng phù hợp cho đơn này.</p>
                </div>
                <button type="button" class="pc-link-button" @click="goToAddress">Quản lý địa chỉ</button>
              </div>

              <div v-if="addresses.length" class="pc-address-list">
                <button
                  v-for="address in addresses"
                  :key="address.id"
                  type="button"
                  class="pc-address-option"
                  :class="{ 'is-active': defaultAddress && defaultAddress.id === address.id }"
                  @click="selectAddress(address)"
                >
                  <div class="pc-address-option__main">
                    <div class="pc-address-option__top">
                      <strong>{{ address.hoTen }} | {{ address.soDienThoai }}</strong>
                      <span v-if="address.macDinh" class="pc-address-option__badge">Mặc định</span>
                    </div>
                    <p>
                      {{ address.soNha }}, {{ address.phuongXa }}, {{ address.quanHuyen }}, {{ address.tinhThanh }}
                    </p>
                    <span class="pc-address-option__type">{{ address.loaiDiaChi || "Địa chỉ giao hàng" }}</span>
                  </div>
                  <i
                    class="bi"
                    :class="defaultAddress && defaultAddress.id === address.id ? 'bi-check-circle-fill' : 'bi-circle'"
                  ></i>
                </button>
              </div>

              <div v-else class="pc-address-empty">
                <p>Hiện chưa có địa chỉ nhận hàng.</p>
                <button type="button" class="pc-address-button" @click="goToAddress">
                  <i class="bi bi-plus-lg"></i>
                  Thêm địa chỉ nhận hàng
                </button>
              </div>

              <div class="pc-checkout-note">
                <label for="checkout-note">Ghi chú cho đơn hàng (không bắt buộc)</label>
                <textarea
                  id="checkout-note"
                  v-model="state.note"
                  rows="3"
                  placeholder="Nhập ghi chú ở đây"
                ></textarea>
              </div>
            </div>
          </section>

          <section class="pc-order-card">
            <div class="pc-order-card__sectiontitle">Phương thức thanh toán</div>
            <div class="pc-payment-body">
              <label v-for="method in paymentMethods" :key="method.id" class="pc-payment-method">
                <input v-model="state.paymentMethod" type="radio" :value="method.id" name="payment-method" />
                <span class="pc-payment-method__logo" :class="`pc-payment-method__logo--${method.id}`">
                  <img v-if="method.logo" :src="method.logo" :alt="method.label" class="pc-payment-method__logo-image" />
                  <span v-else-if="method.id === 'cod'" class="pc-payment-method__logo-text">COD</span>
                  <span v-else-if="method.id === 'atm'" class="pc-payment-method__logo-text">ATM</span>
                  <span v-else-if="method.id === 'payos'" class="pc-payment-method__logo-text">PayOS</span>
                  <i v-else class="bi bi-credit-card-2-front"></i>
                </span>
                <span class="pc-payment-method__label">{{ method.label }}</span>
              </label>
            </div>
          </section>
        </div>

        <div class="col-xl-4">
          <aside class="pc-summary-stack">
            <div class="pc-summary-card">
              <h2>Đơn hàng</h2>
              <div class="pc-summary-card__line pc-summary-card__line--link">
                <span><i class="bi bi-ticket-perforated"></i> Khuyến mãi</span>
                <button
                  type="button"
                  class="pc-link-button"
                  :disabled="!canUsePromotionCode"
                  @click="openPromotionPicker"
                >
                  {{ canUsePromotionCode ? "Chọn mã" : "Đăng nhập để dùng mã" }}
                </button>
              </div>

              <p v-if="!canUsePromotionCode" class="pc-promo-message pc-promo-message--info">
                Bạn cần đăng nhập trước khi sử dụng mã giảm giá đơn hàng.
              </p>

              <div v-if="promotionMessage" class="pc-promo-message pc-promo-message--success">
                {{ promotionMessage }}
              </div>
              <div v-if="promotionError" class="pc-promo-message pc-promo-message--error">
                {{ promotionError }}
              </div>

              <div v-if="orderError" class="pc-order-alert">
                <strong>Chưa thể đặt hàng</strong>
                <span>{{ orderError }}</span>
              </div>

              <div v-if="state.appliedPromotion" class="pc-applied-promo">
                <div>
                  <strong>{{ state.appliedPromotion.maGiamGia }}</strong>
                  <p class="mb-0">{{ state.appliedPromotion.tenMa }}</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="removePromotionCode">Bỏ mã</button>
              </div>

              <div class="pc-reward-box">
                <div class="pc-reward-box__head">
                  <span><i class="bi bi-stars"></i> Điểm thưởng</span>
                  <strong>{{ formatNumber(rewardPointBalance) }} điểm</strong>
                </div>
                <label v-if="canUseRewardPoints" class="pc-reward-box__toggle">
                  <input v-model="state.useRewardPoints" type="checkbox" />
                  <span>
                    Tích chọn sử dụng {{ formatNumber(rewardPointsPreviewToUse) }} điểm để giảm
                    {{ formatCurrency(rewardPointDiscountPreview) }}
                  </span>
                </label>
                <p v-else class="pc-reward-box__hint">
                  Tích đủ 1.000 điểm và đơn sau giảm từ 10.000đ để đổi 10.000đ.
                </p>
              </div>

              <div class="pc-summary-card__line">
                <span>Tạm tính</span>
                <strong>{{ formatCurrency(subtotal) }}</strong>
              </div>
              <div class="pc-summary-card__line">
                <span>Phí vận chuyển</span>
                <strong>{{ formatCurrency(0) }}</strong>
              </div>
              <div class="pc-summary-card__line" v-if="orderPromotionDiscount > 0">
                <span>Giảm giá mã khuyến mãi</span>
                <strong class="text-success">-{{ formatCurrency(orderPromotionDiscount) }}</strong>
              </div>
              <div class="pc-summary-card__line" v-if="rewardPointDiscount > 0">
                <span>Giảm bằng điểm thưởng</span>
                <strong class="text-success">-{{ formatCurrency(rewardPointDiscount) }}</strong>
              </div>
              <div class="pc-summary-card__line">
                <span>VAT (10%)</span>
                <strong>{{ formatCurrency(vatAmount) }}</strong>
              </div>
              <div class="pc-summary-card__line">
                <span>Điểm</span>
                <strong class="text-primary">+{{ formatNumber(estimatedRewardPointsEarned) }} điểm</strong>
              </div>
              <div class="pc-summary-card__total">
                <span>Tổng tiền</span>
                <strong>{{ formatCurrency(orderTotal) }}</strong>
              </div>

              <label class="pc-summary-card__agree">
                <input v-model="acceptedTerms" type="checkbox" />
                <span>
                  Bằng cách tích vào ô chọn, bạn đã đồng ý với Điều khoản PharmaGo và xác nhận đã đọc kỹ thông tin
                  sản phẩm.
                </span>
              </label>

              <button
                class="btn btn-primary btn-lg w-100 rounded-4"
                type="button"
                :disabled="!acceptedTerms || submittingOrder"
                @click="submitOrder"
              >
                <span v-if="submittingOrder" class="spinner-border spinner-border-sm me-2"></span>
                {{ submittingOrder ? "Đang đặt hàng..." : "Đặt hàng" }}
              </button>
            </div>
          </aside>
        </div>
      </div>
    </div>

    <div ref="promotionPickerModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header">
            <div>
              <h5 class="modal-title fw-bold mb-1">Chọn khuyến mãi</h5>
              <p class="mb-0 text-secondary small">Các mã khuyến mãi đang có sẽ hiển thị tại đây để áp dụng cho đơn hàng.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
          </div>
          <div class="modal-body">
            <div v-if="availableCodesLoading" class="py-5 text-center text-secondary">
              <div class="spinner-border text-primary mb-3"></div>
              <p class="mb-0">Đang tải danh sách mã giảm giá...</p>
            </div>

            <div v-else-if="availablePromotionCodes.length" class="vstack gap-4">
              <section v-if="eligiblePromotionCodes.length" class="vstack gap-3">
                <div class="pc-code-section-heading">
                  <strong>Mã đang đủ điều kiện áp dụng</strong>
                  <span>{{ eligiblePromotionCodes.length }} mã</span>
                </div>

                <article v-for="item in eligiblePromotionCodes" :key="`eligible-${item.id}`" class="pc-code-option">
                  <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div>
                      <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <strong>{{ item.ma_giam_gia }}</strong>
                        <span class="soft-badge soft-badge--teal">Đủ điều kiện</span>
                      </div>
                      <h6 class="mb-1">{{ item.ten_ma }}</h6>
                      <p class="mb-2 text-secondary">{{ item.mo_ta || 'Áp dụng cho đơn hàng đủ điều kiện.' }}</p>
                      <div class="small text-secondary vstack gap-1">
                        <span>Giảm giá: {{ promotionValueLabel(item) }}</span>
                        <span>Đơn tối thiểu: {{ formatCurrency(item.gia_tri_don_toi_thieu) }}</span>
                        <span>
                          {{ item.gioi_han_moi_khach ? `Mỗi khách dùng tối đa ${item.gioi_han_moi_khach} lần` : 'Không giới hạn số lần sử dụng' }}
                        </span>
                      </div>
                    </div>
                    <div class="text-end d-flex flex-column justify-content-between align-items-end gap-3">
                      <div class="fw-semibold text-primary">Giảm {{ formatCurrency(item.giam_gia_don_hang) }}</div>
                      <button type="button" class="btn btn-primary btn-sm" @click="selectPromotionCode(item)">
                        Áp dụng
                      </button>
                    </div>
                  </div>
                </article>
              </section>

              <section v-if="unavailablePromotionCodes.length" class="vstack gap-3">
                <div class="pc-code-section-heading">
                  <strong>Mã chưa đủ điều kiện</strong>
                  <span>{{ unavailablePromotionCodes.length }} mã</span>
                </div>

                <article v-for="item in unavailablePromotionCodes" :key="`unavailable-${item.id}`" class="pc-code-option is-disabled">
                  <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div>
                      <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <strong>{{ item.ma_giam_gia }}</strong>
                        <span class="soft-badge soft-badge--orange">Chưa đủ điều kiện</span>
                      </div>
                      <h6 class="mb-1">{{ item.ten_ma }}</h6>
                      <p class="mb-2 text-secondary">{{ item.mo_ta || 'Áp dụng cho đơn hàng đủ điều kiện.' }}</p>
                      <div class="small text-secondary vstack gap-1">
                        <span>Giảm giá: {{ promotionValueLabel(item) }}</span>
                        <span>Đơn tối thiểu: {{ formatCurrency(item.gia_tri_don_toi_thieu) }}</span>
                        <span>
                          {{ item.gioi_han_moi_khach ? `Mỗi khách dùng tối đa ${item.gioi_han_moi_khach} lần` : 'Không giới hạn số lần sử dụng' }}
                        </span>
                        <span v-if="item.ly_do_khong_ap_dung" class="pc-code-option__notice">
                          {{ item.ly_do_khong_ap_dung }}
                        </span>
                      </div>
                    </div>
                    <div class="text-end d-flex flex-column justify-content-between align-items-end gap-3">
                      <div class="fw-semibold text-secondary">Chưa thể áp dụng</div>
                      <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                        Xem điều kiện
                      </button>
                    </div>
                  </div>
                </article>
              </section>
            </div>

            <div v-else class="master-empty">
              <p class="mb-2 fw-semibold">Chưa có mã giảm giá khả dụng.</p>
              <p class="mb-0 text-secondary">Không có mã nào phù hợp với tổng đơn hàng hiện tại.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div ref="productInfoModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header">
            <div>
              <h5 class="modal-title fw-bold mb-1">Thông tin sản phẩm</h5>
              <p class="mb-0 text-secondary small">Chi tiết sản phẩm đang có trong giỏ hàng.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
          </div>
            <div v-if="selectedProductInfo" class="modal-body">
              <div class="pc-product-info">
              <div class="pc-product-info__thumb" :class="selectedProductInfo.imageTone || 'pink'">
                <img
                  v-if="selectedProductInfo.hinhAnhUrl"
                  :src="selectedProductInfo.hinhAnhUrl"
                  :alt="selectedProductInfo.ten"
                />
                <i v-else class="bi bi-capsule-pill"></i>
              </div>
              <div class="pc-product-info__content">
                <h4>{{ selectedProductInfo.ten }}</h4>
                <div class="pc-product-info__meta">
                  <span>Mã thuốc: {{ selectedProductInfo.maThuoc }}</span>
                  <span>Loại: {{ selectedProductInfo.loai }}</span>
                  <span>Đơn vị: {{ selectedProductInfo.donVi }}</span>
                  <span>Nhà sản xuất: {{ selectedProductInfo.nhaSanXuat }}</span>
                  <span>Tồn kho: {{ selectedProductInfo.tonKho }}</span>
                </div>
                  <div class="pc-product-info__price">
                    <strong>{{ formatCurrency(selectedProductInfo.gia) }}</strong>
                    <span v-if="selectedProductInfo.giaGoc > selectedProductInfo.gia">
                      Giá niêm yết: {{ formatCurrency(selectedProductInfo.giaGoc) }}
                    </span>
                  </div>
                  <div class="pc-product-info__description">
                    <strong>Mô tả</strong>
                    <p>{{ selectedProductInfo.moTa || "Chưa có mô tả cho sản phẩm này." }}</p>
                  </div>
                  <div v-if="selectedProductInfo.promoTags && selectedProductInfo.promoTags.length" class="pc-cart-item__tags">
                    <span v-for="tag in selectedProductInfo.promoTags" :key="tag">{{ tag }}</span>
                  </div>
                <p class="pc-product-info__note">
                  Sản phẩm này đang được chọn trong giỏ hàng với số lượng {{ selectedProductInfo.soLuong }}.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from "bootstrap";
import { cancelPayosOrder, createCheckoutOrder } from "../../../api/orderApi";
import { getAvailableOrderDiscountCodes, validatePromotionCode } from "../../../api/pricingApi";
import { useCustomerStore } from "../../../lib/customerStore";
import { normalizeApiError } from "../../../lib/errorMessages";
import { showToast } from "../../../lib/toast";

function cleanupBootstrapModalArtifacts() {
  if (typeof document === "undefined") return;

  document.body.classList.remove("modal-open");
  document.body.style.removeProperty("overflow");
  document.body.style.removeProperty("padding-right");
  document.querySelectorAll(".modal-backdrop").forEach((backdrop) => backdrop.remove());
}

export default {
  name: "ThanhToanClient",

  data() {
    return {
      customerStore: useCustomerStore(),
      acceptedTerms: true,
      promotionCode: "",
      promotionLoading: false,
      promotionMessage: "",
      promotionError: "",
      orderError: "",
      promotionPickerModal: null,
      productInfoModal: null,
      selectedProductInfo: null,
      submittingOrder: false,
      availableCodesLoading: false,
      availablePromotionCodes: [],
      paymentMethods: [
        { id: "cod", label: "Tiền mặt" },
        { id: "payos", label: "PayOS" },
      ],
    };
  },

  computed: {
    state() {
      return this.customerStore.state;
    },
    selectedItems() {
      return this.customerStore.selectedItems;
    },
    subtotal() {
      return this.customerStore.subtotal;
    },
    orderPromotionDiscount() {
      return this.customerStore.orderPromotionDiscount;
    },
    subtotalAfterPromotion() {
      return this.customerStore.subtotalAfterPromotion;
    },
    rewardPointBalance() {
      return this.customerStore.rewardPointBalance;
    },
    canUseRewardPoints() {
      return this.customerStore.canUseRewardPoints;
    },
    rewardPointsToUse() {
      return this.customerStore.rewardPointsToUse;
    },
    rewardPointDiscount() {
      return this.customerStore.rewardPointDiscount;
    },
    rewardPointsPreviewToUse() {
      if (!this.canUseRewardPoints) {
        return 0;
      }

      return Math.min(
        Math.floor(Number(this.rewardPointBalance || 0) / 1000),
        Math.floor(Number(this.subtotalAfterPromotion || 0) / 10000)
      ) * 1000;
    },
    rewardPointDiscountPreview() {
      return Math.floor(this.rewardPointsPreviewToUse / 1000) * 10000;
    },
    estimatedRewardPointsEarned() {
      return this.customerStore.estimatedRewardPointsEarned;
    },
    vatAmount() {
      return this.customerStore.vatAmount;
    },
    orderTotal() {
      return this.customerStore.orderTotal;
    },
    canUsePromotionCode() {
      return this.customerStore.canUsePromotionCode;
    },
    defaultAddress() {
      return this.customerStore.defaultAddress;
    },
    addresses() {
      return [...this.state.addresses].sort((left, right) => Number(Boolean(right.macDinh)) - Number(Boolean(left.macDinh)));
    },
    payableSubtotal() {
      return this.customerStore.payableSubtotal;
    },
    eligiblePromotionCodes() {
      return this.availablePromotionCodes.filter((item) => item.co_the_ap_dung);
    },
    unavailablePromotionCodes() {
      return this.availablePromotionCodes.filter((item) => !item.co_the_ap_dung);
    },
  },

  async mounted() {
    if (await this.handlePayosCancelReturn()) {
      return;
    }

    await this.customerStore.syncCartPricesWithCatalog?.();
    await this.customerStore.syncAddressesFromApi?.();
    this.promotionPickerModal = new Modal(this.$refs.promotionPickerModalEl);
    this.productInfoModal = new Modal(this.$refs.productInfoModalEl);
    this.promotionCode = this.state.appliedPromotion?.maGiamGia || "";
    await this.autoApplyDefaultPromotion();
    this.$refs.promotionPickerModalEl?.addEventListener("hidden.bs.modal", this.handlePromotionModalHidden);
    this.$refs.productInfoModalEl?.addEventListener("hidden.bs.modal", this.handleProductInfoModalHidden);
  },

  beforeUnmount() {
    this.$refs.promotionPickerModalEl?.removeEventListener("hidden.bs.modal", this.handlePromotionModalHidden);
    this.$refs.productInfoModalEl?.removeEventListener("hidden.bs.modal", this.handleProductInfoModalHidden);

    if (this.promotionPickerModal) {
      this.promotionPickerModal.dispose();
      this.promotionPickerModal = null;
    }

    if (this.productInfoModal) {
      this.productInfoModal.dispose();
      this.productInfoModal = null;
    }

    cleanupBootstrapModalArtifacts();
  },

  methods: {
    formatCurrency(value) {
      const n = Number(value || 0);
      if (Math.abs(n) >= 1e9) {
        const ty = n / 1e9;
        return (Math.abs(ty) >= 10 ? Math.round(ty) : ty.toFixed(2).replace('.', ',')) + ' tỷ đ';
      }
      return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 }).format(n);
    },
    formatNumber(value) {
      return new Intl.NumberFormat("vi-VN", {
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    extractErrorMessage(error, fallbackMessage = "Đã xảy ra lỗi. Vui lòng thử lại.") {
      return normalizeApiError(error, fallbackMessage, {
        ten_khach_hang: "tên khách hàng",
        so_dien_thoai: "số điện thoại",
        dia_chi_giao_hang: "địa chỉ giao hàng",
        phuong_thuc_thanh_toan: "phương thức thanh toán",
        ma_giam_gia: "mã giảm giá",
        tong_tam_tinh: "tổng tiền sản phẩm",
      });
    },
    async handlePayosCancelReturn() {
      const query = this.$route.query || {};
      const status = String(query.status || "").trim().toUpperCase();
      const cancel = String(query.cancel || "").trim().toLowerCase() === "true";
      const orderCode = Number(query.orderCode || query.order_code || 0);

      if ((!cancel && !["CANCELLED", "CANCELED"].includes(status)) || !orderCode) {
        return false;
      }

      try {
        await cancelPayosOrder({
          order_code: orderCode,
          payment_link_id: query.id || query.paymentLinkId || query.payment_link_id || "",
          status: status || "CANCELLED",
        });

        this.customerStore.removePayosOrderNotificationByOrderCode?.(orderCode);
        await this.customerStore.syncOrdersFromApi?.();
        await this.customerStore.refreshProfileFromApi?.();
      } catch (error) {
        showToast(this.extractErrorMessage(error, "Không thể cập nhật trạng thái hủy PayOS."), "error");
      }

      this.$router.replace("/tai-khoan/lich-su-don-hang");
      return true;
    },
    promotionValueLabel(item) {
      if (item.loai_ap_dung === "phan_tram") return `${Number(item.gia_tri || 0)}%`;
      return this.formatCurrency(item.gia_tri);
    },
    goToAddress() {
      this.$router.push("/tai-khoan/dia-chi");
    },
    openProductInfo(item) {
      this.selectedProductInfo = {
        ...item,
        hinhAnhUrl: item.hinhAnhUrl || item.hinh_anh_url || "",
      };
      this.productInfoModal.show();
    },
    async selectAddress(address) {
      try {
        this.orderError = "";
        await this.customerStore.saveAddress({
          ...address,
          macDinh: true,
        });
        showToast("Đã chọn vị trí giao hàng.", "success");
      } catch (error) {
        showToast(this.extractErrorMessage(error, "Không thể cập nhật địa chỉ giao hàng."), "error");
      }
    },
    async loadAvailablePromotionCodes(options = {}) {
      const { silent = false } = options;

      if (this.payableSubtotal <= 0) {
        this.availablePromotionCodes = [];
        return;
      }

      this.availableCodesLoading = true;
      try {
        const response = await getAvailableOrderDiscountCodes(this.payableSubtotal);
        this.availablePromotionCodes = Array.isArray(response?.data) ? response.data : [];
      } catch (error) {
        this.availablePromotionCodes = [];
        if (!silent) {
          showToast(this.extractErrorMessage(error, "Không thể tải danh sách mã giảm giá."), "error");
        }
      } finally {
        this.availableCodesLoading = false;
      }
    },
    async openPromotionPicker() {
      if (!this.canUsePromotionCode) {
        this.promotionError = "Bạn cần đăng nhập trước khi sử dụng mã giảm giá.";
        showToast(this.promotionError, "error");
        return;
      }

      this.promotionError = "";
      this.promotionMessage = "";
      await this.loadAvailablePromotionCodes();
      this.promotionPickerModal.show();
    },
    async autoApplyDefaultPromotion() {
      if (!this.canUsePromotionCode || this.state.appliedPromotion || this.payableSubtotal <= 0) {
        return;
      }

      await this.loadAvailablePromotionCodes({ silent: true });
      const defaultCode = this.eligiblePromotionCodes.find(
        (item) => item.tu_dong_ap_dung || item.loai_ma === "first_order"
      );

      if (!defaultCode) {
        return;
      }

      await this.selectPromotionCode(defaultCode, { silent: true });
    },
    handlePromotionModalHidden() {
      cleanupBootstrapModalArtifacts();
    },
    handleProductInfoModalHidden() {
      this.selectedProductInfo = null;
      cleanupBootstrapModalArtifacts();
    },
    async selectPromotionCode(item, options = {}) {
      const { silent = false } = options;

      if (!item.co_the_ap_dung) {
        this.promotionError = item.ly_do_khong_ap_dung || "Bạn không đủ điều kiện sử dụng mã này.";
        if (!silent) {
          showToast(this.promotionError, "error");
        }
        return;
      }

      this.promotionLoading = true;
      this.promotionError = "";
      this.promotionMessage = "";

      try {
        const response = await validatePromotionCode({
          ma_giam_gia: item.ma_giam_gia,
          tong_tam_tinh: this.payableSubtotal,
        });

        this.customerStore.applyPromotionCode(response.data);
        this.promotionCode = response.data.ma_giam_gia;
        this.promotionMessage = `Đã áp dụng mã ${response.data.ma_giam_gia}.`;
        if (!silent) {
          this.promotionPickerModal.hide();
          cleanupBootstrapModalArtifacts();
          showToast(`Áp dụng mã ${response.data.ma_giam_gia} thành công.`);
        }
      } catch (error) {
        this.customerStore.clearAppliedPromotion();
        this.promotionError = this.extractErrorMessage(error, "Không áp dụng được mã giảm giá.");
        if (!silent) {
          showToast(this.promotionError, "error");
        }
      } finally {
        this.promotionLoading = false;
      }
    },
    removePromotionCode() {
      this.customerStore.clearAppliedPromotion();
      this.promotionCode = "";
      this.promotionMessage = "";
      this.promotionError = "";
      showToast("Đã bỏ mã giảm giá.", "success");
    },
    async submitOrder() {
      if (this.submittingOrder) {
        return;
      }

      if (!this.selectedItems.length) {
        this.orderError = "Vui lòng chọn ít nhất một sản phẩm để đặt hàng.";
        showToast(this.orderError, "error");
        return;
      }

      if (!this.defaultAddress) {
        this.orderError = "Vui lòng chọn địa chỉ giao hàng.";
        showToast(this.orderError, "error");
        return;
      }

      this.submittingOrder = true;
      this.orderError = "";
      this.promotionError = "";
      this.promotionMessage = "";

      try {
        const diaChiGiaoHang = [
          this.defaultAddress.soNha,
          this.defaultAddress.phuongXa,
          this.defaultAddress.quanHuyen,
          this.defaultAddress.tinhThanh,
        ]
          .filter(Boolean)
          .join(", ");

        const response = await createCheckoutOrder({
          items: this.selectedItems.map((item) => ({
            ma_thuoc: item.maThuoc || item.id,
            so_luong: Number(item.soLuong || 1),
            don_vi: item.donVi || null,
          })),
          phuong_thuc_thanh_toan: this.state.paymentMethod,
          ma_giam_gia: this.state.appliedPromotion?.maGiamGia || null,
          su_dung_diem: Boolean(this.state.useRewardPoints && this.rewardPointDiscount > 0),
          dia_chi_giao_hang: diaChiGiaoHang,
          ghi_chu: this.state.note || "",
        });

        const orderId = this.customerStore.placeOrder(response?.data || null);
        await this.customerStore.syncOrdersFromApi();
        await this.customerStore.refreshProfileFromApi();

        const payosCheckoutUrl = response?.data?.payos?.checkout_url;

        if (this.state.paymentMethod === "payos" && payosCheckoutUrl) {
          showToast(`Đã tạo đơn ${orderId}. Đang chuyển sang PayOS.`, "success");
          window.location.href = payosCheckoutUrl;
          return;
        }

        showToast(`Đặt hàng thành công. Mã đơn: ${orderId}`, "success");
        this.$router.push("/tai-khoan/lich-su-don-hang");
      } catch (error) {
        const message =
          error?.status === 503
            ? "Hiện tại nhà thuốc chưa có nhân viên trực hệ thống. Vui lòng thử lại sau hoặc liên hệ hỗ trợ."
            : this.extractErrorMessage(error, "Không thể tạo đơn hàng. Vui lòng thử lại.");
        this.orderError = message;
        showToast(message, "error");
      } finally {
        this.submittingOrder = false;
      }
    },
  },
};
</script>

<style scoped>
.pc-page__title h1,
.pc-order-card__sectiontitle,
.pc-summary-card h2,
.pc-summary-card__total strong,
.pc-checkout-item__content h3,
.pc-gift-item__content h3 {
  color: #132b53;
}

.pc-checkout-item__content p,
.pc-gift-item__content p,
.pc-summary-card p,
.pc-summary-card__line span,
.pc-checkout-note label,
.pc-address-preview p {
  color: #314a73;
}

.pc-payment-body {
  padding: 0 18px 12px;
}

.pc-payment-method {
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 92px;
  padding: 18px 2px;
  border-top: 1px solid rgba(22, 82, 197, 0.08);
  color: #203451;
}

.pc-payment-method__logo {
  width: 60px;
  height: 60px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 8px 18px rgba(15, 31, 79, 0.06);
}

.pc-payment-method__logo-image {
  width: 44px;
  height: 44px;
  object-fit: contain;
}

.pc-payment-method__logo-text {
  font-size: 0.95rem;
  font-weight: 800;
  line-height: 1;
}

.pc-payment-method__logo--cod .pc-payment-method__logo-text {
  color: #1c5db6;
}

.pc-payment-method__logo--payos .pc-payment-method__logo-text {
  color: #0f766e;
}

.pc-payment-method__label {
  font-size: 0.96rem;
  font-weight: 700;
  color: #17345f;
}

.pc-promo-message {
  margin: 12px 0;
  padding: 10px 12px;
  border-radius: 12px;
  font-size: 0.92rem;
  font-weight: 600;
}

.pc-promo-message--success {
  background: rgba(25, 135, 84, 0.12);
  color: #0f7a49;
}

.pc-promo-message--error {
  background: rgba(220, 53, 69, 0.12);
  color: #b42318;
}

.pc-promo-message--info {
  background: rgba(32, 111, 241, 0.08);
  color: #1c56c4;
}

.pc-order-alert {
  display: grid;
  gap: 4px;
  margin: 14px 0;
  padding: 12px 14px;
  border: 1px solid rgba(220, 53, 69, 0.22);
  border-radius: 14px;
  background: rgba(220, 53, 69, 0.1);
  color: #b42318;
}

.pc-order-alert strong {
  font-size: 0.96rem;
  color: #991b1b;
}

.pc-order-alert span {
  font-size: 0.92rem;
  line-height: 1.45;
}

.pc-applied-promo {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  padding: 14px 16px;
  border-radius: 16px;
  background: rgba(32, 111, 241, 0.08);
}

.pc-applied-promo strong {
  color: #153c78;
}

.pc-reward-box {
  margin-bottom: 12px;
  padding: 14px 16px;
  border: 1px solid rgba(25, 135, 84, 0.14);
  border-radius: 16px;
  background: rgba(25, 135, 84, 0.06);
}

.pc-reward-box__head,
.pc-reward-box__toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.pc-reward-box__head span,
.pc-reward-box__toggle span {
  color: #17345f;
  font-weight: 700;
}

.pc-reward-box__head strong {
  color: #0f7a49;
}

.pc-reward-box__toggle {
  margin-top: 10px;
  cursor: pointer;
}

.pc-reward-box__toggle input {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.pc-reward-box__hint {
  margin: 8px 0 0;
  color: #5f7393;
  font-size: 0.9rem;
}

.pc-link-button {
  background: none;
  border: none;
  color: #1d63ea;
  font-weight: 700;
  padding: 0;
}

.pc-summary-card__line--link {
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(22, 82, 197, 0.08);
}

.pc-code-option {
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 20px;
  padding: 18px;
  background: #fff;
}

.pc-code-option.is-disabled {
  opacity: 0.6;
  background: #f3f5f8;
  filter: grayscale(0.1);
}

.pc-code-option__notice {
  color: #9098a8;
  font-size: 0.86rem;
  font-weight: 700;
}

.pc-code-section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 4px;
  color: #17345f;
}

.pc-code-section-heading span {
  font-size: 0.88rem;
  color: #65789c;
}

.pc-product-info {
  display: flex;
  gap: 16px;
}

.pc-product-info__thumb {
  width: 88px;
  height: 88px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 20px;
  background: linear-gradient(135deg, #ffe7f2, #f1f6ff);
  overflow: hidden;
  color: #1d63ea;
  font-size: 1.6rem;
}

.pc-product-info__thumb.pink {
  background: linear-gradient(135deg, #ffe7f2, #f2f6ff);
}

.pc-product-info__thumb img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.pc-product-info__content {
  flex: 1;
}

.pc-product-info__content h4 {
  margin-bottom: 10px;
  color: #17345f;
  font-size: 1.15rem;
  font-weight: 800;
}

.pc-product-info__meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px 16px;
  margin-bottom: 12px;
  color: #5f7393;
  font-size: 0.94rem;
}

.pc-product-info__price {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 12px;
}

.pc-product-info__price strong {
  color: #17345f;
  font-size: 1.2rem;
}

.pc-product-info__price span,
.pc-product-info__note {
  color: #6a7d9d;
  font-size: 0.94rem;
}

.pc-product-info__description {
  margin-bottom: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: #f8fbff;
}

.pc-product-info__description strong {
  display: block;
  margin-bottom: 6px;
  color: #17345f;
  font-size: 0.94rem;
  font-weight: 700;
}

.pc-product-info__description p {
  margin: 0;
  color: #5f7393;
  font-size: 0.94rem;
  line-height: 1.55;
}

.pc-product-info__note {
  margin: 12px 0 0;
}

.pc-delivery-body {
  padding: 0 18px 18px;
}

.pc-delivery-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.pc-delivery-header strong {
  display: block;
  margin-bottom: 4px;
  color: #203451;
  font-size: 0.96rem;
}

.pc-delivery-header p {
  margin: 0;
  color: #6a7d9d;
}

.pc-address-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pc-address-option {
  width: 100%;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 18px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 18px;
  background: #f8fbff;
  text-align: left;
  color: #203451;
}

.pc-address-option.is-active {
  border-color: rgba(22, 82, 197, 0.35);
  background: rgba(29, 99, 234, 0.07);
  box-shadow: 0 6px 18px rgba(22, 82, 197, 0.08);
}

.pc-address-option > i {
  color: #1d63ea;
  font-size: 1rem;
}

.pc-address-option__main {
  flex: 1;
}

.pc-address-option__top {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 6px;
}

.pc-address-option__top strong {
  color: #17345f;
}

.pc-address-option__main p {
  margin: 0 0 8px;
  color: #6a7d9d;
  line-height: 1.5;
}

.pc-address-option__badge,
.pc-address-option__type {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 700;
}

.pc-address-option__badge {
  background: rgba(25, 135, 84, 0.14);
  color: #13734c;
}

.pc-address-option__type {
  background: rgba(29, 99, 234, 0.1);
  color: #1d63ea;
}

.pc-address-empty {
  padding: 18px;
  border: 1px dashed rgba(22, 82, 197, 0.18);
  border-radius: 18px;
  background: #f8fbff;
}

.pc-address-empty p {
  margin: 0 0 14px;
  color: #6a7d9d;
}

@media (max-width: 767px) {
  .pc-applied-promo {
    flex-direction: column;
    align-items: stretch;
  }

  .pc-delivery-body {
    padding: 0 14px 14px;
  }

  .pc-payment-body {
    padding: 0 14px 10px;
  }

  .pc-product-info {
    flex-direction: column;
  }

  .pc-product-info__meta {
    grid-template-columns: 1fr;
  }

  .pc-delivery-header {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>

