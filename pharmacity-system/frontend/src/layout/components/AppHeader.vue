<template>
  <header class="master-header">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
      <div class="d-flex align-items-start align-items-lg-center gap-3">
        <button
          class="btn btn-light d-lg-none rounded-circle shadow-sm"
          type="button"
          data-bs-toggle="offcanvas"
          data-bs-target="#masterSidebar"
          aria-controls="masterSidebar"
        >
          <i class="bi bi-list fs-4"></i>
        </button>

        <div>
          <p class="small text-uppercase fw-bold text-primary mb-1">Master Header</p>
          <h1 class="h3 fw-bold mb-1">{{ pageTitle }}</h1>
          <p class="text-secondary mb-0">{{ pageSubtitle }}</p>
        </div>
      </div>

      <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-3">
        <div class="input-group master-header__search">
          <span class="input-group-text text-secondary">
            <i class="bi bi-search"></i>
          </span>
          <input
            type="text"
            class="form-control"
            placeholder="Tim nhanh san pham, chi nhanh, don hang..."
          />
        </div>

        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-light rounded-circle shadow-sm position-relative">
            <i class="bi bi-bell"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              3
            </span>
          </button>

            <div class="d-flex align-items-center gap-2 px-2">
              <div class="user-pill__avatar d-grid place-items-center bg-primary-subtle text-primary">
                <i class="bi bi-person-circle fs-4"></i>
              </div>
              <div>
                <div class="fw-bold">{{ currentUser?.ho_ten || "Chua dang nhap" }}</div>
                <div class="small text-secondary">{{ roleLabel }}</div>
              </div>
            </div>

          <button type="button" class="btn btn-outline-secondary" @click="handleLogout">
            Dang xuat
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { clearAuthSession, getAuthType, getStoredUser } from "../../lib/authStorage";

const route = useRoute();
const router = useRouter();
const currentUser = getStoredUser();
const authType = getAuthType();

const pageTitle = computed(() => route.meta.title || "Pharmacity FE");
const pageSubtitle = computed(
  () => route.meta.subtitle || "He thong master layout dung chung cho toan bo project."
);

const roleLabel = computed(() => {
  if (currentUser?.vai_tro?.ten_vai_tro) {
    return currentUser.vai_tro.ten_vai_tro;
  }

  if (authType === "customer") {
    return "Khach hang";
  }

  return "Tai khoan";
});

function handleLogout() {
  clearAuthSession();
  router.push("/");
}
</script>
