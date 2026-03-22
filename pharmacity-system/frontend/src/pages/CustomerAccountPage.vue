<template>
  <div class="pc-page">
    <div class="container-fluid pc-container">
      <div class="row g-4 align-items-start">
        <div class="col-xl-3">
          <aside class="pc-account-sidebar">
            <div class="pc-account-profile">
              <div class="pc-account-profile__avatar">KH</div>
              <div>
                <h3>{{ state.profile.hoTen }}</h3>
                <div class="pc-account-profile__points">{{ state.profile.pxu.toLocaleString("vi-VN") }} P-Xu</div>
              </div>
            </div>

            <div class="pc-rank-card">
              <div class="pc-rank-card__title">{{ state.profile.rankName }}</div>
              <div class="pc-rank-card__bar"><span></span></div>
              <p>Chi tiêu thêm 3.800.720 đ để thăng hạng</p>
            </div>

            <nav class="pc-account-nav">
              <RouterLink v-for="item in menuItems" :key="item.section" :to="item.to" class="pc-account-nav__item">
                <i :class="item.icon"></i>
                <span>{{ item.label }}</span>
              </RouterLink>
            </nav>
          </aside>
        </div>

        <div class="col-xl-9">
          <section v-if="section === 'thong-tin'" class="pc-account-panel">
            <h1>Thông tin cá nhân</h1>
            <div class="pc-account-form__avatar">KH</div>

            <div class="pc-account-form">
              <div class="pc-form-field">
                <label>Họ và tên</label>
                <input v-model="profileDraft.hoTen" type="text" />
              </div>

              <div class="pc-form-field">
                <label>Số điện thoại</label>
                <input v-model="profileDraft.soDienThoai" type="text" />
              </div>

              <div class="pc-form-field">
                <label>Email</label>
                <input v-model="profileDraft.email" type="email" />
              </div>

              <div class="pc-form-field">
                <label>Ngày sinh</label>
                <input v-model="profileDraft.ngaySinh" type="date" />
              </div>

              <div class="pc-form-field">
                <label>Giới tính</label>
                <select v-model="profileDraft.gioiTinh">
                  <option value="">Giới tính</option>
                  <option value="Nam">Nam</option>
                  <option value="Nữ">Nữ</option>
                  <option value="Khác">Khác</option>
                </select>
              </div>

              <div class="pc-form-field">
                <label>Mật khẩu</label>
                <div class="pc-password-row">
                  <input type="password" value="123456789" disabled />
                  <button type="button" @click="showPasswordHint = !showPasswordHint">Cập nhật</button>
                </div>
                <small v-if="showPasswordHint">Phần đổi mật khẩu có thể nối tiếp API ở bước sau.</small>
              </div>
            </div>

            <div class="pc-account-panel__actions">
              <button class="btn btn-primary rounded-pill px-4" type="button" @click="saveProfile">Lưu thông tin</button>
            </div>
          </section>

          <section v-else-if="section === 'dia-chi'" class="pc-account-panel">
            <div class="pc-account-panel__head">
              <h1>Sổ địa chỉ nhận hàng</h1>
              <button class="btn btn-outline-primary rounded-pill px-4" type="button" @click="showAddressModal = true">
                <i class="bi bi-plus-lg me-2"></i> Thêm địa chỉ
              </button>
            </div>

            <div class="pc-address-list">
              <article v-for="address in state.addresses" :key="address.id" class="pc-address-card">
                <div>
                  <h3>{{ address.hoTen }} | {{ address.soDienThoai }}</h3>
                  <p>
                    {{ address.soNha }}, {{ address.phuongXa }}, {{ address.quanHuyen }}, {{ address.tinhThanh }}
                  </p>
                </div>
                <span class="pc-address-card__tag">{{ address.loaiDiaChi }}</span>
              </article>
            </div>
          </section>

          <section v-else-if="section === 'lich-su-don-hang'" class="pc-account-panel">
            <h1>Lịch sử đơn hàng</h1>
            <div class="pc-history-list">
              <article v-for="order in state.orders" :key="order.id" class="pc-history-card">
                <div>
                  <strong>{{ order.id }}</strong>
                  <p>{{ order.ngay }} • {{ order.sanPham }} sản phẩm</p>
                </div>
                <div class="text-end">
                  <div class="pc-history-card__status">{{ order.trangThai }}</div>
                  <strong>{{ formatCurrency(order.tongTien) }}</strong>
                </div>
              </article>
            </div>
          </section>

          <section v-else-if="section === 'thong-bao'" class="pc-account-panel">
            <div class="pc-account-panel__head">
              <h1>Thông báo của tôi</h1>
              <button type="button" class="pc-link-button">Đọc tất cả</button>
            </div>

            <div class="pc-tabline">
              <button v-for="tab in notificationTabs" :key="tab" type="button" :class="{ active: tab === activeTab }" @click="activeTab = tab">
                {{ tab }}
              </button>
            </div>

            <div v-if="filteredNotifications.length" class="pc-notification-list">
              <article v-for="item in filteredNotifications" :key="item.id" class="pc-notification-card">
                <div>
                  <strong>{{ item.group }}</strong>
                  <p>{{ item.tieuDe }}</p>
                </div>
                <span :class="['pc-notification-card__badge', { read: item.daDoc }]">
                  {{ item.daDoc ? "Đã đọc" : "Mới" }}
                </span>
              </article>
            </div>

            <div v-else class="pc-empty-card pc-empty-card--inline">
              <i class="bi bi-bell-slash"></i>
              <h2>Chưa có thông báo nào</h2>
            </div>
          </section>

          <section v-else class="pc-account-panel">
            <h1>{{ fallbackTitle }}</h1>
            <div class="pc-empty-card pc-empty-card--inline">
              <i class="bi bi-grid"></i>
              <h2>Phần này đã có khung giao diện</h2>
              <p>Mình đã dựng sẵn điều hướng để sau này bạn nối thêm API hoặc dữ liệu thật cho từng mục.</p>
            </div>
          </section>
        </div>
      </div>
    </div>

    <div v-if="showAddressModal" class="pc-modal-backdrop" @click.self="closeAddressModal">
      <div class="pc-modal-card pc-modal-card--form">
        <button class="pc-modal-card__close" type="button" @click="closeAddressModal">
          <i class="bi bi-x-lg"></i>
        </button>

        <h2>Thêm địa chỉ nhận hàng</h2>

        <div class="pc-address-form">
          <div class="pc-form-field">
            <label>Họ và Tên</label>
            <input v-model="addressDraft.hoTen" type="text" placeholder="Nhập họ và tên" />
          </div>

          <div class="pc-form-field">
            <label>Số điện thoại</label>
            <input v-model="addressDraft.soDienThoai" type="text" placeholder="Nhập số điện thoại" />
          </div>

          <div class="pc-form-field">
            <label>Địa chỉ</label>
            <select v-model="addressDraft.tinhThanh">
              <option value="">Chọn Tỉnh/Thành phố</option>
              <option>TP. Hồ Chí Minh</option>
              <option>Hà Nội</option>
              <option>Đà Nẵng</option>
            </select>
          </div>

          <div class="pc-address-form__row">
            <div class="pc-form-field">
              <select v-model="addressDraft.quanHuyen">
                <option value="">Chọn Quận/Huyện</option>
                <option>Quận 1</option>
                <option>Quận 3</option>
                <option>Quận Bình Thạnh</option>
              </select>
            </div>

            <div class="pc-form-field">
              <select v-model="addressDraft.phuongXa">
                <option value="">Chọn Phường/Xã</option>
                <option>Phường Võ Thị Sáu</option>
                <option>Phường Bến Nghé</option>
                <option>Phường 25</option>
              </select>
            </div>
          </div>

          <div class="pc-form-field">
            <input v-model="addressDraft.soNha" type="text" placeholder="Nhập số nhà/tên đường" />
          </div>

          <div class="pc-form-field">
            <label>Loại địa chỉ</label>
            <div class="pc-pill-switch">
              <button
                v-for="item in ['Nhà riêng', 'Công ty']"
                :key="item"
                type="button"
                :class="{ active: addressDraft.loaiDiaChi === item }"
                @click="addressDraft.loaiDiaChi = item"
              >
                {{ item }}
              </button>
            </div>
          </div>

          <label class="pc-checkbox-line">
            <input v-model="addressDraft.macDinh" type="checkbox" />
            <span>Đặt làm mặc định</span>
          </label>
        </div>

        <div class="pc-modal-card__actions">
          <button class="btn btn-light rounded-pill px-4" type="button" @click="closeAddressModal">Quay lại</button>
          <button class="btn btn-primary rounded-pill px-4" type="button" @click="submitAddress">Lưu lại</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { useCustomerStore } from "../lib/customerStore";

const route = useRoute();
const { state, saveAddress, updateProfile } = useCustomerStore();
const section = computed(() => route.params.section || "thong-tin");
const showAddressModal = ref(false);
const showPasswordHint = ref(false);
const activeTab = ref("Đơn hàng");
const notificationTabs = ["Đơn hàng", "Thương hiệu", "Ưu đãi", "Sức khỏe", "Tin tức", "Hệ thống"];

const menuItems = [
  { section: "thong-tin", label: "Thông tin cá nhân", icon: "bi bi-person-circle", to: "/tai-khoan/thong-tin" },
  { section: "dia-chi", label: "Sổ địa chỉ nhận hàng", icon: "bi bi-geo-alt", to: "/tai-khoan/dia-chi" },
  { section: "lich-su-don-hang", label: "Lịch sử đơn hàng", icon: "bi bi-receipt", to: "/tai-khoan/lich-su-don-hang" },
  { section: "ma-giam-gia", label: "Mã giảm giá", icon: "bi bi-ticket-perforated", to: "/tai-khoan/ma-giam-gia" },
  { section: "pxu", label: "Lịch sử P-Xu Vàng", icon: "bi bi-coin", to: "/tai-khoan/pxu" },
  { section: "quy-che", label: "Quy chế xếp hạng", icon: "bi bi-award", to: "/tai-khoan/quy-che" },
  { section: "thong-bao", label: "Thông báo của tôi", icon: "bi bi-bell", to: "/tai-khoan/thong-bao" },
  { section: "thanh-toan", label: "Quản lý thanh toán", icon: "bi bi-credit-card", to: "/tai-khoan/thanh-toan" },
  { section: "gia-dinh", label: "Hồ sơ gia đình", icon: "bi bi-people", to: "/tai-khoan/gia-dinh" },
];

const profileDraft = reactive({
  hoTen: state.profile.hoTen,
  soDienThoai: state.profile.soDienThoai,
  email: state.profile.email,
  ngaySinh: state.profile.ngaySinh,
  gioiTinh: state.profile.gioiTinh,
});

const addressDraft = reactive({
  hoTen: state.profile.hoTen,
  soDienThoai: state.profile.soDienThoai.replaceAll("*", "0"),
  tinhThanh: "",
  quanHuyen: "",
  phuongXa: "",
  soNha: "",
  loaiDiaChi: "Nhà riêng",
  macDinh: false,
});

const filteredNotifications = computed(() => state.notifications.filter((item) => item.group === activeTab.value));
const fallbackTitle = computed(() => menuItems.find((item) => item.section === section.value)?.label || "Tài khoản");

watch(
  () => state.profile,
  (value) => {
    profileDraft.hoTen = value.hoTen;
    profileDraft.soDienThoai = value.soDienThoai;
    profileDraft.email = value.email;
    profileDraft.ngaySinh = value.ngaySinh;
    profileDraft.gioiTinh = value.gioiTinh;
  },
  { deep: true }
);

function formatCurrency(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function saveProfile() {
  updateProfile({ ...profileDraft });
  window.alert("Đã cập nhật thông tin cá nhân.");
}

function closeAddressModal() {
  showAddressModal.value = false;
}

function submitAddress() {
  saveAddress({ ...addressDraft });
  showAddressModal.value = false;
  window.alert("Đã lưu địa chỉ nhận hàng.");
}
</script>
