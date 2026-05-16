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
            v-if="showSystemNotifications"
            ref="notificationMenuRef"
            class="master-topstrip__notification"
          >
            <button
              type="button"
              class="master-topstrip__icon-btn"
              @click="toggleNotificationMenu"
              aria-label="Thông báo khách hàng"
            >
              <i class="bi bi-bell fs-5"></i>
              <span v-if="notificationCount" class="master-topstrip__badge">
                {{ notificationCount }}
              </span>
            </button>

            <div v-if="notificationMenuOpen" class="master-topstrip__notification-menu shadow-lg">
              <div class="master-topstrip__notification-head">
                <strong>Thông báo hệ thống</strong>
                <button type="button" class="btn btn-link btn-sm p-0" @click="openPriorityNotifications">
                  Xem tất cả
                </button>
              </div>

              <div v-if="notificationLoading" class="master-topstrip__notification-empty">
                Đang tải thông báo...
              </div>

              <div
                v-else-if="supportNotifications.length || invoiceNotifications.length"
                class="master-topstrip__notification-list"
              >
                <div v-if="supportNotifications.length" class="master-topstrip__notification-section">
                  <div class="master-topstrip__notification-section-title">Hỗ trợ khách hàng</div>
                  <button
                    v-for="item in supportNotifications"
                    :key="`support-${item.id_hoi_thoai}`"
                    type="button"
                    class="master-topstrip__notification-item"
                    @click="openSupportNotification(item)"
                  >
                    <div class="master-topstrip__notification-title">
                      {{ item.khach_hang?.ten_khach_hang || "Khách hàng" }}
                    </div>
                    <div class="master-topstrip__notification-copy">
                      {{ item.tin_nhan_cuoi?.noi_dung || "Khách hàng vừa gửi tin nhắn hỗ trợ." }}
                    </div>
                    <div class="master-topstrip__notification-meta">
                      <span>{{ item.so_tin_chua_doc || 0 }} tin nhắn mới</span>
                      <span>{{ formatDate(item.thoi_gian_tin_nhan_cuoi) }}</span>
                    </div>
                  </button>
                </div>

                <div v-if="invoiceNotifications.length" class="master-topstrip__notification-section">
                  <div class="master-topstrip__notification-section-title">Đơn hàng mới</div>
                  <button
                    v-for="item in invoiceNotifications"
                    :key="`invoice-${item.id_hoa_don}`"
                    type="button"
                    class="master-topstrip__notification-item"
                    @click="openInvoiceNotification(item)"
                  >
                    <div class="master-topstrip__notification-title">{{ item.ma_hoa_don }}</div>
                    <div class="master-topstrip__notification-copy">
                      {{ item.khach_hang?.ten_khach_hang || "Khách hàng" }} - {{ item.noi_dung_thong_bao || "Đơn hàng hệ thống mới" }}
                    </div>
                    <div class="master-topstrip__notification-meta">
                      <span>{{ formatCurrency(item.tong_tien) }}</span>
                      <span>{{ formatDate(item.ngay_ban) }}</span>
                    </div>
                  </button>
                </div>
              </div>

              <div v-else class="master-topstrip__notification-empty">
                Chưa có thông báo khách hàng mới.
              </div>
            </div>
          </div>

          <div
            v-if="showSystemNotifications"
            ref="inventoryMenuRef"
            class="master-topstrip__notification"
          >
            <button
              type="button"
              class="master-topstrip__icon-btn master-topstrip__icon-btn--warning"
              @click="toggleInventoryAlertMenu"
              aria-label="Cảnh báo tồn kho"
            >
              <span class="master-topstrip__alert-symbol">!</span>
              <span v-if="inventoryNotificationCount" class="master-topstrip__badge">
                {{ inventoryNotificationCount }}
              </span>
            </button>

            <div v-if="inventoryMenuOpen" class="master-topstrip__notification-menu shadow-lg">
              <div class="master-topstrip__notification-head">
                <strong>Cảnh báo tồn kho</strong>
                <button type="button" class="btn btn-link btn-sm p-0" @click="openAllInventoryNotifications">
                  Xem tất cả
                </button>
              </div>

              <div v-if="notificationLoading" class="master-topstrip__notification-empty">
                Đang tải cảnh báo...
              </div>

              <div v-else-if="inventoryAlertNotifications.length" class="master-topstrip__notification-list">
                <div class="master-topstrip__notification-section">
                  <div class="master-topstrip__notification-section-title">Tồn kho cần chú ý</div>
                  <button
                    v-for="item in inventoryAlertNotifications"
                    :key="item.id"
                    type="button"
                    class="master-topstrip__notification-item master-topstrip__notification-item--warning"
                    @click="openInventoryNotification(item)"
                  >
                    <div class="master-topstrip__notification-title">{{ item.title }}</div>
                    <div class="master-topstrip__notification-copy">{{ item.copy }}</div>
                    <div class="master-topstrip__notification-meta">
                      <span>{{ item.badge }}</span>
                      <span>{{ item.meta }}</span>
                    </div>
                  </button>
                </div>
              </div>

              <div v-else class="master-topstrip__notification-empty">
                Chưa có cảnh báo tồn kho.
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
import { getInventoryAlerts } from "../../api/inventoryApi";
import { getSupportConversations } from "../../api/supportApi";
import { authState } from "../../lib/authStorage";

const router = useRouter();
const todayLabel = new Intl.DateTimeFormat("vi-VN", {
  weekday: "long",
  day: "2-digit",
  month: "2-digit",
  year: "numeric",
}).format(new Date());

const notificationMenuRef = ref(null);
const inventoryMenuRef = ref(null);
const notificationMenuOpen = ref(false);
const inventoryMenuOpen = ref(false);
const notificationLoading = ref(false);
const invoiceNotifications = ref([]);
const supportNotifications = ref([]);
const expiringLotNotifications = ref([]);
const lowStockNotifications = ref([]);
const notificationCount = ref(0);
const inventoryNotificationCount = ref(0);

const showSystemNotifications = computed(() =>
  ["admin", "staff", "nhan_vien", "nhanvien"].includes(authState.type)
    && authState.sessionChannel !== "tai_quay"
);

const inventoryAlertNotifications = computed(() => [
  ...expiringLotNotifications.value.map((item) => ({
    id: item.id || `expiring-lot-${item.id_lo}`,
    alertType: "expiring",
    title: `Lô ${item.so_lo} sắp hết hạn`,
    copy: item.message || `${item.ten_thuoc || "Thuốc"} cần kiểm tra hạn sử dụng.`,
    badge: `${Number(item.so_ngay_con_lai || 0)} ngày còn lại`,
    meta: formatAlertQuantity(item.so_luong_con, item.don_vi_ton_kho),
  })),
  ...lowStockNotifications.value.map((item) => ({
    id: item.id || `low-stock-${item.ma_thuoc}`,
    alertType: "low-stock",
    title: `${item.ten_thuoc || item.ma_thuoc} gần hết hàng`,
    copy: item.message || "Thuốc đang dưới ngưỡng tồn kho, cần nhập hàng sớm.",
    badge: `Còn ${formatAlertQuantity(item.so_luong_con, item.don_vi_ton_kho)}`,
    meta: `Ngưỡng ${Number(item.nguong_canh_bao || 20)}`,
  })),
]);

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
  const unreadInvoices = invoiceNotifications.value.filter(
    (item) => !readIds.has(String(item.id_hoa_don))
  ).length;
  const unreadSupportMessages = supportNotifications.value.reduce(
    (total, item) => total + Number(item.so_tin_chua_doc || 0),
    0
  );
  const unreadInventoryAlerts = inventoryAlertNotifications.value.filter(
    (item) => !readIds.has(String(item.id))
  ).length;
  notificationCount.value = unreadInvoices + unreadSupportMessages;
  inventoryNotificationCount.value = unreadInventoryAlerts;
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
  const nextIds = new Set(readStoredNotificationIds());
  invoiceNotifications.value.forEach((item) => nextIds.add(String(item.id_hoa_don)));
  persistReadNotificationIds(Array.from(nextIds));
  syncNotificationCount();
}

function markInventoryNotificationRead(item) {
  const notificationId = String(item?.id || "").trim();
  if (!notificationId) {
    return;
  }

  const nextIds = new Set(readStoredNotificationIds());
  nextIds.add(notificationId);
  persistReadNotificationIds(Array.from(nextIds));
  syncNotificationCount();
}

function markAllInventoryNotificationsRead() {
  const nextIds = new Set(readStoredNotificationIds());
  inventoryAlertNotifications.value.forEach((item) => nextIds.add(String(item.id)));
  persistReadNotificationIds(Array.from(nextIds));
  syncNotificationCount();
}

onMounted(() => {
  document.addEventListener("click", handleDocumentClick);

  if (showSystemNotifications.value) {
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
  if (!showSystemNotifications.value) {
    return;
  }

  if (!silent) {
    notificationLoading.value = true;
  }

  try {
    const [invoiceResponse, supportResponse, inventoryResponse] = await Promise.allSettled([
      getPendingHoaDonNotifications(),
      getSupportConversations(),
      getInventoryAlerts(),
    ]);

    invoiceNotifications.value =
      invoiceResponse.status === "fulfilled" && Array.isArray(invoiceResponse.value?.data)
        ? invoiceResponse.value.data
        : [];

    supportNotifications.value =
      supportResponse.status === "fulfilled" && Array.isArray(supportResponse.value?.data)
        ? supportResponse.value.data.filter((item) => Number(item.so_tin_chua_doc || 0) > 0)
        : [];

    const inventoryData = inventoryResponse.status === "fulfilled" ? inventoryResponse.value?.data : null;
    expiringLotNotifications.value = Array.isArray(inventoryData?.lo_sap_het_han)
      ? inventoryData.lo_sap_het_han
      : [];
    lowStockNotifications.value = Array.isArray(inventoryData?.thuoc_gan_het_ton)
      ? inventoryData.thuoc_gan_het_ton
      : [];

    syncNotificationCount();
  } catch {
    if (!silent) {
      invoiceNotifications.value = [];
      supportNotifications.value = [];
      expiringLotNotifications.value = [];
      lowStockNotifications.value = [];
      notificationCount.value = 0;
      inventoryNotificationCount.value = 0;
    }
  } finally {
    if (!silent) {
      notificationLoading.value = false;
    }
  }
}

async function toggleNotificationMenu() {
  notificationMenuOpen.value = !notificationMenuOpen.value;
  inventoryMenuOpen.value = false;

  if (notificationMenuOpen.value) {
    await loadNotifications();
  }
}

async function toggleInventoryAlertMenu() {
  inventoryMenuOpen.value = !inventoryMenuOpen.value;
  notificationMenuOpen.value = false;

  if (inventoryMenuOpen.value) {
    await loadNotifications();
  }
}

function handleDocumentClick(event) {
  if (!notificationMenuRef.value?.contains(event.target)) {
    notificationMenuOpen.value = false;
  }

  if (!inventoryMenuRef.value?.contains(event.target)) {
    inventoryMenuOpen.value = false;
  }
}

function goToInvoices(query = {}) {
  notificationMenuOpen.value = false;
  inventoryMenuOpen.value = false;
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

function openSupportNotification(item) {
  const conversationId = Number(item?.id_hoi_thoai || 0);
  if (conversationId > 0) {
    supportNotifications.value = supportNotifications.value.filter(
      (entry) => Number(entry.id_hoi_thoai || 0) !== conversationId
    );
    syncNotificationCount();
  }

  notificationMenuOpen.value = false;
  inventoryMenuOpen.value = false;
  router.push({
    path: "/ho-tro-khach-hang",
    query: {
      hoi_thoai: item.id_hoi_thoai,
    },
  });
}

function openInventoryNotification(item) {
  markInventoryNotificationRead(item);
  notificationMenuOpen.value = false;
  inventoryMenuOpen.value = false;
  router.push({
    path: "/ton-kho",
    query: {
      alert: item.alertType || "expiring",
    },
  });
}

function openAllInventoryNotifications() {
  markAllInventoryNotificationsRead();
  notificationMenuOpen.value = false;
  inventoryMenuOpen.value = false;
  const firstAlert = inventoryAlertNotifications.value[0];
  router.push({
    path: "/ton-kho",
    query: {
      alert: firstAlert?.alertType || "expiring",
    },
  });
}

function openPriorityNotifications() {
  if (supportNotifications.value.length) {
    openSupportNotification(supportNotifications.value[0]);
    return;
  }

  openAllInvoiceNotifications();
}

function formatCurrency(value) {
      const n = Number(value || 0);
      if (Math.abs(n) >= 1e9) {
        const ty = n / 1e9;
        return (Math.abs(ty) >= 10 ? Math.round(ty) : ty.toFixed(2).replace('.', ',')) + ' tỷ đ';
      }
      return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 }).format(n);
}

function formatAlertQuantity(value, unit = "") {
  const quantity = Number(value || 0);
  const formatted = new Intl.NumberFormat("vi-VN", {
    maximumFractionDigits: 2,
  }).format(quantity);
  const normalizedUnit = String(unit || "").trim();

  return normalizedUnit ? `${formatted} ${normalizedUnit}` : formatted;
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
