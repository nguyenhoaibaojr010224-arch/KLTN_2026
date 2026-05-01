<template>
  <div class="vstack gap-4">
    <section class="content-card">
      <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
        <div>
          <div class="soft-badge soft-badge--blue mb-3">
            <i class="bi bi-people-fill"></i>
            Quản lý hệ thống
          </div>
          <h2 class="page-section-title mb-2">Quản lý nhân viên</h2>
          <p class="page-section-copy mb-0">
            Quản lý danh sách nhân viên, quyền truy cập, trạng thái làm việc và mật khẩu đăng nhập trên một màn hình.
          </p>
        </div>

        <div class="d-flex flex-column align-items-xl-end gap-2">
          <span class="soft-badge soft-badge--teal">
            <i class="bi bi-person-badge"></i>
            {{ currentUser?.ho_ten || "Chưa đăng nhập" }}
          </span>
          <span class="soft-badge">
            <i class="bi bi-shield-lock"></i>
            {{ roleLabel(currentUser?.vai_tro?.ten_vai_tro) }}
          </span>
        </div>
      </div>
    </section>

    <section class="content-card">
      <div class="row g-3 align-items-end">
        <div class="col-lg-5">
          <label class="form-label fw-semibold">Tìm kiếm nhân viên</label>
          <input
            v-model.trim="keyword"
            class="form-control"
            placeholder="Nhập số điện thoại hoặc họ tên"
            @keyup.enter="handleSearch"
          />
        </div>

        <div class="col-lg-7">
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" @click="loadNhanViens({ notifySuccess: true })" :disabled="loading.list">
              <span v-if="loading.list" class="spinner-border spinner-border-sm me-2"></span>
              Tải danh sách
            </button>
            <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
              <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
              Tìm kiếm
            </button>
            <button class="btn btn-outline-success" @click="openCreateModal">
              <i class="bi bi-plus-circle me-2"></i>
              Thêm nhân viên
            </button>
            <button class="btn btn-outline-secondary" @click="resetState">
              Làm mới
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="row g-4">
      <div class="col-md-6 col-xl-3" v-for="metric in metrics" :key="metric.label">
        <article class="metric-card h-100">
          <div class="d-flex justify-content-between gap-3">
            <div>
              <p class="metric-card__label mb-2">{{ metric.label }}</p>
              <h3 class="metric-card__value mb-1">{{ metric.value }}</h3>
              <span class="metric-card__delta" :class="metric.deltaClass">{{ metric.note }}</span>
            </div>
            <div class="metric-card__icon" :class="metric.iconClass">
              <i :class="metric.icon"></i>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="row g-4">
      <div class="col-12">
        <article class="content-card h-100">
          <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
            <div>
              <h3 class="panel-title mb-1">Danh sách nhân viên</h3>
              <p class="panel-subtitle mb-0">Chọn một nhân viên để sửa thông tin, đổi mật khẩu hoặc xóa.</p>
            </div>
            <span class="soft-badge soft-badge--blue">{{ nhanViens.length }} nhân viên</span>
          </div>

          <div v-if="nhanViens.length" class="table-responsive">
            <table class="table table-master align-middle mb-0">
              <thead>
                <tr>
                  <th>STT</th>
                  <th>Số điện thoại</th>
                  <th>Họ tên</th>
                  <th>Vai trò</th>
                  <th>Trạng thái</th>
                  <th class="text-end">Tác vụ</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(nhanVien, index) in nhanViens"
                  :key="nhanVien.id_nhan_vien"
                  :class="{ 'table-active': selectedId === nhanVien.id_nhan_vien }"
                  @click="setSelectedNhanVien(nhanVien)"
                  style="cursor: pointer;"
                >
                  <td>{{ index + 1 }}</td>
                  <td>
                    <div class="fw-semibold">{{ nhanVien.so_dien_thoai || nhanVien.ten_dang_nhap }}</div>
                    <div class="small text-secondary">{{ nhanVien.bang_cap?.ten_bang_cap || "Chưa cập nhật bằng cấp" }}</div>
                  </td>
                  <td>{{ nhanVien.ho_ten }}</td>
                  <td>{{ roleLabel(nhanVien.vai_tro?.ten_vai_tro) }}</td>
                  <td>
                    <span class="soft-badge" :class="statusBadgeClass(nhanVien.trang_thai)">
                      {{ statusLabel(nhanVien.trang_thai) }}
                    </span>
                  </td>
                  <td>
                    <div class="d-flex justify-content-end flex-wrap gap-2" @click.stop>
                      <button class="btn btn-sm btn-outline-primary" @click="openEditModal(nhanVien)">Sửa</button>
                      <button class="btn btn-sm btn-outline-warning" @click="openPasswordModal(nhanVien)">Đổi mật khẩu</button>
                      <button
                        class="btn btn-sm btn-outline-danger"
                        @click="openDeleteModal(nhanVien)"
                        :disabled="loading.deleteId === nhanVien.id_nhan_vien"
                      >
                        Xóa
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="master-empty">
            <p class="mb-2 fw-semibold">Chưa có dữ liệu để hiển thị.</p>
            <p class="mb-0 text-secondary">Đăng nhập bằng tài khoản admin rồi bấm “Tải danh sách” để lấy dữ liệu từ backend.</p>
          </div>
        </article>
      </div>
    </section>
  </div>

  <div ref="formModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">{{ isEditing ? "Cập nhật nhân viên" : "Thêm nhân viên" }}</h5>
            <p class="mb-0 text-secondary small">
              {{ isEditing ? "Chỉnh sửa thông tin cơ bản của nhân viên đã chọn." : "Nhập đầy đủ thông tin để tạo tài khoản nhân viên mới." }}
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <form class="row g-3" @submit.prevent="handleSubmit">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Số điện thoại đăng nhập</label>
              <input
                v-model.trim="form.so_dien_thoai"
                class="form-control"
                maxlength="10"
                placeholder="Ví dụ: 0901234567"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Họ tên</label>
              <input v-model.trim="form.ho_ten" class="form-control" />
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Vai trò</label>
              <select v-model="form.id_vai_tro" class="form-select">
                <option value="">Chọn vai trò</option>
                <option v-for="vaiTro in vaiTros" :key="vaiTro.id_vai_tro" :value="String(vaiTro.id_vai_tro)">
                  {{ roleLabel(vaiTro.ten_vai_tro) }}
                </option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Bằng cấp</label>
              <select v-model="form.id_bang_cap" class="form-select">
                <option value="">Chọn bằng cấp</option>
                <option v-for="bangCap in bangCaps" :key="bangCap.id_bang_cap" :value="String(bangCap.id_bang_cap)">
                  {{ bangCap.ten_bang_cap }}
                </option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Trạng thái</label>
              <select v-model="form.trang_thai" class="form-select">
                <option value="active">Đang hoạt động</option>
                <option value="inactive">Tạm khóa</option>
              </select>
            </div>

            <template v-if="!isEditing">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Mật khẩu</label>
                <input v-model="form.mat_khau" type="password" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Xác nhận mật khẩu</label>
                <input v-model="form.mat_khau_confirmation" type="password" class="form-control" />
              </div>
            </template>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="btn btn-primary" @click="handleSubmit" :disabled="loading.submit">
            <span v-if="loading.submit" class="spinner-border spinner-border-sm me-2"></span>
            {{ isEditing ? "Lưu cập nhật" : "Tạo nhân viên" }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <div ref="passwordModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Đổi mật khẩu nhân viên</h5>
            <p class="mb-0 text-secondary small">
              {{ selectedNhanVien ? `Đang đổi mật khẩu cho ${selectedNhanVien.ho_ten}.` : "Chọn nhân viên trước khi đổi mật khẩu." }}
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div class="vstack gap-3">
            <div>
              <label class="form-label fw-semibold">Mật khẩu mới</label>
              <input v-model="passwordForm.new_password" type="password" class="form-control" />
            </div>

            <div>
              <label class="form-label fw-semibold">Xác nhận mật khẩu mới</label>
              <input v-model="passwordForm.new_password_confirmation" type="password" class="form-control" />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
          <button class="btn btn-warning" type="button" @click="handleChangePassword" :disabled="loading.password || !selectedNhanVien">
            <span v-if="loading.password" class="spinner-border spinner-border-sm me-2"></span>
            Đổi mật khẩu
          </button>
        </div>
      </div>
    </div>
  </div>

  <div ref="deleteModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Xóa nhân viên</h5>
            <p class="mb-0 text-secondary small">Thao tác này sẽ xóa tài khoản nhân viên khỏi danh sách.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <p class="mb-0">
            Bạn có chắc muốn xóa nhân viên
            <strong>{{ deleteTarget?.ho_ten || "đã chọn" }}</strong>
            không?
          </p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="button" class="btn btn-danger" @click="confirmDelete" :disabled="loading.deleteId !== null || !deleteTarget">
            <span v-if="loading.deleteId !== null" class="spinner-border spinner-border-sm me-2"></span>
            Xóa nhân viên
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from "bootstrap";
import { getBangCaps, getVaiTros } from "../../../api/referenceApi";
import {
  changeNhanVienPassword,
  createNhanVien,
  deleteNhanVien,
  getNhanViens,
  searchNhanViens,
  updateNhanVien,
} from "../../../api/nhanVienApi";
import { getStoredUser, updateAuthUser } from "../../../lib/authStorage";
import { showToast } from "../../../lib/toast";

function createEmptyForm() {
  return {
    so_dien_thoai: "",
    ho_ten: "",
    id_vai_tro: "",
    id_bang_cap: "",
    trang_thai: "active",
    mat_khau: "",
    mat_khau_confirmation: "",
  };
}

function createEmptyPasswordForm() {
  return {
    new_password: "",
    new_password_confirmation: "",
  };
}

export default {
  name: "NhanVienAdmin",
  data() {
    return {
      currentUser: getStoredUser(),
      keyword: "",
      nhanViens: [],
      vaiTros: [],
      bangCaps: [],
      selectedId: null,
      form: createEmptyForm(),
      passwordForm: createEmptyPasswordForm(),
      deleteTarget: null,
      formModal: null,
      passwordModal: null,
      deleteModal: null,
      loading: {
        list: false,
        search: false,
        submit: false,
        password: false,
        deleteId: null,
        meta: false,
      },
    };
  },
  computed: {
    selectedNhanVien() {
      return this.nhanViens.find((item) => item.id_nhan_vien === this.selectedId) || null;
    },
    isEditing() {
      return Boolean(this.selectedNhanVien);
    },
    metrics() {
      const total = this.nhanViens.length;
      const active = this.nhanViens.filter((item) => item.trang_thai === "active").length;
      const inactive = total - active;
      const admins = this.nhanViens.filter((item) => String(item.vai_tro?.ten_vai_tro || "").toLowerCase() === "admin").length;

      return [
        {
          label: "Tổng nhân viên",
          value: total,
          note: "Dữ liệu đang hiển thị",
          deltaClass: "is-positive",
          icon: "bi bi-people",
          iconClass: "metric-card__icon--blue",
        },
        {
          label: "Đang hoạt động",
          value: active,
          note: "Tài khoản đang dùng",
          deltaClass: "is-positive",
          icon: "bi bi-person-check",
          iconClass: "metric-card__icon--teal",
        },
        {
          label: "Tạm khóa",
          value: inactive,
          note: "Tài khoản bị dừng",
          deltaClass: "is-warning",
          icon: "bi bi-person-lock",
          iconClass: "metric-card__icon--orange",
        },
        {
          label: "Quản trị viên",
          value: admins,
          note: "Có quyền quản trị",
          deltaClass: "is-positive",
          icon: "bi bi-shield-lock",
          iconClass: "metric-card__icon--red",
        },
      ];
    },
  },
  mounted() {
    this.formModal = new Modal(this.$refs.formModalEl);
    this.passwordModal = new Modal(this.$refs.passwordModalEl);
    this.deleteModal = new Modal(this.$refs.deleteModalEl);
    this.bootstrapData();
  },
  beforeUnmount() {
    this.formModal?.dispose();
    this.passwordModal?.dispose();
    this.deleteModal?.dispose();
  },
  methods: {
    roleLabel(value) {
      const map = {
        admin: "Quản trị viên",
        staff: "Nhân viên",
        nhan_vien: "Nhân viên",
        nhanvien: "Nhân viên",
      };
      const key = String(value || "").toLowerCase();
      return map[key] || value || "Chưa phân quyền";
    },
    statusLabel(value) {
      const map = {
        active: "Đang hoạt động",
        inactive: "Tạm khóa",
      };
      const key = String(value || "").toLowerCase();
      return map[key] || value || "Không xác định";
    },
    statusBadgeClass(value) {
      return String(value || "").toLowerCase() === "active" ? "soft-badge--teal" : "soft-badge--orange";
    },
    normalizeError(err, fallback = "Đã xảy ra lỗi.") {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(" | ");
      }
      return err?.payload?.message || err?.message || fallback;
    },
    applySelectedToForm(nhanVien) {
      this.form = {
        so_dien_thoai: nhanVien.so_dien_thoai || nhanVien.ten_dang_nhap || "",
        ho_ten: nhanVien.ho_ten || "",
        id_vai_tro: nhanVien.id_vai_tro ? String(nhanVien.id_vai_tro) : "",
        id_bang_cap: nhanVien.id_bang_cap ? String(nhanVien.id_bang_cap) : "",
        trang_thai: nhanVien.trang_thai || "active",
        mat_khau: "",
        mat_khau_confirmation: "",
      };
    },
    resetForm() {
      this.form = createEmptyForm();
      this.passwordForm = createEmptyPasswordForm();
    },
    setSelectedNhanVien(nhanVien) {
      this.selectedId = nhanVien.id_nhan_vien;
    },
    async bootstrapData() {
      try {
        await Promise.all([this.loadMeta(), this.loadNhanViens()]);
      } catch (err) {
        showToast(this.normalizeError(err, "Không thể tải dữ liệu nhân viên."), "error");
      }
    },
    async loadMeta() {
      this.loading.meta = true;
      try {
        const [vaiTroData, bangCapData] = await Promise.all([getVaiTros(), getBangCaps()]);
        this.vaiTros = Array.isArray(vaiTroData) ? vaiTroData : [];
        this.bangCaps = Array.isArray(bangCapData) ? bangCapData : [];
      } finally {
        this.loading.meta = false;
      }
    },
    async loadNhanViens({ notifySuccess = false } = {}) {
      this.loading.list = true;
      try {
        this.nhanViens = await getNhanViens();

        if (this.selectedId) {
          const refreshed = this.nhanViens.find((item) => item.id_nhan_vien === this.selectedId);
          if (!refreshed) {
            this.selectedId = null;
          }
        }

        if (notifySuccess) {
          showToast(`Đã tải ${this.nhanViens.length} nhân viên.`);
        }
      } catch (err) {
        showToast(this.normalizeError(err, "Không thể tải danh sách nhân viên."), "error");
      } finally {
        this.loading.list = false;
      }
    },
    async handleSearch() {
      if (!this.keyword) {
        await this.loadNhanViens({ notifySuccess: true });
        return;
      }

      this.loading.search = true;
      try {
        this.nhanViens = await searchNhanViens(this.keyword);
        this.selectedId = null;
        showToast(`Tìm thấy ${this.nhanViens.length} nhân viên phù hợp.`);
      } catch (err) {
        showToast(this.normalizeError(err, "Không thể tìm kiếm nhân viên."), "error");
      } finally {
        this.loading.search = false;
      }
    },
    openCreateModal() {
      this.selectedId = null;
      this.resetForm();
      this.formModal.show();
    },
    openEditModal(nhanVien) {
      this.setSelectedNhanVien(nhanVien);
      this.applySelectedToForm(nhanVien);
      this.formModal.show();
    },
    openPasswordModal(nhanVien) {
      this.setSelectedNhanVien(nhanVien);
      this.passwordForm = createEmptyPasswordForm();
      this.passwordModal.show();
    },
    openDeleteModal(nhanVien) {
      this.setSelectedNhanVien(nhanVien);
      this.deleteTarget = nhanVien;
      this.deleteModal.show();
    },
    async handleSubmit() {
      this.loading.submit = true;
      try {
        if (this.isEditing) {
          const updatedNhanVien = await updateNhanVien(this.selectedId, {
            so_dien_thoai: this.form.so_dien_thoai,
            ho_ten: this.form.ho_ten,
            id_vai_tro: Number(this.form.id_vai_tro),
            id_bang_cap: Number(this.form.id_bang_cap),
            trang_thai: this.form.trang_thai,
          });

          if (Number(updatedNhanVien.id_nhan_vien) === Number(this.currentUser?.id_nhan_vien)) {
            updateAuthUser({
              ...this.currentUser,
              ...updatedNhanVien,
            });
            this.currentUser = getStoredUser();
          }

          this.formModal.hide();
          showToast("Cập nhật nhân viên thành công.");
        } else {
          await createNhanVien({
            so_dien_thoai: this.form.so_dien_thoai,
            mat_khau: this.form.mat_khau,
            mat_khau_confirmation: this.form.mat_khau_confirmation,
            ho_ten: this.form.ho_ten,
            id_vai_tro: Number(this.form.id_vai_tro),
            id_bang_cap: Number(this.form.id_bang_cap),
            trang_thai: this.form.trang_thai,
          });
          this.formModal.hide();
          this.resetForm();
          showToast("Tạo nhân viên thành công.");
        }

        await this.loadNhanViens();
      } catch (err) {
        showToast(this.normalizeError(err, "Không thể lưu thông tin nhân viên."), "error");
      } finally {
        this.loading.submit = false;
      }
    },
    async handleChangePassword() {
      if (!this.selectedNhanVien) {
        showToast("Hãy chọn nhân viên trước khi đổi mật khẩu.", "error");
        return;
      }

      this.loading.password = true;
      try {
        await changeNhanVienPassword(this.selectedNhanVien.id_nhan_vien, {
          new_password: this.passwordForm.new_password,
          new_password_confirmation: this.passwordForm.new_password_confirmation,
        });
        this.passwordModal.hide();
        this.passwordForm = createEmptyPasswordForm();
        showToast(`Đã đổi mật khẩu cho ${this.selectedNhanVien.ho_ten}.`);
      } catch (err) {
        showToast(this.normalizeError(err, "Không thể đổi mật khẩu nhân viên."), "error");
      } finally {
        this.loading.password = false;
      }
    },
    async confirmDelete() {
      if (!this.deleteTarget) {
        return;
      }

      this.loading.deleteId = this.deleteTarget.id_nhan_vien;
      try {
        await deleteNhanVien(this.deleteTarget.id_nhan_vien);

        if (this.selectedId === this.deleteTarget.id_nhan_vien) {
          this.selectedId = null;
        }

        const deletedName = this.deleteTarget.ho_ten;
        this.deleteTarget = null;
        this.deleteModal.hide();
        await this.loadNhanViens();
        showToast(`Đã xóa nhân viên ${deletedName}.`);
      } catch (err) {
        showToast(this.normalizeError(err, "Không thể xóa nhân viên."), "error");
      } finally {
        this.loading.deleteId = null;
      }
    },
    async resetState() {
      this.keyword = "";
      this.selectedId = null;
      this.deleteTarget = null;
      this.resetForm();
      await this.loadNhanViens({ notifySuccess: true });
    },
  },
};
</script>
