import { computed, reactive } from "vue";
import { authState, getAuthType, getStoredUser, isAuthenticatedState, setAuthSession } from "./authStorage";
import { watch } from "vue";
import { getProfile, updateProfileApi } from "../api/profileApi";
import {
  createCustomerAddress,
  getCustomerAddresses,
  updateCustomerAddress,
} from "../api/customerAddressApi";
import { getCatalogThuocs } from "../api/catalogApi";
import {
  getCustomerBroadcastNotifications,
  markAllCustomerNotificationsRead,
  markCustomerNotificationRead,
} from "../api/customerNotificationApi";
import { getCustomerOrders } from "../api/orderApi";
import { applyProductUnitSelection, buildProductUnitCartKey } from "./productUnits";

const CART_KEY = "pharmacity_customer_cart";
const PROFILE_KEY = "pharmacity_customer_profile";
const ADDRESS_KEY = "pharmacity_customer_addresses";
const ORDER_KEY = "pharmacity_customer_orders_v2";
const HIDDEN_ORDER_KEY = "pharmacity_customer_hidden_orders_v1";
const PROMOTION_KEY = "pharmacity_customer_applied_promotion";
const NOTIFICATION_KEY = "pharmacity_customer_notifications";

function readJson(key, fallback) {
  try {
    const raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw) : fallback;
  } catch {
    return fallback;
  }
}

function writeJson(key, value) {
  localStorage.setItem(key, JSON.stringify(value));
}

function removeJson(key) {
  localStorage.removeItem(key);
}

function normalizeScopePart(value) {
  return String(value ?? "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, "")
    .replace(/[^a-z0-9@._+-]/g, "_");
}

function getTokenScopeFallback() {
  const token = normalizeScopePart(authState.token);
  return token ? `token:${token.slice(-18)}` : "unknown";
}

function buildCustomerScope(user) {
  const parts = [
    ["id", user?.id_khach_hang || user?.id],
    ["phone", user?.so_dien_thoai || user?.phone],
    ["email", user?.email],
  ]
    .map(([label, value]) => {
      const normalizedValue = normalizeScopePart(value);
      return normalizedValue ? `${label}:${normalizedValue}` : "";
    })
    .filter(Boolean);

  return parts.length ? parts.join("|") : getTokenScopeFallback();
}

function buildSystemScope(type, user) {
  const parts = [
    ["id", user?.id_nhan_vien || user?.id],
    ["username", user?.ten_dang_nhap],
    ["email", user?.email],
  ]
    .map(([label, value]) => {
      const normalizedValue = normalizeScopePart(value);
      return normalizedValue ? `${label}:${normalizedValue}` : "";
    })
    .filter(Boolean);

  return parts.length ? parts.join("|") : getTokenScopeFallback();
}

function getStorageScopeId() {
  const type = getAuthType();
  const user = getStoredUser();

  if (!authState.token || !user) {
    return "guest";
  }

  if (type === "customer") {
    return `customer:${buildCustomerScope(user)}`;
  }

  return `system:${normalizeScopePart(type) || "unknown"}:${buildSystemScope(type, user)}`;
}

function getScopedKey(key) {
  return `${key}__${getStorageScopeId()}`;
}

function readScopedJson(key, fallback) {
  return readJson(getScopedKey(key), fallback);
}

function writeScopedJson(key, value) {
  writeJson(getScopedKey(key), value);
}

function removeScopedJson(key) {
  removeJson(getScopedKey(key));
}

function normalizeAvatarUrl(url) {
  if (!url) {
    return "";
  }

  const separator = url.includes("?") ? "&" : "?";
  return `${url}${separator}t=${Date.now()}`;
}

function getUserBirthDate(user) {
  const value = user?.ngay_sinh || user?.thong_tin_nhan_vien?.ngay_sinh || "";
  const normalizedValue = String(value || "");

  if (!normalizedValue) {
    return "";
  }

  if (/^\d{4}-\d{2}-\d{2}$/.test(normalizedValue)) {
    return normalizedValue;
  }

  const parsedDate = new Date(normalizedValue);

  if (Number.isNaN(parsedDate.getTime())) {
    return normalizedValue;
  }

  const year = parsedDate.getFullYear();
  const month = String(parsedDate.getMonth() + 1).padStart(2, "0");
  const day = String(parsedDate.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function normalizePointValue(value) {
  const parsedValue = Number(value || 0);
  return Number.isFinite(parsedValue) ? Math.max(Math.floor(parsedValue), 0) : 0;
}

function buildProfile() {
  const authType = getAuthType();
  const user = getStoredUser();

  return {
    hoTen: user?.ten_khach_hang || user?.ho_ten || "Khách hàng",
    soDienThoai: user?.so_dien_thoai || "**** *** 128",
    email: user?.email || "",
    diaChi: user?.dia_chi || "",
    avatarUrl: normalizeAvatarUrl(user?.avatar_url || ""),
    ngaySinh: getUserBirthDate(user),
    gioiTinh: user?.gioi_tinh || "",
    rankName: authType === "customer" ? "Hạng Vàng" : "Thành viên",
    pxu: normalizePointValue(user?.diem_tich_luy ?? user?.pxu ?? 0),
  };
}

function parseProfileAddress(rawAddress) {
  const source = String(rawAddress || "").trim();

  if (!source) {
    return {
      soNha: "",
      phuongXa: "",
      quanHuyen: "",
      tinhThanh: "",
    };
  }

  const segments = source
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);

  if (segments.length >= 4) {
    return {
      soNha: segments.slice(0, segments.length - 3).join(", "),
      phuongXa: segments[segments.length - 3] || "",
      quanHuyen: segments[segments.length - 2] || "",
      tinhThanh: segments[segments.length - 1] || "",
    };
  }

  if (segments.length === 3) {
    return {
      soNha: segments[0] || "",
      phuongXa: "",
      quanHuyen: segments[1] || "",
      tinhThanh: segments[2] || "",
    };
  }

  if (segments.length === 2) {
    return {
      soNha: segments[0] || "",
      phuongXa: "",
      quanHuyen: "",
      tinhThanh: segments[1] || "",
    };
  }

  return {
    soNha: source,
    phuongXa: "",
    quanHuyen: "",
    tinhThanh: "",
  };
}

function buildDefaultAddresses(profileData = buildProfile()) {
  const parsedAddress = parseProfileAddress(profileData?.diaChi);
  const hasAddressData = [parsedAddress.soNha, parsedAddress.phuongXa, parsedAddress.quanHuyen, parsedAddress.tinhThanh]
    .some(Boolean);

  if (!hasAddressData) {
    return [];
  }

  return [
    {
      id: `seed-${getStorageScopeId()}`,
      hoTen: profileData?.hoTen || "Khách hàng",
      soDienThoai: String(profileData?.soDienThoai || "").replaceAll("*", "0"),
      tinhThanh: parsedAddress.tinhThanh,
      quanHuyen: parsedAddress.quanHuyen,
      phuongXa: parsedAddress.phuongXa,
      soNha: parsedAddress.soNha,
      loaiDiaChi: "Nhà riêng",
      macDinh: true,
      __seededFromProfile: true,
    },
  ];
}

function normalizeAddressValue(value) {
  return String(value || "").trim();
}

function normalizePhoneValue(value) {
  return normalizeAddressValue(value).replaceAll("*", "0").replace(/\D/g, "").slice(0, 10);
}

function createAddressFingerprint(address) {
  return [
    normalizeAddressValue(address?.hoTen).toLowerCase(),
    normalizePhoneValue(address?.soDienThoai),
    normalizeAddressValue(address?.tinhThanh),
    normalizeAddressValue(address?.quanHuyen),
    normalizeAddressValue(address?.phuongXa),
    normalizeAddressValue(address?.soNha),
    normalizeAddressValue(address?.loaiDiaChi).toLowerCase(),
  ].join("|");
}

function normalizeAddressRecord(address, profileData = buildProfile(), index = 0) {
  const phone = normalizePhoneValue(address?.soDienThoai || profileData?.soDienThoai);
  const fallbackFingerprint = createAddressFingerprint({
    ...address,
    hoTen: address?.hoTen || profileData?.hoTen || "Khách hàng",
    soDienThoai: phone,
    loaiDiaChi: address?.loaiDiaChi || "Nhà riêng",
  });

  return {
    ...address,
    id: address?.id || `addr-${getStorageScopeId()}-${fallbackFingerprint || index}`,
    hoTen: address?.hoTen || profileData?.hoTen || "Khách hàng",
    soDienThoai: phone,
    tinhThanh: address?.tinhThanh || "",
    quanHuyen: address?.quanHuyen || "",
    phuongXa: address?.phuongXa || "",
    soNha: address?.soNha || "",
    loaiDiaChi: address?.loaiDiaChi || "Nhà riêng",
    macDinh: Boolean(address?.macDinh),
  };
}

function normalizeApiAddressRecord(address, profileData = buildProfile(), index = 0) {
  return normalizeAddressRecord(
    {
      id: address?.id,
      hoTen: address?.ho_ten,
      soDienThoai: address?.so_dien_thoai,
      tinhThanh: address?.tinh_thanh,
      quanHuyen: address?.quan_huyen,
      phuongXa: address?.phuong_xa,
      soNha: address?.so_nha,
      loaiDiaChi: address?.loai_dia_chi,
      macDinh: address?.mac_dinh,
    },
    profileData,
    index
  );
}

function serializeAddressPayload(address) {
  return {
    ho_ten: address?.hoTen || "Khách hàng",
    so_dien_thoai: normalizePhoneValue(address?.soDienThoai),
    tinh_thanh: address?.tinhThanh || "",
    quan_huyen: address?.quanHuyen || "",
    phuong_xa: address?.phuongXa || "",
    so_nha: address?.soNha || "",
    loai_dia_chi: address?.loaiDiaChi || "Nhà riêng",
    mac_dinh: Boolean(address?.macDinh),
  };
}

function dedupeAddresses(addresses, profileData = buildProfile()) {
  const uniqueMap = new Map();

  addresses.forEach((address, index) => {
    const normalized = normalizeAddressRecord(address, profileData, index);
    const fingerprint = createAddressFingerprint(normalized);
    const existing = uniqueMap.get(fingerprint);

    if (!existing) {
      uniqueMap.set(fingerprint, normalized);
      return;
    }

    if (normalized.macDinh && !existing.macDinh) {
      uniqueMap.set(fingerprint, normalized);
      return;
    }

    if (existing?.__seededFromProfile && !normalized?.__seededFromProfile) {
      uniqueMap.set(fingerprint, normalized);
    }
  });

  const normalizedAddresses = Array.from(uniqueMap.values());

  if (!normalizedAddresses.length) {
    return [];
  }

  const hasDefault = normalizedAddresses.some((address) => address.macDinh);
  if (!hasDefault) {
    normalizedAddresses[0] = {
      ...normalizedAddresses[0],
      macDinh: true,
    };
  } else {
    let foundDefault = false;
    for (let index = 0; index < normalizedAddresses.length; index += 1) {
      if (normalizedAddresses[index].macDinh && !foundDefault) {
        foundDefault = true;
        continue;
      }

      if (normalizedAddresses[index].macDinh) {
        normalizedAddresses[index] = {
          ...normalizedAddresses[index],
          macDinh: false,
        };
      }
    }
  }

  return normalizedAddresses;
}

function isLegacySharedAddress(address) {
  return (
    String(address?.hoTen || "").trim() === "Khách hàng" &&
    String(address?.soDienThoai || "").trim() === "0909 111 128" &&
    String(address?.tinhThanh || "").trim() === "TP. Hồ Chí Minh" &&
    String(address?.quanHuyen || "").trim() === "Quận 3" &&
    String(address?.phuongXa || "").trim() === "Phường Võ Thị Sáu" &&
    String(address?.soNha || "").trim() === "12A Nguyễn Thị Minh Khai"
  );
}

function normalizeAddresses(addresses, profileData = buildProfile()) {
  if (!Array.isArray(addresses) || addresses.length === 0) {
    return buildDefaultAddresses(profileData);
  }

  if (addresses.length === 1 && (addresses[0]?.__seededFromProfile || isLegacySharedAddress(addresses[0]))) {
    return buildDefaultAddresses(profileData);
  }

  return dedupeAddresses(addresses, profileData);
}

function syncAddressesWithProfile(addresses, previousProfile = buildProfile(), nextProfile = buildProfile()) {
  if (!Array.isArray(addresses) || !addresses.length) {
    return normalizeAddresses(addresses, nextProfile);
  }

  const previousPhone = normalizeAddressValue(previousProfile?.soDienThoai).replaceAll("*", "0");
  const nextPhone = normalizeAddressValue(nextProfile?.soDienThoai).replaceAll("*", "0");
  const previousName = normalizeAddressValue(previousProfile?.hoTen);
  const nextName = normalizeAddressValue(nextProfile?.hoTen);

  const nonEmptyPhones = Array.from(
    new Set(
      addresses
        .map((address) => normalizeAddressValue(address?.soDienThoai).replaceAll("*", "0"))
        .filter(Boolean)
    )
  );

  const addressesBelongToCurrentProfile = addresses.every((address) => {
    const addressName = normalizeAddressValue(address?.hoTen);
    return !addressName || !nextName || addressName === nextName;
  });

  const legacyPhoneToReplace =
    nextPhone &&
    addressesBelongToCurrentProfile &&
    nonEmptyPhones.length === 1 &&
    nonEmptyPhones[0] !== nextPhone
      ? nonEmptyPhones[0]
      : "";

  const parsedNextProfileAddress = parseProfileAddress(nextProfile?.diaChi);

  const syncedAddresses = addresses.map((address, index) => {
    const normalized = normalizeAddressRecord(address, nextProfile, index);
    const addressPhone = normalizeAddressValue(normalized.soDienThoai).replaceAll("*", "0");
    const addressName = normalizeAddressValue(normalized.hoTen);

    const shouldSyncPhone =
      Boolean(nextPhone) &&
      (!addressPhone ||
        (previousPhone && addressPhone === previousPhone) ||
        (legacyPhoneToReplace && addressPhone === legacyPhoneToReplace && (!addressName || addressName === nextName)));

    const shouldSyncName =
      Boolean(nextName) &&
      (!addressName || (previousName && addressName === previousName));

    if (normalized.__seededFromProfile) {
      return {
        ...normalized,
        hoTen: nextName || normalized.hoTen,
        soDienThoai: nextPhone || normalized.soDienThoai,
        tinhThanh: parsedNextProfileAddress.tinhThanh || normalized.tinhThanh,
        quanHuyen: parsedNextProfileAddress.quanHuyen || normalized.quanHuyen,
        phuongXa: parsedNextProfileAddress.phuongXa || normalized.phuongXa,
        soNha: parsedNextProfileAddress.soNha || normalized.soNha,
      };
    }

    return {
      ...normalized,
      hoTen: shouldSyncName ? nextName : normalized.hoTen,
      soDienThoai: shouldSyncPhone ? nextPhone : normalized.soDienThoai,
    };
  });

  return normalizeAddresses(syncedAddresses, nextProfile);
}

function normalizeOrderStatus(value) {
  const raw = String(value || "").trim();

  if (!raw) {
    return "Hoàn thành";
  }

  const normalized = raw
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase();

  if (["thanh cong", "hoan thanh", "completed", "complete", "success", "succeeded"].includes(normalized)) {
    return "Hoàn thành";
  }

  if (["dang xu ly", "cho xu ly", "pending", "processing", "in progress"].includes(normalized)) {
    return "Đang xử lý";
  }

  if (["dang giao", "shipping", "delivering"].includes(normalized)) {
    return "Đang giao";
  }

  if (["da huy", "huy", "cancelled", "canceled"].includes(normalized)) {
    return "Đã hủy";
  }

  if (["that bai", "failed", "failure"].includes(normalized)) {
    return "Thất bại";
  }

  return raw;
}

function enrichOrderItemsWithCatalog(items, catalogMap = new Map()) {
  return (Array.isArray(items) ? items : []).map((item) => {
    const maThuoc = item.maThuoc || item.ma_thuoc || item.id || "";
    const catalogItem = catalogMap.get(maThuoc);

    return {
      ...item,
      hinhAnhUrl: item.hinhAnhUrl || item.hinh_anh_url || catalogItem?.hinh_anh_url || "",
      imageTone: item.imageTone || "pink",
      loai: item.loai || item.loai_thuoc || catalogItem?.loai_thuoc || "",
      moTa: item.moTa || item.mo_ta || catalogItem?.mo_ta || "",
    };
  });
}

const defaultOrders = [];

const defaultVouchers = [
  {
    id: "pharmacity-01",
    ten: "Khuyến mãi",
    moTa: "Ưu đãi đang áp dụng trên sản phẩm đã chọn.",
  },
];

let currentStorageScope = getStorageScopeId();
let accountNotificationsSource = [];
let sharedNotificationsSource = [];
let hiddenOrderIds = readScopedJson(HIDDEN_ORDER_KEY, []).map((item) => String(item));

function isLegacySeedNotification(item) {
  const notificationId = String(item?.id ?? "");
  const title = String(item?.tieuDe ?? "").trim();

  return (
    (notificationId === "1" && title === "Đơn hàng DH-20260322-01 đang được chuẩn bị") ||
    (notificationId === "2" && title === "Mua 1 tặng 1 cho một số sản phẩm chăm sóc cá nhân")
  );
}

function normalizeNotificationItem(item, scope = "account") {
  const notification = {
    id: String(item?.id ?? `${scope}-${Date.now()}`),
    scope,
    remoteId: item?.remoteId ?? item?.remote_id ?? null,
    group: item?.group || "Hệ thống",
    tieuDe: item?.tieuDe || "Thông báo mới",
    noiDung: item?.noiDung || item?.noi_dung || "",
    daDoc: Boolean(item?.daDoc),
    daDocScopes: Array.isArray(item?.daDocScopes) ? item.daDocScopes.filter(Boolean).map(String) : [],
    createdAt: item?.createdAt || new Date().toISOString(),
  };

  if (scope === "account") {
    notification.ownerScope = String(item?.ownerScope || item?.owner_scope || getStorageScopeId());
  }

  return notification;
}

function getNotificationReadScope() {
  return getStorageScopeId();
}

function sanitizeAccountNotifications(list) {
  const ownerScope = getStorageScopeId();

  return Array.isArray(list)
    ? list
        .filter((item) => !isLegacySeedNotification(item))
        .map((item) => normalizeNotificationItem(item, "account"))
        .filter((item) => item.ownerScope === ownerScope)
    : [];
}

function sanitizeSharedNotifications(list) {
  return Array.isArray(list) ? list.map((item) => normalizeNotificationItem(item, "shared")) : [];
}

function buildMergedNotifications() {
  const ownerScope = getStorageScopeId();
  const accountNotifications = accountNotificationsSource
    .filter((item) => item.ownerScope === ownerScope)
    .map((item) => ({
      ...item,
      scope: "account",
      daDoc: Boolean(item.daDoc),
    }));
  const sharedNotifications = sharedNotificationsSource.map((item) => ({
    ...item,
    scope: "shared",
    daDoc: Boolean(item.daDoc),
  }));

  return [...accountNotifications, ...sharedNotifications].sort(
    (left, right) => new Date(right.createdAt).getTime() - new Date(left.createdAt).getTime()
  );
}

function syncNotificationsState() {
  state.notifications = buildMergedNotifications();
}

function persistAccountNotifications() {
  writeScopedJson(NOTIFICATION_KEY, accountNotificationsSource);
}

function persistSharedNotifications() {
  return sharedNotificationsSource;
}

function addAccountNotification(payload) {
  const notification = normalizeNotificationItem(
    {
      id: payload?.id || `account-${Date.now()}`,
      group: payload?.group || "Hệ thống",
      tieuDe: payload?.tieuDe || "Thông báo mới",
      noiDung: payload?.noiDung || payload?.noi_dung || "",
      ownerScope: getStorageScopeId(),
      createdAt: payload?.createdAt || new Date().toISOString(),
      daDoc: false,
    },
    "account"
  );

  accountNotificationsSource = [
    notification,
    ...accountNotificationsSource.filter((item) => String(item.id) !== String(notification.id)),
  ];
  persistAccountNotifications();
  syncNotificationsState();
  return notification;
}

function normalizeNotificationToken(value) {
  return String(value || "")
    .trim()
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/[^a-z0-9]+/g, "_")
    .replace(/^_+|_+$/g, "");
}

function isPayosOrder(order) {
  const method = normalizeNotificationToken(order?.phuongThucThanhToan || order?.phuong_thuc_thanh_toan || "");
  const methodLabel = normalizeNotificationToken(order?.phuongThucThanhToanLabel || order?.phuong_thuc_thanh_toan_label || "");
  return method === "payos" || methodLabel.includes("payos");
}

function removeOrderNotification(orderId) {
  const notificationId = `order-${String(orderId || "").trim()}`;

  if (notificationId === "order-") {
    return;
  }

  const nextNotifications = accountNotificationsSource.filter((item) => String(item.id) !== notificationId);

  if (nextNotifications.length === accountNotificationsSource.length) {
    return;
  }

  accountNotificationsSource = nextNotifications;
  persistAccountNotifications();
  syncNotificationsState();
}

function removePayosOrderNotificationByOrderCode(orderCode) {
  const normalizedOrderCode = String(orderCode || "").trim();

  if (!normalizedOrderCode) {
    return;
  }

  const order = state.orders.find((item) =>
    isPayosOrder(item) &&
    [
      item.idHoaDon,
      item.id_hoa_don,
      item.payosOrderCode,
      item.payos?.order_code,
      item.payos?.orderCode,
      item.payosPaymentLinkId,
      item.payos?.payment_link_id,
      item.payos?.paymentLinkId,
      item.id,
    ].some((value) => String(value || "").trim() === normalizedOrderCode)
  );

  if (order?.id) {
    removeOrderNotification(order.id);
  }
}

function buildOrderNotificationTitle(order) {
  if (!order?.id) {
    return "";
  }

  const paymentStatus = normalizeNotificationToken(
    order?.trangThaiThanhToan || order?.payosStatus || order?.payos?.status || ""
  );
  const orderStatus = normalizeNotificationToken(
    order?.trangThaiXuLy || order?.trang_thai_xu_ly || order?.trangThai || order?.trang_thai || ""
  );

  if (["huy", "da_huy", "cancelled", "canceled", "tu_choi", "tu_choi_don_hang"].includes(orderStatus)) {
    return "";
  }

  if (["canceled", "cancelled", "failed", "fail", "that_bai", "expired", "het_han"].includes(paymentStatus)) {
    return "";
  }

  if (isPayosOrder(order)) {
    if (["pending", "unpaid", "cho_thanh_toan", "dang_cho_thanh_toan", "waiting"].includes(paymentStatus)) {
      return `Đơn hàng ${order.id} của bạn chưa được thanh toán.`;
    }

    if (["paid", "success", "completed", "da_thanh_toan", "thanh_toan_thanh_cong"].includes(paymentStatus)) {
      if (["da_xac_nhan", "hoan_thanh", "thanh_cong", "completed", "complete", "success"].includes(orderStatus)) {
        return `Đơn hàng ${order.id} đã đặt hàng thành công.`;
      }

      return `Đơn hàng ${order.id} thanh toán thành công, chờ xác nhận.`;
    }
  }

  if (["da_xac_nhan", "hoan_thanh", "thanh_cong", "completed", "complete", "success"].includes(orderStatus)) {
    return `Đơn hàng ${order.id} đã đặt hàng thành công.`;
  }

  return `Đơn hàng ${order.id} đang chờ nhân viên xác nhận.`;
}

function addOrderNotification(order) {
  if (!order?.id) {
    return null;
  }

  const notificationTitle = buildOrderNotificationTitle(order);
  if (!notificationTitle) {
    removeOrderNotification(order.id);
    return null;
  }

  const notificationId = `order-${order.id}`;
  const existingNotification = accountNotificationsSource.find((item) => String(item.id) === notificationId);
  if (existingNotification?.tieuDe === notificationTitle) {
    return existingNotification;
  }

  return addAccountNotification({
    id: notificationId,
    group: "Đơn hàng",
    tieuDe: notificationTitle,
    createdAt: new Date().toISOString(),
  });
}

function formatOrderDisplayDate(value) {
  if (!value) {
    return new Date().toLocaleDateString("vi-VN");
  }

  const parsedDate = new Date(value);
  if (!Number.isNaN(parsedDate.getTime())) {
    return new Intl.DateTimeFormat("vi-VN").format(parsedDate);
  }

  const rawValue = String(value || "").trim();
  return rawValue || new Date().toLocaleDateString("vi-VN");
}

function normalizeOrderHistoryItem(orderPayload) {
  const orderDate = orderPayload?.ngay_ban || orderPayload?.ngay;
  const tongTienGoc = Number(orderPayload?.tong_tien || orderPayload?.tongTien || 0);
  const giamGia = Number(orderPayload?.giam_gia || orderPayload?.giamGia || 0);
  const giamGiaMa = Number(orderPayload?.giam_gia_ma || orderPayload?.giamGiaMa || 0);
  const giamGiaDiem = Number(orderPayload?.giam_gia_diem || orderPayload?.giamGiaDiem || 0);
  const thueVat = Number(orderPayload?.thue_vat || orderPayload?.thueVat || 0);
  const payosPayload = orderPayload?.payos && typeof orderPayload.payos === "object" ? orderPayload.payos : {};
  const trangThaiThanhToan =
    orderPayload?.trang_thai_thanh_toan ||
    orderPayload?.trangThaiThanhToan ||
    payosPayload?.status ||
    orderPayload?.payos_status ||
    orderPayload?.payosStatus ||
    "";
  const payosCheckoutUrl =
    payosPayload?.checkout_url ||
    payosPayload?.checkoutUrl ||
    orderPayload?.payos_checkout_url ||
    orderPayload?.payosCheckoutUrl ||
    "";
  const payosOrderCode =
    payosPayload?.order_code ||
    payosPayload?.orderCode ||
    orderPayload?.payos_order_code ||
    orderPayload?.payosOrderCode ||
    "";
  const payosPaymentLinkId =
    payosPayload?.payment_link_id ||
    payosPayload?.paymentLinkId ||
    orderPayload?.payos_payment_link_id ||
    orderPayload?.payosPaymentLinkId ||
    "";
  const payosQrCode =
    payosPayload?.qr_code ||
    payosPayload?.qrCode ||
    orderPayload?.payos_qr_code ||
    orderPayload?.payosQrCode ||
    "";
  const payosPaidAt =
    payosPayload?.paid_at ||
    payosPayload?.paidAt ||
    orderPayload?.payos_paid_at ||
    orderPayload?.payosPaidAt ||
    null;
  const hasPayosData = Boolean(
    payosCheckoutUrl ||
      payosOrderCode ||
      payosPaymentLinkId ||
      payosQrCode ||
      trangThaiThanhToan ||
      payosPaidAt
  );
  const purchasedItems = Array.isArray(orderPayload?.items)
      ? orderPayload.items.map((item) => ({
          id: item.id || item.maThuoc || item.ma_thuoc,
          maThuoc: item.maThuoc || item.ma_thuoc || "",
          ten: item.ten || item.ten_thuoc || "",
          donVi: item.donVi || item.don_vi || item.don_vi_tinh || "",
          gia: Number(item.gia || item.gia_ban || 0),
          giaGoc: Number(item.giaGoc || item.gia_goc || item.gia || item.gia_ban || 0),
          thanhTien: Number(item.thanhTien || item.thanh_tien || 0),
          soLuong: Number(item.soLuong || item.so_luong || 1),
          imageTone: item.imageTone || "pink",
          hinhAnhUrl: item.hinhAnhUrl || item.hinh_anh_url || "",
          loai: item.loai || item.loai_thuoc || "",
          moTa: item.moTa || item.mo_ta || "",
        }))
    : [];

  return {
    id: String(orderPayload?.ma_hoa_don || orderPayload?.id || orderPayload?.id_hoa_don || `DH-${Date.now()}`),
    idHoaDon: orderPayload?.id_hoa_don || orderPayload?.idHoaDon || null,
    ngay: formatOrderDisplayDate(orderDate),
    ngayBan: orderPayload?.ngay_ban || null,
    trangThai: normalizeOrderStatus(orderPayload?.trang_thai || orderPayload?.trangThai || orderPayload?.status),
    trangThaiXuLy: orderPayload?.trang_thai_xu_ly || orderPayload?.trangThaiXuLy || "",
    thueVat,
    tongTien: Number(orderPayload?.tien_thanh_toan || orderPayload?.tienThanhToan || tongTienGoc - giamGia + thueVat || 0),
    tamTinh: tongTienGoc,
    giamGia,
    giamGiaMa,
    giamGiaDiem,
    diemDaSuDung: normalizePointValue(orderPayload?.diem_da_su_dung || orderPayload?.diemDaSuDung || 0),
    diemDaCong: normalizePointValue(orderPayload?.diem_da_cong || orderPayload?.diemDaCong || 0),
    diemHienTai: orderPayload?.diem_hien_tai == null
      ? null
      : normalizePointValue(orderPayload?.diem_hien_tai),
    maGiamGia: orderPayload?.ma_giam_gia || orderPayload?.maGiamGia || "",
    tienThanhToan: Number(orderPayload?.tien_thanh_toan || orderPayload?.tienThanhToan || tongTienGoc - giamGia + thueVat || 0),
    sanPham:
      Number(orderPayload?.tong_so_san_pham || 0) ||
      purchasedItems.reduce((total, item) => total + Number(item.soLuong || 0), 0),
    items: purchasedItems,
    nguoiNhan: orderPayload?.nguoi_nhan || "",
    soDienThoaiNhan: orderPayload?.so_dien_thoai_nhan || "",
    diaChiGiaoHang: orderPayload?.dia_chi_giao_hang || "",
    ghiChu: orderPayload?.ghi_chu || "",
    ghiChuHeThong: orderPayload?.ghi_chu_he_thong || "",
    phuongThucThanhToan: orderPayload?.phuong_thuc_thanh_toan || orderPayload?.phuongThucThanhToan || "",
    phuongThucThanhToanLabel: orderPayload?.phuong_thuc_thanh_toan_label || orderPayload?.phuongThucThanhToanLabel || "",
    maGiaoDich: orderPayload?.ma_giao_dich || orderPayload?.maGiaoDich || "",
    thoiGianThanhToan: orderPayload?.thoi_gian_thanh_toan || orderPayload?.thoiGianThanhToan || null,
    trangThaiThanhToan,
    payosStatus: trangThaiThanhToan,
    payosCheckoutUrl,
    payosOrderCode,
    payosPaymentLinkId,
    payosQrCode,
    payosPaidAt,
    payos: hasPayosData
      ? {
          order_code: payosOrderCode,
          orderCode: payosOrderCode,
          payment_link_id: payosPaymentLinkId,
          paymentLinkId: payosPaymentLinkId,
          checkout_url: payosCheckoutUrl,
          checkoutUrl: payosCheckoutUrl,
          qr_code: payosQrCode,
          qrCode: payosQrCode,
          status: trangThaiThanhToan,
          paid_at: payosPaidAt,
          paidAt: payosPaidAt,
        }
      : null,
    timeline: Array.isArray(orderPayload?.timeline)
      ? orderPayload.timeline.map((item, index) => ({
          id: item.id || `timeline-${index}`,
          label: item.label || "",
          thoiGian: item.thoi_gian || item.thoiGian || null,
          moTa: item.mo_ta || item.moTa || "",
        }))
      : [],
    statusUpdatedAt: orderPayload?.thoi_gian_cap_nhat_trang_thai || null,
  };
}

function persistHiddenOrders() {
  writeScopedJson(HIDDEN_ORDER_KEY, hiddenOrderIds);
}

accountNotificationsSource = sanitizeAccountNotifications(readScopedJson(NOTIFICATION_KEY, []));
sharedNotificationsSource = [];
persistAccountNotifications();
persistSharedNotifications();

const state = reactive({
  profile: readScopedJson(PROFILE_KEY, buildProfile()),
  addresses: normalizeAddresses(readScopedJson(ADDRESS_KEY, []), readScopedJson(PROFILE_KEY, buildProfile())),
  cart: sanitizeCartItems(readScopedJson(CART_KEY, [])),
  orders: readScopedJson(ORDER_KEY, defaultOrders)
    .map((order) => normalizeOrderHistoryItem(order))
    .filter((order) => !hiddenOrderIds.includes(String(order?.id || ""))),
  appliedPromotion: readScopedJson(PROMOTION_KEY, null),
  useRewardPoints: false,
  note: "",
  paymentMethod: "cod",
  hideProductInfo: false,
  vouchers: defaultVouchers,
  notifications: buildMergedNotifications(),
});

function persistCart() {
  writeScopedJson(CART_KEY, state.cart);
}

function persistAddresses() {
  state.addresses = normalizeAddresses(state.addresses, state.profile);
  writeScopedJson(ADDRESS_KEY, state.addresses);
}

async function migrateLocalAddressesToApi(localAddresses) {
  const normalizedLocalAddresses = normalizeAddresses(localAddresses, state.profile);

  if (!normalizedLocalAddresses.length) {
    return [];
  }

  const migrated = [];

  for (const address of normalizedLocalAddresses) {
    const response = await createCustomerAddress(serializeAddressPayload(address));
    if (response?.data) {
      migrated.push(response.data);
    }
  }

  return migrated;
}

async function syncAddressesFromApi(options = {}) {
  const { silent = true, migrateLegacy = true } = options;
  hydrateScopedState();

  if (!isAuthenticatedState.value || getAuthType() !== "customer") {
    state.addresses = normalizeAddresses(state.addresses, state.profile);
    persistAddresses();
    return state.addresses;
  }

  try {
    let response = await getCustomerAddresses();
    let remoteAddresses = Array.isArray(response?.data) ? response.data : [];

    if (!remoteAddresses.length && migrateLegacy) {
      const localAddresses = normalizeAddresses(readScopedJson(ADDRESS_KEY, []), state.profile);

      if (localAddresses.length) {
        await migrateLocalAddressesToApi(localAddresses);
        response = await getCustomerAddresses();
        remoteAddresses = Array.isArray(response?.data) ? response.data : [];
      }
    }

    state.addresses = remoteAddresses.length
      ? normalizeAddresses(
          remoteAddresses.map((address, index) => normalizeApiAddressRecord(address, state.profile, index)),
          state.profile
        )
      : normalizeAddresses([], state.profile);

    persistAddresses();
    return state.addresses;
  } catch (error) {
    if (!silent) {
      throw error;
    }

    state.addresses = normalizeAddresses(state.addresses, state.profile);
    persistAddresses();
    return state.addresses;
  }
}

function persistProfile() {
  writeScopedJson(PROFILE_KEY, state.profile);
}

function persistOrders() {
  writeScopedJson(ORDER_KEY, state.orders);
}

function persistAppliedPromotion() {
  if (state.appliedPromotion) {
    writeScopedJson(PROMOTION_KEY, state.appliedPromotion);
    return;
  }

  removeScopedJson(PROMOTION_KEY);
}

function persistNotifications() {
  persistAccountNotifications();
  persistSharedNotifications();
  syncNotificationsState();
}

function hydrateScopedState() {
  const nextScope = getStorageScopeId();

  if (nextScope === currentStorageScope) {
    return;
  }

  currentStorageScope = nextScope;
  state.profile = readScopedJson(PROFILE_KEY, buildProfile());
  state.addresses = normalizeAddresses(readScopedJson(ADDRESS_KEY, []), state.profile);
  state.cart = sanitizeCartItems(readScopedJson(CART_KEY, []));
  hiddenOrderIds = readScopedJson(HIDDEN_ORDER_KEY, []).map((item) => String(item));
  state.orders = readScopedJson(ORDER_KEY, defaultOrders)
    .map((order) => normalizeOrderHistoryItem(order))
    .filter((order) => !hiddenOrderIds.includes(String(order?.id || "")));
  state.appliedPromotion = readScopedJson(PROMOTION_KEY, null);
  accountNotificationsSource = sanitizeAccountNotifications(readScopedJson(NOTIFICATION_KEY, []));
  sharedNotificationsSource = [];
  persistAccountNotifications();
  persistSharedNotifications();
  syncNotificationsState();
  state.useRewardPoints = false;
  state.note = "";
  state.paymentMethod = "cod";
  state.hideProductInfo = false;
}

function clearGuestPromotionState() {
  if (isAuthenticatedState.value) {
    return;
  }

  state.appliedPromotion = null;
  persistAppliedPromotion();
}

function syncProfileFromAuth() {
  hydrateScopedState();
  const previousProfile = { ...state.profile };
  const nextProfile = buildProfile();

  state.profile = {
    ...state.profile,
    hoTen: nextProfile.hoTen,
    soDienThoai: nextProfile.soDienThoai,
    email: nextProfile.email || state.profile.email,
    diaChi: nextProfile.diaChi || state.profile.diaChi || "",
    avatarUrl: nextProfile.avatarUrl || state.profile.avatarUrl,
    ngaySinh: nextProfile.ngaySinh || state.profile.ngaySinh || "",
    gioiTinh: nextProfile.gioiTinh || state.profile.gioiTinh || "",
    rankName: nextProfile.rankName,
    pxu: nextProfile.pxu,
  };

  state.addresses = syncAddressesWithProfile(state.addresses, previousProfile, state.profile);

  clearGuestPromotionState();
  persistProfile();
  persistAddresses();
}

function sanitizeCartItems(items) {
  if (!Array.isArray(items)) {
    return [];
  }

  return items
    .map((item) => {
      const maThuoc = item.maThuoc || item.ma_thuoc || item.id || "";
      const normalizedProduct = applyProductUnitSelection(
        {
          ...item,
          ma_thuoc: maThuoc,
          ten_thuoc: item.ten || item.ten_thuoc || "",
          loai_thuoc: item.loai || item.loai_thuoc || "",
          don_vi_tinh: item.selectedDonVi || item.selected_don_vi || item.donVi || item.don_vi || item.don_vi_tinh || "",
          nha_san_xuat: item.nhaSanXuat || item.nha_san_xuat || "",
          mo_ta: item.moTa || item.mo_ta || "",
          gia_ban: item.gia || item.gia_ban || 0,
          gia_niem_yet: item.giaGoc || item.gia_goc || item.gia || item.gia_ban || 0,
          don_vi_options: item.don_vi_options || item.donViOptions || [],
        },
        item.selectedDonVi || item.selected_don_vi || item.donVi || item.don_vi || item.don_vi_tinh || ""
      );
      const selectedUnit = normalizedProduct.selected_don_vi || normalizedProduct.don_vi_tinh || "";

      if (!maThuoc) {
        return null;
      }

      return {
        id: item.id || buildProductUnitCartKey(maThuoc, selectedUnit),
        maThuoc,
        ten: item.ten || item.ten_thuoc || "",
        donVi: selectedUnit,
        selectedDonVi: selectedUnit,
        gia: Number(normalizedProduct.gia_ban || item.gia || item.gia_ban || 0),
        giaGoc: Number(normalizedProduct.gia_niem_yet || item.giaGoc || item.gia_goc || item.gia || item.gia_ban || 0),
        soLuong: Number(item.soLuong || item.so_luong || 1),
        imageTone: item.imageTone || "pink",
        hinhAnhUrl: item.hinhAnhUrl || item.hinh_anh_url || normalizedProduct.hinh_anh_url || "",
        loai: item.loai || item.loai_thuoc || "",
        moTa: item.moTa || item.mo_ta || "",
        tonKho: Number(item.tonKho || item.so_luong_ton || 0),
        selected: item.selected !== false,
        nhaSanXuat: item.nhaSanXuat || item.nha_san_xuat || "PharmaGo Care",
        promoTags: Array.isArray(item.promoTags) ? item.promoTags : [],
        coKhuyenMai: Boolean(item.coKhuyenMai ?? item.co_khuyen_mai),
      };
    })
    .filter(Boolean);
}

function normalizeCartItem(product) {
  const normalizedProduct = applyProductUnitSelection(
    product,
    product.selected_don_vi || product.selectedDonVi || product.donVi || product.don_vi || product.don_vi_tinh || ""
  );
  const selectedUnit = normalizedProduct.selected_don_vi || normalizedProduct.don_vi_tinh || "Hộp";
  const basePrice = Number(normalizedProduct.gia_ban || normalizedProduct.price || product.gia_ban || product.price || 0);
  const originalPrice = Number(
    normalizedProduct.gia_niem_yet || normalizedProduct.originalPrice || product.gia_niem_yet || product.originalPrice || basePrice
  );
  const stockQuantity = Number(normalizedProduct.so_luong_ton || normalizedProduct.tonKho || product.so_luong_ton || product.tonKho || 0);
  const promoTags = product.co_khuyen_mai && product.khuyen_mai
    ? [product.khuyen_mai.nhan_hien_thi || product.khuyen_mai.ten_khuyen_mai].filter(Boolean)
    : [];
  const maThuoc = normalizedProduct.ma_thuoc || normalizedProduct.id;

  return {
    id: buildProductUnitCartKey(maThuoc, selectedUnit),
    maThuoc,
    ten: normalizedProduct.ten_thuoc || normalizedProduct.ten,
      loai: normalizedProduct.loai_thuoc || product.loai_thuoc || "Thuốc",
    donVi: selectedUnit,
    selectedDonVi: selectedUnit,
    moTa: normalizedProduct.mo_ta || normalizedProduct.moTa || product.mo_ta || product.moTa || product.description || "",
    gia: basePrice,
    giaGoc: originalPrice,
    tonKho: stockQuantity,
    soLuong: 1,
    selected: stockQuantity > 0,
    imageTone: product.imageTone || "pink",
    hinhAnhUrl: normalizedProduct.hinh_anh_url || product.hinh_anh_url || "",
    nhaSanXuat: normalizedProduct.nha_san_xuat || product.nha_san_xuat || "PharmaGo Care",
    promoTags: product.promoTags || promoTags,
    coKhuyenMai: Boolean(product.co_khuyen_mai),
  };
}

async function syncCartPricesWithCatalog() {
  if (!state.cart.length) {
    return;
  }

  try {
    const response = await getCatalogThuocs();
    const catalogItems = Array.isArray(response?.data) ? response.data : [];
    const byCode = new Map(catalogItems.map((item) => [item.ma_thuoc, item]));
    let changed = false;

    state.cart = state.cart.map((item) => {
      const latest = byCode.get(item.maThuoc || item.id);

      if (!latest) {
        return item;
      }

      changed = true;
      const latestWithUnit = applyProductUnitSelection(latest, item.selectedDonVi || item.donVi);
      const selectedUnit = latestWithUnit.selected_don_vi || latestWithUnit.don_vi_tinh || item.donVi;

      return {
        ...item,
        id: buildProductUnitCartKey(item.maThuoc || item.id, selectedUnit),
        loai: latest.loai_thuoc || item.loai,
        donVi: selectedUnit,
        selectedDonVi: selectedUnit,
        moTa: latest.mo_ta || item.moTa || "",
        hinhAnhUrl: latest.hinh_anh_url || item.hinhAnhUrl || "",
        tonKho: Number(latest.so_luong_ton || item.tonKho || 0),
        selected: Number(latest.so_luong_ton || item.tonKho || 0) > 0 ? item.selected !== false : false,
        gia: Number(latestWithUnit.gia_ban || item.gia || 0),
        giaGoc: Number(latestWithUnit.gia_niem_yet || latestWithUnit.gia_ban || item.giaGoc || item.gia || 0),
        nhaSanXuat: latest.nha_san_xuat || item.nhaSanXuat,
        promoTags: latest.co_khuyen_mai && latest.khuyen_mai
          ? [latest.khuyen_mai.nhan_hien_thi || latest.khuyen_mai.ten_khuyen_mai].filter(Boolean)
          : [],
        coKhuyenMai: Boolean(latest.co_khuyen_mai),
      };
    });

    if (changed) {
      persistCart();
    }
  } catch {
    // Giữ giỏ hàng local nếu catalog chưa sẵn sàng.
  }
}

function addToCart(product, quantity = 1) {
  const normalizedItem = normalizeCartItem(product);
  const itemId = normalizedItem.id;
  const existing = state.cart.find((item) => item.id === itemId);
  const incomingStock = Number(normalizedItem.tonKho || existing?.tonKho || 0);
  const nextQuantity = Math.max(1, Number.parseInt(quantity, 10) || 1);

  if (incomingStock <= 0) {
    return false;
  }

  if (existing) {
    const quantityLimit = Number.isFinite(Number(existing.tonKho)) && Number(existing.tonKho) > 0
      ? Number(existing.tonKho)
      : 99;

    existing.soLuong = Math.min(existing.soLuong + nextQuantity, quantityLimit);
  } else {
    state.cart.push({
      ...normalizedItem,
      soLuong: Math.min(nextQuantity, incomingStock),
    });
  }

  persistCart();
  return true;
}

function updateCartQuantity(itemId, nextValue) {
  const item = state.cart.find((entry) => entry.id === itemId);

  if (!item) {
    return;
  }

  if (Number(item.tonKho || 0) <= 0) {
    item.selected = false;
    persistCart();
    return;
  }

  const quantityLimit = Number.isFinite(Number(item.tonKho)) && Number(item.tonKho) > 0
    ? Number(item.tonKho)
    : 99;

  item.soLuong = Math.max(1, Math.min(nextValue, quantityLimit));
  persistCart();
}

function toggleCartSelection(itemId) {
  const item = state.cart.find((entry) => entry.id === itemId);

  if (!item) {
    return;
  }

  if (Number(item.tonKho || 0) <= 0) {
    item.selected = false;
    persistCart();
    return;
  }

  item.selected = !item.selected;
  persistCart();
}

function removeCartItem(itemId) {
  state.cart = state.cart.filter((item) => item.id !== itemId);
  persistCart();
}

function clearCart() {
  state.cart = [];
  persistCart();
}

async function saveAddress(payload) {
  const isEditing = Boolean(payload.id);
  const item = normalizeAddressRecord({
    id: payload.id || Date.now(),
    ...payload,
    macDinh: payload.macDinh ?? state.addresses.length === 0,
  }, state.profile, state.addresses.length);

  if (isAuthenticatedState.value && getAuthType() === "customer") {
    if (isEditing) {
      await updateCustomerAddress(item.id, serializeAddressPayload(item));
    } else {
      await createCustomerAddress(serializeAddressPayload(item));
    }

    await syncAddressesFromApi({ silent: false, migrateLegacy: false });
    return;
  }

  if (item.macDinh) {
    state.addresses = state.addresses.map((address) => ({ ...address, macDinh: false }));
  }

  state.addresses = isEditing
    ? state.addresses.map((address) => (address.id === item.id ? { ...address, ...item } : address))
    : [...state.addresses, item];
  persistAddresses();
}

function setDefaultAddress(addressId) {
  const targetId = String(addressId || "").trim();

  if (!targetId) {
    return false;
  }

  let found = false;
  state.addresses = state.addresses.map((address) => {
    const isTarget = String(address.id) === targetId;
    if (isTarget) {
      found = true;
    }

    return {
      ...address,
      macDinh: isTarget,
    };
  });

  if (!found) {
    return false;
  }

  persistAddresses();
  return true;
}

async function refreshProfileFromApi() {
  try {
    const user = await getProfile();
    const previousProfile = { ...state.profile };

    state.profile = {
      ...state.profile,
      hoTen: user?.ten_khach_hang || user?.ho_ten || state.profile.hoTen,
      soDienThoai: user?.so_dien_thoai || state.profile.soDienThoai,
      email: user?.email || state.profile.email,
      diaChi: user?.dia_chi || state.profile.diaChi || "",
      avatarUrl: normalizeAvatarUrl(user?.avatar_url || ""),
      ngaySinh: getUserBirthDate(user) || state.profile.ngaySinh || "",
      gioiTinh: user?.gioi_tinh || state.profile.gioiTinh || "",
      pxu: normalizePointValue(user?.diem_tich_luy ?? state.profile.pxu),
    };

    state.addresses = syncAddressesWithProfile(state.addresses, previousProfile, state.profile);

    setAuthSession({
      token: authState.token,
      user,
      type: authState.type,
    });

    persistProfile();
    await syncAddressesFromApi({ silent: true });
  } catch {
    // Keep local state when API is unavailable.
  }
}

async function updateProfile(payload) {
  const response = await updateProfileApi(payload);
  const user = response?.user;
  const previousProfile = { ...state.profile };

  state.profile = {
    ...state.profile,
    hoTen: user?.ten_khach_hang || user?.ho_ten || state.profile.hoTen,
    soDienThoai: user?.so_dien_thoai || state.profile.soDienThoai,
    email: user?.email || state.profile.email,
    diaChi: user?.dia_chi || state.profile.diaChi || "",
    avatarUrl: normalizeAvatarUrl(user?.avatar_url || "") || state.profile.avatarUrl || "",
    ngaySinh: getUserBirthDate(user) || state.profile.ngaySinh || "",
    gioiTinh: user?.gioi_tinh || state.profile.gioiTinh || "",
    pxu: normalizePointValue(user?.diem_tich_luy ?? state.profile.pxu),
  };

  state.addresses = syncAddressesWithProfile(state.addresses, previousProfile, state.profile);

  setAuthSession({
    token: authState.token,
    user: user || authState.user,
    type: authState.type,
  });

  persistProfile();
  await syncAddressesFromApi({ silent: true });
  return response;
}

async function syncOrdersFromApi(options = {}) {
  const { silent = true } = options;
  hydrateScopedState();

  if (!isAuthenticatedState.value || getAuthType() !== "customer") {
    return state.orders;
  }

  try {
    const previousOrders = new Map(state.orders.map((order) => [String(order.id), order]));
    const [response, catalogResponse] = await Promise.all([
      getCustomerOrders(),
      getCatalogThuocs(),
    ]);
    const catalogItems = Array.isArray(catalogResponse?.data) ? catalogResponse.data : [];
    const catalogMap = new Map(catalogItems.map((item) => [item.ma_thuoc, item]));
    const normalizedOrders = (Array.isArray(response?.data) ? response.data : [])
      .map((item) =>
        normalizeOrderHistoryItem({
          ...item,
          items: enrichOrderItemsWithCatalog(item?.items, catalogMap),
        })
      )
      .filter((order) => !hiddenOrderIds.includes(String(order.id)));

    normalizedOrders.forEach((order) => {
      addOrderNotification(order);
    });

    const normalizedOrderIds = new Set(normalizedOrders.map((order) => String(order.id)));
    previousOrders.forEach((order, orderId) => {
      if (!normalizedOrderIds.has(orderId) && isPayosOrder(order)) {
        removeOrderNotification(order.id);
      }
    });

    state.orders = normalizedOrders;
    persistOrders();

    return state.orders;
  } catch (error) {
    if (!silent) {
      throw error;
    }

    return state.orders;
  }
}

async function syncSharedNotificationsFromApi(options = {}) {
  const { silent = true } = options;
  hydrateScopedState();

  if (!isAuthenticatedState.value || getAuthType() !== "customer") {
    sharedNotificationsSource = [];
    syncNotificationsState();
    return sharedNotificationsSource;
  }

  try {
    const response = await getCustomerBroadcastNotifications();
    sharedNotificationsSource = sanitizeSharedNotifications(Array.isArray(response?.data) ? response.data : []);
    syncNotificationsState();
    return sharedNotificationsSource;
  } catch (error) {
    if (!silent) {
      throw error;
    }

    return sharedNotificationsSource;
  }
}

function applyPromotionCode(promotion) {
  if (!isAuthenticatedState.value) {
    clearGuestPromotionState();
    return;
  }

  state.appliedPromotion = promotion
    ? {
        id: promotion.id,
        maGiamGia: promotion.ma_giam_gia,
        tenMa: promotion.ten_ma,
        loaiApDung: promotion.loai_ap_dung,
        giaTri: Number(promotion.gia_tri || 0),
        giaTriDonToiThieu: Number(promotion.gia_tri_don_toi_thieu || 0),
        gioiHanMoiKhach: promotion.gioi_han_moi_khach == null ? null : Number(promotion.gioi_han_moi_khach),
        giamGiaDonHang: Number(promotion.giam_gia_don_hang || 0),
        tongSauGiam: Number(promotion.tong_sau_giam || 0),
        loaiMa: promotion.loai_ma || "general",
        tuDongApDung: Boolean(promotion.tu_dong_ap_dung),
      }
    : null;

  persistAppliedPromotion();
}

function clearAppliedPromotion() {
  state.appliedPromotion = null;
  persistAppliedPromotion();
}

function syncRewardPointsFromOrder(orderPayload) {
  if (!orderPayload || orderPayload.diem_hien_tai == null) {
    return;
  }

  const nextPoints = normalizePointValue(orderPayload.diem_hien_tai);
  state.profile = {
    ...state.profile,
    pxu: nextPoints,
  };

  if (authState.user) {
    setAuthSession({
      token: authState.token,
      user: {
        ...authState.user,
        diem_tich_luy: nextPoints,
      },
      type: authState.type,
      expiresAt: authState.sessionExpiresAt,
    });
  }

  persistProfile();
}

function placeOrder(orderPayload) {
  if (!orderPayload) {
    return null;
  }

  const fallbackItems = selectedItems.value.map((item) => ({
    id: item.id,
    maThuoc: item.maThuoc || "",
    ten: item.ten,
    donVi: item.donVi || "",
    gia: Number(item.gia || 0),
    giaGoc: Number(item.giaGoc || item.gia || 0),
    thanhTien: Number(item.gia || 0) * Number(item.soLuong || 1),
    soLuong: Number(item.soLuong || 1),
    imageTone: item.imageTone || "pink",
    hinhAnhUrl: item.hinhAnhUrl || item.hinh_anh_url || "",
    loai: item.loai || "",
    moTa: item.moTa || "",
  }));

  const historyItem = normalizeOrderHistoryItem({
    ...orderPayload,
    items: Array.isArray(orderPayload.items) && orderPayload.items.length ? orderPayload.items : fallbackItems,
  });

  hiddenOrderIds = hiddenOrderIds.filter((id) => id !== String(historyItem.id));
  state.orders = [historyItem, ...state.orders.filter((order) => order.id !== historyItem.id)];
  state.cart = state.cart.filter((item) => !item.selected);
  clearAppliedPromotion();
  state.useRewardPoints = false;
  syncRewardPointsFromOrder(orderPayload);
  persistHiddenOrders();
  persistOrders();
  persistCart();
  addOrderNotification(historyItem);

  return historyItem.id;
}

async function reorderOrder(order) {
  const orderItems = Array.isArray(order?.items) ? order.items : [];

  if (!orderItems.length) {
    return { added: 0, skipped: [] };
  }

  let catalogItems = [];
  try {
    const response = await getCatalogThuocs();
    catalogItems = Array.isArray(response?.data) ? response.data : [];
  } catch {
    return {
      added: 0,
      skipped: orderItems.map((item) => item.ten || item.maThuoc || "Sản phẩm"),
    };
  }

  const catalogMap = new Map(catalogItems.map((item) => [item.ma_thuoc, item]));
  const skipped = [];
  let added = 0;

  orderItems.forEach((item) => {
    const maThuoc = item.maThuoc || item.ma_thuoc || "";
    const selectedUnit = item.donVi || item.don_vi || "";
    const latestProduct = catalogMap.get(maThuoc);

    if (!latestProduct) {
      skipped.push(item.ten || maThuoc || "Sản phẩm");
      return;
    }

    const latestWithUnit = applyProductUnitSelection(latestProduct, selectedUnit);
    const quantityToAdd = Math.max(1, Number(item.soLuong || item.so_luong || 1));
    const normalizedItem = normalizeCartItem({
      ...latestWithUnit,
      selected_don_vi: selectedUnit || latestWithUnit.selected_don_vi || latestWithUnit.don_vi_tinh || "",
    });
    const itemId = normalizedItem.id;
    const existing = state.cart.find((entry) => entry.id === itemId);
    const stockLimit = Number(normalizedItem.tonKho || existing?.tonKho || 0);

    if (stockLimit <= 0) {
      skipped.push(item.ten || latestProduct.ten_thuoc || maThuoc || "Sản phẩm");
      return;
    }

    if (existing) {
      existing.soLuong = Math.min(existing.soLuong + quantityToAdd, stockLimit);
      existing.tonKho = stockLimit;
      existing.gia = normalizedItem.gia;
      existing.giaGoc = normalizedItem.giaGoc;
      existing.hinhAnhUrl = normalizedItem.hinhAnhUrl;
    } else {
      state.cart.push({
        ...normalizedItem,
        soLuong: Math.min(quantityToAdd, stockLimit),
      });
    }

    added += 1;
  });

  persistCart();
  return { added, skipped };
}

function removeOrder(orderId) {
  const normalizedId = String(orderId || "").trim();
  if (!normalizedId) {
    return;
  }

  if (!hiddenOrderIds.includes(normalizedId)) {
    hiddenOrderIds = [...hiddenOrderIds, normalizedId];
    persistHiddenOrders();
  }

  state.orders = state.orders.filter((order) => order.id !== orderId);
  persistOrders();
}

async function markNotificationRead(notificationId) {
  const targetId = String(notificationId || "").trim();
  if (!targetId) {
    return;
  }

  const ownerScope = getStorageScopeId();
  let changed = false;

  accountNotificationsSource = accountNotificationsSource.map((notification) => {
    if (notification.ownerScope !== ownerScope || String(notification.id) !== targetId || notification.daDoc) {
      return notification;
    }

    changed = true;
    return { ...notification, daDoc: true };
  });

  if (changed) {
    persistAccountNotifications();
    syncNotificationsState();
    return;
  }

  const targetNotification = sharedNotificationsSource.find((notification) => String(notification.id) === targetId);
  if (!targetNotification || targetNotification.daDoc) {
    return;
  }

  sharedNotificationsSource = sharedNotificationsSource.map((notification) => {
    if (String(notification.id) !== targetId) {
      return notification;
    }

    changed = true;
    return { ...notification, daDoc: true };
  });

  if (changed) {
    syncNotificationsState();

    if (isAuthenticatedState.value && getAuthType() === "customer" && targetNotification.remoteId) {
      try {
        await markCustomerNotificationRead(targetNotification.remoteId);
      } catch {
        // Giữ trạng thái local, lần đồng bộ sau sẽ lấy lại dữ liệu chuẩn từ server.
      }
    }
  }
}

async function markNotificationsReadByGroup(group) {
  if (!group) {
    return;
  }

  const ownerScope = getStorageScopeId();
  let changed = false;

  accountNotificationsSource = accountNotificationsSource.map((notification) => {
    if (notification.ownerScope !== ownerScope || notification.group !== group || notification.daDoc) {
      return notification;
    }

    changed = true;
    return { ...notification, daDoc: true };
  });

  if (changed) {
    persistAccountNotifications();
  }

  let sharedChanged = false;
  sharedNotificationsSource = sharedNotificationsSource.map((notification) => {
    if (notification.group !== group || notification.daDoc) {
      return notification;
    }

    sharedChanged = true;
    return { ...notification, daDoc: true };
  });

  if (changed || sharedChanged) {
    syncNotificationsState();
  }

  if (sharedChanged && isAuthenticatedState.value && getAuthType() === "customer") {
    try {
      await markAllCustomerNotificationsRead(group);
    } catch {
      // Giữ trạng thái local, lần đồng bộ sau sẽ lấy lại dữ liệu chuẩn từ server.
    }
  }
}

async function markAllNotificationsRead() {
  const ownerScope = getStorageScopeId();
  let changed = false;

  accountNotificationsSource = accountNotificationsSource.map((notification) => {
    if (notification.ownerScope !== ownerScope || notification.daDoc) {
      return notification;
    }

    changed = true;
    return { ...notification, daDoc: true };
  });

  if (changed) {
    persistAccountNotifications();
  }

  let sharedChanged = false;
  sharedNotificationsSource = sharedNotificationsSource.map((notification) => {
    if (notification.daDoc) {
      return notification;
    }

    sharedChanged = true;
    return { ...notification, daDoc: true };
  });

  if (changed || sharedChanged) {
    syncNotificationsState();
  }

  if (sharedChanged && isAuthenticatedState.value && getAuthType() === "customer") {
    try {
      await markAllCustomerNotificationsRead();
    } catch {
      // Giữ trạng thái local, lần đồng bộ sau sẽ lấy lại dữ liệu chuẩn từ server.
    }
  }
}

const selectedItems = computed(() =>
  state.cart.filter((item) => item.selected !== false && Number(item.tonKho || 0) > 0)
);
const cartCount = computed(() => state.cart.reduce((total, item) => total + item.soLuong, 0));
const selectedCount = computed(() => selectedItems.value.reduce((total, item) => total + item.soLuong, 0));
const subtotal = computed(() => selectedItems.value.reduce((sum, item) => sum + item.gia * item.soLuong, 0));
const productDiscount = computed(() =>
  selectedItems.value.reduce((sum, item) => sum + Math.max(item.giaGoc - item.gia, 0) * item.soLuong, 0)
);
const payableSubtotal = computed(() => Math.max(subtotal.value, 0));
const orderPromotionDiscount = computed(() => {
  if (!isAuthenticatedState.value || !state.appliedPromotion || payableSubtotal.value <= 0) {
    return 0;
  }

  const minimumOrderValue = Number(state.appliedPromotion.giaTriDonToiThieu || 0);
  if (payableSubtotal.value < minimumOrderValue) {
    return 0;
  }

  const discountType = String(state.appliedPromotion.loaiApDung || "").toLowerCase();
  const discountValue = Number(state.appliedPromotion.giaTri || 0);

  if (["phan_tram", "phantram", "giam_phan_tram", "percent", "percentage"].includes(discountType)) {
    return Math.min(Math.round((payableSubtotal.value * discountValue) / 100), payableSubtotal.value);
  }

  return Math.min(discountValue, payableSubtotal.value);
});
const subtotalAfterPromotion = computed(() => Math.max(payableSubtotal.value - orderPromotionDiscount.value, 0));
const rewardPointBalance = computed(() => normalizePointValue(state.profile.pxu));
const rewardDiscountBlocks = computed(() => {
  if (!state.useRewardPoints || subtotalAfterPromotion.value <= 0) {
    return 0;
  }

  return Math.min(
    Math.floor(rewardPointBalance.value / 1000),
    Math.floor(subtotalAfterPromotion.value / 10000)
  );
});
const rewardPointsToUse = computed(() => rewardDiscountBlocks.value * 1000);
const rewardPointDiscount = computed(() => rewardDiscountBlocks.value * 10000);
const canUseRewardPoints = computed(() =>
  rewardPointBalance.value >= 1000 && subtotalAfterPromotion.value >= 10000
);
const discountedSubtotal = computed(() => Math.max(subtotalAfterPromotion.value - rewardPointDiscount.value, 0));
const vatAmount = computed(() => Math.round(discountedSubtotal.value * 0.1));
const orderTotal = computed(() => Math.max(discountedSubtotal.value + vatAmount.value, 0));
const estimatedRewardPointsEarned = computed(() => Math.floor(payableSubtotal.value / 1000));
const defaultAddress = computed(() => state.addresses.find((item) => item.macDinh) || state.addresses[0] || null);
const giftItems = computed(() => {
  if (!selectedItems.value.length) {
    return [];
  }

  const firstItem = selectedItems.value[0];

  return [
    {
      id: `${firstItem.id}-gift-1`,
      ten: `Tặng kèm mini từ ${firstItem.ten}`,
      loai: "Quà tặng",
      soLuong: 1,
      gia: 0,
    },
    {
      id: `${firstItem.id}-gift-2`,
      ten: "Voucher giao hàng miễn phí",
      loai: "Mã ưu đãi",
      soLuong: 1,
      gia: 0,
    },
  ];
});

watch(
  [
    () => authState.token,
    () => authState.type,
    () => authState.user?.id,
    () => authState.user?.id_khach_hang,
    () => authState.user?.id_nhan_vien,
    () => authState.user?.so_dien_thoai,
    () => authState.user?.email,
    () => authState.user?.diem_tich_luy,
  ],
  () => {
    syncProfileFromAuth();
  }
);

export function useCustomerStore() {
  syncProfileFromAuth();

  return {
    state,
    cartCount,
    selectedCount,
    selectedItems,
    subtotal,
    productDiscount,
    payableSubtotal,
    subtotalAfterPromotion,
    discountedSubtotal,
    vatAmount,
    orderPromotionDiscount,
    rewardPointBalance,
    canUseRewardPoints,
    rewardPointsToUse,
    rewardPointDiscount,
    estimatedRewardPointsEarned,
    orderTotal,
    canUsePromotionCode: isAuthenticatedState,
    defaultAddress,
    giftItems,
    syncCartPricesWithCatalog,
    addToCart,
    updateCartQuantity,
    toggleCartSelection,
    removeCartItem,
    clearCart,
    saveAddress,
    setDefaultAddress,
    updateProfile,
    refreshProfileFromApi,
    syncAddressesFromApi,
    applyPromotionCode,
    clearAppliedPromotion,
    placeOrder,
    reorderOrder,
    removeOrder,
    addOrderNotification,
    removeOrderNotification,
    removePayosOrderNotificationByOrderCode,
    markNotificationRead,
    markNotificationsReadByGroup,
    markAllNotificationsRead,
    syncProfileFromAuth,
    syncOrdersFromApi,
    syncSharedNotificationsFromApi,
  };
}


