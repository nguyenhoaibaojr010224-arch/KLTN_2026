import { createRouter, createWebHistory } from "vue-router";
import {
  getSessionChannel,
  isAdminUser,
  isAuthenticated,
  isSystemUser,
} from "../lib/authStorage";

const routes = [
  {
    path: "/",
    name: "home",
    component: () => import("../components/Client/TrangChu/index.vue"),
    meta: {
      layout: "customer",
      title: "Trang Chủ",
      subtitle: "Không gian bán thuốc và chăm sóc sức khỏe cho khách hàng.",
    },
  },
  {
    path: "/gio-hang",
    name: "gio-hang",
    component: () => import("../components/Client/GioHang/index.vue"),
    meta: {
      layout: "customer",
      title: "Giỏ Hàng",
      subtitle: "Quản lý giỏ hàng và khuyến mãi trước khi thanh toán.",
    },
  },
  {
    path: "/tim-kiem",
    name: "tim-kiem",
    component: () => import("../components/Client/TimKiem/index.vue"),
    meta: {
      layout: "customer",
      title: "Tìm Kiếm Thuốc",
      subtitle: "Khách hàng tìm thuốc theo tên, loại và triệu chứng.",
    },
  },
  {
    path: "/danh-muc/:sectionSlug/:categorySlug?",
    name: "danh-muc",
    component: () => import("../components/Client/DanhMuc/index.vue"),
    meta: {
      layout: "customer",
      title: "Danh Mục Thuốc",
      subtitle: "Khách hàng xem thuốc theo từng mục trong danh mục.",
    },
  },
  {
    path: "/thanh-toan",
    name: "thanh-toan",
    component: () => import("../components/Client/ThanhToan/index.vue"),
    meta: {
      layout: "customer",
      title: "Thanh Toán",
      subtitle: "Chọn địa chỉ giao hàng và phương thức thanh toán.",
      requiresAuth: true,
      hideCustomerFooter: true,
    },
  },
  {
    path: "/tai-khoan/:section?",
    name: "tai-khoan",
    component: () => import("../components/Client/Profile/index.vue"),
    meta: {
      layout: "customer",
      title: "Tài Khoản",
      subtitle: "Quản lý thông tin cá nhân, địa chỉ, đơn hàng và thông báo.",
      requiresAuth: true,
    },
  },
  {
    path: "/login",
    name: "login",
    component: () => import("../components/Client/DangNhap/index.vue"),
    meta: {
      layout: "empty",
      title: "Đăng Nhập",
      subtitle: "Đăng nhập cho khách hàng, admin và nhân viên.",
      guestOnly: true,
    },
  },
  {
    path: "/register",
    name: "register",
    component: () => import("../components/Client/DangKy/index.vue"),
    meta: {
      layout: "empty",
      title: "Đăng Ký",
      subtitle: "Đăng ký tài khoản khách hàng mới.",
      guestOnly: true,
    },
  },
  {
    path: "/thong-ke",
    name: "thong-ke",
    component: () => import("../components/Admin/Dashboard/index.vue"),
    meta: {
      layout: "default",
      title: "Thống kê doanh thu",
      subtitle: "Theo dõi đăng nhập nhân viên, hóa đơn và doanh thu theo ngày, theo tháng.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/ban-tai-quay",
    name: "ban-tai-quay",
    component: () => import("../components/Admin/BanTaiQuay/index.vue"),
    meta: {
      layout: "default",
      title: "Bán tại quầy",
      subtitle: "Lập hóa đơn và thanh toán trực tiếp cho khách nhận thuốc tại quầy.",
      requiresAuth: true,
      requiresSystem: true,
      requiresCounter: true,
    },
  },
  {
    path: "/nhan-viens",
    name: "nhan-viens",
    component: () => import("../components/Admin/NhanVien/index.vue"),
    meta: {
      layout: "default",
      title: "Quản Lý Nhân Viên",
      subtitle: "Tạo, sửa, xóa và đổi mật khẩu nhân viên từ API admin.",
      requiresAuth: true,
      requiresSystem: true,
      requiresAdmin: true,
    },
  },
  {
    path: "/hoa-dons",
    name: "hoa-dons",
    component: () => import("../components/Admin/HoaDon/index.vue"),
    meta: {
      layout: "default",
      title: "Danh Sách Hóa Đơn",
      subtitle: "Xem doanh thu và hóa đơn đồng bộ từ backend Laravel.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/khach-hangs",
    name: "khach-hangs",
    component: () => import("../components/Admin/KhachHang/index.vue"),
    meta: {
      layout: "default",
      title: "Quản Lý Khách Hàng",
      subtitle: "Xem thông tin khách hàng và lịch sử đơn hàng đã mua.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/ho-tro-khach-hang",
    name: "ho-tro-khach-hang",
    component: () => import("../components/Admin/HoTroKhachHang/index.vue"),
    meta: {
      layout: "default",
      title: "Hỗ Trợ Khách Hàng",
      subtitle: "Nhân viên và admin trả lời chat hỗ trợ trực tiếp từ khách hàng.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/ton-kho",
    name: "ton-kho",
    component: () => import("../components/Admin/TonKho/index.vue"),
    meta: {
      layout: "default",
      title: "Tồn Kho Và Lô Thuốc",
      subtitle: "Nhân viên xem tồn kho, lô thuốc và hạn sử dụng từ database hiện tại.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/thuocs",
    name: "thuocs",
    component: () => import("../components/Admin/Thuoc/index.vue"),
    meta: {
      layout: "default",
      title: "Quản Lý Thuốc",
      subtitle: "Xem danh sách, tìm kiếm và thêm thuốc mới theo database hiện tại.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/gia-khuyen-mai",
    name: "gia-khuyen-mai",
    component: () => import("../components/Admin/GiaKhuyenMai/index.vue"),
    meta: {
      layout: "default",
      title: "Giá Và Khuyến Mãi",
      subtitle: "Nhân viên và admin cập nhật giá bán và chương trình ưu đãi cho thuốc.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  const loggedIn = isAuthenticated();

  if (to.meta.requiresAuth && !loggedIn) {
    return {
      path: "/login",
      query: { redirect: to.fullPath },
    };
  }

  if (to.meta.requiresSystem && !isSystemUser()) {
    return { path: "/" };
  }

  if (to.meta.requiresCounter && getSessionChannel() !== "tai_quay") {
    return { path: "/thong-ke" };
  }

  if (to.meta.requiresSystem && !to.meta.requiresCounter && getSessionChannel() === "tai_quay") {
    return { path: "/ban-tai-quay" };
  }

  if (to.meta.requiresAdmin && !isAdminUser()) {
    return { path: "/thong-ke" };
  }

  if (to.meta.guestOnly && loggedIn) {
    if (isSystemUser()) {
      return { path: getSessionChannel() === "tai_quay" ? "/ban-tai-quay" : "/thong-ke" };
    }

    return { path: "/" };
  }
});

router.afterEach((to) => {
  document.title = `${to.meta.title || "Pharmacity FE"} | Pharmacity FE`;
});

export default router;
