<template>
  <div class="sidebar-shell">
    <div v-if="mobile" class="offcanvas-header border-bottom px-4 py-3">
      <h5 id="masterSidebarLabel" class="mb-0 fw-bold">Quản lý hệ thống</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
    </div>

    <div class="sidebar-brand">
      <div class="d-flex align-items-center gap-3">
        <div class="sidebar-brand__mark">
          <i class="bi bi-capsule-pill"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">Quản lý hệ thống</div>
          <div class="small text-secondary">Điều hành thuốc, đơn hàng và tồn kho</div>
        </div>
      </div>
    </div>

    <div class="sidebar-scroll">
      <div v-for="section in menuSections" :key="section.label" class="mb-4">
        <p class="sidebar-caption">{{ section.label }}</p>

        <div class="d-flex flex-column gap-1">
          <button
            v-for="item in section.items"
            :key="item.to"
            type="button"
            class="sidebar-link"
            :class="{ active: isActive(item.to) }"
            @click="navigateTo(item.to)"
          >
            <span class="sidebar-link__icon">
              <i :class="item.icon"></i>
            </span>
            <span>
              <span class="d-block">{{ item.label }}</span>
              <small class="d-block opacity-75 fw-medium">{{ item.caption }}</small>
            </span>
          </button>
        </div>
      </div>

      <div class="promo-card p-3 mt-4">
        <div class="soft-badge soft-badge--blue mb-3">
          <i class="bi bi-layout-text-window-reverse"></i>
          Khung dùng chung
        </div>
        <h6 class="fw-bold mb-2">Menu, header và footer đã tách riêng</h6>
        <p class="small text-secondary mb-0">
          Các trang chỉ cần tập trung vào nội dung, khung quản trị sẽ lo phần giao diện dùng chung.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { isAdminState } from "../../lib/authStorage";

defineProps({
  mobile: {
    type: Boolean,
    default: false,
  },
});

const route = useRoute();
const router = useRouter();

const menuSections = computed(() => [
  {
    label: "Điều hành",
    items: [
      {
        to: "/",
        label: "Trang chủ khách hàng",
        caption: "Quay lại giao diện bán hàng",
        icon: "bi bi-house-door",
      },
      {
        to: "/dashboard",
        label: "Bảng điều khiển",
        caption: "Tổng quan vận hành",
        icon: "bi bi-grid-1x2-fill",
      },
      {
        to: "/hoa-dons",
        label: "Hóa đơn",
        caption: "Doanh thu và đơn hàng",
        icon: "bi bi-receipt",
      },
      {
        to: "/ton-kho",
        label: "Tồn kho",
        caption: "Thuốc và lô thuốc",
        icon: "bi bi-box-seam",
      },
      {
        to: "/thuocs",
        label: "Thuốc",
        caption: "Danh sách và thêm thuốc",
        icon: "bi bi-capsule-pill",
      },
      {
        to: "/gia-khuyen-mai",
        label: "Giá và khuyến mãi",
        caption: "Giá bán và ưu đãi",
        icon: "bi bi-tags",
      },
      ...(isAdminState.value
        ? [
            {
              to: "/nhan-viens",
              label: "Nhân viên",
              caption: "Quản lý dữ liệu nhân sự",
              icon: "bi bi-people",
            },
          ]
        : []),
    ],
  },
  {
    label: "Công cụ",
    items: [
      {
        to: "/sample",
        label: "Trang mẫu",
        caption: "Kiểm tra trang kế thừa",
        icon: "bi bi-window-stack",
      },
      {
        to: "/api-playground",
        label: "Kết nối API",
        caption: "Kiểm tra đăng nhập và gọi backend",
        icon: "bi bi-plug-fill",
      },
    ],
  },
]);

function isActive(path) {
  return route.path === path;
}

function navigateTo(path) {
  if (route.path === path) {
    return;
  }

  router.push(path);
}
</script>
