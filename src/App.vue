<template>
  <component :is="layout">
    <router-view :key="$route.fullPath"></router-view>
  </component>

  <transition name="pc-toast">
    <div v-if="toastState.visible" class="pc-toast" :class="`pc-toast--${toastState.type}`" role="status" aria-live="polite">
      <div class="pc-toast__icon">
        <i :class="toastIcon"></i>
      </div>
      <div class="pc-toast__body">
        <strong>{{ toastTitle }}</strong>
        <span>{{ toastState.message }}</span>
      </div>
      <button class="pc-toast__close" type="button" @click="hideToast" aria-label="Đóng thông báo">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  </transition>
</template>

<script>
import { toastState, hideToast } from "./lib/toast";

const default_layout = "default";

export default {
  setup() {
    return {
      toastState,
      hideToast,
    };
  },
  computed: {
    layout() {
      return (this.$route.meta.layout || default_layout) + "-layout";
    },
    toastTitle() {
      return this.toastState.type === "error" ? "\u004c\u1ed7i" : "Th\u00e0nh c\u00f4ng";
    },
    toastIcon() {
      return this.toastState.type === "error"
        ? "bi bi-exclamation-circle-fill"
        : "bi bi-check-circle-fill";
    },
  },
};
</script>
<style>
  @import url("https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap");

  :root {
    --pc-primary: #1652c5;
    --pc-primary-strong: #0d2b73;
    --pc-secondary: #14b8a6;
    --pc-accent: #ef4444;
    --pc-warning: #f59e0b;
    --pc-surface: #f3f7fd;
    --pc-surface-elevated: rgba(255, 255, 255, 0.94);
    --pc-border: rgba(18, 52, 110, 0.12);
    --pc-text: #183153;
    --pc-text-muted: #6b7a90;
    --pc-shadow: 0 24px 60px rgba(15, 31, 79, 0.12);
    --pc-shadow-soft: 0 14px 30px rgba(15, 31, 79, 0.08);
    --pc-radius-xl: 28px;
    --pc-radius-lg: 22px;
    --pc-radius-md: 16px;
    --pc-radius-pill: 999px;
    --pc-space-1: 4px;
    --pc-space-2: 8px;
    --pc-space-3: 12px;
    --pc-space-4: 16px;
    --pc-space-5: 24px;
    --pc-space-6: 32px;
    --pc-space-7: 40px;
  }

  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  html {
    font-size: 16px;
  }

  body {
    margin: 0;
    min-width: 320px;
    min-height: 100vh;
    font-family: "Be Vietnam Pro", "Segoe UI", sans-serif;
    background:
      radial-gradient(circle at top left, rgba(22, 82, 197, 0.12), transparent 26%),
      radial-gradient(circle at right center, rgba(20, 184, 166, 0.1), transparent 24%),
      var(--pc-surface);
    color: var(--pc-text);
    text-rendering: optimizeLegibility;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
  }

  a {
    color: inherit;
    text-decoration: none;
  }

  button,
  input,
  textarea,
  select {
    font: inherit;
  }

  #app {
    min-height: 100vh;
  }

  .pc-toast {
    position: fixed;
    top: 88px;
    right: 20px;
    z-index: 3000;
    width: min(360px, calc(100vw - 24px));
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 18px;
    box-shadow: 0 18px 40px rgba(12, 32, 74, 0.2);
    color: #fff;
  }

  .pc-toast--success {
    background: linear-gradient(135deg, #0f9f6f, #12b981);
  }

  .pc-toast--error {
    background: linear-gradient(135deg, #dc2626, #ef4444);
  }

  .pc-toast__icon {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.16);
    font-size: 1.15rem;
    flex-shrink: 0;
  }

  .pc-toast__body {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
  }

  .pc-toast__body strong {
    font-size: 0.96rem;
    font-weight: 800;
    line-height: 1.1;
  }

  .pc-toast__body span {
    font-size: 0.9rem;
    line-height: 1.4;
  }

  .pc-toast__close {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    margin-left: auto;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    flex-shrink: 0;
  }

  .pc-toast-enter-active,
  .pc-toast-leave-active {
    transition: opacity 0.22s ease, transform 0.22s ease;
  }

  .pc-toast-enter-from,
  .pc-toast-leave-to {
    opacity: 0;
    transform: translate3d(0, -8px, 0);
  }

  .master-shell {
    min-height: 100vh;
  }

  .master-topstrip {
    position: sticky;
    top: 0;
    z-index: 1040;
    padding: 14px 0;
    background: linear-gradient(90deg, #081a49 0%, #13327d 52%, #1652c5 100%);
    color: #fff;
    box-shadow: 0 10px 24px rgba(8, 26, 73, 0.25);
  }

  .master-topstrip__pill {
    display: inline-flex;
    align-items: center;
    gap: var(--pc-space-2);
    padding: 10px 16px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: var(--pc-radius-pill);
    background: rgba(255, 255, 255, 0.08);
    font-size: 0.875rem;
    font-weight: 500;
  }

  .master-topstrip__right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
  }

  .master-topstrip__notification {
    position: relative;
  }

  .master-topstrip__icon-btn {
    position: relative;
    width: 46px;
    height: 46px;
    display: inline-grid;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    box-shadow: 0 12px 24px rgba(8, 26, 73, 0.16);
  }

  .master-topstrip__badge {
    position: absolute;
    top: -4px;
    right: -3px;
    min-width: 18px;
    height: 18px;
    display: inline-grid;
    place-items: center;
    padding: 0 5px;
    border-radius: 999px;
    background: #ef4444;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 800;
    line-height: 1;
  }

  .master-topstrip__notification-menu {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 360px;
    background: #fff;
    border: 1px solid rgba(20, 63, 148, 0.08);
    border-radius: 20px;
    padding: 16px;
    color: #19335e;
    z-index: 1080;
  }

  .master-topstrip__notification-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
  }

  .master-topstrip__notification-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 420px;
    overflow-y: auto;
  }

  .master-topstrip__notification-item {
    width: 100%;
    text-align: left;
    border: 1px solid rgba(20, 63, 148, 0.08);
    background: #f6f9ff;
    border-radius: 16px;
    padding: 12px 14px;
  }

  .master-topstrip__notification-title {
    font-weight: 800;
    color: #19335e;
  }

  .master-topstrip__notification-copy {
    margin-top: 4px;
    color: #526887;
    font-size: 0.95rem;
  }

  .master-topstrip__notification-meta {
    margin-top: 8px;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    color: #1c4db3;
    font-size: 0.84rem;
    font-weight: 700;
  }

  .master-topstrip__notification-empty {
    color: #6d7f98;
    font-size: 0.95rem;
    padding: 12px 4px;
  }

  .master-topstrip__brand {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    text-decoration: none;
  }

  .master-topstrip__logo-mark {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.08));
    border: 1px solid rgba(255, 255, 255, 0.16);
    font-size: 1.35rem;
    box-shadow: 0 12px 24px rgba(8, 26, 73, 0.22);
  }

  .master-topstrip__logo-text {
    display: flex;
    flex-direction: column;
    line-height: 1.05;
  }

  .master-topstrip__logo-text small {
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    opacity: 0.78;
  }

  .master-topstrip__logo-text strong {
    margin-top: 4px;
    font-size: 1.45rem;
    font-weight: 800;
    color: #9be14a;
  }

  .master-body {
    display: flex;
    gap: var(--pc-space-5);
    padding: var(--pc-space-5);
  }

  .master-sidebar {
    position: sticky;
    top: 86px;
    flex: 0 0 286px;
    align-self: flex-start;
    min-height: calc(100vh - 112px);
    border: 1px solid var(--pc-border);
    border-radius: var(--pc-radius-xl);
    background: var(--pc-surface-elevated);
    box-shadow: var(--pc-shadow);
    overflow: hidden;
    backdrop-filter: blur(16px);
  }

  .master-main {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--pc-space-5);
  }

  .master-header,
  .master-footer,
  .content-card,
  .metric-card,
  .master-panel,
  .promo-card,
  .sidebar-shell {
    border: 1px solid var(--pc-border);
    background: var(--pc-surface-elevated);
    box-shadow: var(--pc-shadow-soft);
  }

  .master-header {
    position: sticky;
    top: 86px;
    z-index: 1030;
    padding: var(--pc-space-5);
    border-radius: var(--pc-radius-xl);
    backdrop-filter: blur(12px);
  }

  .master-header__search {
    min-width: min(100%, 320px);
    border: 1px solid rgba(22, 82, 197, 0.14);
    border-radius: var(--pc-radius-pill);
    background: rgba(22, 82, 197, 0.06);
  }

  .master-header__search .form-control,
  .master-header__search .input-group-text {
    border: 0;
    background: transparent;
    box-shadow: none;
  }

  .master-header__actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .master-header__user-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 10px 8px 8px;
    border: 1px solid rgba(22, 82, 197, 0.1);
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(246, 249, 255, 0.96));
    box-shadow: 0 12px 28px rgba(15, 31, 79, 0.08);
  }

  .master-header__user-meta {
    display: flex;
    flex-direction: column;
    min-width: 132px;
    line-height: 1.2;
  }

  .master-header__user-name {
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--pc-text);
  }

  .master-header__user-role {
    margin-top: 2px;
    font-size: 0.82rem;
    color: var(--pc-text-muted);
  }

  .master-header__logout-btn {
    min-height: 42px;
    padding-inline: 16px;
    border-radius: 14px;
    white-space: nowrap;
    font-weight: 600;
  }

  .master-content {
    display: flex;
    flex-direction: column;
    gap: var(--pc-space-5);
  }

  .master-footer {
    padding: var(--pc-space-4) var(--pc-space-5);
    border-radius: var(--pc-radius-lg);
  }

  .sidebar-shell {
    display: flex;
    flex-direction: column;
    height: 100%;
    border: 0;
    box-shadow: none;
    background: transparent;
  }

  .sidebar-brand {
    padding: var(--pc-space-5);
    border-bottom: 1px solid var(--pc-border);
  }

  .sidebar-brand__mark {
    width: 48px;
    height: 48px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #1652c5, #14b8a6);
    color: #fff;
    font-size: 1.25rem;
    box-shadow: 0 14px 28px rgba(22, 82, 197, 0.24);
  }

  .sidebar-scroll {
    flex: 1;
    overflow: auto;
    padding: var(--pc-space-4);
  }

  .sidebar-caption {
    margin: 0 0 10px;
    color: var(--pc-text-muted);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .sidebar-link {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    border: 0;
    padding: 14px 16px;
    border-radius: 18px;
    background: transparent;
    color: var(--pc-text-muted);
    font-weight: 600;
    text-align: left;
    cursor: pointer;
    transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease;
  }

  .sidebar-link:hover,
  .sidebar-link:focus-visible {
    color: var(--pc-primary);
    background: rgba(22, 82, 197, 0.08);
    transform: translateX(2px);
  }

  .sidebar-link.active {
    color: #fff;
    background: linear-gradient(135deg, #1652c5, #0d2b73);
    box-shadow: 0 18px 28px rgba(22, 82, 197, 0.28);
  }

  .sidebar-link__icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(22, 82, 197, 0.08);
    font-size: 1.15rem;
  }

  .sidebar-link.active .sidebar-link__icon {
    background: rgba(255, 255, 255, 0.18);
  }

  .master-offcanvas {
    width: min(88vw, 320px);
    background: #f9fbff;
  }

  .content-card,
  .metric-card,
  .master-panel,
  .promo-card {
    border-radius: var(--pc-radius-lg);
  }

  .content-card {
    padding: clamp(1.25rem, 2vw, 2rem);
  }

  .hero-card {
    background:
      radial-gradient(circle at top right, rgba(20, 184, 166, 0.18), transparent 34%),
      linear-gradient(135deg, rgba(13, 43, 115, 0.96), rgba(22, 82, 197, 0.92));
    color: #fff;
    overflow: hidden;
  }

  .hero-card__badge {
    display: inline-flex;
    align-items: center;
    gap: var(--pc-space-2);
    padding: 8px 14px;
    border-radius: var(--pc-radius-pill);
    background: rgba(255, 255, 255, 0.14);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .hero-card__title {
    max-width: 14ch;
    margin: 0;
    font-size: clamp(2rem, 3.2vw, 3rem);
    font-weight: 800;
    line-height: 1.05;
  }

  .hero-card__lead {
    max-width: 58ch;
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
  }

  .metric-card {
    padding: 20px;
  }

  .metric-card__icon {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    border-radius: 18px;
    color: #fff;
    font-size: 1.4rem;
  }

  .metric-card__icon--blue {
    background: linear-gradient(135deg, #1652c5, #3b82f6);
  }

  .metric-card__icon--teal {
    background: linear-gradient(135deg, #0891b2, #14b8a6);
  }

  .metric-card__icon--orange {
    background: linear-gradient(135deg, #f59e0b, #f97316);
  }

  .metric-card__icon--red {
    background: linear-gradient(135deg, #ef4444, #fb7185);
  }

  .metric-card__label {
    color: var(--pc-text-muted);
    font-size: 0.875rem;
    font-weight: 600;
  }

  .metric-card__value {
    margin: 0;
    font-size: clamp(1.75rem, 2.1vw, 2.25rem);
    font-weight: 800;
    color: var(--pc-text);
  }

  .metric-card__delta {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: var(--pc-radius-pill);
    font-size: 0.8125rem;
    font-weight: 700;
  }

  .metric-card__delta.is-positive {
    color: #047857;
    background: rgba(16, 185, 129, 0.12);
  }

  .metric-card__delta.is-warning {
    color: #b45309;
    background: rgba(245, 158, 11, 0.14);
  }

  .dashboard-nav-card,
  .dashboard-shortcut {
    width: 100%;
    border: 1px solid var(--pc-border);
    background: #fff;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }

  .dashboard-nav-card {
    padding: 20px;
    border-radius: var(--pc-radius-lg);
    text-align: left;
  }

  .dashboard-nav-card:hover,
  .dashboard-shortcut:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 32px rgba(15, 31, 79, 0.08);
    border-color: rgba(22, 82, 197, 0.25);
  }

  .dashboard-nav-card__eyebrow {
    color: var(--pc-text-muted);
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .dashboard-nav-card__title {
    font-size: 1.35rem;
    font-weight: 800;
  }

  .dashboard-nav-card__copy {
    color: #5b7089;
    min-height: 66px;
  }

  .dashboard-nav-card__cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #1652c5;
    font-weight: 700;
  }

  .dashboard-nav-card__icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    color: #fff;
    flex-shrink: 0;
  }

  .dashboard-shortcut {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 20px;
    border-radius: 20px;
  }

  .dashboard-shortcut__left {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .dashboard-shortcut__left strong,
  .dashboard-shortcut__left small {
    display: block;
    text-align: left;
  }

  .dashboard-shortcut__left small {
    margin-top: 4px;
    color: #60768f;
  }

  .dashboard-shortcut__badge {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    color: #fff;
    flex-shrink: 0;
  }

  .panel-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 800;
  }

  .panel-subtitle {
    margin: 0;
    color: var(--pc-text-muted);
    font-size: 0.9rem;
  }

  .timeline-item {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding: 16px 0;
    border-bottom: 1px dashed rgba(24, 49, 83, 0.12);
  }

  .timeline-item:last-child {
    border-bottom: 0;
    padding-bottom: 0;
  }

  .timeline-item__time {
    min-width: 76px;
    color: var(--pc-text-muted);
    font-size: 0.8125rem;
    font-weight: 700;
  }

  .timeline-item__dot {
    width: 14px;
    height: 14px;
    margin-top: 5px;
    border-radius: 999px;
    background: linear-gradient(135deg, #1652c5, #14b8a6);
    box-shadow: 0 0 0 6px rgba(22, 82, 197, 0.1);
  }

  .stock-item + .stock-item {
    margin-top: 18px;
  }

  .stock-item__meta {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
  }

  .table.table-master {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(22, 82, 197, 0.03);
    --bs-table-hover-bg: rgba(22, 82, 197, 0.06);
    color: var(--pc-text);
  }

  .table-master th {
    color: var(--pc-text-muted);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  .branch-avatar,
  .user-pill__avatar {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    object-fit: cover;
  }

  .product-card {
    padding: 14px;
    border-radius: 20px;
    border: 1px solid rgba(22, 82, 197, 0.08);
    background: rgba(255, 255, 255, 0.8);
  }

  .product-card__image {
    width: 100%;
    height: 168px;
    object-fit: cover;
    border-radius: 18px;
  }

  .soft-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: var(--pc-radius-pill);
    font-size: 0.8125rem;
    font-weight: 700;
  }

  .soft-badge--blue {
    color: var(--pc-primary);
    background: rgba(22, 82, 197, 0.1);
  }

  .soft-badge--teal {
    color: #0f766e;
    background: rgba(20, 184, 166, 0.12);
  }

  .soft-badge--orange {
    color: #b45309;
    background: rgba(245, 158, 11, 0.14);
  }

  .master-empty {
    padding: 28px;
    border-radius: var(--pc-radius-lg);
    border: 1px dashed rgba(22, 82, 197, 0.24);
    background: rgba(22, 82, 197, 0.04);
  }

  .table-active {
    --bs-table-bg: rgba(22, 82, 197, 0.08);
  }

  .page-section-title {
    margin: 0 0 8px;
    font-size: clamp(1.35rem, 2vw, 1.75rem);
    font-weight: 800;
  }

  .page-section-copy {
    margin: 0;
    max-width: 64ch;
    color: var(--pc-text-muted);
  }

  .place-items-center {
    place-items: center;
  }

.auth-switch {
  display: inline-flex;
  padding: 6px;
  border-radius: 999px;
  background: rgba(22, 82, 197, 0.08);
    gap: 6px;
  }

  .auth-switch__button {
    border: 0;
    padding: 10px 18px;
    border-radius: 999px;
    background: transparent;
    color: var(--pc-text-muted);
    font-weight: 700;
  }

  .auth-switch__button.active {
    background: #fff;
    color: var(--pc-primary);
    box-shadow: 0 10px 20px rgba(15, 31, 79, 0.08);
  }

  .customer-home {
    color: var(--pc-text);
    background:
      radial-gradient(circle at top left, rgba(22, 82, 197, 0.12), transparent 24%),
      radial-gradient(circle at right top, rgba(20, 184, 166, 0.12), transparent 20%),
      linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
  }

  .customer-nav {
    position: sticky;
    top: 0;
    z-index: 1040;
    padding: 18px 0;
    backdrop-filter: blur(14px);
    background: rgba(248, 251, 255, 0.86);
    border-bottom: 1px solid rgba(22, 82, 197, 0.08);
  }

  .customer-brand {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    color: var(--pc-text);
  }

  .customer-brand strong,
  .customer-brand small {
    display: block;
  }

  .customer-brand small {
    color: var(--pc-text-muted);
  }

  .customer-brand__mark {
    width: 52px;
    height: 52px;
    display: grid;
    place-items: center;
    border-radius: 18px;
    background: linear-gradient(135deg, #1652c5, #0d2b73);
    color: #fff;
    font-size: 1.35rem;
    box-shadow: 0 16px 28px rgba(22, 82, 197, 0.24);
  }

  .customer-nav__links {
    align-items: center;
    gap: 24px;
  }

  .customer-nav__links a {
    font-weight: 600;
    color: var(--pc-text-muted);
  }

  .customer-nav__links a:hover {
    color: var(--pc-primary);
  }

  .customer-profile-toggle {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid rgba(22, 82, 197, 0.1);
  }

  .customer-profile-toggle__avatar {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(22, 82, 197, 0.08);
    color: var(--pc-primary);
    font-size: 1.15rem;
  }

  .customer-profile-toggle strong,
  .customer-profile-toggle small {
    display: block;
  }

  .customer-profile-toggle small {
    color: var(--pc-text-muted);
  }

  .customer-profile-menu {
    min-width: 250px;
    padding: 10px;
    border: 1px solid rgba(22, 82, 197, 0.1);
    border-radius: 18px;
    box-shadow: 0 18px 36px rgba(15, 31, 79, 0.12);
  }

  .customer-hero {
    padding: 72px 0 48px;
  }

  .customer-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 999px;
    background: rgba(22, 82, 197, 0.08);
    color: var(--pc-primary);
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  .customer-hero__title {
    margin: 0;
    max-width: 14ch;
    font-size: clamp(2.4rem, 4.8vw, 4.8rem);
    font-weight: 800;
    line-height: 1.02;
  }

  .customer-hero__lead {
    margin: 20px 0 0;
    max-width: 60ch;
    color: var(--pc-text-muted);
    font-size: 1.06rem;
  }

  .customer-hero__ghost {
    border: 1px solid rgba(255, 255, 255, 0.45);
  }

  .customer-stat {
    padding: 18px 20px;
    border: 1px solid rgba(22, 82, 197, 0.1);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.8);
    box-shadow: var(--pc-shadow-soft);
  }

  .customer-stat__value {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--pc-primary-strong);
  }

  .customer-stat__label {
    color: var(--pc-text-muted);
    font-size: 0.92rem;
  }

  .hero-showcase {
    position: relative;
    padding: 28px 0;
  }

  .hero-showcase__panel {
    padding: 30px;
    border-radius: 32px;
    box-shadow: var(--pc-shadow);
  }

  .hero-showcase__panel--primary {
    background:
      radial-gradient(circle at top right, rgba(20, 184, 166, 0.18), transparent 24%),
      linear-gradient(145deg, #0d2b73, #1652c5 58%, #14b8a6);
    color: #fff;
  }

  .customer-mini-list {
    display: grid;
    gap: 14px;
  }

  .customer-mini-list__item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
  }

  .customer-mini-list__item i {
    font-size: 1.05rem;
  }

  .customer-mini-list--light .customer-mini-list__item {
    color: #fff;
  }

  .hero-showcase__floating {
    position: absolute;
    padding: 16px 18px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 18px 40px rgba(15, 31, 79, 0.14);
  }

  .hero-showcase__floating strong,
  .hero-showcase__floating span {
    display: block;
  }

  .hero-showcase__floating span {
    margin-top: 4px;
    color: var(--pc-text-muted);
    font-size: 0.9rem;
  }

  .hero-showcase__floating--top {
    top: 0;
    right: -10px;
    width: min(260px, 72%);
  }

  .hero-showcase__floating--bottom {
    left: -10px;
    bottom: 0;
    width: min(260px, 72%);
  }

  .customer-section {
    padding: 56px 0;
  }

  .customer-section--soft {
    background: rgba(255, 255, 255, 0.58);
  }

  .customer-section--accent {
    color: #fff;
    background:
      radial-gradient(circle at top left, rgba(255, 255, 255, 0.08), transparent 20%),
      linear-gradient(135deg, #0d2b73, #1652c5 58%, #14b8a6);
  }

  .customer-section__sticky {
    top: 92px;
  }

  .section-heading {
    max-width: 780px;
    margin: 0 auto 32px;
  }

  .section-title {
    margin: 12px 0 0;
    font-size: clamp(1.9rem, 3vw, 3rem);
    font-weight: 800;
    line-height: 1.08;
  }

  .section-copy {
    margin: 16px 0 0;
    color: var(--pc-text-muted);
    font-size: 1rem;
  }

  .customer-media-card {
    position: relative;
    min-height: 100%;
    overflow: hidden;
    border: 1px solid rgba(22, 82, 197, 0.12);
    border-radius: 32px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(235, 245, 255, 0.92));
    box-shadow: var(--pc-shadow-soft);
  }

  .customer-media-card__accent {
    position: absolute;
    inset: 0 auto 0 0;
    width: 10px;
    background: linear-gradient(180deg, #1652c5, #14b8a6);
  }

  .customer-media-card__content {
    padding: 36px 36px 36px 42px;
  }

  .customer-info-card,
  .service-card,
  .product-focus-card,
  .consult-card,
  .promise-card {
    height: 100%;
    padding: 24px;
    border: 1px solid rgba(22, 82, 197, 0.1);
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: var(--pc-shadow-soft);
  }

  .customer-info-card__icon,
  .service-card__icon,
  .promise-card__icon {
    width: 58px;
    height: 58px;
    display: grid;
    place-items: center;
    margin-bottom: 18px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(22, 82, 197, 0.14), rgba(20, 184, 166, 0.18));
    color: var(--pc-primary);
    font-size: 1.4rem;
  }

  .service-card:hover,
  .product-focus-card:hover,
  .promise-card:hover,
  .customer-info-card:hover {
    transform: translateY(-4px);
    transition: transform 0.25s ease;
  }

  .product-focus-card__count {
    color: var(--pc-text-muted);
    font-size: 0.88rem;
    font-weight: 700;
  }

  .customer-bullet-list {
    display: grid;
    gap: 10px;
    margin: 0;
    padding-left: 18px;
    color: var(--pc-text);
  }

  .customer-searchbar .input-group-text,
  .customer-searchbar .form-control {
    border-color: rgba(22, 82, 197, 0.12);
    background: rgba(255, 255, 255, 0.95);
  }

  .medicine-card {
    display: flex;
    flex-direction: column;
    padding: 24px;
    border: 1px solid rgba(22, 82, 197, 0.1);
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.94);
    box-shadow: var(--pc-shadow-soft);
    cursor: pointer;
    transition: transform 0.24s ease, box-shadow 0.24s ease;
  }

  .medicine-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 22px 40px rgba(15, 31, 79, 0.12);
  }

  .medicine-card__thumb {
    display: flex;
    align-items: end;
    justify-content: space-between;
    min-height: 180px;
    margin-bottom: 20px;
    padding: 18px;
    border-radius: 24px;
    color: #fff;
    overflow: hidden;
  }

  .medicine-card__thumb i {
    font-size: 2rem;
  }

  .medicine-card__thumb span {
    max-width: 12ch;
    text-align: right;
    font-weight: 700;
  }

  .medicine-card__thumb--classic {
    background: linear-gradient(145deg, #1652c5, #0d2b73);
  }

  .medicine-card__thumb--sun {
    background: linear-gradient(145deg, #f59e0b, #f97316);
  }

  .medicine-card__thumb--air {
    background: linear-gradient(145deg, #0891b2, #14b8a6);
  }

  .medicine-card__thumb--care {
    background: linear-gradient(145deg, #ec4899, #fb7185);
  }

  .medicine-card__meta {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    margin-top: auto;
  }

  .medicine-card__label,
  .medicine-detail-card__label {
    display: block;
    margin-bottom: 6px;
    color: var(--pc-text-muted);
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }

  .medicine-modal {
    position: fixed;
    inset: 0;
    z-index: 1080;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgba(8, 26, 73, 0.44);
    backdrop-filter: blur(10px);
  }

  .medicine-modal__dialog {
    position: relative;
    width: min(980px, 100%);
    max-height: calc(100vh - 40px);
    overflow: auto;
    padding: 28px;
    border-radius: 30px;
    background: #fff;
    box-shadow: 0 28px 70px rgba(8, 26, 73, 0.28);
  }

  .medicine-modal__close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 42px;
    height: 42px;
    border: 0;
    border-radius: 999px;
    background: rgba(22, 82, 197, 0.08);
    color: var(--pc-primary);
  }

  .medicine-modal__head {
    display: grid;
    grid-template-columns: minmax(220px, 280px) 1fr;
    gap: 22px;
    align-items: center;
  }

  .medicine-detail-card {
    padding: 20px;
    border: 1px solid rgba(22, 82, 197, 0.08);
    border-radius: 22px;
    background: rgba(243, 247, 253, 0.72);
  }

  .inventory-lot-card {
    padding: 18px;
    border: 1px solid rgba(22, 82, 197, 0.08);
    border-radius: 22px;
    background: rgba(243, 247, 253, 0.78);
  }

  .consult-card {
    background: #fff;
  }

  .consult-card .form-control,
  .consult-card .form-select {
    border-radius: 18px;
    border-color: rgba(22, 82, 197, 0.14);
    background: rgba(243, 247, 253, 0.9);
  }

  .customer-footer {
    padding: 48px 0;
    color: #fff;
    background: #081a49;
  }

  .customer-footer__title {
    margin-bottom: 16px;
    font-size: 1rem;
    font-weight: 800;
  }

  .customer-footer__links {
    display: grid;
    gap: 10px;
    margin: 0;
    padding: 0;
    list-style: none;
    color: rgba(255, 255, 255, 0.72);
  }

  @media (max-width: 991.98px) {
    .master-body {
      display: block;
      padding: 16px;
    }

    .master-main {
      gap: 16px;
    }

    .master-header {
      position: static;
    }

    .hero-card__title {
      max-width: none;
    }

    .customer-hero {
      padding-top: 40px;
    }

    .customer-hero__title {
      max-width: none;
    }

    .hero-showcase__floating {
      position: static;
      width: 100%;
      margin-top: 16px;
    }

    .medicine-modal__head {
      grid-template-columns: 1fr;
    }
  }

  .pc-customer-layout {
    min-height: 100vh;
    background: #f5f7fb;
  }

  .pc-customer-layout__body {
    min-height: calc(100vh - 220px);
  }

  .pc-container {
    width: min(1200px, calc(100vw - 40px));
    margin: 0 auto;
    padding-left: 0;
    padding-right: 0;
  }

.pc-logo--footer .pc-logo__name {
  color: #b5f56b;
}

  .pc-footer {
    padding: 48px 0;
    background: #0f2d73;
    color: #fff;
  }

  .pc-footer__title {
    margin-bottom: 14px;
    font-size: 1rem;
    font-weight: 800;
  }

  .pc-footer__copy {
    max-width: 44ch;
    margin: 18px 0 0;
    color: rgba(255, 255, 255, 0.76);
  }

  .pc-footer__links {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 10px;
    color: rgba(255, 255, 255, 0.78);
  }

.tone-blue {
  background: linear-gradient(145deg, #e9f0ff, #c8dcff);
  color: #1652c5;
}

  .tone-green {
    background: linear-gradient(145deg, #e6fbf6, #c7f2e4);
    color: #0f8f78;
  }

  .tone-purple {
    background: linear-gradient(145deg, #efeafe, #ddd0ff);
    color: #7a53c5;
  }

  .tone-yellow {
    background: linear-gradient(145deg, #fff8db, #ffe7a1);
    color: #b97d00;
  }

  .tone-pink {
    background: linear-gradient(145deg, #ffe7f3, #ffd2ea);
    color: #ce4284;
  }

  .tone-orange {
    background: linear-gradient(145deg, #fff0e1, #ffd7b1);
    color: #d46e11;
  }

  .tone-cream {
    background: linear-gradient(145deg, #fbf7e4, #eef5d9);
    color: #7a8a2f;
  }

  .tone-soft {
    background: linear-gradient(145deg, #f6f8fc, #ebf1fb);
    color: #1652c5;
  }

  .pc-page {
    padding: 24px 0 56px;
  }

  .pc-page__title {
    margin-bottom: 22px;
  }

  .pc-page__title h1 {
    margin: 0;
    font-size: clamp(2rem, 3vw, 2.6rem);
    font-weight: 800;
    color: #203451;
  }

  .pc-homepage {
    padding-bottom: 60px;
  }

  .pc-homepage .pc-container,
  .pc-page .pc-container {
    max-width: 1200px;
  }

  .pc-hero {
    padding: 0 0 28px;
    background: linear-gradient(180deg, #dff0ff 0%, #f5f7fb 72%);
  }

  .pc-hero__banner {
    position: relative;
    min-height: 460px;
    margin-top: 18px;
    padding: 36px 92px;
    border-radius: 0 0 34px 34px;
    overflow: hidden;
    background:
      radial-gradient(circle at 20% 32%, rgba(255, 255, 255, 0.84), transparent 24%),
      radial-gradient(circle at 78% 18%, rgba(255, 255, 255, 0.62), transparent 18%),
      linear-gradient(125deg, #b7dcff 0%, #d2f4ff 38%, #71c4ff 100%);
    transition: background 0.65s ease;
  }

  .pc-hero__banner--sky {
    background:
      radial-gradient(circle at 20% 32%, rgba(255, 255, 255, 0.84), transparent 24%),
      radial-gradient(circle at 78% 18%, rgba(255, 255, 255, 0.62), transparent 18%),
      linear-gradient(125deg, #b7dcff 0%, #d2f4ff 38%, #71c4ff 100%);
  }

  .pc-hero__banner--mint {
    background:
      radial-gradient(circle at 18% 24%, rgba(255, 255, 255, 0.76), transparent 24%),
      radial-gradient(circle at 84% 14%, rgba(255, 255, 255, 0.5), transparent 18%),
      linear-gradient(125deg, #d8fff2 0%, #dff7ff 35%, #8cd6ff 100%);
  }

  .pc-hero__banner--violet {
    background:
      radial-gradient(circle at 16% 26%, rgba(255, 255, 255, 0.7), transparent 24%),
      radial-gradient(circle at 82% 18%, rgba(255, 255, 255, 0.42), transparent 18%),
      linear-gradient(125deg, #d8e1ff 0%, #e8ecff 35%, #8bbcff 100%);
  }

  .pc-hero__banner::after {
    content: "";
    position: absolute;
    inset: auto -80px -80px auto;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.18);
  }

  .pc-slider-button {
    position: absolute;
    top: 50%;
    z-index: 2;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(22, 82, 197, 0.16);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.92);
    color: #456;
    transform: translateY(-50%);
  }

  .pc-slider-button--left {
    left: 22px;
  }

  .pc-slider-button--right {
    right: 22px;
  }

  .pc-hero__content {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 430px;
    gap: 28px;
    align-items: center;
    min-height: 390px;
  }

  .pc-hero__dots {
    position: absolute;
    left: 50%;
    bottom: 22px;
    z-index: 2;
    display: flex;
    gap: 8px;
    transform: translateX(-50%);
  }

  .pc-hero__dot {
    width: 10px;
    height: 10px;
    border: 0;
    border-radius: 999px;
    background: rgba(18, 64, 160, 0.22);
    transition: all 0.24s ease;
  }

  .pc-hero__dot.active {
    width: 28px;
    background: #1652c5;
  }

  .pc-hero__eyebrow {
    margin-bottom: 16px;
    color: #1652c5;
    font-size: 1rem;
    font-weight: 800;
    text-transform: uppercase;
  }

  .pc-hero__copy h1 {
    max-width: 12ch;
    margin: 0;
    color: #1240a0;
    font-size: clamp(2.6rem, 4vw, 4.5rem);
    font-weight: 900;
    line-height: 0.97;
  }

  .pc-hero__copy p {
    max-width: 58ch;
    margin: 18px 0 0;
    color: #33516b;
    font-size: 1.04rem;
  }

  .pc-hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 24px;
  }

  .pc-hero__visual {
    display: grid;
    gap: 18px;
    width: 430px;
    justify-items: end;
    justify-self: end;
    align-content: end;
    min-height: 390px;
  }

  .pc-phone-card {
    width: 300px;
    height: 390px;
    padding: 12px;
    border: 4px solid #164fbc;
    border-radius: 34px;
    background: linear-gradient(180deg, #123b90, #1652c5 50%, #3aa0ff);
    box-shadow: 0 30px 50px rgba(18, 64, 160, 0.24);
    transform: rotate(-8deg);
    transform-origin: center center;
    transition: transform 0.5s ease, box-shadow 0.5s ease, background 0.5s ease;
  }

  .pc-phone-card--blue {
    background: linear-gradient(180deg, #123b90, #1652c5 50%, #3aa0ff);
  }

  .pc-phone-card--violet {
    background: linear-gradient(180deg, #4338ca, #5b5ef7 48%, #5ed4ff);
  }

  .pc-phone-card--cyan {
    background: linear-gradient(180deg, #1452a5, #1f7de2 48%, #42c2ff);
  }

  .pc-phone-card__camera {
    width: 88px;
    height: 12px;
    margin: 0 auto 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.22);
  }

  .pc-phone-card__screen {
    min-height: 100%;
    height: 100%;
    padding: 24px 18px;
    border-radius: 24px;
    background:
      radial-gradient(circle at top left, rgba(255, 255, 255, 0.16), transparent 24%),
      linear-gradient(180deg, #1d62df 0%, #0e3e9f 100%);
    color: #fff;
  }

  .pc-phone-card__badge {
    display: inline-flex;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    font-size: 0.84rem;
    font-weight: 700;
  }

  .pc-phone-card__price {
    margin: 24px 0;
    font-size: 2.6rem;
    font-weight: 900;
  }

  .pc-phone-card__chips {
    display: grid;
    gap: 10px;
  }

  .pc-phone-card__chips span {
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.1);
    font-size: 0.9rem;
  }

  .pc-ticket-stack {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    width: 100%;
    max-width: 420px;
    align-items: stretch;
  }

  .pc-ticket {
    width: 100%;
    min-width: 0;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 18px;
    border-radius: 18px;
    color: #fff;
    font-weight: 800;
    box-shadow: 0 14px 24px rgba(15, 31, 79, 0.14);
    text-align: center;
  }

  .pc-ticket-stack .pc-ticket:last-child {
    grid-column: 2;
  }

  .pc-ticket--green {
    background: linear-gradient(135deg, #10b981, #34d399);
  }

  .pc-ticket--pink {
    background: linear-gradient(135deg, #ff4f9a, #ff7ec2);
  }

  .pc-ticket--purple {
    background: linear-gradient(135deg, #6d5efc, #8c7eff);
  }

  .pc-hero-fade-enter-active,
  .pc-hero-fade-leave-active {
    transition: opacity 0.45s ease, transform 0.45s ease;
  }

  .pc-hero-fade-enter-from,
  .pc-hero-fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
  }

  .pc-ticket--blue {
    background: linear-gradient(135deg, #1652c5, #1f7cff);
  }

  .pc-ticket--cyan {
    background: linear-gradient(135deg, #0aa1ff, #36d9ff);
  }

  .pc-ticket--yellow {
    background: linear-gradient(135deg, #ffb400, #ffdd5d);
    color: #3a2a00;
  }

  .pc-search-panel {
    width: min(800px, calc(100vw - 70px));
    margin: -46px auto 0;
    padding: 10px;
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 18px 42px rgba(15, 31, 79, 0.14);
  }

  .pc-search-panel__box {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 52px;
    padding: 0 16px;
    border: 1px solid rgba(22, 82, 197, 0.14);
    border-radius: 16px;
  }

  .pc-search-panel__box input {
    width: 100%;
    border: 0;
    outline: 0;
  }

  .pc-search-panel__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    padding: 14px 10px 10px;
  }

  .pc-search-panel__tags button {
    border: 0;
    background: transparent;
    color: #54667f;
  }

  .pc-search-panel__actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    border-top: 1px solid rgba(22, 82, 197, 0.08);
  }

  .pc-search-panel__actions button {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 54px;
    padding: 0 10px;
    border: 0;
    background: transparent;
    color: #243b5d;
    font-weight: 700;
  }

  .pc-promo-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-top: 30px;
  }

  .pc-promo-card {
    min-height: 220px;
    display: flex;
    align-items: center;
    padding: 28px 30px;
    border-radius: 26px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(15, 31, 79, 0.12);
  }

  .pc-promo-card h3 {
    max-width: 16ch;
    margin: 10px 0 18px;
    font-size: clamp(1.6rem, 2.2vw, 2.3rem);
    font-weight: 800;
    line-height: 1.08;
  }

  .pc-promo-card__label {
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
  }

  .pc-promo-card--aqua {
    background: linear-gradient(125deg, #9bddff, #d8f4ff 45%, #7ecaff);
  }

  .pc-promo-card--pink {
    background: linear-gradient(125deg, #ffe2f3, #ebf7ff 45%, #ffc4eb);
  }

  .pc-services {
    padding: 18px 0 0;
  }

  .pc-services .pc-container {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 16px;
  }

  .pc-service-chip {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 92px;
    padding: 18px 20px;
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 12px 28px rgba(15, 31, 79, 0.08);
    font-size: 1rem;
    font-weight: 700;
  }

  .pc-service-chip__icon {
    width: 54px;
    height: 54px;
    display: grid;
    place-items: center;
    border-radius: 18px;
    font-size: 1.5rem;
  }

  .pc-services .tone-pink .pc-service-chip__icon {
    background: #ffe6f3;
    color: #ce4284;
  }

  .pc-services .tone-blue .pc-service-chip__icon {
    background: #e8f1ff;
    color: #1652c5;
  }

  .pc-services .tone-green .pc-service-chip__icon {
    background: #e6fbf6;
    color: #0f8f78;
  }

  .pc-services .tone-orange .pc-service-chip__icon {
    background: #fff1e6;
    color: #d46e11;
  }

  .pc-services .tone-cream .pc-service-chip__icon {
    background: #f8f7e7;
    color: #7a8a2f;
  }

  .pc-categories,
  .pc-products {
    padding-top: 34px;
  }

  .pc-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
  }

  .pc-section-head h2 {
    margin: 0;
    font-size: clamp(1.9rem, 2.8vw, 2.6rem);
    font-weight: 800;
    color: #203451;
  }

  .pc-section-head button {
    border: 0;
    background: transparent;
    color: #1a73cf;
    font-weight: 700;
  }

  .pc-category-grid {
    display: grid;
    grid-template-columns: repeat(8, minmax(0, 1fr));
    gap: 16px;
  }

  .pc-category-card {
    min-height: 136px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    padding: 16px 10px;
    border: 1px solid rgba(22, 82, 197, 0.08);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 12px 28px rgba(15, 31, 79, 0.06);
    color: #2a3f5d;
    text-align: center;
  }

  .pc-category-card__icon {
    width: 66px;
    height: 66px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    background: linear-gradient(145deg, #e7f5ff, #d5ecff);
    color: #0f7dde;
    font-size: 1.7rem;
  }

  .pc-catalog-page {
    padding: 20px 0 40px;
    background: linear-gradient(180deg, #f7fbff 0%, #ffffff 45%);
  }

  .pc-catalog-shell {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 18px;
    align-items: start;
  }

  .pc-catalog-sidebar,
  .pc-catalog-content {
    background: #fff;
    border: 1px solid rgba(22, 82, 197, 0.08);
    border-radius: 28px;
    box-shadow: 0 18px 45px rgba(22, 82, 197, 0.08);
  }

  .pc-catalog-sidebar {
    padding: 16px;
    display: grid;
    gap: 8px;
  }

  .pc-catalog-sidebar__item {
    border: 0;
    background: transparent;
    text-align: left;
    padding: 12px 14px;
    border-radius: 14px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #243b5d;
    transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
  }

  .pc-catalog-sidebar__item:hover,
  .pc-catalog-sidebar__item.active {
    background: rgba(22, 82, 197, 0.1);
    color: #1652c5;
    transform: translateX(2px);
  }

  .pc-catalog-content {
    padding: 20px;
  }

  .pc-catalog-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: end;
    margin-bottom: 18px;
  }

  .pc-catalog-head__eyebrow {
    margin: 0 0 8px;
    color: #1652c5;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-size: 0.72rem;
  }

  .pc-catalog-head h1 {
    margin: 0 0 8px;
    font-size: clamp(1.55rem, 2.4vw, 2rem);
    line-height: 1.15;
  }

  .pc-catalog-head p {
    margin: 0;
    max-width: 58ch;
    color: #5a6f89;
    font-size: 0.98rem;
    line-height: 1.55;
  }

  .pc-catalog-head__search {
    min-width: min(100%, 360px);
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    border-radius: 14px;
    border: 1px solid rgba(22, 82, 197, 0.12);
    background: #f8fbff;
  }

  .pc-catalog-head__search input {
    flex: 1;
    border: 0;
    background: transparent;
    outline: 0;
    font-size: 0.95rem;
  }

  .pc-catalog-card-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 20px;
  }

  .pc-catalog-link-card {
    border: 1px solid rgba(22, 82, 197, 0.1);
    background: #fff;
    border-radius: 20px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    text-align: left;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }

  .pc-catalog-link-card:hover,
  .pc-catalog-link-card.active {
    transform: translateY(-3px);
    border-color: rgba(22, 82, 197, 0.2);
    box-shadow: 0 16px 32px rgba(22, 82, 197, 0.1);
  }

  .pc-catalog-link-card__icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    font-size: 1.45rem;
  }

  .pc-catalog-link-card__content {
    display: grid;
    gap: 4px;
  }

  .pc-catalog-link-card__content strong {
    font-size: clamp(0.98rem, 1.5vw, 1.15rem);
    color: #243b5d;
  }

  .pc-catalog-link-card__content small {
    color: #7890aa;
    font-size: 0.82rem;
  }

  .pc-catalog-toolbar {
  margin-bottom: 18px;
}

.pc-catalog-toolbar__intro {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 18px 20px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 22px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.pc-catalog-toolbar__badge {
  display: inline-flex;
  align-self: flex-start;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(22, 82, 197, 0.08);
  color: #1652c5;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.02em;
}

.pc-catalog-toolbar h2 {
  margin: 0;
  font-size: 1.28rem;
}

.pc-catalog-toolbar p {
  margin: 0;
  color: #698099;
  font-size: 0.92rem;
  line-height: 1.6;
}

.pc-catalog-symptoms {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  padding: 18px 20px;
  border: 1px solid rgba(22, 82, 197, 0.08);
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(22, 82, 197, 0.05);
}

.pc-catalog-symptoms__head {
  min-width: 220px;
  max-width: 320px;
}

.pc-catalog-symptoms__head h3 {
  margin: 0 0 6px;
  font-size: 1rem;
  color: #19345f;
}

.pc-catalog-symptoms__head p {
  margin: 0;
  color: #698099;
  font-size: 0.88rem;
  line-height: 1.55;
}

.pc-catalog-symptoms__chips {
  display: flex;
  flex: 1;
  flex-wrap: wrap;
  align-content: flex-start;
  gap: 10px;
}

.pc-catalog-symptoms__chips span {
  display: inline-flex;
  align-items: center;
  min-height: 36px;
  padding: 8px 14px;
  border-radius: 999px;
  background: #eef5ff;
  color: #1652c5;
  font-size: 0.86rem;
  font-weight: 700;
  white-space: nowrap;
}

  .pc-catalog-page .pc-product-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
  }

  .pc-catalog-page .pc-product-card {
    padding: 12px;
    border-radius: 22px;
  }

  .pc-catalog-page .pc-product-card__badge {
    margin: -2px 0 0 -2px;
    padding: 4px 8px;
    font-size: 0.74rem;
    border-radius: 12px;
  }

  .pc-catalog-page .pc-product-card__image {
    min-height: 168px;
    border-radius: 20px;
  }

  .pc-catalog-page .pc-product-card__image i {
    font-size: 2.9rem;
  }

  .pc-catalog-page .pc-product-card__pill {
    right: 8px;
    bottom: 8px;
    padding: 5px 8px;
    font-size: 0.72rem;
    border-radius: 10px;
  }

  .pc-catalog-page .pc-product-card h3 {
    margin: 0 0 6px;
    font-size: 0.94rem;
    line-height: 1.38;
    min-height: 2.6em;
  }

  .pc-catalog-page .pc-product-card p {
    min-height: 48px;
    margin: 0 0 10px;
    font-size: 0.88rem;
    line-height: 1.45;
  }

  .pc-catalog-page .pc-product-card__price strong {
    font-size: 0.98rem;
  }

  .pc-catalog-page .pc-product-card__price span {
    font-size: 0.8rem;
  }

  .pc-catalog-page .pc-product-card__stock {
    margin-top: 6px;
    font-size: 0.84rem;
  }

  .pc-catalog-page .pc-product-card__stock--prescription {
    display: inline-flex;
    align-self: flex-start;
    padding: 8px 12px;
    border-radius: 999px;
    background: #fff1de;
    color: #b85a00;
    font-size: 0.98rem;
    font-weight: 800;
    line-height: 1.25;
  }

  .pc-catalog-page .pc-product-card__actions {
    gap: 8px;
    margin-top: 12px;
  }

  .pc-catalog-page .pc-product-card__actions button {
    min-height: 38px;
    padding: 8px 10px;
    border-radius: 12px;
    font-size: 0.84rem;
    font-weight: 700;
  }

  .pc-banner-strip {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-top: 26px;
    padding: 26px 28px;
    border-radius: 28px;
    background: linear-gradient(125deg, #a9d9ff, #ffe9f4 54%, #d1f7ff);
  }

  .pc-banner-strip__title {
    flex: 1;
    font-size: clamp(1.8rem, 2.5vw, 2.5rem);
    font-weight: 900;
    color: #ff4d8f;
    text-transform: uppercase;
  }

  .pc-banner-strip__tag {
    padding: 14px 24px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.9);
    color: #1b6ed3;
    font-weight: 800;
    text-transform: uppercase;
  }

  .pc-product-section + .pc-product-section {
    margin-top: 44px;
  }

  .pc-product-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
  }

  .pc-product-card {
    display: flex;
    flex-direction: column;
    padding: 16px;
    border: 1px solid rgba(22, 82, 197, 0.08);
    border-radius: 20px;
    background: #fff;
    box-shadow: 0 12px 28px rgba(15, 31, 79, 0.06);
  }

  .pc-product-card__badge {
    position: absolute;
    margin: -4px 0 0 -4px;
    display: inline-flex;
    padding: 6px 10px;
    border-radius: 10px;
    background: #eb3030;
    color: #fff;
    font-size: 0.78rem;
    font-weight: 800;
  }

  .pc-product-card__image {
    position: relative;
    min-height: 210px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    margin-bottom: 14px;
    border-radius: 18px;
    overflow: hidden;
  }

  .pc-product-card__image i {
    font-size: 4rem;
    opacity: 0.88;
  }

  .pc-product-card__pill {
    position: absolute;
    right: 10px;
    bottom: 10px;
    padding: 6px 10px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.92);
    color: #1652c5;
    font-size: 0.78rem;
    font-weight: 800;
  }

  .pc-product-card h3 {
    margin: 0 0 8px;
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.45;
    color: #243b5d;
  }

  .pc-product-card p {
    display: -webkit-box;
    min-height: 66px;
    margin: 0 0 12px;
    color: #6a7e95;
    font-size: 0.92rem;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .pc-product-card__price {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: auto;
  }

  .pc-product-card__price strong {
    color: #16263d;
    font-size: 1.15rem;
  }

  .pc-product-card__price span {
    color: #99a4b2;
    text-decoration: line-through;
  }

  .pc-product-card__stock {
    margin-top: 8px;
    color: #4d6987;
    font-size: 0.9rem;
    font-weight: 600;
  }

  .pc-product-card__stock--prescription {
    display: inline-flex;
    align-self: flex-start;
    padding: 10px 14px;
    border: 1px solid rgba(236, 134, 0, 0.12);
    border-radius: 999px;
    background: linear-gradient(180deg, #fff4e2 0%, #ffedd3 100%);
    color: #b85a00;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: 0.01em;
    line-height: 1.25;
  }

  .pc-product-card__actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 14px;
  }

  .pc-product-card__actions button {
    min-height: 44px;
    border-radius: 14px;
    border: 1px solid rgba(22, 82, 197, 0.22);
    background: #fff;
    color: #1652c5;
    font-weight: 700;
  }

  .pc-product-card__actions button.primary {
    background: #1652c5;
    color: #fff;
  }

  .pc-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1100;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgba(8, 26, 73, 0.46);
  }

  .pc-modal-card {
    position: relative;
    width: min(980px, 100%);
    max-height: calc(100vh - 40px);
    overflow: auto;
    padding: 28px;
    border-radius: 26px;
    background: #fff;
    box-shadow: 0 24px 54px rgba(8, 26, 73, 0.26);
  }

  .pc-modal-card--form {
    width: min(640px, 100%);
  }

  .pc-modal-card__close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 999px;
    background: #f1f5fb;
    color: #29456f;
  }

  .pc-modal-card__head {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 24px;
    margin-bottom: 22px;
  }

  .pc-modal-card__preview {
    min-height: 220px;
    display: grid;
    place-items: center;
    border-radius: 22px;
  }

  .pc-modal-card__preview i {
    font-size: 4.8rem;
  }

  .pc-modal-card__code {
    display: inline-flex;
    margin-bottom: 10px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #edf2ff;
    color: #1652c5;
    font-size: 0.82rem;
    font-weight: 700;
  }

  .pc-modal-card__head h3 {
    margin: 0 0 10px;
    font-size: 2rem;
    font-weight: 800;
  }

  .pc-modal-card__head p {
    margin: 0;
    color: #617892;
  }

  .pc-modal-card__section {
    height: 100%;
    padding: 18px;
    border-radius: 18px;
    background: #f6f8fc;
  }

  .pc-modal-card__label {
    margin-bottom: 10px;
    color: #5a6f89;
    font-size: 0.82rem;
    font-weight: 800;
    text-transform: uppercase;
  }

  .pc-modal-card__list {
    margin: 0;
    padding-left: 18px;
    display: grid;
    gap: 8px;
  }

  .pc-modal-card__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .pc-modal-card__chips span {
    padding: 8px 12px;
    border-radius: 999px;
    background: #e6fbf6;
    color: #0f8f78;
    font-weight: 700;
  }

  .pc-modal-card__actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 22px;
  }

  .pc-empty-card {
    display: grid;
    place-items: center;
    gap: 12px;
    min-height: 260px;
    padding: 32px;
    border: 1px dashed rgba(22, 82, 197, 0.24);
    border-radius: 22px;
    background: #fff;
    color: #243b5d;
    text-align: center;
  }

  .pc-empty-card i {
    font-size: 2.6rem;
    color: #7aa7ea;
  }

  .pc-empty-card h2 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 800;
  }

  .pc-empty-card p {
    max-width: 48ch;
    margin: 0;
    color: #657c95;
  }

  .pc-empty-card--inline {
    min-height: 360px;
  }

  .pc-order-card,
  .pc-summary-card,
  .pc-account-sidebar,
  .pc-account-panel {
    border: 1px solid rgba(22, 82, 197, 0.08);
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 12px 28px rgba(15, 31, 79, 0.06);
  }

  .pc-order-card {
    overflow: hidden;
  }

  .pc-order-card + .pc-order-card {
    margin-top: 20px;
  }

  .pc-order-card__banner {
    min-height: 52px;
    display: grid;
    place-items: center;
    background: #1652c5;
    color: #fff;
    font-weight: 700;
  }

  .pc-order-card__tablehead {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 140px 180px;
    gap: 20px;
    padding: 18px 18px 0;
    color: #54667f;
    font-weight: 700;
  }

  .pc-order-card__producthead {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .pc-order-card__producthead button {
    border: 0;
    background: transparent;
    color: #1a73cf;
    font-weight: 700;
  }

  .pc-check-button {
    border: 0;
    background: transparent;
    color: #1652c5;
    font-size: 1.15rem;
  }

  .pc-cart-item,
  .pc-checkout-item,
  .pc-gift-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 140px 180px;
    gap: 20px;
    align-items: center;
    padding: 18px;
    border-top: 1px solid rgba(22, 82, 197, 0.08);
  }

  .pc-gift-item {
    grid-template-columns: 64px minmax(0, 1fr) 60px 90px;
  }

  .pc-cart-item__main,
  .pc-checkout-item {
    display: grid;
    grid-template-columns: auto 80px minmax(0, 1fr);
    gap: 14px;
  }

  .pc-checkout-item {
    grid-template-columns: 80px minmax(0, 1fr) 60px 110px;
  }

  .pc-cart-item__thumb,
  .pc-checkout-item__thumb,
  .pc-gift-item__thumb {
    border-radius: 18px;
    background: linear-gradient(145deg, #ffe3f1, #f3f8ff);
    overflow: hidden;
    box-shadow: inset 0 0 0 1px rgba(22, 82, 197, 0.08);
  }

  .pc-cart-item__thumb,
  .pc-checkout-item__thumb {
    width: 80px;
    height: 80px;
  }

  .pc-gift-item__thumb {
    width: 64px;
    height: 64px;
  }

  .pc-cart-item__content h3,
  .pc-checkout-item__content h3,
  .pc-gift-item__content h3 {
    margin: 0;
    color: #243b5d;
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.42;
  }

  .pc-cart-item__content p,
  .pc-checkout-item__content p,
  .pc-gift-item__content p {
    margin: 6px 0 0;
    color: #6d8199;
  }

  .pc-checkout-item__content button {
    margin-top: 6px;
    border: 0;
    background: transparent;
    color: #1a73cf;
    font-weight: 700;
    text-align: left;
  }

  .pc-cart-item__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
  }

  .pc-cart-item__tags span {
    padding: 4px 8px;
    border-radius: 8px;
    background: #3fd2b8;
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
  }

  .pc-cart-item__price,
  .pc-checkout-item__price,
  .pc-checkout-item__qty,
  .pc-gift-item__meta {
    color: #243b5d;
    font-weight: 700;
  }

  .pc-cart-item__price strong {
    display: block;
    color: #1c4f9d;
    font-size: 1.1rem;
  }

  .pc-cart-item__price span {
    color: #98a4b2;
    text-decoration: line-through;
  }

  .pc-cart-item__quantity {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .pc-cart-item__quantity > button {
    width: 38px;
    height: 38px;
    border: 1px solid rgba(22, 82, 197, 0.14);
    border-radius: 12px;
    background: #fff;
    color: #243b5d;
    font-size: 1.4rem;
  }

  .pc-cart-item__remove {
    margin-left: 6px;
    color: #9aa8b7;
  }

  .pc-order-card__sectiontitle {
    padding: 18px;
    color: #203451;
    font-size: 1.35rem;
    font-weight: 800;
  }

  .pc-summary-stack {
    display: grid;
    gap: 16px;
  }

  .pc-summary-card {
    padding: 18px;
  }

  .pc-summary-card h2 {
    margin: 0 0 14px;
    font-size: 1.1rem;
    font-weight: 800;
    color: #243b5d;
  }

  .pc-summary-card__voucher,
  .pc-summary-card__line,
  .pc-checkout-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .pc-summary-card__voucher {
    padding: 14px 0;
    border-top: 1px solid rgba(22, 82, 197, 0.08);
    border-bottom: 1px solid rgba(22, 82, 197, 0.08);
    color: #243b5d;
    font-weight: 700;
  }

  .pc-summary-card__voucher button,
  .pc-summary-card__line button,
  .pc-checkout-row button,
  .pc-link-button {
    border: 0;
    background: transparent;
    color: #1a73cf;
    font-weight: 700;
  }

  .pc-summary-card__line {
    padding: 10px 0;
    color: #53667e;
  }

  .pc-summary-card__total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-top: 14px;
    border-top: 1px solid rgba(22, 82, 197, 0.08);
    color: #203451;
  }

  .pc-summary-card__total strong {
    font-size: 1.8rem;
    font-weight: 900;
  }

  .pc-summary-card__agree {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 18px 0;
    color: #4d6787;
  }

  .pc-delivery-switch {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
    padding: 4px;
    border-radius: 16px;
    background: #f1f5fb;
  }

  .pc-delivery-switch button {
    min-height: 44px;
    border: 0;
    border-radius: 12px;
    background: transparent;
    color: #96a2b4;
    font-weight: 700;
  }

  .pc-delivery-switch button.active {
    background: #fff;
    color: #243b5d;
  }

  .pc-address-button {
    width: 100%;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border: 1px solid rgba(22, 82, 197, 0.18);
    border-radius: 16px;
    background: #fff;
    color: #1c4f9d;
    font-weight: 700;
  }

  .pc-address-preview {
    margin-top: 14px;
    padding: 16px;
    border-radius: 16px;
    background: #f5f8fe;
  }

  .pc-address-preview p {
    margin: 6px 0 0;
    color: #647993;
  }

  .pc-checkout-row {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(22, 82, 197, 0.08);
    color: #243b5d;
    font-weight: 700;
  }

  .pc-checkout-note {
    margin-top: 18px;
  }

  .pc-checkout-note label {
    display: block;
    margin-bottom: 8px;
    color: #203451;
    font-weight: 700;
  }

  .pc-checkout-note textarea,
  .pc-form-field input,
  .pc-form-field select {
    width: 100%;
    min-height: 48px;
    padding: 0 16px;
    border: 1px solid rgba(22, 82, 197, 0.12);
    border-radius: 14px;
    background: #fff;
    outline: 0;
  }

  .pc-checkout-note textarea {
    min-height: 96px;
    padding-top: 14px;
    resize: vertical;
  }

  .pc-payment-method {
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 78px;
    padding: 14px 2px;
    border-top: 1px solid rgba(22, 82, 197, 0.08);
    color: #203451;
    font-size: 1.1rem;
    font-weight: 700;
  }

  .pc-payment-method__logo {
    min-width: 52px;
    padding: 10px 12px;
    border-radius: 12px;
    background: #f5f8fe;
    color: #1c4f9d;
    font-size: 0.95rem;
    text-align: center;
  }

  .pc-account-sidebar {
    padding: 18px;
  }

  .pc-account-profile {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
  }

  .pc-account-profile__avatar,
  .pc-account-form__avatar {
    width: 64px;
    height: 64px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    background: #1652c5;
    color: #fff;
    font-size: 1.25rem;
    font-weight: 800;
  }

  .pc-account-profile h3 {
    margin: 0 0 6px;
    color: #203451;
    font-size: 1.25rem;
    font-weight: 800;
  }

  .pc-account-profile__points {
    display: inline-flex;
    padding: 8px 12px;
    border-radius: 10px;
    background: linear-gradient(135deg, #b25a00, #ffb031);
    color: #fff;
    font-weight: 700;
  }

  .pc-rank-card {
    margin-bottom: 18px;
    padding: 18px;
    border-radius: 20px;
    background: linear-gradient(135deg, #d39a00, #ffd85a);
    color: #fff;
  }

  .pc-rank-card__title {
    font-size: 1.4rem;
    font-weight: 800;
  }

  .pc-rank-card__bar {
    height: 12px;
    margin: 16px 0 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.36);
    overflow: hidden;
  }

  .pc-rank-card__bar span {
    display: block;
    width: 82%;
    height: 100%;
    border-radius: 999px;
    background: #fff;
  }

  .pc-rank-card p {
    margin: 0;
    font-size: 0.9rem;
  }

  .pc-account-nav {
    display: grid;
    gap: 4px;
  }

  .pc-account-nav__item {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 52px;
    padding: 0 14px;
    border-radius: 14px;
    color: #243b5d;
    font-weight: 700;
    text-decoration: none !important;
  }

  .pc-account-nav__item.router-link-active {
    background: #eef3ff;
    color: #1652c5;
  }

  .pc-account-panel {
    min-height: 760px;
    padding: 24px;
  }

  .pc-account-panel h1 {
    margin: 0 0 22px;
    color: #203451;
    font-size: 2rem;
    font-weight: 800;
  }

  .pc-account-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
  }

  .pc-account-form__avatar {
    margin: 0 auto 22px;
  }

  .pc-account-form {
    max-width: 540px;
    margin: 0 auto;
  }

  .pc-form-field + .pc-form-field {
    margin-top: 16px;
  }

  .pc-form-field label {
    display: block;
    margin-bottom: 8px;
    color: #203451;
    font-weight: 700;
  }

  .pc-password-row {
    display: flex;
    gap: 12px;
  }

  .pc-password-row button {
    min-width: 130px;
    border: 1px solid rgba(22, 82, 197, 0.14);
    border-radius: 14px;
    background: #fff;
    color: #1c4f9d;
    font-weight: 700;
  }

  .pc-account-panel__actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 22px;
  }

  .pc-address-list,
  .pc-history-list,
  .pc-notification-list {
    display: grid;
    gap: 16px;
  }

  .pc-address-card,
  .pc-history-card,
  .pc-notification-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 18px;
    border: 1px solid rgba(22, 82, 197, 0.08);
    border-radius: 18px;
    background: #fff;
  }

  .pc-address-card h3,
  .pc-history-card p,
  .pc-notification-card p {
    margin: 0;
  }

  .pc-address-card p,
  .pc-history-card p,
  .pc-notification-card p {
    margin-top: 6px;
    color: #667b94;
  }

  .pc-address-card__tag,
  .pc-history-card__status,
  .pc-notification-card__badge {
    display: inline-flex;
    padding: 8px 12px;
    border-radius: 999px;
    background: #edf2ff;
    color: #1652c5;
    font-size: 0.84rem;
    font-weight: 700;
  }

  .pc-notification-card__badge.read {
    background: #f1f5fb;
    color: #8392a4;
  }

  .pc-tabline {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 26px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(22, 82, 197, 0.08);
  }

  .pc-tabline button {
    border: 0;
    background: transparent;
    color: #576b85;
    font-size: 1.02rem;
    font-weight: 700;
  }

  .pc-tabline button.active {
    color: #1652c5;
  }

  .pc-address-form {
    display: grid;
    gap: 16px;
    margin-top: 18px;
  }

  .pc-address-form__row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  .pc-pill-switch {
    display: flex;
    gap: 12px;
  }

  .pc-pill-switch button {
    min-width: 120px;
    min-height: 44px;
    border: 1px solid rgba(22, 82, 197, 0.12);
    border-radius: 999px;
    background: #fff;
    color: #334d6d;
    font-weight: 700;
  }

  .pc-pill-switch button.active {
    background: #edf2ff;
    color: #1652c5;
  }

  .pc-checkbox-line {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #576b85;
  }

  @media (max-width: 1199.98px) {
  .pc-services .pc-container,
  .pc-product-grid,
  .pc-category-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  @media (max-width: 991.98px) {
    .master-topstrip__right {
      justify-content: space-between;
    }

    .master-topstrip__notification-menu {
      width: min(360px, calc(100vw - 32px));
    }

    .master-header__actions {
      justify-content: stretch;
    }

    .master-header__user-card {
      width: 100%;
    }

    .master-header__user-card {
      justify-content: space-between;
    }
  }

  .pc-hero__content,
  .pc-promo-row {
    grid-template-columns: 1fr;
  }

  .pc-hero__banner {
    padding: 28px 24px 92px;
  }

    .pc-search-panel {
      width: calc(100% - 24px);
      margin-top: -62px;
    }

    .pc-order-card__tablehead,
    .pc-cart-item,
    .pc-checkout-item {
      grid-template-columns: 1fr;
    }
  }

@media (max-width: 767.98px) {
  .pc-container {
    width: min(100vw - 20px, 100%);
  }

  .pc-search-panel__actions,
  .pc-services .pc-container,
  .pc-category-grid,
  .pc-product-grid,
    .pc-promo-row,
    .pc-address-form__row,
    .pc-modal-card__head {
      grid-template-columns: 1fr;
      display: grid;
    }

  .pc-hero__copy h1 {
    max-width: none;
  }

    .pc-phone-card {
      transform: none;
    }

    .pc-ticket-stack,
    .pc-hero__actions,
    .pc-modal-card__actions,
    .pc-account-panel__head {
      justify-content: stretch;
    }

    .pc-banner-strip {
      flex-direction: column;
      align-items: stretch;
    }

    .pc-cart-item__main,
    .pc-checkout-item,
    .pc-gift-item,
    .pc-address-card,
    .pc-history-card,
    .pc-notification-card {
      grid-template-columns: 1fr;
      display: grid;
    }

    .pc-cart-item__quantity {
      justify-content: flex-start;
    }

  .pc-modal-card {
    padding: 20px;
  }
}

  @media (prefers-reduced-motion: reduce) {
    * {
      animation: none !important;
      transition: none !important;
      scroll-behavior: auto !important;
    }
  }

</style>


