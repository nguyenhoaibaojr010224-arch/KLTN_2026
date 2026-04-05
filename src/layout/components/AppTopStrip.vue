<template>
  <div class="master-topstrip">
    <div class="container-fluid px-3 px-lg-4">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3">
          <RouterLink to="/" class="master-topstrip__brand">
            <span class="master-topstrip__logo-mark">
              <i class="bi bi-capsule-pill"></i>
            </span>
            <span class="master-topstrip__logo-text">
              <small>NHÀ THUỐC</small>
              <strong>PharmaGo</strong>
            </span>
          </RouterLink>

          <span class="master-topstrip__pill">
            <i class="bi bi-heart-pulse"></i>
            Quản trị thuốc, tồn kho và đơn hàng tập trung trên một màn hình.
          </span>
        </div>

        <div class="master-topstrip__pill">
          <i class="bi bi-calendar-event"></i>
          {{ todayLabel }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const todayLabel = new Intl.DateTimeFormat("vi-VN", {
  weekday: "long",
  day: "2-digit",
  month: "2-digit",
  year: "numeric",
}).format(new Date());
</script>
