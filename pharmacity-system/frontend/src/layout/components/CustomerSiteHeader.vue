<template>
  <header class="pc-site-header">
    <div class="pc-topline">
      <div class="container-fluid pc-container">
        <div class="pc-topline__inner">
          <div class="pc-topline__promo">Mien phi van chuyen cho moi don hang tu 0d</div>
          <div class="pc-topline__links">
            <a href="#">Tai ung dung</a>
            <a href="#">Hotline 1800 6821</a>
            <a href="#">Doanh nghiep</a>
            <a href="#">Deal hot thang 03</a>
            <a href="#">Tra cuu don hang</a>
            <a href="#">Goc suc khoe</a>
            <a href="#">He thong nha thuoc</a>
          </div>
        </div>
      </div>
    </div>

    <div class="pc-mainbar">
      <div class="container-fluid pc-container">
        <div class="pc-mainbar__inner">
          <RouterLink to="/" class="pc-logo">
            <span class="pc-logo__brand">NHA THUOC</span>
            <span class="pc-logo__name">Pharmacity</span>
          </RouterLink>

          <div class="pc-mainbar__search">
            <div class="pc-category">
              <button class="pc-category__button" type="button" @click="toggleMegaMenu">
                <i class="bi bi-grid"></i>
                <span>Danh muc</span>
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
                      <button type="button" class="pc-mega-menu__card" @click="closeMegaMenu">
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

            <div class="pc-searchbar">
              <i class="bi bi-search"></i>
              <input
                v-model.trim="searchQuery"
                type="text"
                placeholder="Khach hang dang tim gi hom nay..."
                @keyup.enter="submitSearch"
              />
            </div>
          </div>

          <div class="pc-actions">
            <RouterLink class="pc-action-icon" :to="loggedIn ? '/tai-khoan/thong-bao' : '/login'">
              <i class="bi bi-bell"></i>
            </RouterLink>

            <RouterLink class="pc-action-icon pc-action-icon--cart" to="/gio-hang">
              <i class="bi bi-cart3"></i>
              <span v-if="cartCount" class="pc-action-icon__badge">{{ cartCount }}</span>
            </RouterLink>

            <template v-if="loggedIn">
              <div class="dropdown">
                <button
                  class="pc-auth-link pc-auth-link--dropdown dropdown-toggle"
                  type="button"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  <span class="pc-auth-link__avatar">{{ avatarInitials }}</span>
                  <span>
                    <small>Xin chao</small>
                    <strong>{{ displayName }}</strong>
                  </span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end pc-user-menu">
                  <li><RouterLink class="dropdown-item" to="/tai-khoan/thong-tin">Thong tin ca nhan</RouterLink></li>
                  <li><RouterLink class="dropdown-item" to="/tai-khoan/dia-chi">So dia chi nhan hang</RouterLink></li>
                  <li><RouterLink class="dropdown-item" to="/tai-khoan/lich-su-don-hang">Lich su don hang</RouterLink></li>
                  <li><RouterLink class="dropdown-item" to="/tai-khoan/thong-bao">Thong bao cua toi</RouterLink></li>
                  <li v-if="isSystemAccount"><hr class="dropdown-divider" /></li>
                  <li v-if="isSystemAccount"><RouterLink class="dropdown-item" to="/dashboard">Quan ly he thong</RouterLink></li>
                  <li v-if="isAdminAccount"><RouterLink class="dropdown-item" to="/nhan-viens">Quan ly nhan vien</RouterLink></li>
                  <li v-if="isSystemAccount"><RouterLink class="dropdown-item" to="/ton-kho">Ton kho va lo thuoc</RouterLink></li>
                  <li v-if="isSystemAccount"><RouterLink class="dropdown-item" to="/gia-khuyen-mai">Gia va khuyen mai</RouterLink></li>
                  <li><hr class="dropdown-divider" /></li>
                  <li><button class="dropdown-item text-danger" type="button" @click="handleLogout">Dang xuat</button></li>
                </ul>
              </div>
            </template>

            <RouterLink v-else to="/login" class="pc-auth-link">
              <span class="pc-auth-link__icon"><i class="bi bi-person-circle"></i></span>
              <span>
                <small>Xin chao</small>
                <strong>Dang nhap</strong>
              </span>
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { authState, clearAuthSession, isAdminState, isAuthenticatedState, isSystemUserState } from "../../lib/authStorage";
import { useCustomerStore } from "../../lib/customerStore";

const route = useRoute();
const router = useRouter();
const { cartCount } = useCustomerStore();
const searchQuery = ref(route.query.q || "");
const showMegaMenu = ref(false);
const activeCategory = ref("thuoc");

const categories = [
  {
    id: "thuoc",
    label: "Thuoc",
    items: [
      { label: "Thuoc khong ke don", icon: "bi bi-capsule-pill", tone: "tone-blue" },
      { label: "Thuoc ke don", icon: "bi bi-journal-medical", tone: "tone-green" },
      { label: "Thuoc khac", icon: "bi bi-capsule", tone: "tone-purple" },
      { label: "Vitamin va thuc pham chuc nang", icon: "bi bi-stars", tone: "tone-yellow" },
      { label: "Xem tat ca", icon: "bi bi-arrow-right", tone: "tone-soft" },
    ],
  },
  {
    id: "tra-cuu",
    label: "Tra cuu benh",
    items: [
      { label: "Tai Mui Hong", icon: "bi bi-earbuds", tone: "tone-blue" },
      { label: "Da Toc Mong", icon: "bi bi-droplet-half", tone: "tone-green" },
      { label: "Co Xuong Khop", icon: "bi bi-person-standing", tone: "tone-purple" },
      { label: "Di ung", icon: "bi bi-shield-plus", tone: "tone-yellow" },
      { label: "Xem tat ca", icon: "bi bi-arrow-right", tone: "tone-soft" },
    ],
  },
  {
    id: "me-be",
    label: "Me va Be",
    items: [
      { label: "Sua va dinh duong", icon: "bi bi-heart-pulse", tone: "tone-blue" },
      { label: "Ta bim", icon: "bi bi-bag-heart", tone: "tone-green" },
      { label: "Cham soc be", icon: "bi bi-balloon-heart", tone: "tone-purple" },
      { label: "Cham soc me", icon: "bi bi-flower1", tone: "tone-yellow" },
      { label: "Xem tat ca", icon: "bi bi-arrow-right", tone: "tone-soft" },
    ],
  },
  {
    id: "lam-dep",
    label: "Cham soc sac dep",
    items: [
      { label: "Cham soc da", icon: "bi bi-magic", tone: "tone-blue" },
      { label: "Cham soc toc", icon: "bi bi-brush", tone: "tone-green" },
      { label: "Chong nang", icon: "bi bi-sun", tone: "tone-yellow" },
      { label: "Trang diem", icon: "bi bi-stars", tone: "tone-purple" },
      { label: "Xem tat ca", icon: "bi bi-arrow-right", tone: "tone-soft" },
    ],
  },
];

const loggedIn = isAuthenticatedState;
const isSystemAccount = isSystemUserState;
const isAdminAccount = isAdminState;
const displayName = computed(() => authState.user?.ten_khach_hang || authState.user?.ho_ten || "Khach hang");
const avatarInitials = computed(() => {
  const name = displayName.value.trim();
  return name ? name.slice(0, 2).toUpperCase() : "KH";
});
const activeMegaItems = computed(
  () => categories.find((item) => item.id === activeCategory.value)?.items || categories[0].items
);

watch(
  () => route.fullPath,
  () => {
    showMegaMenu.value = false;
    searchQuery.value = route.query.q || "";
  }
);

function toggleMegaMenu() {
  showMegaMenu.value = !showMegaMenu.value;
}

function closeMegaMenu() {
  showMegaMenu.value = false;
}

function submitSearch() {
  router.push({
    path: "/",
    query: searchQuery.value ? { q: searchQuery.value } : {},
  });
}

function handleLogout() {
  clearAuthSession();
  router.push("/");
}
</script>
