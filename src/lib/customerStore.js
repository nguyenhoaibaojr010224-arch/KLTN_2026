import { computed, reactive } from "vue";
import { authState, getAuthType, getStoredUser, isAuthenticatedState, setAuthSession } from "./authStorage";
import { watch } from "vue";
import { getProfile, updateProfileApi } from "../api/profileApi";
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

function normalizeAddressValue(value) {
  return String(value || "").trim();
}

function createAddressFingerprint(address) {
  return [
    normalizeAddressValue(address?.hoTen).toLowerCase(),
    normalizeAddressValue(address?.soDienThoai),
    normalizeAddressValue(address?.tinhThanh),
    normalizeAddressValue(address?.quanHuyen),
    normalizeAddressValue(address?.phuongXa),
    normalizeAddressValue(address?.soNha),
    normalizeAddressValue(address?.loaiDiaChi).toLowerCase(),
  ].join("|");
}

function normalizeAddressRecord(address, profileData = buildProfile(), index = 0) {
  const fallbackFingerprint = createAddressFingerprint({
    ...address,
    hoTen: address?.hoTen || profileData?.hoTen || "Khách hàng",
    soDienThoai: address?.soDienThoai || String(profileData?.soDienThoai || "").replaceAll("*", "0"),
    loaiDiaChi: address?.loaiDiaChi || "Nhà riêng",
  });

  return {
    ...address,
    id: address?.id || `addr-${getStorageScopeId()}-${fallbackFingerprint || index}`,
    hoTen: address?.hoTen || profileData?.hoTen || "Khách hàng",
    soDienThoai: address?.soDienThoai || String(profileData?.soDienThoai || "").replaceAll("*", "0"),
    tinhThanh: address?.tinhThanh || "",
    quanHuyen: address?.quanHuyen || "",
    phuongXa: address?.phuongXa || "",
    soNha: address?.soNha || "",
    loaiDiaChi: address?.loaiDiaChi || "Nhà riêng",
    macDinh: Boolean(address?.macDinh),
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
  return {
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
  return Array.isArray(list) ? list.map((item) => normalizeNotificationItem(item, "shared")) : [];
}

function buildMergedNotifications() {
  const accountNotifications = accountNotificationsSource.map((item) => ({
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
    tieuDe: `Đơn hàng ${order.id} đã được đặt thành công.`,
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
  const thueVat = Number(orderPayload?.thue_vat || orderPayload?.thueVat || 0);
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
    ngay: formatOrderDisplayDate(orderDate),
    ngayBan: orderPayload?.ngay_ban || null,
    trangThai: normalizeOrderStatus(orderPayload?.trang_thai || orderPayload?.trangThai || orderPayload?.status),
    thueVat,
    tongTien: Number(orderPayload?.tien_thanh_toan || orderPayload?.tienThanhToan || tongTienGoc - giamGia + thueVat || 0),
    tamTinh: tongTienGoc,
    giamGia,
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
    phuongThucThanhToan: orderPayload?.phuong_thuc_thanh_toan || "",
    phuongThucThanhToanLabel: orderPayload?.phuong_thuc_thanh_toan_label || "",
    maGiaoDich: orderPayload?.ma_giao_dich || "",
    thoiGianThanhToan: orderPayload?.thoi_gian_thanh_toan || null,
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
    loai: normalizedProduct.loai_thuoc || product.loai_thuoc || "Thuá»‘c",
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

function addToCart(product) {
  const normalizedItem = normalizeCartItem(product);
  const itemId = normalizedItem.id;
  const existing = state.cart.find((item) => item.id === itemId);
  const incomingStock = Number(normalizedItem.tonKho || existing?.tonKho || 0);

  if (incomingStock <= 0) {
    return false;
  }

  if (existing) {
    const quantityLimit = Number.isFinite(Number(existing.tonKho)) && Number(existing.tonKho) > 0
      ? Number(existing.tonKho)
      : 99;

    existing.soLuong = Math.min(existing.soLuong + 1, quantityLimit);
  } else {
    state.cart.push(normalizedItem);
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
  const item = normalizeAddressRecord({
    id: payload.id || Date.now(),
    ...payload,
    macDinh: payload.macDinh ?? state.addresses.length === 0,
  }, state.profile, state.addresses.length);

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
      const previousOrder = previousOrders.get(String(order.id));
      const hasCreatedNotification = accountNotificationsSource.some(
        (item) => String(item.id) === `order-${order.id}`
      );

      if (!previousOrder && !hasCreatedNotification) {
        addOrderNotification(order);
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
const discountedSubtotal = computed(() => Math.max(payableSubtotal.value - orderPromotionDiscount.value, 0));
const vatAmount = computed(() => Math.round(discountedSubtotal.value * 0.1));
const orderTotal = computed(() => Math.max(discountedSubtotal.value + vatAmount.value, 0));
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
    discountedSubtotal,
    vatAmount,
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
    setDefaultAddress,
    updateProfile,
    refreshProfileFromApi,
    applyPromotionCode,
    clearAppliedPromotion,
    placeOrder,
    reorderOrder,
    removeOrder,
    addOrderNotification,
    markNotificationRead,
    markNotificationsReadByGroup,
    markAllNotificationsRead,
    syncProfileFromAuth,
    syncOrdersFromApi,
    syncSharedNotificationsFromApi,
  };
}


