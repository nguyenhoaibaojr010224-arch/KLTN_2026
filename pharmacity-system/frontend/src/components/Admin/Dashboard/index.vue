<template>
  <section class="content-card hero-card">
    <div class="row align-items-center g-4">
      <div class="col-xl-7">
        <div class="hero-card__badge mb-3">
          <i class="bi bi-activity"></i>
          Dashboard tổng quan
        </div>
        <h2 class="hero-card__title mb-3">Trung tâm điều hướng cho admin và nhân viên</h2>
        <p class="hero-card__lead mb-4">
          Sau khi đăng nhập bằng tài khoản hệ thống, bạn có thể bấm trực tiếp vào từng ô chức năng để mở trang
          Nhân viên, Hóa đơn, Tồn kho, Giá và khuyến mãi.
        </p>

        <div class="d-flex flex-wrap gap-3">
          <span class="master-topstrip__pill">
            <i class="bi bi-person-badge"></i>
            Quyền hiện tại: {{ roleLabel }}
          </span>
          <span class="master-topstrip__pill">
            <i class="bi bi-box-arrow-up-right"></i>
            Click từng card để chuyển trang
          </span>
        </div>
      </div>

      <div class="col-xl-5">
        <div class="master-panel p-4 bg-white bg-opacity-10 border border-white border-opacity-10">
          <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
              <p class="small text-white-50 mb-1">Tài khoản đang sử dụng</p>
              <h3 class="display-6 fw-bold mb-0">{{ currentUserName }}</h3>
            </div>
            <span class="soft-badge bg-white text-primary">
              <i class="bi bi-shield-check"></i>
              Đã xác thực
            </span>
          </div>

          <div class="mb-2 d-flex justify-content-between small text-white-50">
            <span>Điều hướng hệ thống</span>
            <span>Sẵn sàng</span>
          </div>
          <div class="progress bg-white bg-opacity-25" style="height: 10px">
            <div class="progress-bar bg-info" style="width: 100%"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="row g-4">
    <div v-for="item in quickActions" :key="item.to" class="col-md-6 col-xl-3">
      <button type="button" class="dashboard-nav-card h-100" @click="goTo(item.to)">
        <div class="d-flex justify-content-between gap-3 align-items-start">
          <div>
            <p class="dashboard-nav-card__eyebrow mb-2">{{ item.eyebrow }}</p>
            <h3 class="dashboard-nav-card__title mb-2">{{ item.label }}</h3>
            <p class="dashboard-nav-card__copy mb-3">{{ item.caption }}</p>
            <span class="dashboard-nav-card__cta">
              Mở trang
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
            <h3 class="panel-title">Nhịp vận hành hôm nay</h3>
            <p class="panel-subtitle">Các đầu việc chính để đội admin và nhân viên xử lý trong ngày.</p>
          </div>
          <span class="soft-badge soft-badge--blue">
            <i class="bi bi-lightning-charge-fill"></i>
            Focus mode
          </span>
        </div>

        <div v-for="entry in timeline" :key="entry.time + entry.title" class="timeline-item">
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
          <h3 class="panel-title">Tồn kho trọng điểm</h3>
          <p class="panel-subtitle">Nhanh tay bấm vào card Tồn kho ở trên để xem chi tiết lô thuốc.</p>
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
          <h3 class="panel-title">Lối tắt hệ thống</h3>
          <p class="panel-subtitle">Bấm vào từng dòng để đi thẳng đến màn hình cần làm việc.</p>
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
          <h3 class="panel-title">Gợi ý thao tác</h3>
          <p class="panel-subtitle">Nếu cần quay ra khu bán hàng, dùng nút Về trang chủ trên header.</p>
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

<script>
import { authState, isAdminState } from '../../../lib/authStorage';

export default {
  name: 'DashboardAdmin',

  data() {
    return {
      authState,
      timeline: [
        {
          time: '08:30',
          title: 'Rà soát nhân viên đang hoạt động',
          desc: 'Vào module Nhân viên để kiểm tra tài khoản mới, khóa tài khoản và đổi mật khẩu khi cần.',
        },
        {
          time: '10:00',
          title: 'Theo dõi hóa đơn và doanh thu',
          desc: 'Mở module Hóa đơn để kiểm tra doanh thu trong ngày và đơn đang xử lý.',
        },
        {
          time: '13:30',
          title: 'Kiểm tra tồn kho và lô thuốc',
          desc: 'Nhân viên kho có thể mở ngay module Tồn kho để xem tồn và hạn sử dụng.',
        },
        {
          time: '16:00',
          title: 'Cập nhật giá và ưu đãi',
          desc: 'Nếu có chương trình mới, vào Giá và khuyến mãi để chỉnh sửa ngay trên hệ thống.',
        },
      ],
      stockStatus: [
        {
          name: 'Paracetamol 500mg',
          percent: 84,
          note: 'Lượng tồn an toàn cho 5 ngày bán hàng.',
          barClass: 'bg-success',
        },
        {
          name: 'Vitamin C 1000mg',
          percent: 58,
          note: 'Cần bổ sung trong 48 giờ tới để tránh đứt hàng.',
          barClass: 'bg-info',
        },
        {
          name: 'Nước rửa tay y tế',
          percent: 26,
          note: 'Cảnh báo mức tồn thấp, ưu tiên đặt hàng gấp.',
          barClass: 'bg-warning',
        },
      ],
      tips: [
        {
          title: 'Nhân viên không mở được module',
          note: 'Kiểm tra lại role trả về từ login và token đang lưu trong frontend.',
          tag: 'Quyền',
        },
        {
          title: 'Cần quay lại trang chủ bán hàng',
          note: 'Sử dụng nút Về trang chủ ở header quản trị, không cần đăng xuất.',
          tag: 'Luồng đi',
        },
        {
          title: 'Cần vào nhanh trang quản lý',
          note: 'Bấm trực tiếp vào các card ở đầu dashboard hoặc menu trái.',
          tag: 'Điều hướng',
        },
      ],
    };
  },

  computed: {
    currentUserName() {
      return this.authState.user?.ho_ten || this.authState.user?.ten_khach_hang || 'Tài khoản hệ thống';
    },

    roleLabel() {
      if (this.authState.type === 'admin') {
        return 'Admin';
      }

      if (['staff', 'nhan_vien', 'nhanvien'].includes(this.authState.type)) {
        return 'Nhân viên';
      }

      return 'Tài khoản';
    },

    quickActions() {
      const items = [];

      if (isAdminState.value) {
        items.push({
          to: '/nhan-viens',
          eyebrow: 'Quản trị nhân sự',
          label: 'Nhân viên',
          caption: 'Xem danh sách, tạo mới, cập nhật và đổi mật khẩu nhân viên.',
          icon: 'bi bi-people',
          iconClass: 'metric-card__icon--blue',
        });
      }

      items.push(
        {
          to: '/hoa-dons',
          eyebrow: 'Bán hàng',
          label: 'Hóa đơn',
          caption: 'Theo dõi hóa đơn, doanh thu và lịch sử giao dịch.',
          icon: 'bi bi-receipt-cutoff',
          iconClass: 'metric-card__icon--teal',
        },
        {
          to: '/ton-kho',
          eyebrow: 'Kho dược',
          label: 'Tồn kho',
          caption: 'Kiểm tra tồn, lô thuốc, hạn sử dụng và giá nhập.',
          icon: 'bi bi-box-seam',
          iconClass: 'metric-card__icon--orange',
        },
        {
          to: '/gia-khuyen-mai',
          eyebrow: 'Kinh doanh',
          label: 'Giá và khuyến mãi',
          caption: 'Cập nhật giá bán và chương trình ưu đãi cho từng thuốc.',
          icon: 'bi bi-tags',
          iconClass: 'metric-card__icon--red',
        }
      );

      return items;
    },
  },

  methods: {
    goTo(path) {
      this.$router.push(path);
    },
  },
};
</script>
