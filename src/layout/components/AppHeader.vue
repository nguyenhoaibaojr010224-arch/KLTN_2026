<template>
  <header class="master-header">
    <div class="d-flex align-items-center justify-content-between gap-3">
      <button
        class="btn btn-light d-lg-none rounded-circle shadow-sm"
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#masterSidebar"
        aria-controls="masterSidebar"
      >
        <i class="bi bi-list fs-4"></i>
      </button>

      <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-3 flex-grow-1">
        <div class="input-group master-header__search">
          <span class="input-group-text text-secondary">
            <i class="bi bi-search"></i>
          </span>
          <input
            type="text"
            class="form-control"
            placeholder="Tìm nhanh sản phẩm, chi nhánh, đơn hàng..."
          />
        </div>

        <div class="master-header__actions">
          <div
            v-if="showOrderNotifications"
            ref="notificationMenuRef"
            class="master-header__notification"
          >
            <button
              type="button"
              class="btn btn-light rounded-circle shadow-sm position-relative"
              @click="toggleNotificationMenu"
            >
              <i class="bi bi-bell fs-5"></i>
              <span
                v-if="notificationCount"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              >
                {{ notificationCount }}
              </span>
            </button>

            <div v-if="notificationMenuOpen" class="master-header__notification-menu shadow-lg">
              <div class="master-header__notification-head">
                <strong>Thông báo đơn hàng</strong>
                <button type="button" class="btn btn-link btn-sm p-0" @click="goToInvoices()">
                  Xem tất cả
                </button>
              </div>

              <div v-if="notificationLoading" class="master-header__notification-empty">
                Đang tải thông báo...
              </div>

              <div v-else-if="notifications.length" class="master-header__notification-list">
                <button
                  v-for="item in notifications"
                  :key="item.id_hoa_don"
                  type="button"
                  class="master-header__notification-item"
                  @click="openInvoiceNotification(item)"
                >
                  <div class="master-header__notification-title">{{ item.ma_hoa_don }}</div>
                  <div class="master-header__notification-copy">
                    {{ item.khach_hang?.ten_khach_hang || "Khách hàng" }} đang chờ xác nhận
                  </div>
                  <div class="master-header__notification-meta">
                    <span>{{ formatCurrency(item.tong_tien) }}</span>
                    <span>{{ formatDate(item.ngay_ban) }}</span>
                  </div>
                </button>
              </div>

              <div v-else class="master-header__notification-empty">
                Chưa có đơn hàng mới cần xác nhận.
              </div>
            </div>
          </div>

          <div class="master-header__user-card">
            <div class="user-pill__avatar d-grid place-items-center bg-primary-subtle text-primary">
              <i class="bi bi-person-circle fs-4"></i>
            </div>
            <div class="master-header__user-meta">
              <div class="master-header__user-name">
                {{ currentUser?.ho_ten || currentUser?.ten_khach_hang || "Chưa đăng nhập" }}
              </div>
              <div class="master-header__user-role">{{ roleLabel }}</div>
            </div>
            <button
              type="button"
              class="btn btn-outline-secondary master-header__logout-btn"
              @click="handleLogout"
            >
              Đăng xuất
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { getPendingHoaDonNotifications } from "../../api/hoaDonApi";
import { authState, clearAuthSession } from "../../lib/authStorage";

const router = useRouter();
const currentUser = computed(() => authState.user);
const notificationMenuRef = ref(null);
const notificationMenuOpen = ref(false);
const notificationLoading = ref(false);
const notifications = ref([]);
const notificationCount = ref(0);
const showOrderNotifications = computed(() => ["admin", "staff", "nhan_vien", "nhanvien"].includes(authState.type));
let notificationTimer = null;

const roleLabel = computed(() => {
  if (currentUser.value?.vai_tro?.ten_vai_tro) {
    return currentUser.value.vai_tro.ten_vai_tro;
  }

  if (authState.type === "admin") {
    return "Admin";
  }

  if (["staff", "nhan_vien", "nhanvien"].includes(authState.type)) {
    return "Nhân viên";
  }

  if (authState.type === "customer") {
    return "Khách hàng";
  }

  return "Tài khoản";
});

onMounted(() => {
  document.addEventListener("click", handleDocumentClick);

  if (showOrderNotifications.value) {
    loadNotifications();
    notificationTimer = window.setInterval(() => {
      loadNotifications(true);
    }, 15000);
  }
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleDocumentClick);

  if (notificationTimer) {
    clearInterval(notificationTimer);
    notificationTimer = null;
  }
});

async function loadNotifications(silent = false) {
  if (!showOrderNotifications.value) {
    return;
  }

  if (!silent) {
    notificationLoading.value = true;
  }

  try {
    const response = await getPendingHoaDonNotifications();
    notifications.value = Array.isArray(response?.data) ? response.data : [];
    notificationCount.value = Number(response?.tong_thong_bao || 0);
  } catch {
    if (!silent) {
      notifications.value = [];
      notificationCount.value = 0;
    }
  } finally {
    if (!silent) {
      notificationLoading.value = false;
    }
  }
}

async function toggleNotificationMenu() {
  notificationMenuOpen.value = !notificationMenuOpen.value;

  if (notificationMenuOpen.value) {
    await loadNotifications();
  }
}

function handleDocumentClick(event) {
  if (!notificationMenuRef.value?.contains(event.target)) {
    notificationMenuOpen.value = false;
  }
}

function goToInvoices(query = {}) {
  notificationMenuOpen.value = false;
  router.push({
    path: "/hoa-dons",
    query,
  });
}

function openInvoiceNotification(item) {
  goToInvoices({
    q: item.ma_hoa_don,
  });
}

function formatCurrency(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function formatDate(value) {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("vi-VN", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(new Date(value));
}

function handleLogout() {
  clearAuthSession();
  router.push("/");
}
</script>

<style scoped>
.master-header__notification {
  position: relative;
}

.master-header__notification-menu {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  width: 360px;
  background: #fff;
  border: 1px solid rgba(20, 63, 148, 0.08);
  border-radius: 20px;
  padding: 16px;
  z-index: 30;
}

.master-header__notification-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.master-header__notification-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 420px;
  overflow-y: auto;
}

.master-header__notification-item {
  width: 100%;
  text-align: left;
  border: 1px solid rgba(20, 63, 148, 0.08);
  background: #f6f9ff;
  border-radius: 16px;
  padding: 12px 14px;
}

.master-header__notification-title {
  font-weight: 800;
  color: #19335e;
}

.master-header__notification-copy {
  margin-top: 4px;
  color: #526887;
  font-size: 0.95rem;
}

.master-header__notification-meta {
  margin-top: 8px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: #1c4db3;
  font-size: 0.84rem;
  font-weight: 700;
}

.master-header__notification-empty {
  color: #6d7f98;
  font-size: 0.95rem;
  padding: 12px 4px;
}
</style>
