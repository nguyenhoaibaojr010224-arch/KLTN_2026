<template>
  <div class="master-topstrip">
    <div class="container-fluid px-3 px-lg-4">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3">
          <span class="master-topstrip__badge">Master Layout Bootstrap</span>
          <span class="master-topstrip__pill">
            <i class="bi bi-stars"></i>
            Lay cam hung tu Spike Tailwind 1.0.0, duoc viet lai cho Vue 3.
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
