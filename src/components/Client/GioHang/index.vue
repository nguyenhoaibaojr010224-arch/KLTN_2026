<template>
  <div class="pc-page">
    <div class="container-fluid pc-container">
      <div class="pc-page__title">
        <h1>Giỏ hàng ({{ cartCount }})</h1>
      </div>

      <div v-if="!state.cart.length" class="pc-empty-card">
        <i class="bi bi-cart-x"></i>
        <h2>Giỏ hàng của bạn đang trống</h2>
        <p>Hãy quay lại trang chủ để chọn thêm sản phẩm trước khi thanh toán.</p>
        <RouterLink to="/" class="btn btn-primary rounded-pill px-4">Tiếp tục mua sắm</RouterLink>
      </div>

      <template v-else>
        <div class="row g-4 align-items-stretch">
          <div class="col-xl-8 d-flex">
            <section class="pc-order-card pc-order-card--main">
              <div class="pc-order-card__tablehead">
                <div class="pc-order-card__producthead">
                  <button type="button" class="pc-check-button" @click="toggleAll">
                    <i class="bi" :class="allSelected ? 'bi-check-square-fill' : 'bi-square'"></i>
                  </button>
                  <span>Sản phẩm</span>
                  <button type="button" @click="clearCart">Xóa tất cả</button>
                </div>
                <div>Đơn giá</div>
                <div>Số lượng</div>
              </div>

              <article v-for="item in state.cart" :key="item.id" class="pc-cart-item">
                <div class="pc-cart-item__main">
                  <button
                    type="button"
                    class="pc-check-button"
                    :disabled="isOutOfStock(item)"
                    @click="toggleCartSelection(item.id)"
                  >
                    <i class="bi" :class="item.selected ? 'bi-check-square-fill' : 'bi-square'"></i>
                  </button>

                  <div class="pc-cart-item__thumb" :class="item.imageTone || 'pink'"></div>

                  <div class="pc-cart-item__content">
                    <h3>{{ item.ten }}</h3>
                    <p>Phân loại: {{ item.donVi }}</p>
                    <div v-if="isOutOfStock(item)" class="pc-cart-item__tags">
                      <span class="pc-cart-item__tag pc-cart-item__tag--danger">Hết hàng</span>
                    </div>
                    <div v-else-if="item.promoTags && item.promoTags.length" class="pc-cart-item__tags">
                      <span v-for="tag in item.promoTags" :key="tag">{{ tag }}</span>
                    </div>
                  </div>
                </div>

                <div class="pc-cart-item__price">
                  <strong>{{ formatCurrency(item.gia) }}</strong>
                  <span v-if="item.giaGoc > item.gia">{{ formatCurrency(item.giaGoc) }}</span>
                </div>

                <div class="pc-cart-item__quantity">
                  <button
                    type="button"
                    :disabled="isOutOfStock(item)"
                    @click="updateCartQuantity(item.id, item.soLuong - 1)"
                  >
                    -
                  </button>
                  <span>{{ item.soLuong }}</span>
                  <button
                    type="button"
                    :disabled="isOutOfStock(item)"
                    @click="updateCartQuantity(item.id, item.soLuong + 1)"
                  >
                    +
                  </button>
                  <button type="button" class="pc-cart-item__remove" @click="removeCartItem(item.id)">
                    <i class="bi bi-trash3"></i>
                  </button>
                </div>
              </article>
            </section>
          </div>

          <div class="col-xl-4 d-flex">
            <aside class="pc-summary-card pc-summary-card--cart">
              <h2>Đơn hàng</h2>
              <div class="pc-summary-card__voucher">
                <span><i class="bi bi-ticket-perforated"></i> Khuyến mãi</span>
                <RouterLink v-if="canUsePromotionCode" to="/thanh-toan" class="pc-link-button">Chọn mã</RouterLink>
                <RouterLink v-else to="/login" class="pc-link-button">Đăng nhập để dùng mã</RouterLink>
              </div>

              <p v-if="!canUsePromotionCode" class="pc-summary-card__hint">
                Bạn cần đăng nhập trước khi sử dụng mã giảm giá cho tổng hóa đơn.
              </p>

              <div class="pc-summary-card__line">
                <span>Tạm tính</span>
                <strong>{{ formatCurrency(subtotal) }}</strong>
              </div>
              <div class="pc-summary-card__line" v-if="orderPromotionDiscount > 0">
                <span>Giảm giá mã</span>
                <strong class="text-success">-{{ formatCurrency(orderPromotionDiscount) }}</strong>
              </div>
              <div class="pc-summary-card__total">
                <span>Tổng tiền</span>
                <strong>{{ formatCurrency(orderTotal) }}</strong>
              </div>

              <RouterLink to="/thanh-toan" class="btn btn-primary btn-lg w-100 rounded-4 pc-summary-card__cta">
                Mua hàng ({{ selectedCount }})
              </RouterLink>
            </aside>
          </div>
        </div>

        <div v-if="giftItems.length" class="row g-4">
          <div class="col-xl-8">
            <section class="pc-order-card pc-order-card--gifts">
              <div class="pc-order-card__sectiontitle">Quà tặng</div>
              <article v-for="gift in giftItems" :key="gift.id" class="pc-gift-item">
                <div class="pc-gift-item__thumb"></div>
                <div class="pc-gift-item__content">
                  <h3>{{ gift.ten }}</h3>
                  <p>Phân loại: {{ gift.loai }}</p>
                </div>
                <div class="pc-gift-item__meta">x{{ gift.soLuong }}</div>
                <div class="pc-gift-item__meta">{{ formatCurrency(gift.gia) }}</div>
              </article>
            </section>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import { useCustomerStore } from "../../../lib/customerStore";

export default {
  name: "GioHangClient",

  data() {
    return {
      customerStore: useCustomerStore(),
    };
  },

  async mounted() {
    await this.customerStore.syncCartPricesWithCatalog?.();
  },

  computed: {
    state() {
      return this.customerStore.state;
    },
    cartCount() {
      return this.customerStore.cartCount;
    },
    selectedCount() {
      return this.customerStore.selectedCount;
    },
    subtotal() {
      return this.customerStore.subtotal;
    },
    orderPromotionDiscount() {
      return this.customerStore.orderPromotionDiscount;
    },
    orderTotal() {
      return this.customerStore.orderTotal;
    },
    canUsePromotionCode() {
      return this.customerStore.canUsePromotionCode;
    },
    giftItems() {
      return this.customerStore.giftItems;
    },
    allSelected() {
      return this.state.cart.length > 0 && this.state.cart.every((item) => item.selected);
    },
  },

  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    updateCartQuantity(itemId, nextValue) {
      this.customerStore.updateCartQuantity(itemId, nextValue);
    },
    toggleCartSelection(itemId) {
      this.customerStore.toggleCartSelection(itemId);
    },
    removeCartItem(itemId) {
      this.customerStore.removeCartItem(itemId);
    },
    clearCart() {
      this.customerStore.clearCart();
    },
    isOutOfStock(item) {
      return Number(item?.tonKho || 0) <= 0;
    },
    toggleAll() {
      const next = !this.allSelected;
      this.state.cart.forEach((item) => {
        item.selected = this.isOutOfStock(item) ? false : next;
      });
      localStorage.setItem("pharmacity_customer_cart", JSON.stringify(this.state.cart));
    },
  },
};
</script>

<style scoped>
.pc-order-card--main,
.pc-summary-card--cart {
  width: 100%;
}

.pc-summary-card--cart {
  display: flex;
  flex-direction: column;
}

.pc-summary-card__cta {
  margin-top: auto;
}

.pc-summary-card__hint {
  margin: 0 0 14px;
  color: #5e6f91;
  font-size: 0.92rem;
  line-height: 1.5;
}

.pc-order-card--gifts {
  margin-top: 4px;
}

.pc-check-button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.pc-cart-item__tag--danger {
  background: rgba(220, 53, 69, 0.12);
  color: #c93045;
}

.pc-cart-item__quantity button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
</style>
