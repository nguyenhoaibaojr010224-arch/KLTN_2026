<template>
  <div class="sidebar-shell">
    <div v-if="mobile" class="offcanvas-header border-bottom px-4 py-3">
      <h5 id="masterSidebarLabel" class="mb-0 fw-bold">Pharmacity FE</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="sidebar-brand">
      <div class="d-flex align-items-center gap-3">
        <div class="sidebar-brand__mark">
          <i class="bi bi-capsule-pill"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">Pharmacity FE</div>
          <div class="small text-secondary">Bootstrap master workspace</div>
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
          Shared shell
        </div>
        <h6 class="fw-bold mb-2">Menu, header va footer da tach rieng</h6>
        <p class="small text-secondary mb-0">
          Cac page chi can tap trung vao content, master layout se bao phan khung dung chung.
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
    label: "Dieu hanh",
    items: [
      {
        to: "/",
        label: "Trang Chu Khach Hang",
        caption: "Quay lai giao dien ban hang",
        icon: "bi bi-house-door",
      },
      {
        to: "/dashboard",
        label: "Dashboard",
        caption: "Tong quan van hanh",
        icon: "bi bi-grid-1x2-fill",
      },
      {
        to: "/hoa-dons",
        label: "Hoa Don",
        caption: "Doanh thu va don hang",
        icon: "bi bi-receipt",
      },
      {
        to: "/ton-kho",
        label: "Ton Kho",
        caption: "Thuoc va lo thuoc",
        icon: "bi bi-box-seam",
      },
      {
        to: "/gia-khuyen-mai",
        label: "Gia & Khuyen Mai",
        caption: "Gia ban va uu dai",
        icon: "bi bi-tags",
      },
      ...(isAdminState.value
        ? [
            {
              to: "/nhan-viens",
              label: "Nhan Vien",
              caption: "Quan sat du lieu admin",
              icon: "bi bi-people",
            },
          ]
        : []),
    ],
  },
  {
    label: "Thu nghiem layout",
    items: [
      {
        to: "/sample",
        label: "Sample Page",
        caption: "Kiem tra page ke thua",
        icon: "bi bi-window-stack",
      },
      {
        to: "/api-playground",
        label: "API Playground",
        caption: "Test login va goi backend",
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
