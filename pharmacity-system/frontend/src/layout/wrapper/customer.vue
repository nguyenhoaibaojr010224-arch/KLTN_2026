<template>
  <div class="pc-customer-layout">
    <CustomerSiteHeader />
    <main class="pc-customer-layout__body">
      <slot />
    </main>
    <CustomerSiteFooter v-if="!route.meta.hideCustomerFooter" />
  </div>
</template>

<script setup>
import { useRoute } from "vue-router";
import CustomerSiteFooter from "../components/CustomerSiteFooter.vue";
import CustomerSiteHeader from "../components/CustomerSiteHeader.vue";

const route = useRoute();
</script>
