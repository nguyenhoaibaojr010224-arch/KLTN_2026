<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--blue mb-3">
          <i class="bi bi-capsule-pill"></i>
          Quản lý thuốc
        </div>
        <h2 class="page-section-title">Danh sách thuốc và thêm thuốc mới</h2>
        <p class="page-section-copy mb-0">
          Quản lý trực tiếp dữ liệu thuốc theo database hiện tại: mã thuốc, tên thuốc, loại thuốc, nhà sản xuất, giá bán
          và trạng thái kinh doanh.
        </p>
      </div>

      <div class="soft-badge">
        <i class="bi bi-person-workspace"></i>
        {{ currentUser?.ho_ten || 'Tài khoản hệ thống' }}
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-6">
        <label class="form-label fw-semibold">Tìm thuốc</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Nhập tên thuốc, mã thuốc, loại thuốc hoặc nhà sản xuất"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-6">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary" @click="loadData" :disabled="loading.sync">
            <span v-if="loading.sync" class="spinner-border spinner-border-sm me-2"></span>
            Đồng bộ dữ liệu
          </button>
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tìm kiếm
          </button>
          <button class="btn btn-outline-secondary" @click="resetForm">Làm mới form</button>
        </div>
      </div>
    </div>

    <div v-if="!isAdminUser" class="alert alert-warning mt-4 mb-0">
      Tài khoản nhân viên chỉ xem và tra cứu được dữ liệu. Chức năng thêm, sửa, xóa thuốc yêu cầu quyền admin.
    </div>
    <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
    <div v-if="error" class="alert alert-danger mt-4 mb-0">{{ error }}</div>
  </section>

  <section class="row g-4">
    <div class="col-xl-7">
      <article class="content-card h-100">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
          <div>
            <h3 class="panel-title">Danh sách thuốc</h3>
            <p class="panel-subtitle mb-0">Chọn một dòng để nạp dữ liệu lên form bên phải.</p>
          </div>
          <span class="soft-badge soft-badge--teal">{{ thuocs.length }} thuốc</span>
        </div>

        <div v-if="thuocs.length" class="table-responsive">
          <table class="table table-master align-middle mb-0">
            <thead>
              <tr>
                <th>Thuốc</th>
                <th>Loại</th>
                <th>Đơn vị</th>
                <th>Giá bán</th>
                <th>Trạng thái</th>
                <th class="text-end">Tác vụ</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="thuoc in thuocs"
                :key="thuoc.ma_thuoc"
                :class="{ 'table-active': selectedThuoc && selectedThuoc.ma_thuoc === thuoc.ma_thuoc }"
              >
                <td>
                  <div v-if="thuoc.hinh_anh_url" class="small mb-2">
                    <img
                      :src="thuoc.hinh_anh_url"
                      alt="Ảnh thuốc"
                      style="width: 52px; height: 52px; object-fit: cover; border-radius: 12px; border: 1px solid #d9e4ff"
                    />
                  </div>
                  <div class="fw-semibold">{{ thuoc.ten_thuoc }}</div>
                  <div class="small text-secondary">{{ thuoc.ma_thuoc }}</div>
                  <div class="small text-secondary">{{ thuoc.nhaSanXuat?.ten_nha_san_xuat || '-' }}</div>
                </td>
                <td>{{ thuoc.loaiThuoc?.ten_loai || '-' }}</td>
                <td>{{ thuoc.don_vi_tinh || '-' }}</td>
                <td>{{ formatCurrency(thuoc.gia_ban) }}</td>
                <td>
                  <span class="soft-badge" :class="statusBadgeClass(thuoc.trang_thai)">
                    {{ thuoc.trang_thai || 'còn bán' }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-sm btn-outline-primary" @click="selectThuoc(thuoc)">Chọn</button>
                    <button
                      v-if="isAdminUser"
                      class="btn btn-sm btn-outline-danger"
                      @click="removeThuoc(thuoc)"
                      :disabled="loading.deleteId === thuoc.ma_thuoc"
                    >
                      <span v-if="loading.deleteId === thuoc.ma_thuoc" class="spinner-border spinner-border-sm me-2"></span>
                      Xóa
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chưa có dữ liệu thuốc.</p>
          <p class="mb-0 text-secondary">Hãy đồng bộ dữ liệu hoặc tìm kiếm lại bằng từ khóa khác.</p>
        </div>
      </article>
    </div>

    <div class="col-xl-5">
      <article class="content-card h-100">
        <div class="mb-4">
          <h3 class="panel-title">{{ form.originalId ? 'Cập nhật thuốc' : 'Thêm thuốc mới' }}</h3>
          <p class="panel-subtitle mb-0">
            {{
              form.originalId
                ? `Đang chỉnh ${form.ten_thuoc}`
                : 'Nhập đầy đủ thông tin để thêm thuốc mới vào hệ thống.'
            }}
          </p>
        </div>

        <div class="vstack gap-3">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label fw-semibold">Mã thuốc</label>
              <input class="form-control" :value="displayMaThuoc" disabled />
              <div class="form-text">Mã này do hệ thống cấp trước và sẽ được dùng đúng khi thêm thuốc.</div>
            </div>
            <div class="col-md-7">
              <label class="form-label fw-semibold">Tên thuốc</label>
              <input v-model.trim="form.ten_thuoc" class="form-control" placeholder="Nhập tên thuốc" />
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Loại thuốc</label>
              <select v-model="form.id_loai_thuoc" class="form-select">
                <option value="">Chọn loại thuốc</option>
                <option v-for="item in loaiThuocs" :key="item.id" :value="item.id">
                  {{ item.ten_loai }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nhà sản xuất</label>
              <select v-model="form.id_nha_san_xuat" class="form-select">
                <option value="">Chọn nhà sản xuất</option>
                <option v-for="item in nhaSanXuats" :key="item.id" :value="item.id">
                  {{ item.ten_nha_san_xuat }}
                </option>
              </select>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Hàm lượng</label>
              <input v-model.trim="form.ham_luong" class="form-control" placeholder="VD: 500mg" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Đơn vị tính</label>
              <input v-model.trim="form.don_vi_tinh" class="form-control" placeholder="VD: hộp, vỉ, chai" />
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Giá bán</label>
              <input v-model.number="form.gia_ban" type="number" min="1" class="form-control" placeholder="Nhập giá bán" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Trạng thái</label>
              <select v-model="form.trang_thai" class="form-select">
                <option value="còn bán">Còn thuốc</option>
                <option value="ngừng bán">Ngừng bán</option>
              </select>
            </div>
          </div>

          <div>
            <label class="form-label fw-semibold">Ảnh thuốc</label>
            <input class="form-control" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" @change="handleImageChange" />
            <div class="form-text">Cho phép JPG, PNG, WEBP. Bỏ trống nếu bạn chưa cần thêm ảnh.</div>
          </div>

          <div v-if="imagePreviewUrl" class="border rounded-4 p-3 d-flex align-items-center gap-3">
            <img
              :src="imagePreviewUrl"
              alt="Xem trước ảnh thuốc"
              style="width: 88px; height: 88px; object-fit: cover; border-radius: 16px; border: 1px solid #d9e4ff"
            />
            <div class="flex-grow-1">
              <div class="fw-semibold">Ảnh xem trước</div>
              <div class="small text-secondary">
                {{ imageFile ? imageFile.name : 'Đang dùng ảnh đã lưu cũ của thuốc.' }}
              </div>
            </div>
            <button class="btn btn-outline-secondary btn-sm" @click="clearImageSelection">Bỏ ảnh mới</button>
          </div>

          <div class="d-flex flex-wrap gap-2 pt-2">
            <button class="btn btn-primary" @click="saveThuoc" :disabled="loading.save || !isAdminUser">
              <span v-if="loading.save" class="spinner-border spinner-border-sm me-2"></span>
              {{ form.originalId ? 'Lưu cập nhật' : 'Thêm thuốc' }}
            </button>
            <button class="btn btn-outline-secondary" @click="resetForm">Làm mới</button>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>

<script>
import {
  createThuoc,
  deleteThuoc,
  getLoaiThuocOptions,
  getNhaSanXuatOptions,
  getNextThuocCode,
  getThuocList,
  searchThuocList,
  updateThuoc,
} from '../../../api/thuocManagementApi';
import { authState, isAdminState } from '../../../lib/authStorage';

export default {
  name: 'ThuocAdmin',

  data() {
    return {
      authState,
      keyword: '',
      thuocs: [],
      loaiThuocs: [],
      nhaSanXuats: [],
      selectedThuoc: null,
      message: '',
      error: '',
      imageFile: null,
      imagePreviewUrl: '',
      loading: {
        sync: false,
        search: false,
        save: false,
        deleteId: null,
      },
      form: {
        originalId: '',
        ma_thuoc: '',
        ten_thuoc: '',
        ham_luong: '',
        don_vi_tinh: '',
        gia_ban: null,
        trang_thai: 'còn bán',
        id_loai_thuoc: '',
        id_nha_san_xuat: '',
      },
    };
  },

  computed: {
    currentUser() {
      return this.authState.user;
    },

    isAdminUser() {
      return isAdminState.value;
    },

    displayMaThuoc() {
      return this.form.ma_thuoc || 'Đang cấp mã...';
    },
  },

  mounted() {
    this.loadData();
  },

  methods: {
    normalizeError(err) {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(' | ');
      }

      return err?.message || 'Không thể xử lý dữ liệu thuốc.';
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },

    statusBadgeClass(status) {
      return status === 'ngừng bán' ? 'soft-badge--orange' : 'soft-badge--teal';
    },

    fillForm(thuoc) {
      this.form.originalId = thuoc.ma_thuoc;
      this.form.ma_thuoc = thuoc.ma_thuoc;
      this.form.ten_thuoc = thuoc.ten_thuoc || '';
      this.form.ham_luong = thuoc.ham_luong || '';
      this.form.don_vi_tinh = thuoc.don_vi_tinh || '';
      this.form.gia_ban = Number(thuoc.gia_ban || 0);
      this.form.trang_thai = thuoc.trang_thai || 'còn bán';
      this.form.id_loai_thuoc = thuoc.id_loai_thuoc || thuoc.loaiThuoc?.id || '';
      this.form.id_nha_san_xuat = thuoc.id_nha_san_xuat || thuoc.nhaSanXuat?.id || '';
      this.imageFile = null;
      this.imagePreviewUrl = thuoc.hinh_anh_url || '';
    },

    async resetForm() {
      this.selectedThuoc = null;
      this.form.originalId = '';
      this.form.ma_thuoc = '';
      this.form.ten_thuoc = '';
      this.form.ham_luong = '';
      this.form.don_vi_tinh = '';
      this.form.gia_ban = null;
      this.form.trang_thai = 'còn bán';
      this.form.id_loai_thuoc = '';
      this.form.id_nha_san_xuat = '';
      this.imageFile = null;
      this.imagePreviewUrl = '';

      await this.assignNextCode();
    },

    handleImageChange(event) {
      const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
      this.imageFile = file;
      this.imagePreviewUrl = file ? URL.createObjectURL(file) : '';
    },

    clearImageSelection() {
      this.imageFile = null;
      this.imagePreviewUrl = this.selectedThuoc?.hinh_anh_url || '';
    },

    async assignNextCode() {
      if (!this.isAdminUser || this.form.originalId) {
        return;
      }

      const response = await getNextThuocCode();
      this.form.ma_thuoc = response?.ma_thuoc || '';
    },

    selectThuoc(thuoc) {
      this.selectedThuoc = thuoc;
      this.fillForm(thuoc);
      this.error = '';
      this.message = `Đã nạp dữ liệu của ${thuoc.ten_thuoc} lên form.`;
    },

    async loadData() {
      this.loading.sync = true;
      this.error = '';

      try {
        const requests = [getThuocList(), getLoaiThuocOptions(), getNhaSanXuatOptions()];

        if (this.isAdminUser) {
          requests.push(getNextThuocCode());
        }

        const [thuocData, loaiData, nhaSanXuatData, nextCodeData] = await Promise.all(requests);

        this.thuocs = thuocData;
        this.loaiThuocs = loaiData;
        this.nhaSanXuats = nhaSanXuatData;

        if (this.selectedThuoc) {
          const refreshed = thuocData.find((item) => item.ma_thuoc === this.selectedThuoc.ma_thuoc);
          this.selectedThuoc = refreshed || null;

          if (refreshed) {
            this.fillForm(refreshed);
          }
        } else if (this.isAdminUser) {
          this.form.ma_thuoc = nextCodeData?.ma_thuoc || '';
        }

        this.message = `Đã đồng bộ ${this.thuocs.length} thuốc, ${this.loaiThuocs.length} loại thuốc và ${this.nhaSanXuats.length} nhà sản xuất.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.sync = false;
      }
    },

    async handleSearch() {
      if (!this.keyword) {
        return;
      }

      this.loading.search = true;
      this.error = '';

      try {
        this.thuocs = await searchThuocList(this.keyword);
        this.selectedThuoc = null;
        this.message = `Tìm thấy ${this.thuocs.length} thuốc phù hợp.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.search = false;
      }
    },

    buildPayload() {
      const payload = new FormData();
      payload.append('ma_thuoc', this.form.ma_thuoc);
      payload.append('ten_thuoc', this.form.ten_thuoc);
      payload.append('ham_luong', this.form.ham_luong || '');
      payload.append('don_vi_tinh', this.form.don_vi_tinh);
      payload.append('gia_ban', String(Number(this.form.gia_ban || 0)));
      payload.append('trang_thai', this.form.trang_thai);
      payload.append('id_loai_thuoc', String(Number(this.form.id_loai_thuoc)));
      payload.append('id_nha_san_xuat', String(Number(this.form.id_nha_san_xuat)));

      if (this.imageFile) {
        payload.append('hinh_anh', this.imageFile);
      }

      return payload;
    },

    async saveThuoc() {
      if (!this.isAdminUser) {
        this.error = 'Chỉ admin mới có quyền thêm hoặc cập nhật thuốc.';
        return;
      }

      this.loading.save = true;
      this.error = '';

      try {
        const payload = this.buildPayload();

        if (this.form.originalId) {
          payload.append('_method', 'PUT');
          const response = await updateThuoc(this.form.originalId, payload);
          this.message = `Đã cập nhật thuốc ${this.form.ten_thuoc}.`;
          if (response?.data?.ma_thuoc) {
            this.form.ma_thuoc = response.data.ma_thuoc;
          }
        } else {
          const response = await createThuoc(payload);
          this.message = response?.data?.ma_thuoc
            ? `Đã thêm thuốc ${this.form.ten_thuoc} với mã ${response.data.ma_thuoc}.`
            : `Đã thêm thuốc ${this.form.ten_thuoc}.`;
        }

        await this.resetForm();
        await this.loadData();
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.save = false;
      }
    },

    async removeThuoc(thuoc) {
      if (!this.isAdminUser) {
        this.error = 'Chỉ admin mới có quyền xóa thuốc.';
        return;
      }

      this.loading.deleteId = thuoc.ma_thuoc;
      this.error = '';

      try {
        await deleteThuoc(thuoc.ma_thuoc);
        if (this.selectedThuoc && this.selectedThuoc.ma_thuoc === thuoc.ma_thuoc) {
          await this.resetForm();
        }
        await this.loadData();
        this.message = `Đã xóa thuốc ${thuoc.ten_thuoc}.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.deleteId = null;
      }
    },
  },
};
</script>
