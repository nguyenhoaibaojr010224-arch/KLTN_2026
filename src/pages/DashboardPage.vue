<template>
  <section class="content-card hero-card">
    <div class="row align-items-center g-4">
      <div class="col-xl-7">
        <div class="hero-card__badge mb-3">
          <i class="bi bi-activity"></i>
          Dashboard tong quan
        </div>
        <h2 class="hero-card__title mb-3">Trung tam dieu huong cho admin va nhan vien</h2>
        <p class="hero-card__lead mb-4">
          Sau khi dang nhap bang tai khoan he thong, ban co the bam truc tiep vao tung o chuc nang de mo trang
          Nhan vien, Hoa don, Ton kho, Gia va khuyen mai.
        </p>

        <div class="d-flex flex-wrap gap-3">
          <span class="master-topstrip__pill">
            <i class="bi bi-person-badge"></i>
            Quyen hien tai: {{ roleLabel }}
          </span>
          <span class="master-topstrip__pill">
            <i class="bi bi-box-arrow-up-right"></i>
            Click tung card de chuyen trang
          </span>
        </div>
      </div>

      <div class="col-xl-5">
        <div class="master-panel p-4 bg-white bg-opacity-10 border border-white border-opacity-10">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
              <p class="small text-white-50 mb-1">Tai khoan dang su dung</p>
              <h3 class="display-6 fw-bold mb-0">{{ currentUserName }}</h3>
            </div>
            <span class="soft-badge bg-white text-primary">
              <i class="bi bi-shield-check"></i>
              Da xac thuc
            </span>
          </div>

          <div class="mb-2 d-flex justify-content-between small text-white-50">
            <span>Dieu huong he thong</span>
            <span>San sang</span>
          </div>
          <div class="progress bg-white bg-opacity-25" style="height: 10px">
            <div class="progress-bar bg-info" style="width: 100%"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-md-6 col-xl-3" v-for="item in quickActions" :key="item.to">
      <button type="button" class="dashboard-nav-card h-100" @click="goTo(item.to)">
        <div class="d-flex justify-content-between gap-3 align-items-start">
          <div>
            <p class="dashboard-nav-card__eyebrow mb-2">{{ item.eyebrow }}</p>
            <h3 class="dashboard-nav-card__title mb-2">{{ item.label }}</h3>
            <p class="dashboard-nav-card__copy mb-3">{{ item.caption }}</p>
            <span class="dashboard-nav-card__cta">
              Mo trang
              <i class="bi bi-arrow-right"></i>
            </span>
          </div>
          <span class="dashboard-nav-card__icon" :class="item.iconClass">
            <i :class="item.icon"></i>
          </span>
        </div>
      </button>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-xl-7">
      <article class="content-card h-100">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
          <div>
            <h3 class="panel-title">Nhip van hanh hom nay</h3>
            <p class="panel-subtitle">Cac dau viec chinh de doi admin va nhan vien xu ly trong ngay.</p>
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
          <p class="panel-subtitle">Nhanh tay bam vao card Ton kho o tren de xem chi tiet lo thuoc.</p>
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
          <h3 class="panel-title">Loi tat he thong</h3>
          <p class="panel-subtitle">Bam vao tung dong de di thang den man hinh can lam viec.</p>
        </div>

        <div class="d-grid gap-3">
          <button
            v-for="item in quickActions"
            :key="`${item.to}-list`"
            type="button"
            class="dashboard-shortcut"
            @click="goTo(item.to)"
          >
            <span class="dashboard-shortcut__left">
              <span class="dashboard-shortcut__badge" :class="item.iconClass">
                <i :class="item.icon"></i>
              </span>
              <span>
                <strong>{{ item.label }}</strong>
                <small>{{ item.caption }}</small>
              </span>
            </span>
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </article>
    </div>

    <div class="col-xl-4">
      <article class="content-card h-100">
        <div class="mb-3">
          <h3 class="panel-title">Goi y thao tac</h3>
          <p class="panel-subtitle">Neu can quay ra khu ban hang, dung nut Ve trang chu tren header.</p>
        </div>

        <div class="d-flex flex-column gap-3">
          <div v-for="tip in tips" :key="tip.title" class="product-card">
            <div class="d-flex align-items-start justify-content-between gap-3">
              <div>
                <h4 class="h6 fw-bold mb-1">{{ tip.title }}</h4>
                <p class="small text-secondary mb-0">{{ tip.note }}</p>
              </div>
              <span class="soft-badge soft-badge--teal">{{ tip.tag }}</span>
            </div>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { authState, isAdminState } from "../lib/authStorage";

const router = useRouter();

const currentUserName = computed(() => authState.user?.ho_ten || authState.user?.ten_khach_hang || "Tai khoan he thong");

const roleLabel = computed(() => {
  if (authState.type === "admin") {
    return "Admin";
  }

  if (["staff", "nhan_vien", "nhanvien"].includes(authState.type)) {
    return "Nhan vien";
  }

  return "Tai khoan";
});

const quickActions = computed(() => [
  ...(isAdminState.value
    ? [
        {
          to: "/nhan-viens",
          eyebrow: "Quan tri nhan su",
          label: "Nhan vien",
          caption: "Xem danh sach, tao moi, cap nhat va doi mat khau nhan vien.",
          icon: "bi bi-people",
          iconClass: "metric-card__icon--blue",
        },
      ]
    : []),
  {
    to: "/hoa-dons",
    eyebrow: "Ban hang",
    label: "Hoa don",
    caption: "Theo doi hoa don, doanh thu va lich su giao dich.",
    icon: "bi bi-receipt-cutoff",
    iconClass: "metric-card__icon--teal",
  },
  {
    to: "/ton-kho",
    eyebrow: "Kho duoc",
    label: "Ton kho",
    caption: "Kiem tra ton, lo thuoc, han su dung va gia nhap.",
    icon: "bi bi-box-seam",
    iconClass: "metric-card__icon--orange",
  },
  {
    to: "/gia-khuyen-mai",
    eyebrow: "Kinh doanh",
    label: "Gia va khuyen mai",
    caption: "Cap nhat gia ban va chuong trinh uu dai cho tung thuoc.",
    icon: "bi bi-tags",
    iconClass: "metric-card__icon--red",
  },
]);

const timeline = [
  {
    time: "08:30",
    title: "Ra soat nhan vien dang hoat dong",
    desc: "Vao module Nhan vien de kiem tra tai khoan moi, khoa tai khoan va doi mat khau khi can.",
  },
  {
    time: "10:00",
    title: "Theo doi hoa don va doanh thu",
    desc: "Mo module Hoa don de kiem tra doanh thu trong ngay va don dang xu ly.",
  },
  {
    time: "13:30",
    title: "Kiem tra ton kho va lo thuoc",
    desc: "Nhan vien kho co the mo ngay module Ton kho de xem ton va han su dung.",
  },
  {
    time: "16:00",
    title: "Cap nhat gia va uu dai",
    desc: "Neu co chuong trinh moi, vao Gia va khuyen mai de chinh sua ngay tren he thong.",
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

const tips = [
  {
    title: "Nhan vien khong mo duoc module",
    note: "Kiem tra lai role tra ve tu login va token dang luu trong frontend.",
    tag: "Quyen",
  },
  {
    title: "Can quay lai trang chu ban hang",
    note: "Su dung nut Ve trang chu o header quan tri, khong can dang xuat.",
    tag: "Luot di",
  },
  {
    title: "Can vao nhanh trang quan ly",
    note: "Bam truc tiep vao cac card o dau dashboard hoac menu trai.",
    tag: "Dieu huong",
  },
];

function goTo(path) {
  router.push(path);
}
</script>
