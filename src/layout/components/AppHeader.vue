<template>
  <header class="master-header">
    <div class="d-flex align-items-center justify-content-between gap-3">
      <button
        class="btn btn-light d-lg-none rounded-circle shadow-sm"
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#masterSidebar"
        aria-controls="masterSidebar"
      >
        <i class="bi bi-list fs-4"></i>
      </button>

      <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-3 flex-grow-1">
        <div class="input-group master-header__search">
          <span class="input-group-text text-secondary">
            <i class="bi bi-search"></i>
          </span>
          <input type="text" class="form-control" placeholder="Tìm nhanh sản phẩm, chi nhánh, đơn hàng..." />
        </div>

        <div class="master-header__actions">
          <div class="master-header__user-card">
            <div class="user-pill__avatar d-grid place-items-center bg-primary-subtle text-primary">
              <i class="bi bi-person-circle fs-4"></i>
            </div>
            <div class="master-header__user-meta">
              <div class="master-header__user-name">
                {{ currentUser?.ho_ten || currentUser?.ten_khach_hang || "Chưa đăng nhập" }}
              </div>
              <div class="master-header__user-role">{{ roleLabel }}</div>
            </div>
            <button
              type="button"
              class="btn btn-outline-secondary master-header__logout-btn"
              @click="handleLogout"
            >
              Đăng xuất
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { authState, clearAuthSession } from "../../lib/authStorage";

const router = useRouter();
const currentUser = computed(() => authState.user);

const roleLabel = computed(() => {
  if (currentUser.value?.vai_tro?.ten_vai_tro) {
    return currentUser.value.vai_tro.ten_vai_tro;
  }

  if (authState.type === "admin") {
    return "Admin";
  }

  if (["staff", "nhan_vien", "nhanvien"].includes(authState.type)) {
    return "Nhân viên";
  }

  if (authState.type === "customer") {
    return "Khách hàng";
  }

  return "Tài khoản";
});

function handleLogout() {
  clearAuthSession();
  router.push("/");
}
</script>
