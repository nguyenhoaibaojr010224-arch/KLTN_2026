import { computed, reactive } from "vue";
import { getAuthType, getStoredUser } from "./authStorage";

const CART_KEY = "pharmacity_customer_cart";
const PROFILE_KEY = "pharmacity_customer_profile";
const ADDRESS_KEY = "pharmacity_customer_addresses";
const ORDER_KEY = "pharmacity_customer_orders";

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

function buildProfile() {
  const authType = getAuthType();
  const user = getStoredUser();

  return {
    hoTen: user?.ten_khach_hang || user?.ho_ten || "Khách Hàng",
    soDienThoai: user?.so_dien_thoai || "**** *** 128",
    email: user?.email || "",
    ngaySinh: "",
    gioiTinh: "",
    rankName: authType === "customer" ? "Hạng Vàng" : "Thành viên",
    pxu: 1993,
  };
}

const defaultAddresses = [
  {
    id: 1,
    hoTen: "Khách Hàng",
    soDienThoai: "0909 111 128",
    tinhThanh: "TP. Hồ Chí Minh",
    quanHuyen: "Quận 3",
    phuongXa: "Phường Võ Thị Sáu",
    soNha: "12A Nguyễn Thị Minh Khai",
    loaiDiaChi: "Nhà riêng",
    macDinh: true,
  },
];

const defaultOrders = [
  {
    id: "DH-20260322-01",
    ngay: "22/03/2026",
    trangThai: "Đang giao",
    tongTien: 277200,
    sanPham: 2,
  },
];

const state = reactive({
  profile: readJson(PROFILE_KEY, buildProfile()),
  addresses: readJson(ADDRESS_KEY, defaultAddresses),
  cart: readJson(CART_KEY, []),
  orders: readJson(ORDER_KEY, defaultOrders),
  note: "",
  paymentMethod: "cod",
  hideProductInfo: false,
  vouchers: [
    {
      id: "pharmacity-01",
      ten: "Khuyến mãi",
      moTa: "Ưu đãi đang áp dụng trên sản phẩm đã chọn.",
    },
  ],
  notifications: [
    { id: 1, group: "Đơn hàng", tieuDe: "Đơn hàng DH-20260322-01 đang được chuẩn bị", daDoc: false },
    { id: 2, group: "Ưu đãi", tieuDe: "Mua 1 tặng 1 cho một số sản phẩm chăm sóc cá nhân", daDoc: true },
  ],
});

function syncProfileFromAuth() {
  const nextProfile = buildProfile();
  state.profile = {
    ...state.profile,
    hoTen: nextProfile.hoTen,
    soDienThoai: nextProfile.soDienThoai,
    email: state.profile.email || nextProfile.email,
    rankName: nextProfile.rankName,
    pxu: state.profile.pxu || nextProfile.pxu,
  };
  writeJson(PROFILE_KEY, state.profile);
}

function persistCart() {
  writeJson(CART_KEY, state.cart);
}

function persistAddresses() {
  writeJson(ADDRESS_KEY, state.addresses);
}

function persistProfile() {
  writeJson(PROFILE_KEY, state.profile);
}

function persistOrders() {
  writeJson(ORDER_KEY, state.orders);
}

function normalizeCartItem(product) {
  const basePrice = Number(product.gia_ban || product.price || 0);
  const originalPrice = Number(product.gia_niem_yet || product.originalPrice || basePrice);
  const promoTags = product.co_khuyen_mai && product.khuyen_mai
    ? [product.khuyen_mai.nhan_hien_thi || product.khuyen_mai.ten_khuyen_mai].filter(Boolean)
    : [];

  return {
    id: product.ma_thuoc || product.id,
    maThuoc: product.ma_thuoc || product.id,
    ten: product.ten_thuoc || product.ten,
    loai: product.loai_thuoc || "Thuốc",
    donVi: product.don_vi_tinh || "Hộp",
    gia: basePrice,
    giaGoc: originalPrice,
    tonKho: Number(product.so_luong_ton || product.tonKho || 0),
    soLuong: 1,
    selected: true,
    imageTone: product.imageTone || "pink",
    nhaSanXuat: product.nha_san_xuat || "Pharmacity Care",
    promoTags: product.promoTags || promoTags,
    coKhuyenMai: Boolean(product.co_khuyen_mai),
  };
}

function addToCart(product) {
  const itemId = product.ma_thuoc || product.id;
  const existing = state.cart.find((item) => item.id === itemId);

  if (existing) {
    existing.soLuong = Math.min(existing.soLuong + 1, Math.max(existing.tonKho, 1));
  } else {
    state.cart.push(normalizeCartItem(product));
  }

  persistCart();
}

function updateCartQuantity(itemId, nextValue) {
  const item = state.cart.find((entry) => entry.id === itemId);

  if (!item) {
    return;
  }

  item.soLuong = Math.max(1, Math.min(nextValue, Math.max(item.tonKho, 1)));
  persistCart();
}

function toggleCartSelection(itemId) {
  const item = state.cart.find((entry) => entry.id === itemId);

  if (!item) {
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
  const item = {
    id: Date.now(),
    ...payload,
    macDinh: payload.macDinh ?? state.addresses.length === 0,
  };

  if (item.macDinh) {
    state.addresses = state.addresses.map((address) => ({ ...address, macDinh: false }));
  }

  state.addresses = [...state.addresses, item];
  persistAddresses();
}

function updateProfile(payload) {
  state.profile = { ...state.profile, ...payload };
  persistProfile();
}

function placeOrder() {
  const orderId = `DH-${Date.now()}`;
  state.orders = [
    {
      id: orderId,
      ngay: new Date().toLocaleDateString("vi-VN"),
      trangThai: "Chờ xác nhận",
      tongTien: orderTotal.value,
      sanPham: selectedItems.value.length,
    },
    ...state.orders,
  ];

  state.cart = state.cart.filter((item) => !item.selected);
  persistOrders();
  persistCart();

  return orderId;
}

const selectedItems = computed(() => state.cart.filter((item) => item.selected !== false));
const cartCount = computed(() => state.cart.reduce((total, item) => total + item.soLuong, 0));
const selectedCount = computed(() => selectedItems.value.reduce((total, item) => total + item.soLuong, 0));
const subtotal = computed(() => selectedItems.value.reduce((sum, item) => sum + item.gia * item.soLuong, 0));
const productDiscount = computed(() =>
  selectedItems.value.reduce((sum, item) => sum + Math.max(item.giaGoc - item.gia, 0) * item.soLuong, 0)
);
const orderTotal = computed(() => Math.max(subtotal.value - productDiscount.value, 0));
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

export function useCustomerStore() {
  syncProfileFromAuth();

  return {
    state,
    cartCount,
    selectedCount,
    selectedItems,
    subtotal,
    productDiscount,
    orderTotal,
    defaultAddress,
    giftItems,
    addToCart,
    updateCartQuantity,
    toggleCartSelection,
    removeCartItem,
    clearCart,
    saveAddress,
    updateProfile,
    placeOrder,
    syncProfileFromAuth,
  };
}
