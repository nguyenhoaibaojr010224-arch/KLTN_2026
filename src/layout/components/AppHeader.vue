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
        <div ref="searchContainerRef" class="master-header__search-wrap">
          <form class="input-group master-header__search" @submit.prevent="submitGlobalSearch">
            <span class="input-group-text text-secondary">
              <i class="bi bi-search"></i>
            </span>
            <input
              v-model="globalKeyword"
              type="search"
              class="form-control"
              placeholder="Tìm nhanh thuốc, hóa đơn, khách hàng, nhân viên..."
              autocomplete="off"
              @focus="openSearchDropdown"
            />
            <button
              v-if="globalKeyword"
              type="button"
              class="btn master-header__search-clear"
              aria-label="Xóa tìm kiếm"
              @click="clearGlobalSearch"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </form>

          <div v-if="showSearchDropdown" class="master-header__search-dropdown">
            <div class="global-search__head">
              <strong>Tìm kiếm tổng hệ thống</strong>
              <span v-if="normalizedKeyword.length >= 2">{{ totalResultCount }} kết quả</span>
            </div>

            <div v-if="normalizedKeyword.length < 2" class="global-search__empty">
              Nhập ít nhất 2 ký tự để tìm thuốc, hóa đơn, khách hàng, nhân viên và tồn kho.
            </div>

            <div v-else-if="globalSearchLoading" class="global-search__loading">
              <span class="spinner-border spinner-border-sm"></span>
              Đang tìm kiếm...
            </div>

            <div v-else-if="globalSearchError" class="global-search__empty text-danger">
              {{ globalSearchError }}
            </div>

            <div v-else-if="hasGlobalResults" class="global-search__groups">
              <section v-for="group in visibleSearchGroups" :key="group.key" class="global-search__group">
                <div class="global-search__group-title">
                  <span><i :class="group.icon"></i>{{ group.label }}</span>
                  <button type="button" @click="goToGroup(group)">Xem tất cả</button>
                </div>

                <button
                  v-for="item in group.items"
                  :key="item.key"
                  type="button"
                  class="global-search__item"
                  @click="goToResult(item)"
                >
                  <span class="global-search__item-icon"><i :class="group.icon"></i></span>
                  <span class="global-search__item-body">
                    <strong>{{ item.title }}</strong>
                    <small>{{ item.meta || group.label }}</small>
                  </span>
                </button>
              </section>
            </div>

            <div v-else class="global-search__empty">
              Không tìm thấy dữ liệu phù hợp.
            </div>
          </div>
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { logout as logoutFromApi } from "../../api/authApi";
import { getProfile } from "../../api/profileApi";
import { searchHoaDons } from "../../api/hoaDonApi";
import { searchKhachHangs } from "../../api/khachHangApi";
import { searchLoThuocs } from "../../api/inventoryApi";
import { searchNhanViens } from "../../api/nhanVienApi";
import { searchThuocList } from "../../api/thuocManagementApi";
import { authState, clearAuthSession, isSystemUser, updateAuthUser } from "../../lib/authStorage";

const router = useRouter();
const route = useRoute();
const currentUser = computed(() => authState.user);
const globalKeyword = ref("");
const globalSearchLoading = ref(false);
const globalSearchError = ref("");
const globalSearchGroups = ref([]);
const searchDropdownOpen = ref(false);
const searchContainerRef = ref(null);
let searchTimer = null;
let searchRequestId = 0;

const roleLabel = computed(() => {
  if (currentUser.value?.vai_tro?.ten_vai_tro) {
    return currentUser.value.vai_tro.ten_vai_tro;
  }

  if (authState.type === "admin") {
    return "Admin";
  }

  if (["staff", "nhan_vien", "nhanvien"].includes(authState.type)) {
    return authState.sessionChannel === "tai_quay" ? "Nhân viên tại quầy" : "Nhân viên hệ thống";
  }

  if (authState.type === "customer") {
    return "Khách hàng";
  }

  return "Tài khoản";
});

const normalizedKeyword = computed(() => globalKeyword.value.trim());
const visibleSearchGroups = computed(() => globalSearchGroups.value.filter((group) => group.items.length > 0));
const hasGlobalResults = computed(() => visibleSearchGroups.value.length > 0);
const totalResultCount = computed(() => visibleSearchGroups.value.reduce((total, group) => total + group.total, 0));
const showSearchDropdown = computed(() =>
  searchDropdownOpen.value && (normalizedKeyword.value.length > 0 || globalSearchLoading.value)
);

const searchDefinitions = [
  {
    key: "thuocs",
    label: "Thuốc",
    icon: "bi bi-capsule-pill",
    path: "/thuocs",
    search: searchThuocList,
    map: (item) => ({
      key: `thuoc-${item.ma_thuoc}`,
      title: item.ten_thuoc || "Thuốc chưa có tên",
      meta: [item.ma_thuoc, item.nhan, item.nha_san_xuat?.ten_nha_san_xuat || item.nha_san_xuat].filter(Boolean).join(" - "),
      path: "/thuocs",
    }),
  },
  {
    key: "hoa-dons",
    label: "Hóa đơn",
    icon: "bi bi-receipt",
    path: "/hoa-dons",
    search: searchHoaDons,
    map: (item) => ({
      key: `hoa-don-${item.id_hoa_don}`,
      title: item.ma_hoa_don || `Hóa đơn #${item.id_hoa_don}`,
      meta: [
        item.khach_hang?.ten_khach_hang,
        item.khachHang?.ten_khach_hang,
        formatCurrency(item.tien_thanh_toan),
      ].filter(Boolean).join(" - "),
      path: "/hoa-dons",
    }),
  },
  {
    key: "khach-hangs",
    label: "Khách hàng",
    icon: "bi bi-person-lines-fill",
    path: "/khach-hangs",
    search: searchKhachHangs,
    map: (item) => ({
      key: `khach-hang-${item.id_khach_hang}`,
      title: item.ten_khach_hang || "Khách hàng chưa có tên",
      meta: [item.so_dien_thoai, item.email].filter(Boolean).join(" - "),
      path: "/khach-hangs",
    }),
  },
  {
    key: "nhan-viens",
    label: "Nhân viên",
    icon: "bi bi-person-badge",
    path: "/nhan-viens",
    search: searchNhanViens,
    map: (item) => ({
      key: `nhan-vien-${item.id_nhan_vien}`,
      title: item.ho_ten || item.ten_dang_nhap || "Nhân viên chưa có tên",
      meta: [item.ten_dang_nhap, item.so_dien_thoai, item.vai_tro?.ten_vai_tro].filter(Boolean).join(" - "),
      path: "/nhan-viens",
    }),
  },
  {
    key: "ton-kho",
    label: "Tồn kho / lô thuốc",
    icon: "bi bi-box-seam",
    path: "/ton-kho",
    search: searchLoThuocs,
    map: (item) => ({
      key: `lo-thuoc-${item.id_lo || item.so_lo}`,
      title: item.thuoc?.ten_thuoc || item.ten_thuoc || item.so_lo || "Lô thuốc",
      meta: [item.so_lo, item.thuoc?.ma_thuoc || item.ma_thuoc, `Còn ${item.so_luong_con ?? 0}`].filter(Boolean).join(" - "),
      path: "/ton-kho",
    }),
  },
];

onMounted(() => {
  refreshCurrentProfile();
  document.addEventListener("click", handleDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleDocumentClick);
  if (searchTimer) {
    window.clearTimeout(searchTimer);
  }
});

watch(
  () => globalKeyword.value,
  () => {
    queueGlobalSearch();
  }
);

watch(
  () => route.fullPath,
  () => {
    searchDropdownOpen.value = false;
  }
);

function normalizeArrayResponse(response) {
  if (Array.isArray(response)) {
    return response;
  }

  if (Array.isArray(response?.data)) {
    return response.data;
  }

  return [];
}

function formatCurrency(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function openSearchDropdown() {
  searchDropdownOpen.value = true;
  if (normalizedKeyword.value.length >= 2 && !globalSearchGroups.value.length) {
    queueGlobalSearch();
  }
}

function queueGlobalSearch() {
  searchDropdownOpen.value = true;
  globalSearchError.value = "";

  if (searchTimer) {
    window.clearTimeout(searchTimer);
  }

  if (normalizedKeyword.value.length < 2) {
    globalSearchLoading.value = false;
    globalSearchGroups.value = [];
    return;
  }

  searchTimer = window.setTimeout(() => {
    runGlobalSearch();
  }, 280);
}

async function runGlobalSearch() {
  const keyword = normalizedKeyword.value;
  if (keyword.length < 2) {
    return;
  }

  const requestId = ++searchRequestId;
  globalSearchLoading.value = true;
  globalSearchError.value = "";

  const results = await Promise.allSettled(
    searchDefinitions.map(async (definition) => {
      const response = await definition.search(keyword);
      const records = normalizeArrayResponse(response);
      return {
        ...definition,
        items: records.map(definition.map).slice(0, 4),
        total: records.length,
      };
    })
  );

  if (requestId !== searchRequestId) {
    return;
  }

  const groups = [];
  let failedCount = 0;
  results.forEach((result, index) => {
    if (result.status === "fulfilled") {
      groups.push(result.value);
      return;
    }

    failedCount += 1;
    groups.push({ ...searchDefinitions[index], items: [], total: 0 });
  });

  globalSearchGroups.value = groups;
  globalSearchError.value = failedCount === searchDefinitions.length
    ? "Không thể tìm kiếm tổng hệ thống. Vui lòng thử lại."
    : "";
  globalSearchLoading.value = false;
}

async function submitGlobalSearch() {
  if (normalizedKeyword.value.length < 2) {
    searchDropdownOpen.value = true;
    return;
  }

  if (!globalSearchGroups.value.length && !globalSearchLoading.value) {
    await runGlobalSearch();
  }

  const firstGroup = visibleSearchGroups.value[0];
  goToGroup(firstGroup || searchDefinitions[0]);
}

function goToGroup(group) {
  if (!group) {
    return;
  }

  const keyword = normalizedKeyword.value;
  searchDropdownOpen.value = false;
  router.push({
    path: group.path,
    query: keyword ? { q: keyword } : {},
  });
}

function goToResult(item) {
  if (!item) {
    return;
  }

  const keyword = normalizedKeyword.value;
  searchDropdownOpen.value = false;
  router.push({
    path: item.path,
    query: keyword ? { q: keyword } : {},
  });
}

function clearGlobalSearch() {
  globalKeyword.value = "";
  globalSearchGroups.value = [];
  globalSearchError.value = "";
  searchDropdownOpen.value = false;
}

function handleDocumentClick(event) {
  if (!searchContainerRef.value || searchContainerRef.value.contains(event.target)) {
    return;
  }

  searchDropdownOpen.value = false;
}

async function refreshCurrentProfile() {
  if (!authState.token || !isSystemUser()) {
    return;
  }

  try {
    const profile = await getProfile();
    updateAuthUser(profile);
  } catch {
    // Header vẫn dùng session hiện tại nếu profile tạm thời không tải được.
  }
}

async function handleLogout() {
  try {
    if (authState.token) {
      await logoutFromApi();
    }
  } catch {
    // Local session cleanup still runs if the API is already unavailable.
  } finally {
    clearAuthSession();
    router.push("/");
  }
}
</script>

<style scoped>
.master-header__search-wrap {
  position: relative;
  flex: 1 1 auto;
  min-width: min(100%, 320px);
}

.master-header__search {
  width: 100%;
}

.master-header__search-clear {
  border: 0;
  color: #64748b;
  padding-inline: 14px;
}

.master-header__search-clear:hover {
  color: #0f172a;
}

.master-header__search-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  right: 0;
  z-index: 1050;
  max-height: min(68vh, 560px);
  overflow: auto;
  padding: 14px;
  border: 1px solid rgba(22, 82, 197, 0.14);
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 24px 60px rgba(15, 31, 79, 0.18);
}

.global-search__head,
.global-search__group-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.global-search__head {
  padding: 2px 4px 12px;
  border-bottom: 1px solid rgba(100, 116, 139, 0.14);
  color: #0f172a;
}

.global-search__head span {
  color: #64748b;
  font-size: 0.88rem;
}

.global-search__empty,
.global-search__loading {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px 4px 6px;
  color: #64748b;
}

.global-search__groups {
  display: grid;
  gap: 14px;
  padding-top: 12px;
}

.global-search__group {
  display: grid;
  gap: 8px;
}

.global-search__group-title {
  color: #0f2f68;
  font-size: 0.9rem;
  font-weight: 700;
}

.global-search__group-title span {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.global-search__group-title button {
  border: 0;
  background: transparent;
  color: #0d6efd;
  font-size: 0.86rem;
  font-weight: 700;
}

.global-search__item {
  display: grid;
  grid-template-columns: 36px minmax(0, 1fr);
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px;
  border: 1px solid rgba(22, 82, 197, 0.08);
  border-radius: 12px;
  background: #f8fbff;
  text-align: left;
}

.global-search__item:hover {
  border-color: rgba(13, 110, 253, 0.24);
  background: #eef6ff;
}

.global-search__item-icon {
  display: grid;
  width: 36px;
  height: 36px;
  place-items: center;
  border-radius: 12px;
  background: #e7f0ff;
  color: #0d6efd;
}

.global-search__item-body {
  min-width: 0;
}

.global-search__item-body strong,
.global-search__item-body small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.global-search__item-body strong {
  color: #0f172a;
}

.global-search__item-body small {
  color: #64748b;
}
</style>
