<template>
  <section class="content-card">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--blue mb-3">
          <i class="bi bi-diagram-3"></i>
          Test page ke thua
        </div>
        <h2 class="page-section-title">Trang nay chi render phan content, phan khung duoc master lo</h2>
        <p class="page-section-copy">
          Day la cach de cac page moi sau nay khong phai viet lai menu, header hay footer. Chi can khai
          bao route co `meta.layout = "default"` la page se di qua master layout.
        </p>
      </div>

      <RouterLink to="/" class="btn btn-primary btn-lg rounded-pill px-4">
        Quay lai dashboard
      </RouterLink>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-lg-4" v-for="block in blocks" :key="block.title">
      <article class="content-card h-100">
        <div class="soft-badge mb-3" :class="block.badgeClass">
          <i :class="block.icon"></i>
          {{ block.badge }}
        </div>
        <h3 class="h5 fw-bold mb-3">{{ block.title }}</h3>
        <p class="text-secondary mb-0">{{ block.copy }}</p>
      </article>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-xl-7">
      <article class="content-card h-100">
        <h3 class="panel-title mb-2">Checklist test master layout</h3>
        <p class="panel-subtitle mb-4">Neu thay cac muc ben duoi hien thi dung, master layout da hoat dong.</p>

        <div class="vstack gap-3">
          <div v-for="item in checklist" :key="item" class="d-flex align-items-start gap-3">
            <span class="soft-badge soft-badge--teal">
              <i class="bi bi-check2"></i>
            </span>
            <div class="pt-1">{{ item }}</div>
          </div>
        </div>
      </article>
    </div>

    <div class="col-xl-5">
      <article class="content-card h-100">
        <h3 class="panel-title mb-2">Trang con co the tu do sap noi dung</h3>
        <p class="panel-subtitle mb-4">
          Vi du card nay chi thuoc ve page hien tai, nhung van nam trong khung master thong nhat.
        </p>

        <div class="master-empty">
          <div class="d-flex align-items-center gap-3 mb-3">
            <span class="soft-badge soft-badge--orange">
              <i class="bi bi-tools"></i>
              Reusable shell
            </span>
            <span class="soft-badge soft-badge--blue">
              <i class="bi bi-bootstrap-fill"></i>
              Bootstrap-ready
            </span>
          </div>
          <p class="mb-0 text-secondary">
            Buoc tiep theo chung ta co the dua tung trang nghiep vu vao khung nay va thay doi content ma
            khong can cham lai bo menu/header/footer.
          </p>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
const blocks = [
  {
    badge: "Menu",
    title: "Sidebar dung chung",
    copy: "Tat ca link dieu huong duoc tap trung tai mot noi de mo rong sau nay.",
    icon: "bi bi-layout-sidebar",
    badgeClass: "soft-badge--blue",
  },
  {
    badge: "Header",
    title: "Tieu de dong theo route",
    copy: "Header tu doc title va subtitle tu meta cua route, nen moi page co context rieng.",
    icon: "bi bi-window-fullscreen",
    badgeClass: "soft-badge--teal",
  },
  {
    badge: "Footer",
    title: "Thong diep dung chung",
    copy: "Footer luon hien cuoi page va giu thong diep thong nhat cho ca he thong.",
    icon: "bi bi-align-bottom",
    badgeClass: "soft-badge--orange",
  },
];

const checklist = [
  "Menu ben trai hien dung tren desktop va mo bang nut hamburger tren mobile.",
  "Header thay doi theo route Sample Page thay vi Dashboard.",
  "Footer van giu nguyen khi chuyen giua cac route.",
  "Noi dung page nay khong phai viet lai phan khung dung chung.",
];
</script>
