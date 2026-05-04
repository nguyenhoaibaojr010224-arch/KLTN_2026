<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--blue mb-3">
          <i class="bi bi-shop"></i>
          Bán hàng
        </div>
        <h2 class="page-section-title">Lập hóa đơn tại quầy</h2>
        <p class="page-section-copy mb-0">
          Tìm sản phẩm, chọn đơn vị bán, nhập số lượng và thanh toán trực tiếp cho khách nhận thuốc tại quầy.
        </p>
      </div>

      <div class="d-flex flex-wrap align-items-start gap-2">
        <div class="soft-badge">
          <i class="bi bi-person-workspace"></i>
          {{ employeeName }}
        </div>
        <button class="btn btn-outline-primary" type="button" @click="loadProducts" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          Tải lại
        </button>
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-7">
        <label class="form-label fw-semibold">Tìm thuốc</label>
        <div class="input-group input-group-lg">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input
            v-model.trim="keyword"
            class="form-control"
            type="search"
            placeholder="Tìm thuốc theo tên, mã thuốc, nhóm thuốc"
            @keyup.enter="loadProducts"
          />
        </div>
      </div>

      <div class="col-lg-5">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary btn-lg" type="button" @click="loadProducts" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            Tìm
          </button>
          <button class="btn btn-outline-secondary btn-lg" type="button" @click="resetSearch">Làm mới</button>
        </div>
      </div>
    </div>

    <div v-if="catalogError" class="alert alert-danger mt-4 mb-0">{{ catalogError }}</div>
  </section>

  <section class="row g-4">
    <div class="col-xl-8">
      <article class="content-card h-100">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
          <div>
            <h3 class="panel-title">Danh sách sản phẩm</h3>
            <p class="panel-subtitle mb-0">Chọn đơn vị bán rồi bấm thêm vào giỏ tại quầy.</p>
          </div>
          <span class="soft-badge soft-badge--teal">{{ products.length }} sản phẩm</span>
        </div>

        <div v-if="loading" class="counter-empty">
          <span class="spinner-border spinner-border-sm me-2"></span>
          Đang tải sản phẩm...
        </div>

        <div v-else-if="products.length" class="counter-product-grid">
          <article v-for="product in products" :key="product.ma_thuoc" class="counter-product-card">
            <div class="counter-product-card__thumb">
              <img v-if="product.hinh_anh_url" :src="product.hinh_anh_url" :alt="product.ten_thuoc" />
              <i v-else class="bi bi-capsule-pill"></i>
            </div>

            <div class="counter-product-card__body">
              <span>{{ product.ma_thuoc }}</span>
              <h4>{{ product.ten_thuoc }}</h4>
              <p>{{ product.nha_san_xuat || product.nhan || "PharmaGo" }}</p>
              <small>
                Tồn: {{ formatNumber(getProductStock(product)) }}
                {{ getSelectedOption(product)?.ten_don_vi || product.don_vi_tinh || "" }}
              </small>
            </div>

            <div class="counter-product-card__actions">
              <strong>{{ formatCurrency(getProductPrice(product)) }}</strong>
              <select
                v-model="selectedUnits[product.ma_thuoc]"
                class="form-select form-select-sm"
                aria-label="Chọn đơn vị bán"
              >
                <option
                  v-for="option in getProductOptions(product)"
                  :key="`${product.ma_thuoc}-${option.ten_don_vi}`"
                  :value="option.ten_don_vi"
                >
                  {{ option.ten_don_vi }} - {{ formatCurrency(option.gia_ban) }}
                </option>
              </select>
              <button class="btn btn-primary btn-sm" type="button" @click="addToCart(product)">
                <i class="bi bi-plus-lg"></i>
              </button>
            </div>
          </article>
        </div>

        <div v-else class="counter-empty">
          Chưa có sản phẩm phù hợp. Hãy thử từ khóa khác hoặc bấm tải lại.
        </div>
      </article>
    </div>

    <div class="col-xl-4">
      <article class="content-card counter-cart-card">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
          <div>
            <h3 class="panel-title">Giỏ tại quầy</h3>
            <p class="panel-subtitle mb-0">{{ cartItemCount }} sản phẩm</p>
          </div>
          <button class="btn btn-sm btn-outline-secondary" type="button" @click="clearCart" :disabled="!cart.length">
            Xóa giỏ
          </button>
        </div>

        <div class="counter-customer-box mb-3">
          <label class="form-label fw-semibold mb-2">Số điện thoại khách hàng</label>
          <div class="counter-phone-row">
            <input
              v-model.trim="customerPhone"
              class="form-control"
              type="tel"
              placeholder="Nhập số điện thoại để tích điểm"
              @input="handleCustomerPhoneInput"
              @keyup.enter="lookupCustomerByPhone"
            />
            <button
              class="btn btn-outline-primary"
              type="button"
              :disabled="phoneLookupLoading || !customerPhone"
              @click="lookupCustomerByPhone"
            >
              <span v-if="phoneLookupLoading" class="spinner-border spinner-border-sm me-2"></span>
              Áp dụng
            </button>
          </div>

          <div v-if="selectedCustomer" class="counter-customer-result">
            <div>
              <span>Đã chọn khách</span>
              <strong>{{ selectedCustomer.ten_khach_hang }}</strong>
              <small>
                {{ selectedCustomer.so_dien_thoai || selectedCustomer.email }} ·
                {{ formatNumber(selectedCustomer.diem_tich_luy) }} điểm
              </small>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="button" @click="clearSelectedCustomer">
              Bỏ số
            </button>
          </div>

          <div v-else-if="phoneLookupError" class="alert alert-danger py-2 px-3 mb-0">
            {{ phoneLookupError }}
          </div>

          <small v-else class="text-secondary">Nhập số điện thoại khách để cộng điểm cho hóa đơn.</small>
        </div>

        <label v-if="selectedCustomer" class="counter-use-points mb-3">
          <input v-model="usePoints" class="form-check-input" type="checkbox" :disabled="!canUsePoints" />
          <span>
            Sử dụng điểm
            <small v-if="canUsePoints">Giảm dự kiến {{ formatCurrency(estimatedPointDiscount) }}</small>
            <small v-else>Chưa đủ điều kiện dùng điểm.</small>
          </span>
        </label>

        <div v-if="cart.length" class="counter-cart-list">
          <article v-for="item in cart" :key="item.key" class="counter-cart-item">
            <div class="counter-cart-item__main">
              <div class="counter-cart-item__thumb">
                <img v-if="item.hinh_anh_url" :src="item.hinh_anh_url" :alt="item.ten_thuoc" />
                <i v-else class="bi bi-capsule-pill"></i>
              </div>
              <div>
                <strong>{{ item.ten_thuoc }}</strong>
                <span>{{ item.ma_thuoc }} · {{ item.don_vi }}</span>
                <small>{{ formatCurrency(item.gia_ban) }} / {{ item.don_vi }}</small>
              </div>
            </div>

            <div class="counter-cart-item__foot">
              <div class="counter-stepper">
                <button type="button" @click="changeQuantity(item, -1)">-</button>
                <input v-model.number="item.so_luong" type="number" min="1" @change="normalizeQuantity(item)" />
                <button type="button" @click="changeQuantity(item, 1)">+</button>
              </div>
              <strong>{{ formatCurrency(item.gia_ban * item.so_luong) }}</strong>
              <button class="counter-remove" type="button" @click="removeCartItem(item.key)" aria-label="Xóa sản phẩm">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </article>
        </div>

        <div v-else class="counter-empty counter-empty--cart">
          Chưa có sản phẩm trong giỏ.
        </div>

        <div class="counter-summary">
          <div>
            <span>Tạm tính</span>
            <strong>{{ formatCurrency(subtotal) }}</strong>
          </div>
          <div v-if="estimatedPointDiscount > 0">
            <span>Giảm bằng điểm</span>
            <strong>-{{ formatCurrency(estimatedPointDiscount) }}</strong>
          </div>
          <div>
            <span>VAT 10%</span>
            <strong>{{ formatCurrency(vatAmount) }}</strong>
          </div>
          <div class="counter-summary__total">
            <span>Thanh toán</span>
            <strong>{{ formatCurrency(totalAmount) }}</strong>
          </div>
          <small v-if="selectedCustomer">
            Dự kiến cộng {{ formatNumber(estimatedEarnedPoints) }} điểm sau thanh toán.
          </small>
        </div>

        <div class="counter-payment">
          <label class="form-label fw-semibold">Phương thức thanh toán</label>
          <select v-model="paymentMethod" class="form-select">
            <option value="tien_mat">Tiền mặt</option>
            <option value="momo">MoMo</option>
            <option value="zalopay">ZaloPay</option>
            <option value="the_atm">Thẻ ATM</option>
            <option value="the_quoc_te">Thẻ quốc tế</option>
          </select>
        </div>

        <button class="btn btn-primary btn-lg w-100 mt-3" type="button" :disabled="checkoutDisabled" @click="checkout">
          <span v-if="checkingOut" class="spinner-border spinner-border-sm me-2"></span>
          Thanh toán tại quầy
        </button>

        <div v-if="lastInvoice" class="counter-receipt">
          <span>Hóa đơn vừa tạo</span>
          <strong>{{ lastInvoice.ma_hoa_don }}</strong>
          <small>{{ formatCurrency(lastInvoice.tien_thanh_toan) }}</small>
        </div>
      </article>
    </div>
  </section>

</template>

<script>
import { getCatalogThuocs } from "../../../api/catalogApi";
import { createCounterSale, findCounterCustomerByPhone } from "../../../api/counterSaleApi";
import { authState } from "../../../lib/authStorage";
import {
  buildProductUnitCartKey,
  getDefaultProductUnit,
  getProductUnitOption,
  getProductUnitOptions,
  getProductUnitStock,
} from "../../../lib/productUnits";
import { showToast } from "../../../lib/toast";

export default {
  name: "BanTaiQuay",
  data() {
    return {
      keyword: "",
      products: [],
      selectedUnits: {},
      cart: [],
      loading: false,
      catalogError: "",
      paymentMethod: "tien_mat",
      customerPhone: "",
      selectedCustomer: null,
      customerToken: "",
      usePoints: false,
      phoneLookupLoading: false,
      phoneLookupError: "",
      checkingOut: false,
      lastInvoice: null,
    };
  },
  computed: {
    employeeName() {
      return authState.user?.ho_ten || authState.user?.ten_nhan_vien || authState.user?.name || "Nhân viên tại quầy";
    },
    cartItemCount() {
      return this.cart.reduce((total, item) => total + Number(item.so_luong || 0), 0);
    },
    subtotal() {
      return this.cart.reduce((total, item) => total + Number(item.gia_ban || 0) * Number(item.so_luong || 0), 0);
    },
    pointBlocks() {
      if (!this.selectedCustomer || this.subtotal <= 0) {
        return 0;
      }

      const availableBlocks = Math.floor(Number(this.selectedCustomer.diem_tich_luy || 0) / 1000);
      const orderBlocks = Math.floor(this.subtotal / 10000);

      return Math.min(availableBlocks, orderBlocks);
    },
    canUsePoints() {
      return this.pointBlocks > 0;
    },
    estimatedPointDiscount() {
      return this.usePoints && this.canUsePoints ? this.pointBlocks * 10000 : 0;
    },
    amountAfterDiscount() {
      return Math.max(this.subtotal - this.estimatedPointDiscount, 0);
    },
    vatAmount() {
      return Math.round(this.amountAfterDiscount * 0.1);
    },
    totalAmount() {
      return this.amountAfterDiscount + this.vatAmount;
    },
    estimatedEarnedPoints() {
      return this.selectedCustomer ? Math.floor(this.subtotal / 1000) : 0;
    },
    checkoutDisabled() {
      return this.checkingOut || !this.cart.length;
    },
  },
  mounted() {
    this.loadProducts();
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    formatNumber(value) {
      return new Intl.NumberFormat("vi-VN").format(Number(value || 0));
    },
    normalizeError(error, fallback) {
      const firstErrors = error?.errors ? Object.values(error.errors).flat() : [];
      return firstErrors[0] || error?.message || fallback;
    },
    async loadProducts() {
      this.loading = true;
      this.catalogError = "";

      try {
        const response = await getCatalogThuocs(this.keyword);
        this.products = Array.isArray(response?.data) ? response.data : [];
        this.products.forEach((product) => {
          if (!this.selectedUnits[product.ma_thuoc]) {
            this.selectedUnits[product.ma_thuoc] = getDefaultProductUnit(product);
          }
        });
      } catch (error) {
        this.catalogError = this.normalizeError(error, "Không tải được danh sách thuốc.");
      } finally {
        this.loading = false;
      }
    },
    resetSearch() {
      this.keyword = "";
      this.loadProducts();
    },
    getProductOptions(product) {
      return getProductUnitOptions(product);
    },
    getSelectedOption(product) {
      return getProductUnitOption(product, this.selectedUnits[product.ma_thuoc]);
    },
    getProductPrice(product) {
      return Number(this.getSelectedOption(product)?.gia_ban || product.gia_ban || 0);
    },
    getProductStock(product) {
      return getProductUnitStock(product, this.selectedUnits[product.ma_thuoc]);
    },
    addToCart(product) {
      const option = this.getSelectedOption(product);
      const unitName = option?.ten_don_vi || product.don_vi_tinh || "";
      const stock = Number(getProductUnitStock(product, unitName) || 0);

      if (stock <= 0) {
        showToast("Sản phẩm này đã hết hàng.", "error");
        return;
      }

      const key = buildProductUnitCartKey(product.ma_thuoc, unitName);
      const existing = this.cart.find((item) => item.key === key);

      if (existing) {
        if (existing.so_luong >= existing.so_luong_ton) {
          showToast("Số lượng trong giỏ đã bằng tồn kho hiện tại.", "error");
          return;
        }

        existing.so_luong += 1;
        return;
      }

      this.cart.push({
        key,
        ma_thuoc: product.ma_thuoc,
        ten_thuoc: product.ten_thuoc,
        hinh_anh_url: product.hinh_anh_url || "",
        don_vi: unitName,
        gia_ban: Number(option?.gia_ban || product.gia_ban || 0),
        so_luong: 1,
        so_luong_ton: stock,
      });
    },
    normalizeQuantity(item) {
      const stock = Math.max(1, Number(item.so_luong_ton || 1));
      const value = Math.floor(Number(item.so_luong || 1));
      item.so_luong = Math.min(Math.max(value, 1), stock);
    },
    changeQuantity(item, amount) {
      item.so_luong = Number(item.so_luong || 1) + amount;
      this.normalizeQuantity(item);
    },
    removeCartItem(key) {
      this.cart = this.cart.filter((item) => item.key !== key);
    },
    clearCart() {
      this.cart = [];
      this.lastInvoice = null;
    },
    handleCustomerPhoneInput() {
      this.phoneLookupError = "";

      if (this.selectedCustomer && this.customerPhone !== this.selectedCustomer.so_dien_thoai) {
        this.selectedCustomer = null;
        this.customerToken = "";
        this.usePoints = false;
      }
    },
    clearSelectedCustomer() {
      this.selectedCustomer = null;
      this.customerToken = "";
      this.customerPhone = "";
      this.usePoints = false;
      this.phoneLookupError = "";
    },
    async lookupCustomerByPhone() {
      if (!this.customerPhone) {
        this.phoneLookupError = "Vui lòng nhập số điện thoại khách hàng.";
        return;
      }

      this.phoneLookupLoading = true;
      this.phoneLookupError = "";

      try {
        const response = await findCounterCustomerByPhone({
          so_dien_thoai: this.customerPhone,
        });
        this.selectedCustomer = response?.data?.khach_hang || null;
        this.customerToken = response?.data?.customer_token || "";
        this.customerPhone = this.selectedCustomer?.so_dien_thoai || this.customerPhone;
        this.usePoints = false;
        showToast("Đã áp dụng khách hàng để tích điểm.");
      } catch (error) {
        this.selectedCustomer = null;
        this.customerToken = "";
        this.usePoints = false;
        this.phoneLookupError = this.normalizeError(error, "Không tìm thấy khách hàng với số điện thoại này.");
      } finally {
        this.phoneLookupLoading = false;
      }
    },
    async checkout() {
      if (this.checkoutDisabled) {
        return;
      }

      if (this.customerPhone && !this.customerToken) {
        this.phoneLookupError = "Bấm áp dụng số điện thoại trước khi thanh toán để tích điểm.";
        return;
      }

      this.checkingOut = true;

      try {
        const payload = {
          phuong_thuc_thanh_toan: this.paymentMethod,
          items: this.cart.map((item) => ({
            ma_thuoc: item.ma_thuoc,
            don_vi: item.don_vi,
            so_luong: Number(item.so_luong || 1),
          })),
        };

        if (this.customerToken) {
          payload.customer_token = this.customerToken;
          payload.su_dung_diem = this.usePoints;
        }

        const response = await createCounterSale(payload);
        this.lastInvoice = response?.data || null;
        this.cart = [];

        if (response?.data?.khach_hang_tich_diem) {
          this.selectedCustomer = response.data.khach_hang_tich_diem;
          this.customerPhone = this.selectedCustomer?.so_dien_thoai || this.customerPhone;
          this.usePoints = false;
        }

        showToast("Đã tạo hóa đơn tại quầy.");
        this.loadProducts();
      } catch (error) {
        showToast(this.normalizeError(error, "Không tạo được hóa đơn tại quầy."), "error", 4200);
      } finally {
        this.checkingOut = false;
      }
    },
  },
};
</script>

<style>
.counter-product-grid {
  display: grid !important;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)) !important;
  gap: 16px !important;
}

.counter-product-card {
  display: grid !important;
  grid-template-columns: 92px minmax(0, 1fr) !important;
  grid-template-areas:
    "thumb body"
    "thumb actions";
  gap: 12px 14px !important;
  min-width: 0 !important;
  padding: 16px !important;
  border: 1px solid rgba(22, 82, 197, 0.1) !important;
  border-radius: 18px !important;
  background: #fff !important;
  box-shadow: 0 10px 24px rgba(15, 31, 79, 0.05) !important;
}

.counter-product-card__thumb {
  grid-area: thumb;
  width: 92px !important;
  height: 92px !important;
  max-width: 92px !important;
  max-height: 92px !important;
  display: grid !important;
  place-items: center !important;
  align-self: start !important;
  border-radius: 18px !important;
  background: linear-gradient(145deg, #ffe3f1, #edf7ff) !important;
  overflow: hidden !important;
}

.counter-product-card__thumb img {
  display: block !important;
  width: 86px !important;
  height: 86px !important;
  max-width: 86px !important;
  max-height: 86px !important;
  object-fit: contain !important;
}

.counter-product-card__thumb i,
.counter-cart-item__thumb i {
  color: #1652c5;
  font-size: 2rem;
}

.counter-product-card__body {
  grid-area: body;
  min-width: 0;
}

.counter-product-card__body span {
  color: #64748b;
  font-size: 0.78rem;
  font-weight: 800;
}

.counter-product-card__body h4 {
  display: -webkit-box;
  overflow: hidden;
  margin: 4px 0 6px;
  color: #0d2b55;
  font-size: 1rem;
  font-weight: 900;
  line-height: 1.35;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
}

.counter-product-card__body p,
.counter-product-card__body small {
  display: block;
  margin: 0;
  color: #64748b;
  font-size: 0.86rem;
}

.counter-product-card__body p {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.counter-product-card__actions {
  grid-area: actions;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 42px;
  gap: 8px;
  align-items: center;
}

.counter-product-card__actions strong {
  grid-column: 1 / -1;
  color: #111827;
  font-size: 1.02rem;
  font-weight: 900;
}

.counter-cart-card {
  position: sticky;
  top: 112px;
  align-self: start;
  max-height: calc(100vh - 132px);
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-gutter: stable;
}

.counter-cart-card::-webkit-scrollbar {
  width: 8px;
}

.counter-cart-card::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: rgba(22, 82, 197, 0.22);
}

.counter-cart-card::-webkit-scrollbar-track {
  background: transparent;
}

.counter-customer-box,
.counter-use-points,
.counter-summary,
.counter-receipt {
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 16px;
  background: #f7faff;
}

.counter-customer-box {
  display: grid;
  gap: 12px;
  padding: 14px;
}

.counter-phone-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 108px;
  gap: 10px;
  align-items: stretch;
}

.counter-phone-row .form-control,
.counter-phone-row .btn {
  width: 100%;
  min-height: 44px;
  border-radius: 12px;
}

.counter-phone-row .form-control {
  min-width: 0;
}

.counter-use-points span {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.counter-customer-result {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px;
  border-radius: 14px;
  background: #fff;
}

.counter-customer-result > div {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.counter-customer-result span,
.counter-use-points small {
  color: #64748b;
  font-size: 0.82rem;
}

.counter-customer-result strong {
  color: #0d2b55;
  font-weight: 900;
}

.counter-customer-result small {
  color: #64748b;
}

.counter-use-points {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px;
}

.counter-use-points span {
  color: #0d2b55;
  font-weight: 800;
}

.counter-cart-list {
  display: grid;
  gap: 12px;
  padding-right: 4px;
}

.counter-cart-item {
  padding: 14px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 16px;
  background: #fff;
}

.counter-cart-item__main {
  display: grid;
  grid-template-columns: 64px minmax(0, 1fr);
  gap: 12px;
  align-items: start;
}

.counter-cart-item__thumb {
  width: 64px;
  height: 64px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  background: linear-gradient(145deg, #ffe3f1, #edf7ff);
  overflow: hidden;
}

.counter-cart-item__thumb img {
  display: block;
  width: 58px;
  height: 58px;
  object-fit: contain;
}

.counter-cart-item__main strong,
.counter-cart-item__main span,
.counter-cart-item__main small {
  display: block;
}

.counter-cart-item__main strong {
  color: #0d2b55;
  font-weight: 900;
  line-height: 1.35;
}

.counter-cart-item__main span,
.counter-cart-item__main small {
  color: #64748b;
  font-size: 0.84rem;
}

.counter-cart-item__foot {
  display: grid;
  grid-template-columns: 116px minmax(0, 1fr) 34px;
  gap: 10px;
  align-items: center;
  margin-top: 12px;
}

.counter-cart-item__foot > strong {
  justify-self: end;
  color: #111827;
}

.counter-stepper {
  display: grid;
  grid-template-columns: 32px 48px 32px;
  border: 1px solid rgba(22, 82, 197, 0.14);
  border-radius: 12px;
  overflow: hidden;
}

.counter-stepper button,
.counter-stepper input,
.counter-remove {
  border: 0;
  background: #fff;
}

.counter-stepper button {
  color: #1652c5;
  font-weight: 900;
}

.counter-stepper input {
  min-width: 0;
  text-align: center;
  border-inline: 1px solid rgba(22, 82, 197, 0.12);
}

.counter-remove {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  color: #dc2626;
}

.counter-summary {
  display: grid;
  gap: 10px;
  margin-top: 16px;
  padding: 16px;
}

.counter-summary > div {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  color: #526887;
}

.counter-summary strong {
  color: #111827;
}

.counter-summary__total {
  padding-top: 12px;
  border-top: 1px solid rgba(22, 82, 197, 0.12);
  color: #0d2b55 !important;
  font-size: 1.05rem;
  font-weight: 900;
}

.counter-summary small {
  color: #64748b;
}

.counter-payment {
  margin-top: 16px;
}

.counter-receipt {
  display: grid;
  gap: 2px;
  margin-top: 16px;
  padding: 14px;
  color: #0d2b55;
}

.counter-receipt span,
.counter-receipt small {
  color: #64748b;
}

.counter-receipt strong {
  font-weight: 900;
}

.counter-empty {
  display: grid;
  place-items: center;
  min-height: 220px;
  padding: 24px;
  border: 1px dashed rgba(22, 82, 197, 0.22);
  border-radius: 18px;
  color: #64748b;
  text-align: center;
}

.counter-empty--cart {
  min-height: 180px;
}

@media (max-width: 1199.98px) {
  .counter-cart-card {
    position: static;
    max-height: none;
    overflow: visible;
  }
}

@media (max-width: 767.98px) {
  .counter-product-grid,
  .counter-product-card,
  .counter-cart-item__foot,
  .counter-phone-row {
    grid-template-columns: 1fr;
    grid-template-areas: none;
  }

  .counter-product-card__thumb,
  .counter-product-card__body,
  .counter-product-card__actions {
    grid-area: auto;
  }

  .counter-cart-item__foot > strong {
    justify-self: start;
  }
}
</style>
