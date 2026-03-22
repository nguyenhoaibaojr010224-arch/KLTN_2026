<template>
  <section class="content-card hero-card">
    <div class="row align-items-center g-4">
      <div class="col-xl-7">
        <div class="hero-card__badge mb-3">
          <i class="bi bi-activity"></i>
          Dashboard tong quan
        </div>
        <h2 class="hero-card__title mb-3">Khung master moi cho toan bo giao dien Pharmacity FE</h2>
        <p class="hero-card__lead mb-4">
          Bo cuc duoc viet lai bang Bootstrap, giu tinh than cua Spike voi sidebar trai, header card,
          footer tach rieng va khu noi dung trung tam cho cac page ke thua.
        </p>

        <div class="d-flex flex-wrap gap-3">
          <span class="master-topstrip__pill">
            <i class="bi bi-layout-sidebar-inset"></i>
            Sidebar co dinh tren desktop
          </span>
          <span class="master-topstrip__pill">
            <i class="bi bi-phone"></i>
            Offcanvas menu tren mobile
          </span>
        </div>
      </div>

      <div class="col-xl-5">
        <div class="master-panel p-4 bg-white bg-opacity-10 border border-white border-opacity-10">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
              <p class="small text-white-50 mb-1">Tien do migration giao dien</p>
              <h3 class="display-6 fw-bold mb-0">68%</h3>
            </div>
            <span class="soft-badge bg-white text-primary">
              <i class="bi bi-arrow-up-right"></i>
              +12% tuan nay
            </span>
          </div>

          <div class="mb-2 d-flex justify-content-between small text-white-50">
            <span>Master layout</span>
            <span>Da san sang</span>
          </div>
          <div class="progress bg-white bg-opacity-25" style="height: 10px">
            <div class="progress-bar bg-info" style="width: 68%"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-md-6 col-xl-3" v-for="item in metrics" :key="item.label">
      <article class="metric-card h-100">
        <div class="d-flex justify-content-between gap-3">
          <div>
            <p class="metric-card__label mb-2">{{ item.label }}</p>
            <h3 class="metric-card__value mb-3">{{ item.value }}</h3>
            <span class="metric-card__delta" :class="item.deltaClass">
              <i :class="item.deltaIcon"></i>
              {{ item.delta }}
            </span>
          </div>
          <div class="metric-card__icon" :class="item.iconClass">
            <i :class="item.icon"></i>
          </div>
        </div>
      </article>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-xl-7">
      <article class="content-card h-100">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
          <div>
            <h3 class="panel-title">Nhip van hanh hom nay</h3>
            <p class="panel-subtitle">Cac moc uu tien duoc dua vao khu noi dung trung tam cua master.</p>
          </div>
          <span class="soft-badge soft-badge--blue">
            <i class="bi bi-lightning-charge-fill"></i>
            Focus mode
          </span>
        </div>

        <div class="timeline-item" v-for="entry in timeline" :key="entry.time + entry.title">
          <div class="timeline-item__time">{{ entry.time }}</div>
          <div class="timeline-item__dot"></div>
          <div>
            <div class="fw-bold mb-1">{{ entry.title }}</div>
            <div class="text-secondary small">{{ entry.desc }}</div>
          </div>
        </div>
      </article>
    </div>

    <div class="col-xl-5">
      <article class="content-card h-100">
        <div class="mb-4">
          <h3 class="panel-title">Ton kho trong diem</h3>
          <p class="panel-subtitle">Mau progress bar dung Bootstrap de test kha nang ke thua UI.</p>
        </div>

        <div v-for="stock in stockStatus" :key="stock.name" class="stock-item">
          <div class="stock-item__meta">
            <span>{{ stock.name }}</span>
            <span>{{ stock.percent }}%</span>
          </div>
          <div class="progress" style="height: 10px">
            <div class="progress-bar" :class="stock.barClass" :style="{ width: `${stock.percent}%` }"></div>
          </div>
          <div class="small text-secondary mt-2">{{ stock.note }}</div>
        </div>
      </article>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-xl-8">
      <article class="content-card h-100">
        <div class="mb-3">
          <h3 class="panel-title">Chi nhanh uu tien xu ly</h3>
          <p class="panel-subtitle">Bang nay giup test table, avatar va spacing trong layout moi.</p>
        </div>

        <div class="table-responsive">
          <table class="table table-master align-middle mb-0">
            <thead>
              <tr>
                <th>Chi nhanh</th>
                <th>Quan ly</th>
                <th>Don dang cho</th>
                <th>Trang thai</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="branch in branches" :key="branch.name">
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <img :src="branch.avatar" :alt="branch.name" class="branch-avatar" />
                    <div>
                      <div class="fw-bold">{{ branch.name }}</div>
                      <div class="small text-secondary">{{ branch.zone }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ branch.manager }}</td>
                <td>{{ branch.orders }}</td>
                <td>
                  <span class="soft-badge" :class="branch.badgeClass">
                    <i :class="branch.badgeIcon"></i>
                    {{ branch.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>

    <div class="col-xl-4">
      <article class="content-card h-100">
        <div class="mb-3">
          <h3 class="panel-title">The hien style tu mau Spike</h3>
          <p class="panel-subtitle">Card san pham dung de kiem tra card component trong master.</p>
        </div>

        <div class="d-flex flex-column gap-3">
          <div v-for="product in products" :key="product.name" class="product-card">
            <img :src="product.image" :alt="product.name" class="product-card__image mb-3" />
            <div class="d-flex align-items-start justify-content-between gap-3">
              <div>
                <h4 class="h6 fw-bold mb-1">{{ product.name }}</h4>
                <p class="small text-secondary mb-2">{{ product.note }}</p>
              </div>
              <span class="soft-badge soft-badge--teal">{{ product.stock }}</span>
            </div>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import productOne from "../assets/spike-tailwind-1.0.0/src/assets/images/products/product-1.jpg";
import productTwo from "../assets/spike-tailwind-1.0.0/src/assets/images/products/product-2.jpg";
import userOne from "../assets/spike-tailwind-1.0.0/src/assets/images/profile/user-1.jpg";
import userTwo from "../assets/spike-tailwind-1.0.0/src/assets/images/profile/user-2.jpg";
import userThree from "../assets/spike-tailwind-1.0.0/src/assets/images/profile/user-3.jpg";

const metrics = [
  {
    label: "Don dang xu ly",
    value: "1,248",
    delta: "+8.4% so voi hom qua",
    deltaClass: "is-positive",
    deltaIcon: "bi bi-arrow-up-right",
    icon: "bi bi-receipt-cutoff",
    iconClass: "metric-card__icon--blue",
  },
  {
    label: "Dung han giao thuoc",
    value: "96.2%",
    delta: "+1.2 diem chat luong",
    deltaClass: "is-positive",
    deltaIcon: "bi bi-arrow-up-right",
    icon: "bi bi-truck",
    iconClass: "metric-card__icon--teal",
  },
  {
    label: "Mat hang can bo sung",
    value: "32",
    delta: "5 mat hang can dat gap",
    deltaClass: "is-warning",
    deltaIcon: "bi bi-exclamation-triangle",
    icon: "bi bi-capsule",
    iconClass: "metric-card__icon--orange",
  },
  {
    label: "Canh bao khieu nai",
    value: "07",
    delta: "Can theo doi sat",
    deltaClass: "is-warning",
    deltaIcon: "bi bi-shield-exclamation",
    icon: "bi bi-heart-pulse",
    iconClass: "metric-card__icon--red",
  },
];

const timeline = [
  {
    time: "08:30",
    title: "Dong bo dashboard ca sang",
    desc: "Header, footer va sidebar da goi dung trong master layout moi.",
  },
  {
    time: "10:00",
    title: "Kiem tra branch uu tien tai TP.HCM",
    desc: "Tap trung vao ton kho va don dang cho o khu vuc trung tam.",
  },
  {
    time: "13:30",
    title: "Ra soat giao dien mobile",
    desc: "Thu menu offcanvas de bao dam page con van ke thua khung chung.",
  },
  {
    time: "16:00",
    title: "Build va xac nhan routing",
    desc: "Dam bao Sample Page va Dashboard cung di qua master.",
  },
];

const stockStatus = [
  {
    name: "Paracetamol 500mg",
    percent: 84,
    note: "Luong ton an toan cho 5 ngay ban hang.",
    barClass: "bg-success",
  },
  {
    name: "Vitamin C 1000mg",
    percent: 58,
    note: "Can bo sung trong 48 gio toi de tranh dut hang.",
    barClass: "bg-info",
  },
  {
    name: "Nuoc rua tay y te",
    percent: 26,
    note: "Canh bao muc ton thap, uu tien dat hang gap.",
    barClass: "bg-warning",
  },
];

const branches = [
  {
    name: "Pharmacity Q1 - Le Loi",
    zone: "Cum trung tam",
    manager: "Nguyen Minh Chau",
    orders: 128,
    status: "On track",
    badgeClass: "soft-badge--teal",
    badgeIcon: "bi bi-check2-circle",
    avatar: userOne,
  },
  {
    name: "Pharmacity Q7 - Nguyen Huu Tho",
    zone: "Cum nam thanh pho",
    manager: "Tran Bao Uyen",
    orders: 94,
    status: "Can bo sung",
    badgeClass: "soft-badge--orange",
    badgeIcon: "bi bi-box-seam",
    avatar: userTwo,
  },
  {
    name: "Pharmacity Thu Duc - Vo Van Ngan",
    zone: "Cum dong thanh pho",
    manager: "Pham Duc Nam",
    orders: 76,
    status: "Can theo doi",
    badgeClass: "soft-badge--blue",
    badgeIcon: "bi bi-eye",
    avatar: userThree,
  },
];

const products = [
  {
    name: "Bo trinh bay san pham OTC",
    note: "Card mau de tham khao cach sap xep module tren page con.",
    stock: "24 item",
    image: productOne,
  },
  {
    name: "Them khu vuc marketing",
    note: "Co the tan dung cho campaign, uu dai hoac banner noi bo.",
    stock: "12 block",
    image: productTwo,
  },
];
</script>
