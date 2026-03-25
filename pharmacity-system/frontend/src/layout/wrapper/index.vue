<template>
  <div class="master-shell">
    <AppTopStrip />

    <div class="master-body">
      <aside class="master-sidebar d-none d-lg-flex">
        <AppSidebar />
      </aside>

      <div class="master-main">
        <AppHeader />

        <main class="master-content">
          <slot />
        </main>

        <AppFooter />
      </div>
    </div>

    <div
      class="offcanvas offcanvas-start master-offcanvas d-lg-none"
      tabindex="-1"
      id="masterSidebar"
      aria-labelledby="masterSidebarLabel"
    >
      <AppSidebar mobile />
    </div>
  </div>
</template>

<script setup>
import AppFooter from "../components/AppFooter.vue";
import AppHeader from "../components/AppHeader.vue";
import AppSidebar from "../components/AppSidebar.vue";
import AppTopStrip from "../components/AppTopStrip.vue";
</script>
