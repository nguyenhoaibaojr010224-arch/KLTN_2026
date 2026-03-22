import { createRouter, createWebHistory } from "vue-router";
import { isAdminUser, isAuthenticated, isSystemUser } from "../lib/authStorage";

const routes = [
  {
    path: "/",
    name: "home",
    component: () => import("../pages/CustomerHomePage.vue"),
    meta: {
      layout: "customer",
      title: "Trang Chu",
      subtitle: "Khong gian ban thuoc va cham soc suc khoe cho khach hang.",
    },
  },
  {
    path: "/gio-hang",
    name: "gio-hang",
    component: () => import("../pages/CustomerCartPage.vue"),
    meta: {
      layout: "customer",
      title: "Gio Hang",
      subtitle: "Quan ly gio hang va khuyen mai truoc khi thanh toan.",
    },
  },
  {
    path: "/thanh-toan",
    name: "thanh-toan",
    component: () => import("../pages/CustomerCheckoutPage.vue"),
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
    component: () => import("../pages/CustomerAccountPage.vue"),
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
    component: () => import("../pages/LoginPage.vue"),
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
    component: () => import("../pages/RegisterPage.vue"),
    meta: {
      layout: "empty",
      title: "Dang Ky",
      subtitle: "Dang ky tai khoan khach hang moi.",
      guestOnly: true,
    },
  },
  {
    path: "/dashboard",
    name: "dashboard",
    component: () => import("../pages/DashboardPage.vue"),
    meta: {
      layout: "default",
      title: "Dashboard Dieu Phoi",
      subtitle: "Theo doi van hanh cac chi nhanh va uu tien xu ly trong ngay.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/sample",
    name: "sample",
    component: () => import("../pages/SamplePage.vue"),
    meta: {
      layout: "default",
      title: "Trang Mau Ke Thua",
      subtitle: "Kiem tra viec cac trang tai su dung master layout Bootstrap.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/api-playground",
    name: "api-playground",
    component: () => import("../pages/ApiPlaygroundPage.vue"),
    meta: {
      layout: "default",
      title: "Ket Noi API",
      subtitle: "Dang nhap, luu token va goi API Laravel truc tiep tu frontend.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/nhan-viens",
    name: "nhan-viens",
    component: () => import("../pages/NhanVienListPage.vue"),
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
    component: () => import("../pages/HoaDonListPage.vue"),
    meta: {
      layout: "default",
      title: "Danh Sach Hoa Don",
      subtitle: "Xem doanh thu va hoa don dong bo tu backend Laravel.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/ton-kho",
    name: "ton-kho",
    component: () => import("../pages/TonKhoPage.vue"),
    meta: {
      layout: "default",
      title: "Ton Kho Va Lo Thuoc",
      subtitle: "Nhan vien xem ton kho, lo thuoc va han su dung tu database hien tai.",
      requiresAuth: true,
      requiresSystem: true,
    },
  },
  {
    path: "/gia-khuyen-mai",
    name: "gia-khuyen-mai",
    component: () => import("../pages/PricingManagementPage.vue"),
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
    return { path: "/login" };
  }

  if (to.meta.requiresSystem && !isSystemUser()) {
    return { path: "/" };
  }

  if (to.meta.requiresAdmin && !isAdminUser()) {
    return { path: "/dashboard" };
  }

  if (to.meta.guestOnly && loggedIn) {
    return { path: "/" };
  }
});

router.afterEach((to) => {
  document.title = `${to.meta.title || "Pharmacity FE"} | Pharmacity FE`;
});

export default router;
