<template>
  <div class="master-topstrip">
    <div class="container-fluid px-3 px-lg-4">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3">
          <RouterLink to="/" class="master-topstrip__brand">
            <span class="master-topstrip__logo-mark">
              <i class="bi bi-capsule-pill"></i>
            </span>
            <span class="master-topstrip__logo-text">
              <small>NHÀ THUỐC</small>
              <strong>PharmaGo</strong>
            </span>
          </RouterLink>

          <span class="master-topstrip__pill">
            <i class="bi bi-heart-pulse"></i>
            Quản trị thuốc, tồn kho và đơn hàng tập trung trên một màn hình.
          </span>
        </div>

        <div class="master-topstrip__right">
          <div
            v-if="showOrderNotifications"
            ref="notificationMenuRef"
            class="master-topstrip__notification"
          >
            <button
              type="button"
              class="master-topstrip__icon-btn"
              @click="toggleNotificationMenu"
              aria-label="Thông báo đơn hàng"
            >
              <i class="bi bi-bell fs-5"></i>
              <span v-if="notificationCount" class="master-topstrip__badge">
                {{ notificationCount }}
              </span>
            </button>

            <div v-if="notificationMenuOpen" class="master-topstrip__notification-menu shadow-lg">
              <div class="master-topstrip__notification-head">
                <strong>Thông báo đơn hàng</strong>
                <button type="button" class="btn btn-link btn-sm p-0" @click="openAllInvoiceNotifications()">
                  Xem tất cả
                </button>
              </div>

              <div v-if="notificationLoading" class="master-topstrip__notification-empty">
                Đang tải thông báo...
              </div>

              <div v-else-if="notifications.length" class="master-topstrip__notification-list">
                <button
                  v-for="item in notifications"
                  :key="item.id_hoa_don"
                  type="button"
                  class="master-topstrip__notification-item"
                  @click="openInvoiceNotification(item)"
                >
                  <div class="master-topstrip__notification-title">{{ item.ma_hoa_don }}</div>
                  <div class="master-topstrip__notification-copy">
                    {{ item.khach_hang?.ten_khach_hang || "Khách hàng" }} đã đặt hàng thành công
                  </div>
                  <div class="master-topstrip__notification-meta">
                    <span>{{ formatCurrency(item.tong_tien) }}</span>
                    <span>{{ formatDate(item.ngay_ban) }}</span>
                  </div>
                </button>
              </div>

              <div v-else class="master-topstrip__notification-empty">
                Chưa có đơn hàng mới.
              </div>
            </div>
          </div>

          <div class="master-topstrip__pill">
            <i class="bi bi-calendar-event"></i>
            {{ todayLabel }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { getPendingHoaDonNotifications } from "../../api/hoaDonApi";
import { authState } from "../../lib/authStorage";

const router = useRouter();
const todayLabel = new Intl.DateTimeFormat("vi-VN", {
  weekday: "long",
  day: "2-digit",
  month: "2-digit",
  year: "numeric",
}).format(new Date());

const notificationMenuRef = ref(null);
const notificationMenuOpen = ref(false);
const notificationLoading = ref(false);
const notifications = ref([]);
const notificationCount = ref(0);
const showOrderNotifications = computed(() =>
  ["admin", "staff", "nhan_vien", "nhanvien"].includes(authState.type)
);

let notificationTimer = null;

function getNotificationReadStorageKey() {
  const scopeId =
    authState.user?.id_nhan_vien || authState.user?.ten_dang_nhap || authState.user?.email || "system";
  return `pharmago_admin_invoice_notification_reads__${authState.type || "unknown"}__${scopeId}`;
}

function readStoredNotificationIds() {
  try {
    const raw = localStorage.getItem(getNotificationReadStorageKey());
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed.map((item) => String(item)) : [];
  } catch {
    return [];
  }
}

function persistReadNotificationIds(ids) {
  localStorage.setItem(getNotificationReadStorageKey(), JSON.stringify(Array.from(new Set(ids.map(String)))));
}

function syncNotificationCount() {
  const readIds = new Set(readStoredNotificationIds());
  notificationCount.value = notifications.value.filter(
    (item) => !readIds.has(String(item.id_hoa_don))
  ).length;
}

function markInvoiceNotificationRead(item) {
  const notificationId = String(item?.id_hoa_don || "").trim();
  if (!notificationId) {
    return;
  }

  const nextIds = new Set(readStoredNotificationIds());
  nextIds.add(notificationId);
  persistReadNotificationIds(Array.from(nextIds));
  syncNotificationCount();
}

function markAllInvoiceNotificationsRead() {
  persistReadNotificationIds(notifications.value.map((item) => String(item.id_hoa_don)));
  syncNotificationCount();
}

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
    syncNotificationCount();
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
  markInvoiceNotificationRead(item);
  goToInvoices({
    q: item.ma_hoa_don,
  });
}

function openAllInvoiceNotifications() {
  markAllInvoiceNotificationsRead();
  goToInvoices();
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
</script>
