import { computed, reactive } from "vue";
import { authState, getAuthType, getStoredUser, isAuthenticatedState, setAuthSession } from "./authStorage";
import { watch } from "vue";
import { getProfile, updateProfileApi } from "../api/profileApi";
import { getCatalogThuocs } from "../api/catalogApi";
import { getCustomerOrders } from "../api/orderApi";

const CART_KEY = "pharmacity_customer_cart";
const PROFILE_KEY = "pharmacity_customer_profile";
const ADDRESS_KEY = "pharmacity_customer_addresses";
const ORDER_KEY = "pharmacity_customer_orders_v2";
const HIDDEN_ORDER_KEY = "pharmacity_customer_hidden_orders_v1";
const PROMOTION_KEY = "pharmacity_customer_applied_promotion";
const NOTIFICATION_KEY = "pharmacity_customer_notifications";
const SHARED_NOTIFICATION_KEY = "pharmacity_customer_shared_notifications_v2";

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

function getStorageScopeId() {
  const type = getAuthType();
  const user = getStoredUser();

  if (!authState.token || !user) {
    return "guest";
  }

  if (type === "customer") {
    return `customer:${user.id_khach_hang || user.so_dien_thoai || user.email || "unknown"}`;
  }

  return `system:${type || "unknown"}:${user.id_nhan_vien || user.ten_dang_nhap || user.email || "unknown"}`;
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

function buildProfile() {
  const authType = getAuthType();
  const user = getStoredUser();

  return {
    hoTen: user?.ten_khach_hang || user?.ho_ten || "Khách hàng",
    soDienThoai: user?.so_dien_thoai || "**** *** 128",
    email: user?.email || "",
    diaChi: user?.dia_chi || "",
    avatarUrl: normalizeAvatarUrl(user?.avatar_url || ""),
    ngaySinh: user?.ngay_sinh || "",
    gioiTinh: user?.gioi_tinh || "",
    rankName: authType === "customer" ? "Hạng Vàng" : "Thành viên",
    pxu: 1993,
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

  return addresses;
}

const defaultOrders = [];

const defaultSharedNotifications = [
  {
    id: "shared-promo-20260404",
    scope: "shared",
    group: "Ưu đãi",
    tieuDe: "Mua 1 tặng 1 cho một số sản phẩm chăm sóc cá nhân",
    daDocScopes: [],
    createdAt: "2026-04-04T09:00:00+07:00",
  },
];

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
  return {
    id: String(item?.id ?? `${scope}-${Date.now()}`),
    scope,
    group: item?.group || "Hệ thống",
    tieuDe: item?.tieuDe || "Thông báo mới",
    daDoc: Boolean(item?.daDoc),
    daDocScopes: Array.isArray(item?.daDocScopes) ? item.daDocScopes.filter(Boolean).map(String) : [],
    createdAt: item?.createdAt || new Date().toISOString(),
  };
}

function getNotificationReadScope() {
  return getStorageScopeId();
}

function sanitizeAccountNotifications(list) {
  return Array.isArray(list)
    ? list
        .filter((item) => !isLegacySeedNotification(item))
        .map((item) => normalizeNotificationItem(item, "account"))
    : [];
}

function sanitizeSharedNotifications(list) {
  if (list == null) {
    return defaultSharedNotifications.map((item) => normalizeNotificationItem(item, "shared"));
  }

  return Array.isArray(list) ? list.map((item) => normalizeNotificationItem(item, "shared")) : [];
}

function buildMergedNotifications() {
  const readScope = getNotificationReadScope();
  const accountNotifications = accountNotificationsSource.map((item) => ({
    ...item,
    scope: "account",
    daDoc: Boolean(item.daDoc),
  }));
  const sharedNotifications = sharedNotificationsSource.map((item) => ({
    ...item,
    scope: "shared",
    daDoc: item.daDocScopes.includes(readScope),
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
  writeJson(SHARED_NOTIFICATION_KEY, sharedNotificationsSource);
}

function addAccountNotification(payload) {
  const notification = normalizeNotificationItem(
    {
      id: payload?.id || `account-${Date.now()}`,
      group: payload?.group || "Hệ thống",
      tieuDe: payload?.tieuDe || "Thông báo mới",
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

function addOrderNotification(order) {
  if (!order?.id) {
    return null;
  }

  return addAccountNotification({
    id: `order-${order.id}`,
    group: "Đơn hàng",
    tieuDe: `Đơn hàng ${order.id} đang chờ xác nhận.`,
    createdAt: new Date().toISOString(),
  });
}

function normalizeOrderHistoryItem(orderPayload) {
  const orderDate = orderPayload?.ngay_ban || orderPayload?.ngay;
  const purchasedItems = Array.isArray(orderPayload?.items)
    ? orderPayload.items.map((item) => ({
        id: item.id || item.maThuoc || item.ma_thuoc,
        maThuoc: item.maThuoc || item.ma_thuoc || "",
        ten: item.ten || item.ten_thuoc || "",
        donVi: item.donVi || item.don_vi || item.don_vi_tinh || "",
        gia: Number(item.gia || item.gia_ban || 0),
        giaGoc: Number(item.giaGoc || item.gia_goc || item.gia || item.gia_ban || 0),
        soLuong: Number(item.soLuong || item.so_luong || 1),
        imageTone: item.imageTone || "pink",
        loai: item.loai || item.loai_thuoc || "",
        moTa: item.moTa || item.mo_ta || "",
      }))
    : [];

  return {
    id: String(orderPayload?.ma_hoa_don || orderPayload?.id || orderPayload?.id_hoa_don || `DH-${Date.now()}`),
    ngay: orderDate
      ? new Intl.DateTimeFormat("vi-VN").format(new Date(orderDate))
      : new Date().toLocaleDateString("vi-VN"),
    ngayBan: orderPayload?.ngay_ban || null,
    trangThai: orderPayload?.trang_thai || "Chờ xác nhận",
    tongTien: Number(orderPayload?.tien_thanh_toan || orderPayload?.tongTien || 0),
    sanPham:
      Number(orderPayload?.tong_so_san_pham || 0) ||
      purchasedItems.reduce((total, item) => total + Number(item.soLuong || 0), 0),
    items: purchasedItems,
    statusUpdatedAt: orderPayload?.thoi_gian_cap_nhat_trang_thai || null,
  };
}

function persistHiddenOrders() {
  writeScopedJson(HIDDEN_ORDER_KEY, hiddenOrderIds);
}

accountNotificationsSource = sanitizeAccountNotifications(readScopedJson(NOTIFICATION_KEY, []));
sharedNotificationsSource = sanitizeSharedNotifications(readJson(SHARED_NOTIFICATION_KEY, null));
persistAccountNotifications();
persistSharedNotifications();

const state = reactive({
  profile: readScopedJson(PROFILE_KEY, buildProfile()),
  addresses: normalizeAddresses(readScopedJson(ADDRESS_KEY, []), readScopedJson(PROFILE_KEY, buildProfile())),
  cart: readScopedJson(CART_KEY, []),
  orders: readScopedJson(ORDER_KEY, defaultOrders).filter((order) => !hiddenOrderIds.includes(String(order?.id || ""))),
  appliedPromotion: readScopedJson(PROMOTION_KEY, null),
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
  writeScopedJson(ADDRESS_KEY, state.addresses);
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
  state.cart = readScopedJson(CART_KEY, []);
  hiddenOrderIds = readScopedJson(HIDDEN_ORDER_KEY, []).map((item) => String(item));
  state.orders = readScopedJson(ORDER_KEY, defaultOrders).filter((order) => !hiddenOrderIds.includes(String(order?.id || "")));
  state.appliedPromotion = readScopedJson(PROMOTION_KEY, null);
  accountNotificationsSource = sanitizeAccountNotifications(readScopedJson(NOTIFICATION_KEY, []));
  sharedNotificationsSource = sanitizeSharedNotifications(readJson(SHARED_NOTIFICATION_KEY, null));
  persistAccountNotifications();
  persistSharedNotifications();
  syncNotificationsState();
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
    pxu: state.profile.pxu || nextProfile.pxu,
  };

  state.addresses = normalizeAddresses(state.addresses, state.profile);

  clearGuestPromotionState();
  persistProfile();
  persistAddresses();
}

function normalizeCartItem(product) {
  const basePrice = Number(product.gia_ban || product.price || 0);
  const originalPrice = Number(product.gia_niem_yet || product.originalPrice || basePrice);
  const stockQuantity = Number(product.so_luong_ton || product.tonKho || 0);
  const promoTags = product.co_khuyen_mai && product.khuyen_mai
    ? [product.khuyen_mai.nhan_hien_thi || product.khuyen_mai.ten_khuyen_mai].filter(Boolean)
    : [];

  return {
    id: product.ma_thuoc || product.id,
    maThuoc: product.ma_thuoc || product.id,
    ten: product.ten_thuoc || product.ten,
    loai: product.loai_thuoc || "Thuá»‘c",
    donVi: product.don_vi_tinh || "Hộp",
    moTa: product.mo_ta || product.moTa || product.description || "",
    gia: basePrice,
    giaGoc: originalPrice,
    tonKho: stockQuantity,
    soLuong: 1,
    selected: stockQuantity > 0,
    imageTone: product.imageTone || "pink",
    nhaSanXuat: product.nha_san_xuat || "PharmaGo Care",
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

      return {
        ...item,
        loai: latest.loai_thuoc || item.loai,
        donVi: latest.don_vi_tinh || item.donVi,
        moTa: latest.mo_ta || item.moTa || "",
        tonKho: Number(latest.so_luong_ton || item.tonKho || 0),
        selected: Number(latest.so_luong_ton || item.tonKho || 0) > 0 ? item.selected !== false : false,
        gia: Number(latest.gia_ban || item.gia || 0),
        giaGoc: Number(latest.gia_niem_yet || latest.gia_ban || item.giaGoc || item.gia || 0),
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

function addToCart(product) {
  const itemId = product.ma_thuoc || product.id;
  const existing = state.cart.find((item) => item.id === itemId);
  const incomingStock = Number(product.so_luong_ton || product.tonKho || existing?.tonKho || 0);

  if (incomingStock <= 0) {
    return false;
  }

  if (existing) {
    const quantityLimit = Number.isFinite(Number(existing.tonKho)) && Number(existing.tonKho) > 0
      ? Number(existing.tonKho)
      : 99;

    existing.soLuong = Math.min(existing.soLuong + 1, quantityLimit);
  } else {
    state.cart.push(normalizeCartItem(product));
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

function saveAddress(payload) {
  const isEditing = Boolean(payload.id);
  const item = {
    id: payload.id || Date.now(),
    ...payload,
    macDinh: payload.macDinh ?? state.addresses.length === 0,
  };

  if (item.macDinh) {
    state.addresses = state.addresses.map((address) => ({ ...address, macDinh: false }));
  }

  state.addresses = isEditing
    ? state.addresses.map((address) => (address.id === item.id ? item : address))
    : [...state.addresses, item];
  persistAddresses();
}

async function refreshProfileFromApi() {
  try {
    const user = await getProfile();

    state.profile = {
      ...state.profile,
      hoTen: user?.ten_khach_hang || user?.ho_ten || state.profile.hoTen,
      soDienThoai: user?.so_dien_thoai || state.profile.soDienThoai,
      email: user?.email || state.profile.email,
      diaChi: user?.dia_chi || state.profile.diaChi || "",
      avatarUrl: normalizeAvatarUrl(user?.avatar_url || ""),
      ngaySinh: user?.ngay_sinh || state.profile.ngaySinh || "",
      gioiTinh: user?.gioi_tinh || state.profile.gioiTinh || "",
    };

    setAuthSession({
      token: authState.token,
      user,
      type: authState.type,
    });

    persistProfile();
  } catch {
    // Keep local state when API is unavailable.
  }
}

async function updateProfile(payload) {
  const response = await updateProfileApi(payload);
  const user = response?.user;

  state.profile = {
    ...state.profile,
    hoTen: user?.ten_khach_hang || user?.ho_ten || state.profile.hoTen,
    soDienThoai: user?.so_dien_thoai || state.profile.soDienThoai,
    email: user?.email || state.profile.email,
    diaChi: user?.dia_chi || state.profile.diaChi || "",
    avatarUrl: normalizeAvatarUrl(user?.avatar_url || "") || state.profile.avatarUrl || "",
    ngaySinh: user?.ngay_sinh || state.profile.ngaySinh || "",
    gioiTinh: user?.gioi_tinh || state.profile.gioiTinh || "",
  };

  setAuthSession({
    token: authState.token,
    user: user || authState.user,
    type: authState.type,
  });

  persistProfile();
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
    const response = await getCustomerOrders();
    const normalizedOrders = (Array.isArray(response?.data) ? response.data : [])
      .map((item) => normalizeOrderHistoryItem(item))
      .filter((order) => !hiddenOrderIds.includes(String(order.id)));

    normalizedOrders.forEach((order) => {
      const previousOrder = previousOrders.get(String(order.id));
      const hasCreatedNotification = accountNotificationsSource.some(
        (item) => String(item.id) === `order-${order.id}`
      );
      const hasConfirmedNotification = accountNotificationsSource.some(
        (item) => String(item.id) === `order-confirmed-${order.id}`
      );

      if (
        order.trangThai === "Thành công" &&
        !hasConfirmedNotification &&
        (previousOrder?.trangThai === "Chờ xác nhận" || hasCreatedNotification)
      ) {
        addAccountNotification({
          id: `order-confirmed-${order.id}`,
          group: "Đơn hàng",
          tieuDe: `Đơn hàng ${order.id} đã được xác nhận.`,
          createdAt: order.statusUpdatedAt || new Date().toISOString(),
        });
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
      }
    : null;

  persistAppliedPromotion();
}

function clearAppliedPromotion() {
  state.appliedPromotion = null;
  persistAppliedPromotion();
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
    soLuong: Number(item.soLuong || 1),
    imageTone: item.imageTone || "pink",
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
  persistHiddenOrders();
  persistOrders();
  persistCart();
  addOrderNotification(historyItem);

  return historyItem.id;
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

function markNotificationRead(notificationId) {
  const targetId = String(notificationId || "").trim();
  if (!targetId) {
    return;
  }

  let changed = false;

  accountNotificationsSource = accountNotificationsSource.map((notification) => {
    if (String(notification.id) !== targetId || notification.daDoc) {
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

  const readScope = getNotificationReadScope();
  sharedNotificationsSource = sharedNotificationsSource.map((notification) => {
    if (String(notification.id) !== targetId || notification.daDocScopes.includes(readScope)) {
      return notification;
    }

    changed = true;
    return {
      ...notification,
      daDocScopes: [...notification.daDocScopes, readScope],
    };
  });

  if (changed) {
    persistSharedNotifications();
    syncNotificationsState();
  }
}

function markNotificationsReadByGroup(group) {
  if (!group) {
    return;
  }

  let changed = false;

  accountNotificationsSource = accountNotificationsSource.map((notification) => {
    if (notification.group !== group || notification.daDoc) {
      return notification;
    }

    changed = true;
    return { ...notification, daDoc: true };
  });

  if (changed) {
    persistAccountNotifications();
  }

  const readScope = getNotificationReadScope();
  sharedNotificationsSource = sharedNotificationsSource.map((notification) => {
    if (notification.group !== group || notification.daDocScopes.includes(readScope)) {
      return notification;
    }

    changed = true;
    return {
      ...notification,
      daDocScopes: [...notification.daDocScopes, readScope],
    };
  });

  if (changed) {
    persistSharedNotifications();
    syncNotificationsState();
  }
}

function markAllNotificationsRead() {
  let changed = false;

  accountNotificationsSource = accountNotificationsSource.map((notification) => {
    if (notification.daDoc) {
      return notification;
    }

    changed = true;
    return { ...notification, daDoc: true };
  });

  if (changed) {
    persistAccountNotifications();
  }

  const readScope = getNotificationReadScope();
  sharedNotificationsSource = sharedNotificationsSource.map((notification) => {
    if (notification.daDocScopes.includes(readScope)) {
      return notification;
    }

    changed = true;
    return {
      ...notification,
      daDocScopes: [...notification.daDocScopes, readScope],
    };
  });

  if (changed) {
    persistSharedNotifications();
    syncNotificationsState();
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
const orderTotal = computed(() => Math.max(payableSubtotal.value - orderPromotionDiscount.value, 0));
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
    () => authState.user?.id_khach_hang,
    () => authState.user?.id_nhan_vien,
    () => authState.user?.so_dien_thoai,
    () => authState.user?.email,
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
    orderPromotionDiscount,
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
    updateProfile,
    refreshProfileFromApi,
    applyPromotionCode,
    clearAppliedPromotion,
    placeOrder,
    removeOrder,
    addOrderNotification,
    markNotificationRead,
    markNotificationsReadByGroup,
    markAllNotificationsRead,
    syncProfileFromAuth,
    syncOrdersFromApi,
  };
}


