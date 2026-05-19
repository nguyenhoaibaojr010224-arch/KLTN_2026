<template>
  <header class="pc-site-header">

    <div class="pc-mainbar">
      <div class="pc-container">
        <div class="pc-mainbar__inner">
          <RouterLink to="/" class="pc-logo">
            <span class="pc-logo__brand">NHÀ THUỐC</span>
            <span class="pc-logo__name">PharmaGo</span>
          </RouterLink>

          <div class="pc-mainbar__search">
            <div ref="categoryRef" class="pc-category">
              <button class="pc-category__button" type="button" @click="toggleMegaMenu">
                <i class="bi bi-grid"></i>
                <span>Danh Mục</span>
                <i class="bi bi-chevron-down"></i>
              </button>

              <div v-if="showMegaMenu" class="pc-mega-menu">
                <div class="pc-mega-menu__aside">
                  <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    class="pc-mega-menu__category"
                    :class="{ active: activeCategory === category.id }"
                    @mouseenter="activeCategory = category.id"
                    @focus="activeCategory = category.id"
                  >
                    {{ category.label }}
                  </button>
                </div>

                <div class="pc-mega-menu__content">
                  <div class="row g-3">
                    <div class="col-sm-6 col-lg-4" v-for="item in activeMegaItems" :key="item.label">
                      <button type="button" class="pc-mega-menu__card" @click="openCatalog(item.slug)">
                        <span class="pc-mega-menu__thumb" :class="item.tone">
                          <i :class="item.icon"></i>
                        </span>
                        <span>{{ item.label }}</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div ref="searchContainerRef" class="pc-searchbar-wrap">
              <div class="pc-searchbar">
                <i class="bi bi-search"></i>
                <span
                  v-if="showAnimatedPlaceholder"
                  class="pc-searchbar__animated-placeholder"
                >
                  {{ animatedPlaceholder }}
                  <span class="pc-searchbar__caret"></span>
                </span>
                <input
                  v-model.trim="searchQuery"
                  type="text"
                  placeholder=""
                  @focus="openSearchDropdown"
                  @keyup.enter="submitSearch()"
                />
              </div>

              <div v-if="showSearchDropdown" class="pc-search-dropdown">
                <div class="pc-search-dropdown__head">
                  <span>Tìm kiếm gần đây</span>
                  <button
                    v-if="recentSearches.length"
                    type="button"
                    class="pc-search-dropdown__clear"
                    @click="clearRecentSearchHistory"
                  >
                    Xóa tất cả
                  </button>
                </div>

                <div v-if="recentSearches.length" class="pc-search-dropdown__list">
                  <button
                    v-for="item in recentSearches"
                    :key="item"
                    type="button"
                    class="pc-search-dropdown__item"
                    @click="selectRecentSearch(item)"
                  >
                    <i class="bi bi-clock-history"></i>
                    <span>{{ item }}</span>
                  </button>
                </div>

                <p v-else class="pc-search-dropdown__empty">
                  Chưa có tìm kiếm gần đây.
                </p>
              </div>
            </div>
          </div>

          <div class="pc-actions">
            <div ref="notificationMenuRef" class="pc-notification-dropdown">
              <button class="pc-action-icon pc-action-icon--notification" type="button" @click="toggleNotificationMenu">
                <i class="bi bi-bell"></i>
                <span v-if="unreadNotificationCount" class="pc-action-icon__badge">{{ unreadNotificationCount }}</span>
              </button>

              <div v-if="showNotificationMenu" class="pc-notification-menu">
                <div class="pc-notification-menu__head">
                  <strong>Thông báo</strong>
                  <span v-if="notificationRefreshing" class="pc-notification-menu__refresh">
                    <span class="spinner-border spinner-border-sm"></span>
                  </span>
                  <button v-if="isCustomerLoggedIn" type="button" class="pc-notification-menu__link" @click="goToNotifications">
                    Xem tất cả
                  </button>
                </div>

                <div v-if="isCustomerLoggedIn && recentNotifications.length" class="pc-notification-menu__list">
                  <button
                    v-for="item in recentNotifications"
                    :key="item.id"
                    type="button"
                    class="pc-notification-menu__item"
                    @click="openNotification(item)"
                  >
                    <div class="pc-notification-menu__content">
                      <strong>{{ item.group }}</strong>
                      <span>{{ item.tieuDe }}</span>
                    </div>
                    <small :class="{ 'is-read': item.daDoc }">{{ item.daDoc ? "Đã đọc" : "Mới" }}</small>
                  </button>
                </div>

                <p v-else-if="isCustomerLoggedIn && notificationRefreshing" class="pc-notification-menu__empty">
                  Đang cập nhật thông báo...
                </p>
                <p v-else-if="isCustomerLoggedIn" class="pc-notification-menu__empty">Chưa có thông báo nào.</p>

                <div v-else class="pc-notification-menu__guest">
                  <p>Đăng nhập để xem thông báo của bạn.</p>
                  <RouterLink to="/login" class="pc-notification-menu__login" @click="closeNotificationMenu">
                    Đăng nhập
                  </RouterLink>
                </div>
              </div>
            </div>

            <RouterLink class="pc-action-icon pc-action-icon--cart" to="/gio-hang">
              <i class="bi bi-cart3"></i>
              <span v-if="cartCount" class="pc-action-icon__badge">{{ cartCount }}</span>
            </RouterLink>

            <template v-if="loggedIn">
              <div ref="userMenuRef" class="pc-user-dropdown">
                <button
                  class="pc-auth-link pc-auth-link--dropdown"
                  type="button"
                  :aria-expanded="showUserMenu"
                  @click="toggleUserMenu"
                >
                  <span v-if="avatarUrl && !avatarLoadFailed" class="pc-auth-link__avatar pc-auth-link__avatar--image">
                    <img :src="avatarUrl" alt="Avatar khách hàng" @error="handleAvatarError" />
                  </span>
                  <span v-else class="pc-auth-link__avatar">{{ avatarInitials }}</span>
                  <span class="pc-auth-link__meta">
                    <small>Xin Chào</small>
                    <strong>{{ displayName }}</strong>
                  </span>
                  <i class="bi bi-chevron-down pc-auth-link__chevron" :class="{ 'is-open': showUserMenu }"></i>
                </button>

                <ul v-if="showUserMenu" class="pc-user-menu">
                  <li><RouterLink class="dropdown-item" to="/tai-khoan/thong-tin" @click="closeUserMenu">Thông tin cá nhân</RouterLink></li>
                  <li><RouterLink class="dropdown-item" to="/tai-khoan/dia-chi" @click="closeUserMenu">Số địa chỉ nhận hàng</RouterLink></li>
                  <li><RouterLink class="dropdown-item" to="/tai-khoan/lich-su-don-hang" @click="closeUserMenu">Lịch sử đơn hàng</RouterLink></li>
                  <li><RouterLink class="dropdown-item" to="/tai-khoan/thong-bao" @click="closeUserMenu">Thông báo của tôi</RouterLink></li>
                  <li v-if="isSystemAccount"><hr class="dropdown-divider" /></li>
                  <li v-if="isSystemAccount"><RouterLink class="dropdown-item" to="/thong-ke" @click="closeUserMenu">Quản lý hệ thống</RouterLink></li>
                  <li><hr class="dropdown-divider" /></li>
                  <li><button class="dropdown-item text-danger" type="button" @click="handleLogout">Đăng xuất</button></li>
                </ul>
              </div>
            </template>

            <RouterLink v-else to="/login" class="pc-auth-link">
              <span class="pc-auth-link__icon"><i class="bi bi-person-circle"></i></span>
              <span class="pc-auth-link__meta">
                <strong>Đăng nhập</strong>
              </span>
            </RouterLink>
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
import { catalogSections } from "../../data/catalogSections";
import { authState, clearAuthSession, getAuthType, isAuthenticatedState, isSystemUserState } from "../../lib/authStorage";
import { useCustomerStore } from "../../lib/customerStore";
import { clearRecentSearches, getRecentSearches, saveRecentSearch } from "../../lib/recentSearches";

const route = useRoute();
const router = useRouter();
const {
  cartCount,
  state,
  markNotificationRead,
  markNotificationsReadByGroup,
  markAllNotificationsRead,
  syncOrdersFromApi,
  syncSharedNotificationsFromApi,
} = useCustomerStore();
const searchQuery = ref(route.query.q || "");
const searchContainerRef = ref(null);
const categoryRef = ref(null);
const userMenuRef = ref(null);
const notificationMenuRef = ref(null);
const searchDropdownOpen = ref(false);
const showUserMenu = ref(false);
const showNotificationMenu = ref(false);
const notificationRefreshing = ref(false);
const recentSearches = ref(getRecentSearches());
const showMegaMenu = ref(false);
const activeCategory = ref("thuoc");
const animatedPlaceholder = ref("");

const categories = catalogSections;
const placeholderPhrases = [
  "Bạn đang tìm gì cho sức khỏe hôm nay?",
  "Tìm thuốc ho, đau họng, vitamin...",
  "Gõ triệu chứng để tìm thuốc phù hợp",
  "Tìm nhanh theo tên thuốc hoặc loại thuốc",
];
let placeholderTimer = null;
let orderSyncTimer = null;
let notificationRefreshPromise = null;
let lastNotificationRefreshAt = 0;
let currentPhraseIndex = 0;
let currentCharIndex = 0;
let isDeletingPlaceholder = false;
const notificationRefreshThrottleMs = 4000;

const loggedIn = isAuthenticatedState;
const isSystemAccount = isSystemUserState;
const authType = computed(() => getAuthType());
const isCustomerLoggedIn = computed(() => loggedIn.value && authType.value === "customer");
const displayName = computed(() => authState.user?.ten_khach_hang || authState.user?.ho_ten || "Khách hàng");
const avatarUrl = computed(() => authState.user?.avatar_url || "");
const avatarLoadFailed = ref(false);
const avatarInitials = computed(() => {
  const name = displayName.value.trim();
  return name ? name.slice(0, 2).toUpperCase() : "KH";
});
const recentNotifications = computed(() => (isCustomerLoggedIn.value ? [...state.notifications].slice(0, 5) : []));
const unreadNotificationCount = computed(() =>
  isCustomerLoggedIn.value ? state.notifications.filter((item) => !item.daDoc).length : 0
);
const showSearchDropdown = computed(() => searchDropdownOpen.value);
const showAnimatedPlaceholder = computed(() => !searchQuery.value);
const activeMegaItems = computed(
  () => categories.find((item) => item.id === activeCategory.value)?.cards || categories[0].cards
);

watch(
  () => route.fullPath,
  () => {
    showMegaMenu.value = false;
    searchQuery.value = route.query.q || "";
    searchDropdownOpen.value = false;
    showUserMenu.value = false;
    showNotificationMenu.value = false;
  }
);

watch(
  () => avatarUrl.value,
  () => {
    avatarLoadFailed.value = false;
  },
  { immediate: true }
);

onMounted(async () => {
  document.addEventListener("click", handleDocumentClick);
  if (isCustomerLoggedIn.value) {
    refreshCustomerNotifications({ includeOrders: true, force: true });
    startOrderSyncPolling();
  }
  runPlaceholderAnimation();
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleDocumentClick);
  stopPlaceholderAnimation();
  stopOrderSyncPolling();
});

watch(
  () => [loggedIn.value, authType.value],
  async ([isLoggedIn, nextAuthType]) => {
    stopOrderSyncPolling();
    showNotificationMenu.value = false;

    if (isLoggedIn && nextAuthType === "customer") {
      refreshCustomerNotifications({ includeOrders: true, force: true });
      startOrderSyncPolling();
    }
  }
);

function toggleMegaMenu() {
  showMegaMenu.value = !showMegaMenu.value;
}

function closeMegaMenu() {
  showMegaMenu.value = false;
}

function openSearchDropdown() {
  recentSearches.value = getRecentSearches();
  searchDropdownOpen.value = true;
}

function handleDocumentClick(event) {
  if (!categoryRef.value?.contains(event.target)) {
    showMegaMenu.value = false;
  }

  if (!searchContainerRef.value?.contains(event.target)) {
    searchDropdownOpen.value = false;
  }

  if (!userMenuRef.value?.contains(event.target)) {
    showUserMenu.value = false;
  }

  if (!notificationMenuRef.value?.contains(event.target)) {
    showNotificationMenu.value = false;
  }
}

function openCatalog(cardSlug) {
  closeMegaMenu();

  if (!cardSlug || cardSlug === "tat-ca") {
    router.push(`/danh-muc/${activeCategory.value}`);
    return;
  }

  router.push(`/danh-muc/${activeCategory.value}/${cardSlug}`);
}

function submitSearch(nextKeyword = searchQuery.value) {
  const normalizedKeyword = String(nextKeyword || "")
    .trim()
    .replace(/\s+/g, " ");

  if (!normalizedKeyword) {
    searchDropdownOpen.value = false;
    return;
  }

  searchQuery.value = normalizedKeyword;
  recentSearches.value = saveRecentSearch(normalizedKeyword);
  searchDropdownOpen.value = false;

  router.push({
    path: "/tim-kiem",
    query: { q: normalizedKeyword },
  });
}

function selectRecentSearch(keyword) {
  submitSearch(keyword);
}

function clearRecentSearchHistory() {
  recentSearches.value = clearRecentSearches();
}

async function toggleNotificationMenu() {
  if (!isCustomerLoggedIn.value) {
    router.push("/login");
    return;
  }

  showNotificationMenu.value = !showNotificationMenu.value;
  showUserMenu.value = false;

  if (showNotificationMenu.value) {
    refreshCustomerNotifications({ includeOrders: false });
  }
}

function closeNotificationMenu() {
  showNotificationMenu.value = false;
}

async function goToNotifications() {
  if (isCustomerLoggedIn.value) {
    await markAllNotificationsRead();
  }

  closeNotificationMenu();
  router.push("/tai-khoan/thong-bao");
}

async function openNotification(item) {
  closeNotificationMenu();
  await markNotificationRead(item.id);
  router.push({
    path: "/tai-khoan/thong-bao",
    query: {
      notice: item.id,
      tab: item.group,
    },
  });
}

function toggleUserMenu() {
  showUserMenu.value = !showUserMenu.value;
  showNotificationMenu.value = false;
}

function closeUserMenu() {
  showUserMenu.value = false;
}

function startOrderSyncPolling() {
  stopOrderSyncPolling();

  orderSyncTimer = window.setInterval(() => {
    if (!isCustomerLoggedIn.value) {
      stopOrderSyncPolling();
      return;
    }

    refreshCustomerNotifications({ includeOrders: true });
  }, 15000);
}

function refreshCustomerNotifications({ includeOrders = false, force = false } = {}) {
  if (!isCustomerLoggedIn.value) {
    return Promise.resolve();
  }

  const now = Date.now();
  if (!force && notificationRefreshPromise) {
    return notificationRefreshPromise;
  }

  if (!force && now - lastNotificationRefreshAt < notificationRefreshThrottleMs) {
    return Promise.resolve();
  }

  lastNotificationRefreshAt = now;
  notificationRefreshing.value = true;
  const tasks = [syncSharedNotificationsFromApi()];

  if (includeOrders) {
    tasks.push(syncOrdersFromApi());
  }

  notificationRefreshPromise = Promise.allSettled(tasks)
    .finally(() => {
      notificationRefreshing.value = false;
      notificationRefreshPromise = null;
    });

  return notificationRefreshPromise;
}

function stopOrderSyncPolling() {
  if (orderSyncTimer) {
    clearInterval(orderSyncTimer);
    orderSyncTimer = null;
  }
}

function stopPlaceholderAnimation() {
  if (placeholderTimer) {
    clearTimeout(placeholderTimer);
    placeholderTimer = null;
  }
}

function runPlaceholderAnimation() {
  stopPlaceholderAnimation();

  const currentPhrase = placeholderPhrases[currentPhraseIndex];

  if (!searchQuery.value) {
    if (!isDeletingPlaceholder) {
      currentCharIndex = Math.min(currentCharIndex + 1, currentPhrase.length);
      animatedPlaceholder.value = currentPhrase.slice(0, currentCharIndex);

      if (currentCharIndex === currentPhrase.length) {
        placeholderTimer = window.setTimeout(() => {
          isDeletingPlaceholder = true;
          runPlaceholderAnimation();
        }, 1500);
        return;
      }
    } else {
      currentCharIndex = Math.max(currentCharIndex - 1, 0);
      animatedPlaceholder.value = currentPhrase.slice(0, currentCharIndex);

      if (currentCharIndex === 0) {
        isDeletingPlaceholder = false;
        currentPhraseIndex = (currentPhraseIndex + 1) % placeholderPhrases.length;
      }
    }
  }

  const delay = isDeletingPlaceholder ? 35 : 70;
  placeholderTimer = window.setTimeout(runPlaceholderAnimation, delay);
}

watch(
  () => searchQuery.value,
  (value) => {
    if (value) {
      stopPlaceholderAnimation();
      animatedPlaceholder.value = "";
      return;
    }

    if (!placeholderTimer) {
      runPlaceholderAnimation();
    }
  }
);

async function handleLogout() {
  closeUserMenu();
  stopOrderSyncPolling();

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

function handleAvatarError() {
  avatarLoadFailed.value = true;
}
</script>

<style scoped>
.pc-site-header {
  position: sticky;
  top: 0;
  z-index: 1050;
}

.pc-topline {
  background: #fff;
  border-bottom: 1px solid rgba(22, 82, 197, 0.08);
}

.pc-topline__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  min-height: 46px;
}

.pc-topline__promo {
  white-space: nowrap;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--pc-primary);
}

.pc-topline__links {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 22px;
  flex-wrap: wrap;
  font-size: 0.95rem;
}

.pc-topline__links a {
  color: #243b5d;
}

.pc-mainbar {
  background: linear-gradient(180deg, #1652c5 0%, #134aa8 100%);
  box-shadow: 0 10px 20px rgba(22, 82, 197, 0.14);
}

.pc-mainbar__inner {
  position: relative;
  display: grid;
  grid-template-columns: 210px minmax(0, 1fr) auto;
  align-items: center;
  gap: 16px;
  min-height: 74px;
}

.pc-logo {
  display: inline-flex;
  flex-direction: column;
  line-height: 1;
  color: #fff;
  text-decoration: none !important;
}

.pc-logo__brand {
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.pc-logo__name {
  margin-top: 2px;
  color: #9be14a;
  font-size: 2.05rem;
  font-weight: 800;
}

.pc-mainbar__search {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  gap: 14px;
  align-items: center;
}

.pc-category {
  position: relative;
}

.pc-category__button {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  min-height: 50px;
  padding: 0 18px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  font-size: 0.98rem;
  font-weight: 700;
}

.pc-searchbar {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 50px;
  padding: 0 18px;
  border-radius: 18px;
  background: #fff;
}

.pc-searchbar i {
  color: #6b7a90;
}

.pc-searchbar input {
  width: 100%;
  border: 0;
  background: transparent;
  outline: 0;
  color: #243b5d;
  font-size: 0.98rem;
  position: relative;
  z-index: 1;
}

.pc-searchbar__animated-placeholder {
  position: absolute;
  left: 48px;
  right: 18px;
  overflow: hidden;
  color: #6b7a90;
  font-size: 0.98rem;
  white-space: nowrap;
  pointer-events: none;
  background: linear-gradient(90deg, #6b7a90 0%, #8fb9ff 45%, #6b7a90 100%);
  background-size: 220% auto;
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: pc-placeholder-flow 2.6s linear infinite;
}

.pc-searchbar__caret {
  display: inline-block;
  width: 1px;
  height: 1.1em;
  margin-left: 4px;
  vertical-align: -0.16em;
  background: #7a9ef0;
  animation: pc-placeholder-caret 0.9s steps(1) infinite;
}

.pc-searchbar-wrap {
  position: relative;
}

.pc-search-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  right: 0;
  padding: 14px;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 24px 54px rgba(15, 31, 79, 0.2);
}

.pc-search-dropdown__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
  color: #243b5d;
  font-size: 0.92rem;
  font-weight: 800;
}

.pc-search-dropdown__clear {
  border: 0;
  background: transparent;
  color: #1652c5;
  font-size: 0.88rem;
  font-weight: 700;
}

.pc-search-dropdown__list {
  display: grid;
  gap: 8px;
}

.pc-search-dropdown__item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 0;
  border-radius: 12px;
  background: #f5f8ff;
  color: #243b5d;
  font-size: 0.95rem;
  font-weight: 600;
  text-align: left;
}

.pc-search-dropdown__item i {
  color: #1652c5;
}

.pc-search-dropdown__empty {
  margin: 0;
  color: #60738d;
  font-size: 0.92rem;
}

@keyframes pc-placeholder-flow {
  from {
    background-position: 200% 50%;
  }

  to {
    background-position: -40% 50%;
  }
}

@keyframes pc-placeholder-caret {
  50% {
    opacity: 0;
  }
}

.pc-actions {
  position: relative;
  z-index: 4;
  display: flex;
  align-items: center;
  gap: 14px;
}

.pc-actions > * {
  display: flex;
  align-items: center;
}

.pc-action-icon {
  position: relative;
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  border-radius: 999px;
  border: 0;
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  font-size: 1.25rem;
  transition: background-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
}

.pc-action-icon:hover,
.pc-action-icon:focus-visible {
  background: rgba(255, 255, 255, 0.16);
  box-shadow: 0 10px 24px rgba(8, 28, 79, 0.18);
  transform: translateY(-1px);
}

.pc-action-icon--notification {
  background: rgba(255, 255, 255, 0.1);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.14);
}

.pc-action-icon--notification i {
  transform: translateY(1px);
}

.pc-action-icon__badge {
  position: absolute;
  top: -3px;
  right: -3px;
  min-width: 21px;
  height: 21px;
  display: grid;
  place-items: center;
  padding: 0 4px;
  border: 2px solid #1652c5;
  border-radius: 999px;
  background: #ff6b35;
  color: #fff;
  font-size: 0.7rem;
  font-weight: 700;
  line-height: 1;
}

.pc-auth-link {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  max-width: 210px;
  min-height: 42px;
  padding: 0;
  border: 0;
  border-radius: 0;
  background: transparent;
  color: #fff;
  text-decoration: none !important;
}

.pc-user-dropdown {
  position: relative;
  z-index: 5;
}

.pc-notification-dropdown {
  position: relative;
  z-index: 5;
}

.pc-auth-link--dropdown {
  cursor: pointer;
}

.pc-logo *,
.pc-auth-link * {
  text-decoration: none !important;
}

.pc-auth-link small,
.pc-auth-link strong {
  display: block;
  line-height: 1.1;
}

.pc-auth-link__meta {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
}

.pc-auth-link small {
  font-size: 0.82rem;
  opacity: 0.96;
}

.pc-auth-link strong {
  font-size: 0.92rem;
  font-weight: 800;
}

.pc-auth-link small,
.pc-auth-link strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pc-auth-link__icon,
.pc-auth-link__avatar {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 999px;
}

.pc-auth-link__avatar {
  font-size: 0.9rem;
  font-weight: 700;
}

.pc-auth-link__avatar--image {
  overflow: hidden;
}

.pc-auth-link__avatar--image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.pc-auth-link__chevron {
  margin-left: 2px;
  font-size: 0.75rem;
  transition: transform 0.18s ease;
}

.pc-auth-link__chevron.is-open {
  transform: rotate(180deg);
}

.pc-user-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  min-width: 250px;
  margin: 0;
  padding: 8px;
  border: 0;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 24px 48px rgba(15, 31, 79, 0.18);
  z-index: 1080;
  list-style: none;
}

.pc-notification-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 320px;
  padding: 10px;
  border: 0;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 24px 48px rgba(15, 31, 79, 0.18);
  z-index: 1080;
}

.pc-notification-menu__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 6px 6px 10px;
}

.pc-notification-menu__head strong {
  color: #243b5d;
  font-size: 1rem;
  font-weight: 800;
}

.pc-notification-menu__refresh {
  margin-left: auto;
  color: #1652c5;
  line-height: 1;
}

.pc-notification-menu__link {
  border: 0;
  background: transparent;
  color: #1652c5;
  font-size: 0.9rem;
  font-weight: 700;
}

.pc-notification-menu__list {
  display: grid;
  gap: 8px;
}

.pc-notification-menu__item {
  width: 100%;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 12px;
  border: 0;
  border-radius: 14px;
  background: #f6f8ff;
  color: #243b5d;
  text-align: left;
}

.pc-notification-menu__content {
  display: grid;
  gap: 4px;
}

.pc-notification-menu__content strong {
  color: #17345f;
  font-size: 0.92rem;
  font-weight: 800;
}

.pc-notification-menu__content span {
  color: #60738d;
  font-size: 0.9rem;
  line-height: 1.45;
}

.pc-notification-menu__item small {
  flex-shrink: 0;
  color: #1652c5;
  font-size: 0.78rem;
  font-weight: 700;
}

.pc-notification-menu__item small.is-read {
  color: #7a8ca7;
}

.pc-notification-menu__empty,
.pc-notification-menu__guest p {
  margin: 0;
  padding: 12px;
  color: #60738d;
  font-size: 0.92rem;
}

.pc-notification-menu__guest {
  display: grid;
  gap: 10px;
  padding: 4px 2px 2px;
}

.pc-notification-menu__login {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 40px;
  padding: 0 14px;
  border-radius: 12px;
  background: #1652c5;
  color: #fff;
  font-size: 0.92rem;
  font-weight: 700;
  text-decoration: none !important;
}

.pc-user-menu li {
  list-style: none;
}

.pc-user-menu :deep(.dropdown-item) {
  width: 100%;
  display: flex;
  align-items: center;
  min-height: 44px;
  padding: 10px 14px;
  border: 0;
  border-radius: 12px;
  background: transparent;
  color: #243b5d;
  font-size: 0.98rem;
  font-weight: 600;
  line-height: 1.35;
  text-decoration: none !important;
  transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
}

.pc-user-menu :deep(.dropdown-item:hover),
.pc-user-menu :deep(.dropdown-item:focus),
.pc-user-menu :deep(.dropdown-item.router-link-active) {
  background: #f4f7ff;
  color: #1652c5;
  transform: translateX(2px);
}

.pc-user-menu :deep(.dropdown-item.text-danger) {
  color: #e53935 !important;
}

.pc-user-menu :deep(.dropdown-item.text-danger:hover),
.pc-user-menu :deep(.dropdown-item.text-danger:focus) {
  background: #fff3f2;
  color: #d32f2f !important;
}

.pc-user-menu :deep(.dropdown-divider) {
  margin: 6px 4px;
  border-top-color: rgba(22, 82, 197, 0.1);
}

.pc-mega-menu {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  width: min(820px, calc(100vw - 40px));
  display: grid;
  grid-template-columns: 190px minmax(0, 1fr);
  overflow: hidden;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 24px 54px rgba(15, 31, 79, 0.2);
}

.pc-mega-menu__aside {
  max-height: 420px;
  overflow: auto;
  padding: 12px;
  border-right: 1px solid rgba(22, 82, 197, 0.08);
  background: #fff;
}

.pc-mega-menu__content {
  padding: 16px;
}

.pc-mega-menu__category {
  width: 100%;
  padding: 10px 12px;
  border: 0;
  border-radius: 12px;
  background: transparent;
  color: #243b5d;
  font-size: 0.95rem;
  font-weight: 700;
  text-align: left;
}

.pc-mega-menu__category.active {
  background: #edf2ff;
  color: var(--pc-primary);
}

.pc-mega-menu__card {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border: 1px solid rgba(22, 82, 197, 0.08);
  border-radius: 14px;
  background: #fff;
  color: #243b5d;
  font-size: 0.95rem;
  font-weight: 700;
  text-align: left;
}

.pc-mega-menu__thumb {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  font-size: 1.15rem;
}

@media (max-width: 1199.98px) {
  .pc-mainbar__inner,
  .pc-mainbar__search {
    grid-template-columns: 1fr;
  }

  .pc-actions {
    justify-content: flex-end;
  }
}

@media (max-width: 767.98px) {
  .pc-topline__inner,
  .pc-topline__links,
  .pc-actions {
    display: grid;
    grid-template-columns: 1fr;
  }

  .pc-topline__links {
    gap: 10px;
    justify-content: flex-start;
  }

  .pc-topline__promo {
    white-space: normal;
  }

  .pc-mainbar__inner {
    gap: 14px;
    padding: 14px 0;
  }

  .pc-mainbar__search {
    gap: 12px;
  }
}
</style>

