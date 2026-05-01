import { createRouter, createWebHistory } from "vue-router";
import { isAdminUser, isAuthenticated, isSystemUser } from "../lib/authStorage";

const routes = [
  {
    path: "/",
    name: "home",
    component: () => import("../components/Client/TrangChu/index.vue"),
    meta: {
      layout: "customer",
      title: "Trang Chu",
      subtitle: "Khong gian ban thuoc va cham soc suc khoe cho khach hang.",
    },
  },
  {
    path: "/gio-hang",
    name: "gio-hang",
    component: () => import("../components/Client/GioHang/index.vue"),
    meta: {
      layout: "customer",
      title: "Gio Hang",
      subtitle: "Quan ly gio hang va khuyen mai truoc khi thanh toan.",
    },
  },
  {
    path: "/tim-kiem",
    name: "tim-kiem",
    component: () => import("../components/Client/TimKiem/index.vue"),
    meta: {
      layout: "customer",
      title: "Tim Kiem Thuoc",
      subtitle: "Khach hang tim thuoc theo ten, loai va trieu chung.",
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
      title: "Thanh Toan",
      subtitle: "Chon dia chi giao hang va phuong thuc thanh toan.",
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
      title: "Tai Khoan",
      subtitle: "Quan ly thong tin ca nhan, dia chi, don hang va thong bao.",
      requiresAuth: true,
    },
  },
  {
    path: "/login",
    name: "login",
    component: () => import("../components/Client/DangNhap/index.vue"),
    meta: {
      layout: "empty",
      title: "Dang Nhap",
      subtitle: "Dang nhap cho khach hang, admin va nhan vien.",
      guestOnly: true,
    },
  },
  {
    path: "/register",
    name: "register",
    component: () => import("../components/Client/DangKy/index.vue"),
    meta: {
      layout: "empty",
      title: "Dang Ky",
      subtitle: "Dang ky tai khoan khach hang moi.",
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
    path: "/nhan-viens",
    name: "nhan-viens",
    component: () => import("../components/Admin/NhanVien/index.vue"),
    meta: {
      layout: "default",
      title: "Quan Ly Nhan Vien",
      subtitle: "Tao, sua, xoa va doi mat khau nhan vien tu API admin.",
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
      title: "Danh Sach Hoa Don",
      subtitle: "Xem doanh thu va hoa don dong bo tu backend Laravel.",
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
      title: "Ho Tro Khach Hang",
      subtitle: "Nhan vien va admin tra loi chat ho tro truc tiep tu khach hang.",
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
      title: "Ton Kho Va Lo Thuoc",
      subtitle: "Nhan vien xem ton kho, lo thuoc va han su dung tu database hien tai.",
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
      title: "Quan Ly Thuoc",
      subtitle: "Xem danh sach, tim kiem va them thuoc moi theo database hien tai.",
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
      title: "Gia Va Khuyen Mai",
      subtitle: "Nhan vien va admin cap nhat gia ban va chuong trinh uu dai cho thuoc.",
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

  if (to.meta.requiresAdmin && !isAdminUser()) {
    return { path: "/thong-ke" };
  }

  if (to.meta.guestOnly && loggedIn) {
    return { path: "/" };
  }
});

router.afterEach((to) => {
  document.title = `${to.meta.title || "Pharmacity FE"} | Pharmacity FE`;
});

export default router;
