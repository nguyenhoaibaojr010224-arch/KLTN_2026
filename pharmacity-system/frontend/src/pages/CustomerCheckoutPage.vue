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
              <div class="pc-checkout-item__thumb" :class="item.imageTone || 'pink'"></div>
              <div class="pc-checkout-item__content">
                <h3>{{ item.ten }}</h3>
                <p>Phân loại: {{ item.donVi }}</p>
                <button type="button"><i class="bi bi-info-circle"></i> Thông tin sản phẩm</button>
                <div v-if="item.promoTags?.length" class="pc-cart-item__tags">
                  <span v-for="tag in item.promoTags" :key="tag">{{ tag }}</span>
                </div>
              </div>
              <div class="pc-checkout-item__qty">x{{ item.soLuong }}</div>
              <div class="pc-checkout-item__price">{{ formatCurrency(item.gia * item.soLuong) }}</div>
            </article>
          </section>

          <section v-if="giftItems.length" class="pc-order-card">
            <div class="pc-order-card__sectiontitle">Quà tặng</div>
            <article v-for="gift in giftItems" :key="gift.id" class="pc-gift-item">
              <div class="pc-gift-item__thumb"></div>
              <div class="pc-gift-item__content">
                <h3>{{ gift.ten }}</h3>
                <p>{{ gift.loai }}</p>
              </div>
              <div class="pc-gift-item__meta">x{{ gift.soLuong }}</div>
              <div class="pc-gift-item__meta">{{ formatCurrency(gift.gia) }}</div>
            </article>
          </section>

          <section class="pc-order-card">
            <div class="pc-order-card__sectiontitle">Hình thức nhận hàng</div>
            <div class="pc-delivery-switch">
              <button type="button" class="active">Giao hàng tận nơi</button>
              <button type="button" disabled>Nhận tại nhà thuốc</button>
            </div>

            <button type="button" class="pc-address-button" @click="goToAddress">
              <i class="bi bi-plus-lg"></i>
              {{ defaultAddress ? "Cập nhật địa chỉ nhận hàng" : "Thêm địa chỉ nhận hàng" }}
            </button>

            <div v-if="defaultAddress" class="pc-address-preview">
              <strong>{{ defaultAddress.hoTen }} | {{ defaultAddress.soDienThoai }}</strong>
              <p>
                {{ defaultAddress.soNha }}, {{ defaultAddress.phuongXa }}, {{ defaultAddress.quanHuyen }},
                {{ defaultAddress.tinhThanh }}
              </p>
            </div>

            <div class="pc-checkout-row">
              <span><i class="bi bi-truck"></i> Phương thức vận chuyển</span>
              <button type="button">Thay đổi</button>
            </div>

            <div class="pc-checkout-note">
              <label for="checkout-note">Ghi chú cho đơn hàng (không bắt buộc)</label>
              <textarea id="checkout-note" v-model="state.note" rows="3" placeholder="Nhập ghi chú ở đây"></textarea>
            </div>
          </section>

          <section class="pc-order-card">
            <div class="pc-order-card__sectiontitle">Phương thức thanh toán</div>
            <label v-for="method in paymentMethods" :key="method.id" class="pc-payment-method">
              <input v-model="state.paymentMethod" type="radio" :value="method.id" name="payment-method" />
              <span class="pc-payment-method__logo">{{ method.short }}</span>
              <span>{{ method.label }}</span>
            </label>
          </section>
        </div>

        <div class="col-xl-4">
          <aside class="pc-summary-stack">
            <div class="pc-summary-card">
              <div class="pc-summary-card__line">
                <span><i class="bi bi-coin"></i> Dùng P-Xu Vàng</span>
                <button type="button">Tùy chọn</button>
              </div>
              <div class="pc-summary-card__line">
                <span>P-Xu Vàng hiện có</span>
                <strong>{{ state.profile.pxu.toLocaleString("vi-VN") }}</strong>
              </div>
            </div>

            <div class="pc-summary-card">
              <div class="pc-summary-card__line">
                <span>Hoá đơn VAT</span>
                <button type="button">Yêu cầu xuất hoá đơn</button>
              </div>
            </div>

            <div class="pc-summary-card">
              <div class="pc-summary-card__line">
                <span>Ẩn thông tin sản phẩm</span>
                <label class="form-check form-switch">
                  <input v-model="state.hideProductInfo" class="form-check-input" type="checkbox" role="switch" />
                </label>
              </div>
              <p>Thông tin sản phẩm sẽ được ẩn trên Phiếu gửi hàng.</p>
            </div>

            <div class="pc-summary-card">
              <h2>Pharmacity khuyến mãi</h2>
              <div class="pc-summary-card__voucher">
                <span><i class="bi bi-ticket-perforated"></i> Khuyến mãi</span>
                <button type="button">Chọn mã</button>
              </div>

              <div class="pc-summary-card__line">
                <span>Tạm tính</span>
                <strong>{{ formatCurrency(subtotal) }}</strong>
              </div>
              <div class="pc-summary-card__line">
                <span>Phí vận chuyển</span>
                <strong>{{ formatCurrency(0) }}</strong>
              </div>
              <div class="pc-summary-card__line">
                <span>Giảm giá sản phẩm</span>
                <strong class="text-success">-{{ formatCurrency(productDiscount) }}</strong>
              </div>
              <div class="pc-summary-card__total">
                <span>Tổng tiền</span>
                <strong>{{ formatCurrency(orderTotal) }}</strong>
              </div>

              <label class="pc-summary-card__agree">
                <input v-model="acceptedTerms" type="checkbox" />
                <span>Bằng cách tích vào ô chọn, bạn đã đồng ý với Điều khoản Pharmacity và xác nhận đã đọc kỹ thông tin sản phẩm</span>
              </label>

              <button class="btn btn-primary btn-lg w-100 rounded-4" type="button" :disabled="!acceptedTerms" @click="submitOrder">
                Đặt hàng
              </button>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useCustomerStore } from "../lib/customerStore";

const router = useRouter();
const { state, selectedItems, subtotal, productDiscount, orderTotal, defaultAddress, giftItems, placeOrder } =
  useCustomerStore();
const acceptedTerms = ref(true);

const paymentMethods = [
  { id: "cod", label: "Tiền mặt", short: "COD" },
  { id: "momo", label: "MoMo", short: "MoMo" },
  { id: "zalopay", label: "ZaloPay", short: "Zalo" },
  { id: "atm", label: "Thẻ ATM", short: "ATM" },
];

function formatCurrency(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function goToAddress() {
  router.push("/tai-khoan/dia-chi");
}

function submitOrder() {
  const orderId = placeOrder();
  window.alert(`Đặt hàng thành công. Mã đơn: ${orderId}`);
  router.push("/tai-khoan/lich-su-don-hang");
}
</script>
