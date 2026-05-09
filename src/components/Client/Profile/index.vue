<template>
  <div class="pc-page">
    <div class="container-fluid pc-container">
      <div class="row g-4 align-items-start">
        <div class="col-xl-3">
          <aside class="pc-account-sidebar">
            <div class="pc-account-profile">
              <div class="pc-account-profile__avatar">
                <img
                  v-if="currentAvatarUrl && !avatarLoadFailed"
                  :src="currentAvatarUrl"
                  alt="Ảnh đại diện"
                  @error="handleAvatarError"
                />
                <span v-else>{{ avatarText }}</span>
              </div>

              <div class="pc-account-profile__content">
                <h3>{{ state.profile.hoTen }}</h3>
                <p class="pc-account-profile__subtext">{{ state.profile.email || state.profile.soDienThoai }}</p>
              </div>
            </div>

            <div class="pc-reward-summary">
              <div class="pc-reward-summary__label">
                <span>Điểm thưởng hiện có</span>
                <button
                  type="button"
                  class="pc-info-icon"
                  aria-label="1000 điểm giảm được 10000"
                  @click="openRewardInfoModal"
                >
                  <i class="bi bi-info-circle"></i>
                </button>
              </div>
              <strong>{{ formatNumber(state.profile.pxu) }} điểm</strong>
            </div>

            <nav class="pc-account-nav">
              <RouterLink
                v-for="item in menuItems"
                :key="item.section"
                :to="item.to"
                class="pc-account-nav__item"
              >
                <i :class="item.icon"></i>
                <span>{{ item.label }}</span>
              </RouterLink>
            </nav>
          </aside>
        </div>

        <div class="col-xl-9">
          <section v-if="section === 'thong-tin'" class="pc-account-panel">
            <h1>Thông tin cá nhân</h1>

            <div class="pc-account-form__avatar-wrap">
              <div class="pc-account-form__avatar">
                <img
                  v-if="currentAvatarUrl && !avatarLoadFailed"
                  :src="currentAvatarUrl"
                  alt="Ảnh đại diện"
                  @error="handleAvatarError"
                />
                <span v-else>{{ avatarText }}</span>
              </div>

              <label class="btn btn-outline-primary rounded-pill px-3 mt-3">
                <input
                  hidden
                  type="file"
                  accept="image/png,image/jpeg,image/jpg,image/webp"
                  @change="handleAvatarChange"
                />
                Chọn ảnh đại diện
              </label>
            </div>

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
                <input
                  v-model="profileDraft.email"
                  class="pc-input-locked"
                  type="email"
                  disabled
                  title="Email là thông tin duy nhất, không thể chỉnh sửa."
                />
              </div>

              <div class="pc-form-field">
                <label>Địa chỉ</label>
                <input v-model="profileDraft.diaChi" type="text" />
              </div>

              <div class="pc-form-field">
                <label>Ngày sinh</label>
                <DatePickerInput
                  v-model="profileDraft.ngaySinh"
                  placeholder="dd/mm/yyyy"
                />
              </div>

              <div class="pc-form-field">
                <label>Giới tính</label>
                <select v-model="profileDraft.gioiTinh">
                  <option value="">Chọn giới tính</option>
                  <option value="Nam">Nam</option>
                  <option value="Nữ">Nữ</option>
                  <option value="Khác">Khác</option>
                </select>
              </div>

              <div class="pc-form-field">
                <label>Mật khẩu</label>
                <div class="pc-password-row">
                  <input type="text" value="Mật khẩu đã được bảo mật" disabled />
                  <button type="button" @click="togglePasswordForm">
                    {{ showPasswordForm ? "Đóng" : "Cập nhật" }}
                  </button>
                </div>
                <div v-if="showPasswordForm" class="pc-password-form">
                  <div class="pc-password-form__grid">
                    <div class="pc-form-field">
                      <label>Mật khẩu hiện tại</label>
                      <input
                        v-model="passwordDraft.currentPassword"
                        type="password"
                        autocomplete="current-password"
                        :class="{ 'is-invalid': passwordErrors.currentPassword }"
                        @input="clearPasswordError('currentPassword')"
                      />
                      <div v-if="passwordErrors.currentPassword" class="pc-field-error">{{ passwordErrors.currentPassword }}</div>
                    </div>
                    <div class="pc-form-field">
                      <label>Mật khẩu mới</label>
                      <input
                        v-model="passwordDraft.newPassword"
                        type="password"
                        autocomplete="new-password"
                        :class="{ 'is-invalid': passwordErrors.newPassword }"
                        @input="clearPasswordError('newPassword')"
                      />
                      <div v-if="passwordErrors.newPassword" class="pc-field-error">{{ passwordErrors.newPassword }}</div>
                    </div>
                    <div class="pc-form-field">
                      <label>Xác nhận mật khẩu mới</label>
                      <input
                        v-model="passwordDraft.confirmPassword"
                        type="password"
                        autocomplete="new-password"
                        :class="{ 'is-invalid': passwordErrors.confirmPassword }"
                        @input="clearPasswordError('confirmPassword')"
                      />
                      <div v-if="passwordErrors.confirmPassword" class="pc-field-error">{{ passwordErrors.confirmPassword }}</div>
                    </div>
                  </div>
                  <div class="pc-password-form__actions">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" @click="cancelPasswordForm">
                      Hủy
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" @click="submitPasswordChange">
                      Đổi mật khẩu
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="pc-account-panel__actions">
              <button class="btn btn-primary rounded-pill px-4" type="button" @click="saveProfile">
                Lưu thông tin
              </button>
            </div>
          </section>

          <section v-else-if="section === 'dia-chi'" class="pc-account-panel">
            <div class="pc-account-panel__head">
              <h1>Sổ địa chỉ nhận hàng</h1>
              <button class="btn btn-outline-primary rounded-pill px-4" type="button" @click="openAddAddressModal">
                <i class="bi bi-plus-lg me-2"></i> Thêm địa chỉ
              </button>
            </div>

            <div class="pc-address-list">
              <article v-for="address in state.addresses" :key="address.id" class="pc-address-card">
                <div class="pc-address-card__content">
                  <h3 :title="`${address.hoTen} | ${address.soDienThoai}`">{{ address.hoTen }} | {{ address.soDienThoai }}</h3>
                  <p :title="formatFullAddress(address)">{{ formatFullAddress(address) }}</p>
                </div>
                <div class="pc-address-card__actions">
                  <span v-if="address.macDinh" class="pc-address-card__tag pc-address-card__tag--default">Mặc định</span>
                  <span class="pc-address-card__tag">{{ address.loaiDiaChi }}</span>
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="openEditAddressModal(address)">
                    Chỉnh sửa
                  </button>
                </div>
              </article>
            </div>
          </section>

          <section v-else-if="section === 'lich-su-don-hang'" class="pc-account-panel">
            <h1>Lịch sử đơn hàng</h1>
            <div class="pc-history-list">
              <article
                v-for="order in state.orders"
                :key="order.id"
                class="pc-history-card"
                :class="{ 'is-open': expandedOrderId === order.id }"
              >
                <div class="pc-history-card__head">
                  <strong class="pc-history-card__code">{{ order.id }}</strong>
                  <div class="pc-history-card__meta">
                    <p>{{ order.ngay }} • {{ order.sanPham }} sản phẩm</p>
                    <small v-if="order.phuongThucThanhToanLabel">{{ order.phuongThucThanhToanLabel }}</small>
                  </div>
                  <div class="pc-history-card__summary">
                    <div class="pc-history-card__status">{{ order.trangThai }}</div>
                    <div class="pc-history-card__total">
                      <span>Tổng thanh toán</span>
                      <strong>{{ formatCurrency(order.tongTien) }}</strong>
                    </div>
                    <button
                      v-if="canContinuePayosPayment(order)"
                      type="button"
                      class="pc-history-card__payos"
                      @click.stop="continuePayosPayment(order)"
                    >
                      Tiếp tục thanh toán
                    </button>
                    <button
                      type="button"
                      class="pc-history-card__reorder"
                      @click.stop="reorderOrder(order)"
                    >
                      Mua lại
                    </button>
                    <button
                      type="button"
                      class="pc-history-card__toggle"
                      @click.stop="toggleOrderDetails(order.id)"
                    >
                      {{ expandedOrderId === order.id ? "Thu gọn" : "Xem sản phẩm đã mua" }}
                    </button>
                  </div>
                </div>

                <div v-if="expandedOrderId === order.id" class="pc-history-card__details">
                  <div class="pc-history-card__overview">
                    <section class="pc-history-info">
                      <span class="pc-history-info__label">Người nhận</span>
                      <strong>{{ order.nguoiNhan || state.profile.hoTen }}</strong>
                      <p v-if="order.soDienThoaiNhan">{{ order.soDienThoaiNhan }}</p>
                      <p v-if="order.diaChiGiaoHang">{{ order.diaChiGiaoHang }}</p>
                    </section>

                    <section class="pc-history-info">
                      <span class="pc-history-info__label">Thanh toán</span>
                      <strong>{{ order.phuongThucThanhToanLabel || "Tiền mặt" }}</strong>
                      <p v-if="order.trangThaiThanhToan">Trạng thái: {{ paymentStatusLabel(order.trangThaiThanhToan) }}</p>
                      <p v-if="order.thoiGianThanhToan">Ghi nhận: {{ formatDateTime(order.thoiGianThanhToan) }}</p>
                      <p v-if="order.maGiaoDich">Mã giao dịch: {{ order.maGiaoDich }}</p>
                      <button
                        v-if="canContinuePayosPayment(order)"
                        type="button"
                        class="pc-history-info__payos"
                        @click="continuePayosPayment(order)"
                      >
                        Mở lại PayOS
                      </button>
                    </section>

                    <section class="pc-history-info pc-history-info--totals">
                      <span class="pc-history-info__label">Chi tiết tiền</span>
                      <div class="pc-history-info__row">
                        <span>Tạm tính</span>
                        <strong>{{ formatCurrency(order.tamTinh) }}</strong>
                      </div>
                      <div v-if="order.giamGiaMa > 0" class="pc-history-info__row">
                        <span>Mã giảm giá{{ order.maGiamGia ? ` (${order.maGiamGia})` : "" }}</span>
                        <strong class="text-success">-{{ formatCurrency(order.giamGiaMa) }}</strong>
                      </div>
                      <div v-if="order.giamGiaDiem > 0" class="pc-history-info__row">
                        <span>Điểm thưởng</span>
                        <strong class="text-success">-{{ formatCurrency(order.giamGiaDiem) }}</strong>
                      </div>
                      <div v-if="order.giamGia > 0 && !order.giamGiaMa && !order.giamGiaDiem" class="pc-history-info__row">
                        <span>Giảm giá</span>
                        <strong class="text-success">-{{ formatCurrency(order.giamGia) }}</strong>
                      </div>
                      <div v-if="!order.giamGia" class="pc-history-info__row">
                        <span>Giảm giá</span>
                        <strong>{{ formatCurrency(0) }}</strong>
                      </div>
                      <div class="pc-history-info__row">
                        <span>VAT</span>
                        <strong>{{ formatCurrency(order.thueVat) }}</strong>
                      </div>
                      <div v-if="order.diemDaSuDung > 0" class="pc-history-info__row pc-history-info__row--points">
                        <span>Điểm đã dùng</span>
                        <strong>-{{ formatNumber(order.diemDaSuDung) }} điểm</strong>
                      </div>
                      <div v-if="order.diemDaCong > 0" class="pc-history-info__row pc-history-info__row--points">
                        <span>Điểm đã cộng</span>
                        <strong class="text-primary">+{{ formatNumber(order.diemDaCong) }} điểm</strong>
                      </div>
                      <div class="pc-history-info__row pc-history-info__row--total">
                        <span>Tổng thanh toán</span>
                        <strong>{{ formatCurrency(order.tongTien) }}</strong>
                      </div>
                    </section>
                  </div>

                  <div v-if="order.ghiChu || order.ghiChuHeThong" class="pc-history-card__notes">
                    <div v-if="order.ghiChu" class="pc-history-note">
                      <span class="pc-history-info__label">Ghi chú đơn hàng</span>
                      <p>{{ order.ghiChu }}</p>
                    </div>
                    <div v-if="order.ghiChuHeThong" class="pc-history-note">
                      <span class="pc-history-info__label">Lưu ý hệ thống</span>
                      <p>{{ order.ghiChuHeThong }}</p>
                    </div>
                  </div>

                  <div v-if="order.timeline && order.timeline.length" class="pc-history-timeline">
                    <div
                      v-for="event in order.timeline"
                      :key="`${order.id}-${event.id}`"
                      class="pc-history-timeline__item"
                    >
                      <span class="pc-history-timeline__dot"></span>
                      <div class="pc-history-timeline__content">
                        <strong>{{ event.label }}</strong>
                        <p>{{ formatDateTime(event.thoiGian) }}</p>
                        <small v-if="event.moTa">{{ event.moTa }}</small>
                      </div>
                    </div>
                  </div>

                  <article v-for="item in order.items || []" :key="`${order.id}-${item.id}`" class="pc-history-product">
                    <div class="pc-history-product__thumb" :class="`tone-${item.imageTone || 'pink'}`">
                      <img
                        v-if="item.hinhAnhUrl"
                        :src="item.hinhAnhUrl"
                        :alt="item.ten"
                        style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: inherit"
                      />
                    </div>
                    <div class="pc-history-product__content">
                      <strong>{{ item.ten }}</strong>
                      <p v-if="item.loai">Loại: {{ item.loai }}</p>
                      <p v-if="item.donVi">Phân loại: {{ item.donVi }}</p>
                      <p v-if="item.moTa">{{ item.moTa }}</p>
                    </div>
                    <div class="pc-history-product__meta">
                      <span>{{ item.soLuong }} x {{ formatCurrency(item.gia) }}</span>
                      <small>Thành tiền</small>
                      <strong>{{ formatCurrency(item.thanhTien || item.gia * item.soLuong) }}</strong>
                    </div>
                  </article>

                  <div v-if="!(order.items && order.items.length)" class="pc-history-card__empty">
                    <i class="bi bi-bag"></i>
                    <span>Đơn hàng chưa có chi tiết sản phẩm.</span>
                  </div>
                </div>
              </article>
            </div>
          </section>

          <section v-else-if="section === 'ma-giam-gia'" class="pc-account-panel">
            <div class="pc-account-panel__head">
              <h1>Mã giảm giá</h1>
            </div>

            <div v-if="discountCodesLoading" class="pc-empty-card pc-empty-card--inline">
              <div class="spinner-border text-primary mb-3"></div>
              <h2>Đang tải danh sách mã giảm giá</h2>
            </div>

            <div v-else-if="discountCodes.length" class="pc-discount-list">
              <article
                v-for="item in discountCodes"
                :key="item.id"
                class="pc-discount-card"
                :class="{ 'is-disabled': !item.co_the_ap_dung }"
              >
                <div class="pc-discount-card__code">{{ item.ma_giam_gia }}</div>
                <div class="pc-discount-card__content">
                  <h3>{{ item.ten_ma }}</h3>
                  <p>{{ item.mo_ta || "Áp dụng khi đơn hàng đủ điều kiện." }}</p>
                  <div class="pc-discount-card__meta">
                    <span>Giảm: {{ promotionValueLabel(item) }}</span>
                    <span>Đơn tối thiểu: {{ formatCurrency(item.gia_tri_don_toi_thieu) }}</span>
                    <span v-if="item.ngay_bat_dau || item.ngay_ket_thuc">
                      Hiệu lực: {{ item.ngay_bat_dau || "..." }} - {{ item.ngay_ket_thuc || "..." }}
                    </span>
                  </div>
                  <small v-if="!item.co_the_ap_dung" class="pc-discount-card__notice">
                    {{ item.ly_do_khong_ap_dung || "Chưa đủ điều kiện để sử dụng mã này." }}
                  </small>
                </div>
              </article>
            </div>

            <div v-else class="pc-empty-card pc-empty-card--inline">
              <i class="bi bi-ticket-perforated"></i>
              <h2>Chưa có mã giảm giá</h2>
              <p>Hiện chưa có mã giảm giá nào để hiển thị.</p>
            </div>
          </section>

          <section v-else-if="section === 'thong-bao'" class="pc-account-panel">
            <div class="pc-account-panel__head">
              <h1>Thông báo của tôi</h1>
              <button type="button" class="pc-link-button" @click="markAllNotificationsAsRead">Đọc tất cả</button>
            </div>

            <div class="pc-tabline">
              <button
                v-for="tab in notificationTabs"
                :key="tab"
                type="button"
                :class="{ active: tab === activeTab }"
                @click="activeTab = tab"
              >
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
              <p>Màn này đã có điều hướng sẵn, có thể nối thêm API hoặc dữ liệu thật ở bước sau.</p>
            </div>
          </section>
        </div>
      </div>
    </div>

    <div v-if="showRewardInfoModal" class="pc-modal-backdrop" @click.self="closeRewardInfoModal">
      <div class="pc-modal-card pc-modal-card--reward-info">
        <button class="pc-modal-card__close" type="button" @click="closeRewardInfoModal">
          <i class="bi bi-x-lg"></i>
        </button>

        <div class="pc-reward-modal">
          <div class="pc-reward-modal__icon">
            <i class="bi bi-info-circle"></i>
          </div>
          <div>
            <h2>Điểm thưởng</h2>
            <p>1000 điểm giảm được 10000</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showAddressModal" class="pc-modal-backdrop" @click.self="closeAddressModal">
      <div class="pc-modal-card pc-modal-card--form">
        <button class="pc-modal-card__close" type="button" @click="closeAddressModal">
          <i class="bi bi-x-lg"></i>
        </button>

        <h2>{{ addressDraft.id ? "Chỉnh sửa địa chỉ nhận hàng" : "Thêm địa chỉ nhận hàng" }}</h2>

        <div class="pc-address-form">
          <div class="pc-form-field">
            <label>Họ và tên</label>
            <input
              v-model="addressDraft.hoTen"
              type="text"
              maxlength="30"
              placeholder="Nhập họ và tên"
              :class="{ 'is-invalid': addressErrors.hoTen }"
              @input="handleAddressNameInput"
            />
            <div v-if="addressErrors.hoTen" class="pc-field-error">{{ addressErrors.hoTen }}</div>
          </div>

          <div class="pc-form-field">
            <label>Số điện thoại</label>
            <input
              v-model="addressDraft.soDienThoai"
              type="tel"
              inputmode="numeric"
              maxlength="10"
              placeholder="Nhập số điện thoại"
              :class="{ 'is-invalid': addressErrors.soDienThoai }"
              @input="handleAddressPhoneInput"
            />
            <div v-if="addressErrors.soDienThoai" class="pc-field-error">{{ addressErrors.soDienThoai }}</div>
          </div>

          <div class="pc-form-field">
            <label>Tỉnh/Thành phố</label>
            <select v-model="addressDraft.tinhThanh">
              <option value="">Chọn Tỉnh/Thành phố</option>
              <option v-for="city in cityOptions" :key="city" :value="city">{{ city }}</option>
            </select>
          </div>

          <div class="pc-address-form__row">
            <div class="pc-form-field">
              <label>Quận/Huyện</label>
              <select v-model="addressDraft.quanHuyen" :disabled="!addressDraft.tinhThanh">
                <option value="">Chọn Quận/Huyện</option>
                <option v-for="district in districtOptions" :key="district" :value="district">{{ district }}</option>
              </select>
            </div>

            <div class="pc-form-field">
              <label>Phường/Xã</label>
              <select v-model="addressDraft.phuongXa" :disabled="!addressDraft.quanHuyen">
                <option value="">Chọn Phường/Xã</option>
                <option v-for="ward in wardOptions" :key="ward" :value="ward">{{ ward }}</option>
              </select>
            </div>
          </div>

          <div class="pc-form-field">
            <label>Số nhà/Tên đường</label>
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

<script>
import DatePickerInput from "../../Common/DatePickerInput.vue";
import { getAuthType } from "../../../lib/authStorage";
import { getCustomerOrderDiscountCodes } from "../../../api/pricingApi";
import { changePasswordApi } from "../../../api/profileApi";
import { useCustomerStore } from "../../../lib/customerStore";
import { formatDateInputValue, parseDateInputValue } from "../../../lib/dateInput";
import { translateErrorMessage } from "../../../lib/errorMessages";
import { validateStrongPassword } from "../../../lib/passwordRules";
import { showToast } from "../../../lib/toast";

function normalizePhoneDigits(value) {
  return String(value || "").replace(/\D/g, "").slice(0, 10);
}

function normalizeAddressName(value) {
  return String(value || "").replace(/\s+/g, " ").trim().slice(0, 30);
}

function limitAddressNameInput(value) {
  return String(value || "").replace(/\s+/g, " ").slice(0, 30);
}

function createEmptyAddressDraft(profile) {
  return {
    id: null,
    hoTen: normalizeAddressName(profile.hoTen),
    soDienThoai: normalizePhoneDigits(String(profile.soDienThoai || "").replaceAll("*", "0")),
    tinhThanh: "",
    quanHuyen: "",
    phuongXa: "",
    soNha: "",
    loaiDiaChi: "Nhà riêng",
    macDinh: false,
  };
}

const LOCATION_OPTIONS = {
  "TP. Hồ Chí Minh": {
    "Quận 1": ["Phường Bến Nghé", "Phường Bến Thành", "Phường Cầu Ông Lãnh"],
    "Quận 3": ["Phường Võ Thị Sáu", "Phường 1", "Phường 2"],
    "Quận Bình Thạnh": ["Phường 25", "Phường 26", "Phường 14"],
  },
  "Hà Nội": {
    "Quận Ba Đình": ["Phường Điện Biên", "Phường Kim Mã", "Phường Ngọc Hà"],
    "Quận Cầu Giấy": ["Phường Dịch Vọng", "Phường Mai Dịch", "Phường Nghĩa Tân"],
    "Quận Đống Đa": ["Phường Láng Hạ", "Phường Ô Chợ Dừa", "Phường Trung Liệt"],
  },
  "Đà Nẵng": {
    "Quận Hải Châu": ["Phường Thạch Thang", "Phường Hải Châu I", "Phường Bình Hiên"],
    "Quận Thanh Khê": ["Phường Thạc Gián", "Phường Chính Gián", "Phường Tân Chính"],
    "Quận Sơn Trà": ["Phường An Hải Bắc", "Phường Phước Mỹ", "Phường Mân Thái"],
  },
};

function normalizeAddressDraft(address) {
  const draft = {
    ...address,
    hoTen: normalizeAddressName(address?.hoTen),
    soDienThoai: normalizePhoneDigits(address?.soDienThoai),
    tinhThanh: address?.tinhThanh || "",
    quanHuyen: address?.quanHuyen || "",
    phuongXa: address?.phuongXa || "",
  };

  const validDistricts = Object.keys(LOCATION_OPTIONS[draft.tinhThanh] || {});
  if (!validDistricts.includes(draft.quanHuyen)) {
    draft.quanHuyen = "";
  }

  const validWards = LOCATION_OPTIONS[draft.tinhThanh]?.[draft.quanHuyen] || [];
  if (!validWards.includes(draft.phuongXa)) {
    draft.phuongXa = "";
  }

  return draft;
}

export default {
  name: "ProfileClient",
  components: {
    DatePickerInput,
  },

  data() {
    const customerStore = useCustomerStore();
    const state = customerStore.state;

    return {
      customerStore,
      state,
      showAddressModal: false,
      showPasswordForm: false,
      activeTab: "Đơn hàng",
      avatarFile: null,
      avatarLoadFailed: false,
      avatarObjectUrl: "",
      avatarPreviewUrl: state.profile.avatarUrl || "",
      showRewardInfoModal: false,
      discountCodesLoading: false,
      discountCodes: [],
      expandedOrderId: null,
      notificationTabs: ["Đơn hàng", "Thương hiệu", "Ưu đãi", "Sức khỏe", "Tin tức", "Hệ thống"],
      menuItems: [
        { section: "thong-tin", label: "Thông tin cá nhân", icon: "bi bi-person-circle", to: "/tai-khoan/thong-tin" },
        { section: "dia-chi", label: "Sổ địa chỉ nhận hàng", icon: "bi bi-geo-alt", to: "/tai-khoan/dia-chi" },
        { section: "lich-su-don-hang", label: "Lịch sử đơn hàng", icon: "bi bi-receipt", to: "/tai-khoan/lich-su-don-hang" },
        { section: "ma-giam-gia", label: "Mã giảm giá", icon: "bi bi-ticket-perforated", to: "/tai-khoan/ma-giam-gia" },
        { section: "thong-bao", label: "Thông báo của tôi", icon: "bi bi-bell", to: "/tai-khoan/thong-bao" },
      ],
      profileDraft: {
        hoTen: state.profile.hoTen,
        soDienThoai: state.profile.soDienThoai,
        email: state.profile.email,
        diaChi: state.profile.diaChi,
        ngaySinh: formatDateInputValue(state.profile.ngaySinh),
        gioiTinh: state.profile.gioiTinh,
      },
      passwordDraft: {
        currentPassword: "",
        newPassword: "",
        confirmPassword: "",
      },
      passwordErrors: {
        currentPassword: "",
        newPassword: "",
        confirmPassword: "",
      },
      addressErrors: {
        hoTen: "",
        soDienThoai: "",
      },
      addressDraft: createEmptyAddressDraft(state.profile),
    };
  },

  computed: {
    section() {
      return this.$route.params.section || "thong-tin";
    },
    cityOptions() {
      return Object.keys(LOCATION_OPTIONS);
    },
    districtOptions() {
      return Object.keys(LOCATION_OPTIONS[this.addressDraft.tinhThanh] || {});
    },
    wardOptions() {
      return LOCATION_OPTIONS[this.addressDraft.tinhThanh]?.[this.addressDraft.quanHuyen] || [];
    },
    currentAvatarUrl() {
      return this.avatarPreviewUrl || this.state.profile.avatarUrl || "";
    },
    avatarText() {
      const name = String(this.state.profile.hoTen || "KH").trim();
      return name ? name.slice(0, 2).toUpperCase() : "KH";
    },
    filteredNotifications() {
      return this.state.notifications.filter((item) => item.group === this.activeTab);
    },
    fallbackTitle() {
      return this.menuItems.find((item) => item.section === this.section)?.label || "Tài khoản";
    },
  },

  watch: {
    "state.profile": {
      handler(value) {
        this.profileDraft = {
          hoTen: value.hoTen || "",
          soDienThoai: value.soDienThoai || "",
          email: value.email || "",
          diaChi: value.diaChi || "",
          ngaySinh: formatDateInputValue(value.ngaySinh),
          gioiTinh: value.gioiTinh || "",
        };
      },
      deep: true,
    },
    "state.profile.avatarUrl": {
      handler(value) {
        this.avatarLoadFailed = false;
        if (!this.avatarFile) {
          this.avatarPreviewUrl = value || "";
        }
      },
      immediate: true,
    },
    section: {
      immediate: true,
      handler(nextSection) {
        if (!this.isValidSection(nextSection)) {
          this.$router.replace("/tai-khoan/thong-tin");
          return;
        }

        if (nextSection === "ma-giam-gia") {
          this.loadDiscountCodes();
        }

        if (nextSection === "lich-su-don-hang" || nextSection === "thong-bao") {
          this.customerStore.syncOrdersFromApi();
        }

        this.syncNotificationRouteState();
      },
    },
    "state.cart": {
      deep: true,
      handler() {
        if (this.section === "ma-giam-gia") {
          this.loadDiscountCodes();
        }
      },
    },
    '$route.fullPath': {
      handler() {
        this.syncNotificationRouteState();
      },
    },
    "addressDraft.tinhThanh"(nextValue) {
      const validDistricts = Object.keys(LOCATION_OPTIONS[nextValue] || {});
      if (!validDistricts.includes(this.addressDraft.quanHuyen)) {
        this.addressDraft.quanHuyen = "";
        this.addressDraft.phuongXa = "";
      }
    },
    "addressDraft.quanHuyen"(nextValue) {
      const validWards = LOCATION_OPTIONS[this.addressDraft.tinhThanh]?.[nextValue] || [];
      if (!validWards.includes(this.addressDraft.phuongXa)) {
        this.addressDraft.phuongXa = "";
      }
    },
  },

  mounted() {
    this.customerStore.refreshProfileFromApi();
    this.customerStore.syncAddressesFromApi();
    this.customerStore.syncOrdersFromApi();
    this.syncNotificationRouteState();
  },

  beforeUnmount() {
    if (this.avatarObjectUrl) {
      URL.revokeObjectURL(this.avatarObjectUrl);
    }
  },

  methods: {
    isValidSection(section) {
      return this.menuItems.some((item) => item.section === section);
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    formatNumber(value) {
      return new Intl.NumberFormat("vi-VN", {
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    formatDateTime(value) {
      if (!value) {
        return "Đang cập nhật";
      }

      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        return "Đang cập nhật";
      }

      return new Intl.DateTimeFormat("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      }).format(date);
    },
    normalizePaymentStatus(value) {
      return String(value || "")
        .trim()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/đ/g, "d")
        .replace(/[^a-z0-9]+/g, "_")
        .replace(/^_+|_+$/g, "");
    },
    paymentStatusLabel(value) {
      const status = this.normalizePaymentStatus(value);
      const labels = {
        pending: "Chờ thanh toán",
        unpaid: "Chờ thanh toán",
        cho_thanh_toan: "Chờ thanh toán",
        paid: "Đã thanh toán",
        success: "Đã thanh toán",
        completed: "Đã thanh toán",
        da_thanh_toan: "Đã thanh toán",
        thanh_toan_thanh_cong: "Đã thanh toán",
        failed: "Thanh toán thất bại",
        fail: "Thanh toán thất bại",
        cancelled: "Đã hủy thanh toán",
        canceled: "Đã hủy thanh toán",
        da_huy: "Đã hủy thanh toán",
        expired: "Link đã hết hạn",
        het_han: "Link đã hết hạn",
      };

      return labels[status] || value || "Đang cập nhật";
    },
    getPayosCheckoutUrl(order) {
      return order?.payosCheckoutUrl || order?.payos?.checkout_url || order?.payos?.checkoutUrl || "";
    },
    canContinuePayosPayment(order) {
      const method = String(order?.phuongThucThanhToan || "").trim().toLowerCase();
      const methodLabel = String(order?.phuongThucThanhToanLabel || "").trim().toLowerCase();

      if (method !== "payos" && !methodLabel.includes("payos")) {
        return false;
      }

      if (!this.getPayosCheckoutUrl(order)) {
        return false;
      }

      const orderStatus = this.normalizePaymentStatus(order?.trangThaiXuLy || order?.trangThai || "");
      const completedOrderStatuses = new Set([
        "da_xac_nhan",
        "hoan_thanh",
        "thanh_cong",
        "completed",
        "complete",
        "success",
      ]);

      if (completedOrderStatuses.has(orderStatus)) {
        return false;
      }

      const status = this.normalizePaymentStatus(order?.trangThaiThanhToan || order?.payosStatus || order?.payos?.status || "");
      const completedPaymentStatuses = new Set([
        "paid",
        "success",
        "completed",
        "complete",
        "succeeded",
        "da_thanh_toan",
        "thanh_toan_thanh_cong",
      ]);
      const stoppedPaymentStatuses = new Set([
        "failed",
        "fail",
        "that_bai",
        "cancelled",
        "canceled",
        "da_huy",
        "expired",
        "het_han",
      ]);

      if (
        completedPaymentStatuses.has(status) ||
        order?.payosPaidAt ||
        order?.payos?.paid_at ||
        order?.payos?.paidAt
      ) {
        return false;
      }

      if (stoppedPaymentStatuses.has(status)) {
        return false;
      }

      const pendingStatuses = new Set([
        "pending",
        "unpaid",
        "cho_thanh_toan",
        "dang_cho_thanh_toan",
        "waiting",
      ]);

      return pendingStatuses.has(status) || !status;
    },
    async continuePayosPayment(order) {
      let currentOrder = order;

      try {
        await this.customerStore.syncOrdersFromApi({ silent: false });
        const orderId = String(order?.id || "");
        const invoiceId = String(order?.idHoaDon || order?.id_hoa_don || "");

        currentOrder = this.state.orders.find((item) =>
          (orderId && String(item.id || "") === orderId) ||
          (invoiceId && String(item.idHoaDon || item.id_hoa_don || "") === invoiceId)
        ) || order;
      } catch {
        currentOrder = order;
      }

      if (!this.canContinuePayosPayment(currentOrder)) {
        const status = this.normalizePaymentStatus(
          currentOrder?.trangThaiThanhToan ||
            currentOrder?.payosStatus ||
            currentOrder?.payos?.status ||
            ""
        );

        if (["paid", "success", "completed", "complete", "succeeded", "da_thanh_toan", "thanh_toan_thanh_cong"].includes(status)) {
          showToast("Đơn hàng đã thanh toán thành công, đang chờ nhân viên xác nhận.");
          return;
        }

        showToast("Đơn hàng này không còn ở trạng thái chờ thanh toán.", "error");
        return;
      }

      const checkoutUrl = this.getPayosCheckoutUrl(currentOrder);

      if (!checkoutUrl) {
        showToast("Không tìm thấy link PayOS cho đơn hàng này.", "error");
        return;
      }

      window.location.href = checkoutUrl;
    },
    promotionValueLabel(item) {
      if (item.loai_ap_dung === "phan_tram") {
        return `${Number(item.gia_tri || 0)}%`;
      }
      return this.formatCurrency(item.gia_tri);
    },
    formatFullAddress(address) {
      return [address.soNha, address.phuongXa, address.quanHuyen, address.tinhThanh].filter(Boolean).join(", ");
    },
    openRewardInfoModal() {
      this.showRewardInfoModal = true;
    },
    closeRewardInfoModal() {
      this.showRewardInfoModal = false;
    },
    extractErrorMessage(error, fallbackMessage) {
      const fieldErrors = error?.payload?.errors || error?.response?.data?.errors;

      if (fieldErrors && typeof fieldErrors === "object") {
        const firstField = Object.keys(fieldErrors)[0];
        const firstMessage = Array.isArray(fieldErrors[firstField]) ? fieldErrors[firstField][0] : fieldErrors[firstField];
        if (firstMessage) return this.translateProfileValidationMessage(firstMessage, firstField);
      }

      const message = error?.payload?.message || error?.response?.data?.message || error?.message || fallbackMessage;
      return this.translateProfileValidationMessage(message);
    },
    translateProfileValidationMessage(message, field = "") {
      const rawMessage = String(message || "").trim();

      if (!rawMessage) {
        return "Không thể thực hiện yêu cầu. Vui lòng thử lại.";
      }

      const fieldLabels = {
        ten_khach_hang: "họ và tên",
        "ten khach hang": "họ và tên",
        ho_ten: "họ và tên",
        "ho ten": "họ và tên",
        so_dien_thoai: "số điện thoại",
        "so dien thoai": "số điện thoại",
        email: "email",
        dia_chi: "địa chỉ",
        "dia chi": "địa chỉ",
        ngay_sinh: "ngày sinh",
        "ngay sinh": "ngày sinh",
        gioi_tinh: "giới tính",
        "gioi tinh": "giới tính",
        avatar: "ảnh đại diện",
      };
      const normalized = rawMessage
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/đ/g, "d");

      const englishFieldMatch = normalized.match(/^the (.+?) field /);
      const selectedFieldMatch = normalized.match(/^the selected (.+?) is invalid/);
      const fieldKey = englishFieldMatch?.[1] || selectedFieldMatch?.[1] || "";
      const fieldLabel = fieldLabels[fieldKey] || fieldLabels[fieldKey.replace(/\s+/g, "_")] || "thông tin";

      if (normalized.includes("field is required")) {
        return `Vui lòng nhập ${fieldLabel}.`;
      }

      if (normalized.includes("must be at least")) {
        const minValue = normalized.match(/at least (\d+)/)?.[1];
        return `${this.capitalizeFirst(fieldLabel)} phải có ít nhất ${minValue || ""} ký tự.`.replace("  ", " ");
      }

      if (normalized.includes("may not be greater than")) {
        const maxValue = normalized.match(/greater than (\d+)/)?.[1];
        return `${this.capitalizeFirst(fieldLabel)} không được vượt quá ${maxValue || ""} ký tự.`.replace("  ", " ");
      }

      if (normalized.includes("must be a valid email")) {
        return "Email không hợp lệ.";
      }

      if (normalized.includes("has already been taken")) {
        return `${this.capitalizeFirst(fieldLabel)} này đã được sử dụng.`;
      }

      if (normalized.includes("must be a date") || normalized.includes("not a valid date")) {
        return "Ngày sinh không hợp lệ.";
      }

      if (normalized.includes("selected") && normalized.includes("is invalid")) {
        return "Giới tính chỉ được chọn Nam, Nữ hoặc Khác.";
      }

      if (normalized.includes("must be an image")) {
        return "Ảnh đại diện phải là tệp hình ảnh.";
      }

      return translateErrorMessage(rawMessage, field, fieldLabels);
    },
    capitalizeFirst(value) {
      const text = String(value || "");
      return text ? text.charAt(0).toUpperCase() + text.slice(1) : text;
    },
    async loadDiscountCodes() {
      this.discountCodesLoading = true;
      try {
        const response = await getCustomerOrderDiscountCodes(Number(this.customerStore.payableSubtotal?.value || 0));
        this.discountCodes = Array.isArray(response?.data) ? response.data : [];
      } catch (error) {
        this.discountCodes = [];
        showToast(this.extractErrorMessage(error, "Không thể tải danh sách mã giảm giá."), "error");
      } finally {
        this.discountCodesLoading = false;
      }
    },
    handleAvatarChange(event) {
      const file = event.target.files?.[0] || null;

      if (this.avatarObjectUrl) {
        URL.revokeObjectURL(this.avatarObjectUrl);
        this.avatarObjectUrl = "";
      }

      this.avatarFile = file;
      this.avatarLoadFailed = false;

      if (!file) {
        this.avatarPreviewUrl = this.state.profile.avatarUrl || "";
        return;
      }

      this.avatarObjectUrl = URL.createObjectURL(file);
      this.avatarPreviewUrl = this.avatarObjectUrl;
    },
    handleAvatarError() {
      this.avatarLoadFailed = true;
    },
    togglePasswordForm() {
      this.showPasswordForm = !this.showPasswordForm;

      if (!this.showPasswordForm) {
        this.resetPasswordDraft();
      }
    },
    cancelPasswordForm() {
      this.showPasswordForm = false;
      this.resetPasswordDraft();
    },
    resetPasswordDraft() {
      this.passwordDraft = {
        currentPassword: "",
        newPassword: "",
        confirmPassword: "",
      };
      this.passwordErrors = {
        currentPassword: "",
        newPassword: "",
        confirmPassword: "",
      };
    },
    clearPasswordError(field) {
      if (Object.prototype.hasOwnProperty.call(this.passwordErrors, field)) {
        this.passwordErrors[field] = "";
      }
    },
    validatePasswordDraft() {
      const errors = {
        currentPassword: "",
        newPassword: "",
        confirmPassword: "",
      };

      if (!this.passwordDraft.currentPassword) {
        errors.currentPassword = "Vui lòng nhập mật khẩu hiện tại.";
      }

      errors.newPassword = validateStrongPassword(this.passwordDraft.newPassword, "Mật khẩu mới");

      if (!this.passwordDraft.confirmPassword) {
        errors.confirmPassword = "Vui lòng xác nhận mật khẩu mới.";
      } else if (this.passwordDraft.confirmPassword !== this.passwordDraft.newPassword) {
        errors.confirmPassword = "Xác nhận mật khẩu mới không khớp.";
      }

      this.passwordErrors = errors;
      return !Object.values(errors).some(Boolean);
    },
    async saveProfile() {
      try {
        const birthDate = parseDateInputValue(this.profileDraft.ngaySinh);

        if (this.profileDraft.ngaySinh && !birthDate) {
          showToast("Ngày sinh phải theo định dạng dd/mm/yyyy.", "error");
          return;
        }

        const payload = new FormData();
        const authType = getAuthType();

        if (authType === "customer") {
          payload.append("ten_khach_hang", this.profileDraft.hoTen || "");
        } else {
          payload.append("ho_ten", this.profileDraft.hoTen || "");
        }

        payload.append("so_dien_thoai", this.profileDraft.soDienThoai || "");
        payload.append("dia_chi", this.profileDraft.diaChi || "");
        payload.append("gioi_tinh", this.profileDraft.gioiTinh || "");

        if (birthDate) {
          payload.append("ngay_sinh", birthDate);
        }

        if (this.avatarFile) {
          payload.append("avatar", this.avatarFile);
        }

        await this.customerStore.updateProfile(payload);

        if (this.avatarObjectUrl) {
          URL.revokeObjectURL(this.avatarObjectUrl);
          this.avatarObjectUrl = "";
        }

        this.avatarFile = null;
        this.avatarLoadFailed = false;
        this.avatarPreviewUrl = this.state.profile.avatarUrl || "";
        showToast("Đã cập nhật thông tin cá nhân.");
      } catch (error) {
        showToast(this.extractErrorMessage(error, "Không thể cập nhật thông tin cá nhân."), "error");
      }
    },
    async submitPasswordChange() {
      if (!this.validatePasswordDraft()) {
        showToast("Vui lòng kiểm tra lại các trường thông tin bên trên.", "error");
        return;
      }

      try {
        await changePasswordApi({
          current_password: this.passwordDraft.currentPassword,
          new_password: this.passwordDraft.newPassword,
          new_password_confirmation: this.passwordDraft.confirmPassword,
        });

        this.cancelPasswordForm();
        showToast("Đổi mật khẩu thành công.");
      } catch (error) {
        showToast(this.extractErrorMessage(error, "Không thể đổi mật khẩu."), "error");
      }
    },
    openAddAddressModal() {
      this.addressDraft = normalizeAddressDraft(createEmptyAddressDraft(this.state.profile));
      this.resetAddressErrors();
      this.showAddressModal = true;
    },
    openEditAddressModal(address) {
      this.addressDraft = normalizeAddressDraft({ ...address });
      this.resetAddressErrors();
      this.showAddressModal = true;
    },
    closeAddressModal() {
      this.showAddressModal = false;
      this.addressDraft = normalizeAddressDraft(createEmptyAddressDraft(this.state.profile));
      this.resetAddressErrors();
    },
    resetAddressErrors() {
      this.addressErrors = {
        hoTen: "",
        soDienThoai: "",
      };
    },
    handleAddressNameInput() {
      const rawName = String(this.addressDraft.hoTen || "");
      this.addressDraft.hoTen = limitAddressNameInput(rawName);
      this.addressErrors.hoTen = rawName.length > 30 ? "Họ tên người nhận không được vượt quá 30 ký tự." : "";
    },
    handleAddressPhoneInput() {
      this.addressDraft.soDienThoai = normalizePhoneDigits(this.addressDraft.soDienThoai);
      this.addressErrors.soDienThoai = "";
    },
    toggleOrderDetails(orderId) {
      this.expandedOrderId = this.expandedOrderId === orderId ? null : orderId;
    },
    async reorderOrder(order) {
      try {
        const result = await this.customerStore.reorderOrder(order);

        if (!result.added) {
          showToast("Không thể mua lại vì các sản phẩm trong đơn hiện không còn khả dụng.", "error");
          return;
        }

        if (result.skipped.length) {
          showToast(`Đã thêm ${result.added} sản phẩm vào giỏ. ${result.skipped.length} sản phẩm hiện không còn hàng.`, "warning");
        } else {
          showToast(`Đã thêm ${result.added} sản phẩm vào giỏ hàng.`);
        }

        this.$router.push("/gio-hang");
      } catch (error) {
        showToast(this.extractErrorMessage(error, "Không thể mua lại đơn hàng này."), "error");
      }
    },
    syncNotificationRouteState() {
      const noticeId = String(this.$route.query.notice || "").trim();
      const queryTab = String(this.$route.query.tab || "").trim();

      if (this.section === "thong-bao") {
        if (queryTab && this.notificationTabs.includes(queryTab)) {
          this.activeTab = queryTab;
        }

        if (noticeId) {
          this.customerStore.markNotificationRead(noticeId);
        } else {
          this.customerStore.markAllNotificationsRead();
        }

        return;
      }

      if (this.section === "lich-su-don-hang" || this.section === "ma-giam-gia") {
        if (noticeId) {
          this.customerStore.markNotificationRead(noticeId);
        }
      }
    },
    markAllNotificationsAsRead() {
      this.customerStore.markAllNotificationsRead();
      showToast("Đã đánh dấu tất cả thông báo là đã đọc.");
    },
    async submitAddress() {
      const normalizedDraft = normalizeAddressDraft({ ...this.addressDraft });
      this.resetAddressErrors();

      if (!normalizedDraft.hoTen) {
        this.addressErrors.hoTen = "Vui lòng nhập họ tên người nhận.";
        showToast("Vui lòng kiểm tra lại họ tên người nhận.", "error");
        return;
      }

      if (normalizedDraft.hoTen.length > 30) {
        this.addressErrors.hoTen = "Họ tên người nhận không được vượt quá 30 ký tự.";
        showToast("Vui lòng kiểm tra lại họ tên người nhận.", "error");
        return;
      }

      if (!/^\d{10}$/.test(normalizedDraft.soDienThoai)) {
        this.addressErrors.soDienThoai = "Số điện thoại người nhận phải gồm đúng 10 chữ số.";
        showToast("Vui lòng kiểm tra lại số điện thoại người nhận.", "error");
        return;
      }

      if (!normalizedDraft.tinhThanh || !normalizedDraft.quanHuyen || !normalizedDraft.phuongXa) {
        showToast("Vui lòng chọn đúng Tỉnh/Thành phố, Quận/Huyện và Phường/Xã.", "error");
        return;
      }

      try {
        await this.customerStore.saveAddress(normalizedDraft);
        this.showAddressModal = false;
        this.addressDraft = normalizeAddressDraft(createEmptyAddressDraft(this.state.profile));
        this.resetAddressErrors();
        showToast("Đã lưu địa chỉ nhận hàng.");
      } catch (error) {
        const fieldErrors = error?.payload?.errors || error?.response?.data?.errors;
        const nameError = fieldErrors?.ho_ten;
        const phoneError = fieldErrors?.so_dien_thoai;
        const nameMessage = Array.isArray(nameError) ? nameError[0] : nameError;
        const phoneMessage = Array.isArray(phoneError) ? phoneError[0] : phoneError;

        if (nameMessage) {
          this.addressErrors.hoTen = this.translateProfileValidationMessage(nameMessage, "ho_ten");
        }

        if (phoneMessage) {
          this.addressErrors.soDienThoai = this.translateProfileValidationMessage(phoneMessage, "so_dien_thoai");
        }

        showToast(this.extractErrorMessage(error, "Không thể lưu địa chỉ nhận hàng."), "error");
      }
    },
  },
};
</script>

<style scoped>
.pc-account-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.pc-account-profile__content {
  flex: 1;
  min-width: 0;
  overflow: hidden;
}

.pc-account-profile__content h3 {
  margin: 0;
  font-size: 1.05rem;
  line-height: 1.25;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pc-account-profile__subtext {
  margin: 4px 0 0;
  color: #6a7894;
  font-size: 0.84rem;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pc-reward-summary {
  display: grid;
  gap: 4px;
  margin: 16px 0 18px;
  padding: 14px 16px;
  border: 1px solid rgba(25, 135, 84, 0.14);
  border-radius: 16px;
  background: rgba(25, 135, 84, 0.06);
}

.pc-reward-summary span {
  color: #5f7393;
  font-size: 0.86rem;
  font-weight: 700;
}

.pc-reward-summary__label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.pc-reward-summary strong {
  color: #0f7a49;
  font-size: 1.12rem;
}

.pc-info-icon {
  width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: none;
  border-radius: 50%;
  background: rgba(22, 82, 197, 0.1);
  color: #1652c5;
}

.pc-modal-card--reward-info {
  max-width: 420px;
}

.pc-reward-modal {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 4px 8px 0 0;
}

.pc-reward-modal__icon {
  width: 48px;
  height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: 50%;
  background: rgba(22, 82, 197, 0.1);
  color: #1652c5;
  font-size: 1.35rem;
}

.pc-reward-modal h2 {
  margin: 0 0 8px;
  color: #122b52;
  font-size: 1.3rem;
  font-weight: 800;
}

.pc-reward-modal p {
  margin: 0;
  color: #314a73;
  font-size: 1rem;
  font-weight: 700;
}

.pc-account-form__avatar-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin: 8px 0 28px;
}

.pc-account-profile__avatar,
.pc-account-form__avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: #eaf2ff;
  color: #1652c5;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 50%;
  box-shadow: 0 10px 24px rgba(18, 43, 82, 0.08);
}

.pc-account-profile__avatar {
  width: 72px;
  height: 72px;
  flex-shrink: 0;
  font-size: 1.2rem;
  font-weight: 800;
}

.pc-account-form__avatar {
  width: 112px;
  height: 112px;
  flex-shrink: 0;
  font-size: 1.8rem;
  font-weight: 800;
}

.pc-account-profile__avatar img,
.pc-account-form__avatar img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.pc-address-card__actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  flex-shrink: 0;
}

.pc-account-panel .pc-address-list {
  min-width: 0;
  overflow: hidden;
}

.pc-account-panel .pc-address-card {
  width: 100%;
  max-width: 100%;
  min-width: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  overflow: hidden;
}

.pc-address-card__content {
  flex: 1;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}

.pc-address-card__content h3,
.pc-address-card__content p {
  display: block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pc-address-card__tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  background: #eef4ff;
  color: #1652c5;
  font-size: 0.85rem;
  font-weight: 800;
}

.pc-address-card__tag--default {
  background: #e6f8f5;
  color: #047c6c;
}

.pc-discount-list {
  display: grid;
  gap: 16px;
}

.pc-discount-card {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 18px;
  padding: 20px 22px;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 22px;
  background: #fff;
}

.pc-discount-card.is-disabled {
  opacity: 0.62;
  background: #f5f7fb;
}

.pc-discount-card__code {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 76px;
  border-radius: 18px;
  background: linear-gradient(135deg, #1757d8, #2bc0f2);
  color: #fff;
  font-size: 1.05rem;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.pc-discount-card__content h3 {
  margin: 0 0 8px;
  color: #122b52;
  font-size: 1.08rem;
  font-weight: 800;
}

.pc-discount-card__content p {
  margin: 0 0 12px;
  color: #6a7894;
}

.pc-discount-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.pc-discount-card__meta span {
  display: inline-flex;
  align-items: center;
  min-height: 36px;
  padding: 0 12px;
  border-radius: 999px;
  background: #eef5ff;
  color: #1757d8;
  font-size: 0.88rem;
  font-weight: 700;
}

.pc-discount-card__notice {
  display: inline-block;
  margin-top: 12px;
  color: #8f96a3;
  font-size: 0.86rem;
  font-weight: 700;
}

.pc-address-form__row {
  align-items: start;
}

.pc-address-form__row > .pc-form-field + .pc-form-field {
  margin-top: 0;
}

.pc-password-form {
  margin-top: 14px;
  padding: 18px;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 20px;
  background: #f8fbff;
}

.pc-password-form__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
}

.pc-password-form__actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 14px;
}

.pc-input-locked:disabled {
  cursor: not-allowed;
  background: #f1f5f9;
  color: #64748b;
  -webkit-text-fill-color: #64748b;
}

.pc-field-error {
  margin-top: 6px;
  color: #dc3545;
  font-size: 0.9rem;
}

.pc-form-field input.is-invalid {
  border-color: #dc3545;
}

@media (max-width: 767.98px) {
  .pc-account-profile {
    align-items: flex-start;
  }

  .pc-account-profile__content h3,
  .pc-account-profile__subtext {
    white-space: normal;
    word-break: break-word;
  }

  .pc-discount-card {
    grid-template-columns: 1fr;
  }

  .pc-address-card__actions {
    align-items: flex-start;
    flex-direction: column;
  }

  .pc-password-form__grid {
    grid-template-columns: 1fr;
  }

  .pc-password-form__actions {
    flex-direction: column-reverse;
    align-items: stretch;
  }
}
.pc-history-card {
  display: block;
  width: 100%;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
  }

.pc-history-card:hover {
  border-color: rgba(22, 82, 197, 0.18);
  box-shadow: 0 12px 28px rgba(18, 43, 82, 0.08);
  transform: translateY(-1px);
}

.pc-history-card.is-open {
  border-color: rgba(22, 82, 197, 0.22);
  box-shadow: 0 16px 32px rgba(18, 43, 82, 0.1);
}

.pc-history-card__head {
    width: 100%;
    display: grid;
    grid-template-columns: minmax(180px, 240px) minmax(180px, 1fr) minmax(220px, auto);
    align-items: center;
    gap: 18px;
  }

.pc-history-card__code {
  display: block;
  color: #122b52;
  font-size: 1.05rem;
}

.pc-history-card__meta p {
  margin: 0;
}

.pc-history-card__meta small {
  display: block;
  margin-top: 4px;
  color: #6a7894;
  font-size: 0.84rem;
  font-weight: 700;
}

.pc-history-card__summary {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 14px;
  flex-wrap: wrap;
}

.pc-history-card__total {
  display: grid;
  gap: 2px;
  text-align: right;
}

.pc-history-card__total span {
  color: #6a7894;
  font-size: 0.82rem;
  font-weight: 700;
}

.pc-history-card__total strong {
  color: #122b52;
  font-size: 1.02rem;
}

.pc-history-card__reorder {
  min-height: 36px;
  padding: 0 16px;
  border: 1px solid rgba(22, 82, 197, 0.18);
  border-radius: 999px;
  background: #eef5ff;
  color: #1652c5;
  font-size: 0.88rem;
  font-weight: 800;
  cursor: pointer;
}

.pc-history-card__reorder:hover {
  background: #dfeaff;
}

.pc-history-card__payos {
  min-height: 36px;
  padding: 0 16px;
  border: 1px solid rgba(20, 184, 166, 0.24);
  border-radius: 999px;
  background: #e8fffb;
  color: #04766b;
  font-size: 0.88rem;
  font-weight: 800;
  cursor: pointer;
}

.pc-history-card__payos:hover {
  background: #d1fbf3;
}

.pc-history-card__toggle {
  padding: 0;
  border: 0;
  background: transparent;
  color: #1652c5;
  font-size: 0.92rem;
  font-weight: 700;
  cursor: pointer;
}

.pc-history-card__toggle:hover {
  color: #0f45ac;
  text-decoration: underline;
}

.pc-history-card__details {
    width: 100%;
    display: grid;
    gap: 12px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(22, 82, 197, 0.08);
  }

.pc-history-card__overview {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.pc-history-info,
.pc-history-note {
  padding: 16px 18px;
  border: 1px solid rgba(22, 82, 197, 0.08);
  border-radius: 18px;
  background: #f8fbff;
}

.pc-history-info__label {
  display: block;
  margin-bottom: 8px;
  color: #6a7894;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.pc-history-info strong,
.pc-history-timeline__content strong {
  color: #122b52;
}

.pc-history-info p,
.pc-history-note p {
  margin: 6px 0 0;
  color: #6a7894;
  line-height: 1.55;
}

.pc-history-info__payos {
  min-height: 34px;
  margin-top: 12px;
  padding: 0 14px;
  border: 1px solid rgba(20, 184, 166, 0.24);
  border-radius: 999px;
  background: #e8fffb;
  color: #04766b;
  font-size: 0.86rem;
  font-weight: 800;
  cursor: pointer;
}

.pc-history-info__payos:hover {
  background: #d1fbf3;
}

.pc-history-info--totals {
  display: grid;
  gap: 8px;
}

.pc-history-info__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  color: #6a7894;
  font-size: 0.93rem;
}

.pc-history-info__row strong {
  font-size: 0.95rem;
}

.pc-history-info__row--points {
  font-size: 0.9rem;
}

.pc-history-info__row--total {
  padding-top: 8px;
  margin-top: 4px;
  border-top: 1px dashed rgba(22, 82, 197, 0.16);
  color: #122b52;
  font-weight: 800;
}

.pc-history-card__notes {
  display: grid;
  gap: 12px;
}

.pc-history-timeline {
  display: grid;
  gap: 10px;
}

.pc-history-timeline__item {
  display: grid;
  grid-template-columns: 18px minmax(0, 1fr);
  gap: 12px;
  align-items: start;
}

.pc-history-timeline__dot {
  width: 10px;
  height: 10px;
  margin-top: 7px;
  border-radius: 50%;
  background: #1652c5;
  box-shadow: 0 0 0 4px rgba(22, 82, 197, 0.12);
}

.pc-history-timeline__content p,
.pc-history-timeline__content small {
  display: block;
  margin: 4px 0 0;
  color: #6a7894;
  line-height: 1.45;
}
  
  .pc-history-product {
    width: 100%;
    display: grid;
    grid-template-columns: 58px minmax(0, 1fr) auto;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 0;
  }

.pc-history-product__thumb {
  width: 58px;
  height: 58px;
  flex-shrink: 0;
  border-radius: 18px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  background: linear-gradient(145deg, #ffeaf3, #f3e8ff);
  overflow: hidden;
}

.pc-history-product__thumb.tone-blue {
  background: linear-gradient(145deg, #dff3ff, #eef5ff);
}

.pc-history-product__thumb.tone-green {
  background: linear-gradient(145deg, #e7ffe7, #eefdf2);
}

.pc-history-product__content {
  flex: 1;
  min-width: 0;
}

.pc-history-product__content strong {
  display: block;
  margin-bottom: 6px;
  color: #122b52;
  font-size: 1rem;
}

.pc-history-product__content p {
  margin: 0;
  color: #6a7894;
  font-size: 0.93rem;
  line-height: 1.55;
  white-space: pre-line;
  overflow-wrap: anywhere;
  word-break: break-word;
}

  .pc-history-product__meta {
    min-width: 120px;
    text-align: right;
    display: grid;
    gap: 6px;
    color: #122b52;
  }

.pc-history-product__meta span {
  color: #6a7894;
  font-weight: 700;
}

.pc-history-product__meta small {
  color: #8a96ad;
  font-size: 0.8rem;
  font-weight: 700;
}

.pc-history-card__empty {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 56px;
  padding: 14px 16px;
  border: 1px dashed rgba(22, 82, 197, 0.18);
  border-radius: 16px;
  background: #f8fbff;
  color: #6a7894;
}

.pc-history-card__empty i {
  color: #7aa7ea;
  font-size: 1.15rem;
}

@media (max-width: 767.98px) {
  .pc-account-form__avatar {
    width: 96px;
    height: 96px;
  }

  .pc-history-card__head {
    grid-template-columns: 1fr;
    align-items: flex-start;
  }

  .pc-history-card__overview {
    grid-template-columns: 1fr;
  }
  
    .pc-history-product {
      grid-template-columns: 1fr;
    }
  
    .pc-history-product__meta {
      min-width: auto;
      text-align: left;
  }

  .pc-history-card__summary {
    justify-content: flex-start;
  }
}
</style>

