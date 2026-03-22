<template>
  <div class="customer-home">
    <header class="customer-nav">
      <div class="container-fluid px-3 px-lg-4">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <RouterLink to="/" class="customer-brand">
            <span class="customer-brand__mark">
              <i class="bi bi-capsule-pill"></i>
            </span>
            <span>
              <strong>Pharmacity Care</strong>
              <small>Nha thuoc va tu van suc khoe</small>
            </span>
          </RouterLink>

          <nav class="customer-nav__links d-none d-lg-flex">
            <a href="#gioi-thieu">Gioi thieu</a>
            <a href="#san-pham">San pham</a>
            <a href="#tu-van">Tu van</a>
            <a href="#cam-ket">Cam ket</a>
          </nav>

          <div v-if="!isLoggedIn" class="d-flex align-items-center gap-2">
            <RouterLink to="/login" class="btn btn-outline-primary rounded-pill px-4">Dang nhap</RouterLink>
            <RouterLink to="/register" class="btn btn-primary rounded-pill px-4 d-none d-sm-inline-flex">Dang ky</RouterLink>
          </div>

          <div v-else class="dropdown">
            <button
              class="btn btn-light customer-profile-toggle dropdown-toggle"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <span class="customer-profile-toggle__avatar">
                <i class="bi bi-person-circle"></i>
              </span>
              <span class="text-start">
                <strong>{{ displayName }}</strong>
                <small>{{ authTypeLabel }}</small>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end customer-profile-menu">
              <li><span class="dropdown-item-text fw-semibold">Xin chao, {{ displayName }}</span></li>
              <li><span class="dropdown-item-text small text-secondary">Ban dang o homepage dung chung.</span></li>
              <li><hr class="dropdown-divider" /></li>
              <li><button class="dropdown-item" type="button">Thong tin tai khoan</button></li>
              <li><button class="dropdown-item" type="button">Don hang cua toi</button></li>
              <li v-if="isSystemAccount"><hr class="dropdown-divider" /></li>
              <li v-if="isSystemAccount">
                <RouterLink class="dropdown-item" to="/dashboard">Quan ly he thong</RouterLink>
              </li>
              <li v-if="isSystemAccount">
                <RouterLink class="dropdown-item" to="/nhan-viens">Quan ly nhan vien</RouterLink>
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li><button class="dropdown-item text-danger" type="button" @click="handleLogout">Dang xuat</button></li>
            </ul>
          </div>
        </div>
      </div>
    </header>

    <section class="customer-hero">
      <div class="container-fluid px-3 px-lg-4">
        <div class="row align-items-center g-4 g-xl-5">
          <div class="col-xl-7">
            <div class="customer-kicker mb-3">Ban thuoc theo giao dien de doc, de nhin va de thao tac</div>
            <h1 class="customer-hero__title">
              Xem thuoc, gia ban, ton kho va thong tin can thiet cho khach hang ngay tren trang chu
            </h1>
            <p class="customer-hero__lead">
              Homepage nay tap trung vao trai nghiem mua thuoc cho khach hang. Moi the san pham hien gia, ton kho, va
              co popup chi tiet ve mo ta, trieu chung va tac dung phu thuong gap.
            </p>

            <div class="d-flex flex-wrap gap-3 mt-4">
              <a href="#san-pham" class="btn btn-primary btn-lg rounded-pill px-4">Xem catalog thuoc</a>
              <a href="#tu-van" class="btn btn-light btn-lg rounded-pill px-4 customer-hero__ghost">Nhan tu van</a>
            </div>

            <div class="row g-3 mt-4">
              <div class="col-sm-4" v-for="item in heroStats" :key="item.label">
                <div class="customer-stat">
                  <div class="customer-stat__value">{{ item.value }}</div>
                  <div class="customer-stat__label">{{ item.label }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-5">
            <div class="hero-showcase">
              <div class="hero-showcase__panel hero-showcase__panel--primary">
                <div class="soft-badge bg-white text-primary mb-3">
                  <i class="bi bi-bag-heart"></i>
                  Catalog public
                </div>
                <h3 class="mb-3 fw-bold">Mot trang chu phuc vu ca khach hang va quan tri</h3>
                <p class="mb-4 text-white-50">
                  Khach hang thay thong tin mua thuoc. Admin va nhan vien cung thay dung homepage nay, nhung trong menu
                  profile co them loi vao khu quan ly he thong.
                </p>

                <div class="customer-mini-list">
                  <div v-for="item in supportPoints" :key="item" class="customer-mini-list__item">
                    <i class="bi bi-check2-circle"></i>
                    <span>{{ item }}</span>
                  </div>
                </div>
              </div>

              <div class="hero-showcase__floating hero-showcase__floating--top">
                <strong>{{ thuocs.length }} san pham dang hien thi</strong>
                <span>Du lieu lay tu public API catalog</span>
              </div>

              <div class="hero-showcase__floating hero-showcase__floating--bottom">
                <strong>{{ isSystemAccount ? "Ban co quyen quan tri" : "Trai nghiem cho khach hang" }}</strong>
                <span>{{ isSystemAccount ? "Mo profile menu de vao khu quan ly he thong" : "Dang ky de tiep tuc mo rong don hang sau nay" }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="gioi-thieu" class="customer-section">
      <div class="container-fluid px-3 px-lg-4">
        <div class="row g-4 align-items-center">
          <div class="col-xl-5">
            <div class="customer-media-card">
              <div class="customer-media-card__accent"></div>
              <div class="customer-media-card__content">
                <div class="customer-kicker mb-3">Dinh huong moi</div>
                <h2 class="section-title mb-3">Khong chi la landing page, day la homepage co the ban thuoc that</h2>
                <p class="section-copy mb-0">
                  Bo cuc duoc doi tu trang gioi thieu sang trang chu thuoc co kha nang mo rong: danh muc, popup chi
                  tiet, auth khach hang va admin, va truong hop admin van bat dau tu homepage nhu khach hang.
                </p>
              </div>
            </div>
          </div>

          <div class="col-xl-7">
            <div class="row g-3">
              <div class="col-md-6" v-for="feature in aboutFeatures" :key="feature.title">
                <article class="customer-info-card h-100">
                  <div class="customer-info-card__icon">
                    <i :class="feature.icon"></i>
                  </div>
                  <h3 class="h5 fw-bold mb-2">{{ feature.title }}</h3>
                  <p class="text-secondary mb-0">{{ feature.copy }}</p>
                </article>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="san-pham" class="customer-section customer-section--soft">
      <div class="container-fluid px-3 px-lg-4">
        <div class="section-heading text-center">
          <div class="customer-kicker">Catalog thuoc</div>
          <h2 class="section-title">Thuoc, gia ban va so luong ton hien ngay tren trang chu</h2>
          <p class="section-copy mx-auto">
            Bam vao tung the thuoc de mo popup chi tiet. Popup hien mo ta, trieu chung thuong lien quan va tac dung phu.
          </p>
        </div>

        <div class="customer-searchbar mb-4">
          <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input
              v-model.trim="keyword"
              type="text"
              class="form-control"
              placeholder="Tim theo ten thuoc, ma thuoc hoac loai thuoc"
              @keyup.enter="loadThuocs"
            />
            <button class="btn btn-primary" type="button" @click="loadThuocs" :disabled="loadingThuocs">
              <span v-if="loadingThuocs" class="spinner-border spinner-border-sm me-2"></span>
              Tim thuoc
            </button>
          </div>
        </div>

        <div v-if="catalogError" class="alert alert-danger mb-4">{{ catalogError }}</div>

        <div class="row g-4">
          <div class="col-md-6 col-xl-4" v-for="thuoc in thuocs" :key="thuoc.ma_thuoc">
            <article class="medicine-card h-100" @click="openThuoc(thuoc)">
              <div class="medicine-card__thumb" :class="thumbClass(thuoc)">
                <i :class="thumbIcon(thuoc)"></i>
                <span>{{ thuoc.loai_thuoc || "Thuoc thong dung" }}</span>
              </div>

              <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                  <h3 class="h5 fw-bold mb-1">{{ thuoc.ten_thuoc }}</h3>
                  <div class="small text-secondary">{{ thuoc.ma_thuoc }} • {{ thuoc.ham_luong || "Dang cap nhat" }}</div>
                </div>
                <span class="soft-badge" :class="thuoc.so_luong_ton > 0 ? 'soft-badge--teal' : 'soft-badge--orange'">
                  {{ thuoc.so_luong_ton > 0 ? "Con hang" : "Het hang" }}
                </span>
              </div>

              <p class="text-secondary mb-3">{{ thuoc.mo_ta }}</p>

              <div class="medicine-card__meta">
                <div>
                  <span class="medicine-card__label">Gia ban</span>
                  <strong>{{ formatCurrency(thuoc.gia_ban) }}</strong>
                </div>
                <div>
                  <span class="medicine-card__label">Ton kho</span>
                  <strong>{{ thuoc.so_luong_ton }} {{ thuoc.don_vi_tinh }}</strong>
                </div>
              </div>

              <button class="btn btn-outline-primary rounded-pill px-4 mt-4" type="button">
                Xem chi tiet
              </button>
            </article>
          </div>
        </div>

        <div v-if="!loadingThuocs && !thuocs.length" class="master-empty mt-4">
          <p class="mb-2 fw-semibold">Chua tim thay thuoc phu hop.</p>
          <p class="mb-0 text-secondary">Ban thu doi tu khoa khac hoac kiem tra lai du lieu catalog.</p>
        </div>
      </div>
    </section>

    <section id="tu-van" class="customer-section customer-section--accent">
      <div class="container-fluid px-3 px-lg-4">
        <div class="row g-4 align-items-center">
          <div class="col-xl-6">
            <div class="customer-kicker text-white-50">Tu van va dong hanh</div>
            <h2 class="section-title text-white">Sau khi xem thuoc, khach hang co the chuyen sang buoc nhan tu van</h2>
            <p class="section-copy text-white-50">
              Khu vuc nay duoc giu lai de sau do noi backend cho tu van duoc si, dat mua hoac nhac lich su dung thuoc.
            </p>

            <div class="customer-mini-list customer-mini-list--light mt-4">
              <div v-for="item in consultHighlights" :key="item" class="customer-mini-list__item">
                <i class="bi bi-check2-circle"></i>
                <span>{{ item }}</span>
              </div>
            </div>
          </div>

          <div class="col-xl-6">
            <article class="consult-card">
              <h3 class="fw-bold mb-3">Yeu cau tu van nhanh</h3>
              <p class="text-secondary mb-4">
                Buoc tiep theo hop ly de bien homepage thanh mot trai nghiem mua thuoc hoan chinh hon.
              </p>

              <div class="row g-3">
                <div class="col-md-6">
                  <input class="form-control form-control-lg" placeholder="Ho va ten" />
                </div>
                <div class="col-md-6">
                  <input class="form-control form-control-lg" placeholder="So dien thoai" />
                </div>
                <div class="col-12">
                  <textarea class="form-control form-control-lg" rows="4" placeholder="Nhap trieu chung hoac nhu cau cua ban"></textarea>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-3 mt-4">
                <button class="btn btn-primary btn-lg rounded-pill px-4">Gui yeu cau</button>
                <button class="btn btn-light btn-lg rounded-pill px-4">Hotline duoc si</button>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section id="cam-ket" class="customer-section">
      <div class="container-fluid px-3 px-lg-4">
        <div class="section-heading text-center">
          <div class="customer-kicker">Cam ket hien tai</div>
          <h2 class="section-title">Homepage nay da dat nen cho ca khach hang va quan tri</h2>
          <p class="section-copy mx-auto">
            Khach hang thay catalog thuoc va auth rieng, con admin van dung cung homepage nhung co them loi vao quan
            ly he thong trong profile menu.
          </p>
        </div>

        <div class="row g-4">
          <div class="col-md-6 col-xl-3" v-for="promise in promises" :key="promise.title">
            <article class="promise-card h-100">
              <div class="promise-card__icon">
                <i :class="promise.icon"></i>
              </div>
              <h3 class="h6 fw-bold mb-2">{{ promise.title }}</h3>
              <p class="text-secondary mb-0">{{ promise.copy }}</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <footer class="customer-footer">
      <div class="container-fluid px-3 px-lg-4">
        <div class="row g-4">
          <div class="col-lg-4">
            <div class="customer-brand mb-3">
              <span class="customer-brand__mark">
                <i class="bi bi-capsule-pill"></i>
              </span>
              <span>
                <strong>Pharmacity Care</strong>
                <small>Khong gian ban thuoc va tu van suc khoe cho gia dinh</small>
              </span>
            </div>
            <p class="text-white-50 mb-0">
              Trang chu nay hien da co auth cho khach hang va admin, catalog thuoc public va popup chi tiet theo san
              pham.
            </p>
          </div>

          <div class="col-sm-6 col-lg-2">
            <h4 class="customer-footer__title">Dieu huong</h4>
            <ul class="customer-footer__links">
              <li><a href="#gioi-thieu">Gioi thieu</a></li>
              <li><a href="#san-pham">San pham</a></li>
              <li><a href="#tu-van">Tu van</a></li>
              <li><a href="#cam-ket">Cam ket</a></li>
            </ul>
          </div>

          <div class="col-sm-6 col-lg-3">
            <h4 class="customer-footer__title">Lien he nhanh</h4>
            <ul class="customer-footer__links">
              <li>Hotline: 1900 6868</li>
              <li>Email: cskh@pharmacity.vn</li>
              <li>Ho tro mua thuoc va huong dan su dung</li>
            </ul>
          </div>

          <div class="col-lg-3">
            <h4 class="customer-footer__title">Bat dau</h4>
            <p class="text-white-50">
              Dang nhap cho khach hang hoac quan tri. Neu ban la admin, menu profile se co them Quan ly he thong.
            </p>
            <div class="d-flex flex-wrap gap-2">
              <RouterLink to="/login" class="btn btn-light rounded-pill px-4">Dang nhap</RouterLink>
              <RouterLink to="/register" class="btn btn-outline-light rounded-pill px-4">Dang ky</RouterLink>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <div v-if="selectedThuoc" class="medicine-modal" @click.self="closeModal">
      <div class="medicine-modal__dialog">
        <button class="medicine-modal__close" type="button" @click="closeModal">
          <i class="bi bi-x-lg"></i>
        </button>

        <div class="medicine-modal__head">
          <div class="medicine-card__thumb" :class="thumbClass(selectedThuoc)">
            <i :class="thumbIcon(selectedThuoc)"></i>
            <span>{{ selectedThuoc.loai_thuoc || "Thuoc thong dung" }}</span>
          </div>
          <div>
            <div class="soft-badge soft-badge--blue mb-3">{{ selectedThuoc.ma_thuoc }}</div>
            <h3 class="h3 fw-bold mb-2">{{ selectedThuoc.ten_thuoc }}</h3>
            <p class="text-secondary mb-0">{{ selectedThuoc.nha_san_xuat || "Dang cap nhat nha san xuat" }}</p>
          </div>
        </div>

        <div class="row g-4 mt-1">
          <div class="col-md-6">
            <div class="medicine-detail-card">
              <div class="medicine-detail-card__label">Mo ta</div>
              <p class="mb-0">{{ selectedThuoc.mo_ta }}</p>
            </div>
          </div>

          <div class="col-md-6">
            <div class="medicine-detail-card">
              <div class="medicine-detail-card__label">Thong tin nhanh</div>
              <div class="vstack gap-2">
                <div><strong>Gia ban:</strong> {{ formatCurrency(selectedThuoc.gia_ban) }}</div>
                <div><strong>Ton kho:</strong> {{ selectedThuoc.so_luong_ton }} {{ selectedThuoc.don_vi_tinh }}</div>
                <div><strong>Ham luong:</strong> {{ selectedThuoc.ham_luong || "Dang cap nhat" }}</div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="medicine-detail-card">
              <div class="medicine-detail-card__label">Thuoc nay thuong lien quan den</div>
              <div class="d-flex flex-wrap gap-2">
                <span v-for="symptom in selectedThuoc.trieu_chung" :key="symptom" class="soft-badge soft-badge--teal">
                  {{ symptom }}
                </span>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="medicine-detail-card">
              <div class="medicine-detail-card__label">Tac dung phu thuong gap</div>
              <ul class="customer-bullet-list mb-0">
                <li v-for="effect in selectedThuoc.tac_dung_phu" :key="effect">{{ effect }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { getCatalogThuoc, getCatalogThuocs } from "../api/catalogApi";
import { clearAuthSession, getAuthType, getStoredUser, isAuthenticated, isSystemUser } from "../lib/authStorage";

const router = useRouter();
const keyword = ref("");
const thuocs = ref([]);
const loadingThuocs = ref(false);
const catalogError = ref("");
const selectedThuoc = ref(null);
const currentUser = ref(getStoredUser());
const currentType = ref(getAuthType());
const isLoggedIn = isAuthenticated();
const isSystemAccount = isSystemUser();

const heroStats = [
  { value: "Gia ro", label: "Gia ban hien ngay tren the san pham" },
  { value: "Ton kho", label: "So luong ton de khach de quyet dinh" },
  { value: "Popup", label: "Bam vao thuoc de xem chi tiet" },
];

const supportPoints = [
  "Catalog thuoc lay tu backend public API",
  "Khach hang dang nhap bang email hoac so dien thoai",
  "Admin van dung homepage nay va co them menu quan ly he thong",
];

const aboutFeatures = [
  {
    icon: "bi bi-currency-dollar",
    title: "Gia ban minh bach",
    copy: "Muc gia hien ro ngay tren the thuoc de khach hang khong phai tim kiem them o noi khac.",
  },
  {
    icon: "bi bi-box-seam",
    title: "Ton kho hien tai",
    copy: "So luong ton duoc hien de khach co them co so quyet dinh mua nhanh hon.",
  },
  {
    icon: "bi bi-journal-medical",
    title: "Chi tiet phuc vu mua hang",
    copy: "Popup gom mo ta, trieu chung va tac dung phu thay vi chi co thong tin quang ba.",
  },
  {
    icon: "bi bi-person-workspace",
    title: "Cung mot homepage cho nhieu vai tro",
    copy: "Admin va nhan vien van o homepage khach hang, nhung profile menu co them Quan ly he thong.",
  },
];

const consultHighlights = [
  "Giup khach hang di tu xem thuoc sang hoi dap duoc si",
  "La buoc tiep theo hop ly sau khi da co catalog va auth",
  "San sang noi tiep thanh don tu van, gio hang va dat mua",
];

const promises = [
  {
    icon: "bi bi-bag-check",
    title: "Dinh huong ban thuoc ro rang",
    copy: "Homepage khong chi de gioi thieu ma da co logic hien san pham, gia va ton kho phu hop ban hang.",
  },
  {
    icon: "bi bi-person-heart",
    title: "Auth tach ro khach hang va admin",
    copy: "Khach hang dang ky va dang nhap rieng, admin dang nhap bang ten dang nhap he thong.",
  },
  {
    icon: "bi bi-window-sidebar",
    title: "Admin van co loi vao he thong",
    copy: "Profile menu tren homepage se hien them cac loi vao khu quan ly khi role la admin hoac nhan vien.",
  },
  {
    icon: "bi bi-diagram-3",
    title: "San sang mo rong nghiep vu",
    copy: "Nen hien tai co the noi them gio hang, don hang, profile khach hang va chi tiet thuoc sau nay.",
  },
];

const displayName = computed(() => currentUser.value?.ho_ten || currentUser.value?.ten_khach_hang || "Tai khoan");
const authTypeLabel = computed(() => {
  if (currentType.value === "admin") return "Quan tri he thong";
  if (currentType.value === "staff") return "Nhan vien";
  if (currentType.value === "customer") return "Khach hang";

  return "Khach";
});

function formatCurrency(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function thumbIcon(thuoc) {
  const loai = (thuoc.loai_thuoc || "").toLowerCase();

  if (loai.includes("vitamin")) return "bi bi-sun";
  if (loai.includes("ho")) return "bi bi-lungs";
  if (loai.includes("da")) return "bi bi-droplet";

  return "bi bi-capsule-pill";
}

function thumbClass(thuoc) {
  const loai = (thuoc.loai_thuoc || "").toLowerCase();

  if (loai.includes("vitamin")) return "medicine-card__thumb--sun";
  if (loai.includes("ho")) return "medicine-card__thumb--air";
  if (loai.includes("da")) return "medicine-card__thumb--care";

  return "medicine-card__thumb--classic";
}

async function loadThuocs() {
  loadingThuocs.value = true;
  catalogError.value = "";

  try {
    const response = await getCatalogThuocs(keyword.value);
    thuocs.value = response.data || [];
  } catch (err) {
    catalogError.value = err?.message || "Khong tai duoc danh muc thuoc.";
  } finally {
    loadingThuocs.value = false;
  }
}

async function openThuoc(thuoc) {
  try {
    const response = await getCatalogThuoc(thuoc.ma_thuoc);
    selectedThuoc.value = response.data;
  } catch (err) {
    catalogError.value = err?.message || "Khong tai duoc chi tiet thuoc.";
  }
}

function closeModal() {
  selectedThuoc.value = null;
}

function handleLogout() {
  clearAuthSession();
  currentUser.value = null;
  currentType.value = "";
  router.push("/");
  window.location.reload();
}

onMounted(() => {
  loadThuocs();
});
</script>
