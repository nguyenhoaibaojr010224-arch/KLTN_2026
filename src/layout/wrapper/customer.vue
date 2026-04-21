<template>
  <div class="pc-customer-layout">
    <CustomerSiteHeader />
    <main class="pc-customer-layout__body">
      <slot />
    </main>
    <CustomerSiteFooter v-if="!route.meta.hideCustomerFooter" />
    <CustomerSupportWidget v-if="!route.meta.hideCustomerChat" />
  </div>
</template>

<script setup>
import { useRoute } from "vue-router";
import CustomerSupportWidget from "../../components/Client/HoTroChatbox/index.vue";
import CustomerSiteFooter from "../components/CustomerSiteFooter.vue";
import CustomerSiteHeader from "../components/CustomerSiteHeader.vue";

const route = useRoute();
</script>
