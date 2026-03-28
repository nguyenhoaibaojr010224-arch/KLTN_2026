<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--blue mb-3">
          <i class="bi bi-people-fill"></i>
          Admin module
        </div>
        <h2 class="page-section-title">Quan ly nhan vien</h2>
        <p class="page-section-copy mb-0">
          Trang nay dung truc tiep cac API admin de tao, sua, xoa, tim kiem va doi mat khau nhan vien.
        </p>
      </div>

      <div class="d-flex flex-column align-items-xl-end gap-2">
        <span class="soft-badge soft-badge--teal">
          <i class="bi bi-person-badge"></i>
          {{ currentUser?.ho_ten || "Chua dang nhap" }}
        </span>
        <span class="soft-badge">
          <i class="bi bi-shield-lock"></i>
          {{ currentUser?.vai_tro?.ten_vai_tro || "Khong ro quyen" }}
        </span>
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-5">
        <label class="form-label fw-semibold">Tim kiem nhan vien</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Nhap ten dang nhap hoac ho ten"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-7">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary" @click="loadNhanViens" :disabled="loading.list">
            <span v-if="loading.list" class="spinner-border spinner-border-sm me-2"></span>
            Tai danh sach
          </button>
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tim kiem
          </button>
          <button class="btn btn-outline-secondary" @click="startCreate">Them moi</button>
          <button class="btn btn-outline-secondary" @click="resetState">Lam moi</button>
        </div>
      </div>
    </div>

    <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
    <div v-if="error" class="alert alert-danger mt-4 mb-0">{{ error }}</div>
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

  <section class="row g-4 mt-1">
    <div class="col-xl-8">
      <article class="content-card h-100">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
          <div>
            <h3 class="panel-title">Danh sach nhan vien</h3>
            <p class="panel-subtitle mb-0">Chon mot dong de nap du lieu vao form ben phai.</p>
          </div>
          <span class="soft-badge soft-badge--blue">{{ nhanViens.length }} dong</span>
        </div>

        <div v-if="nhanViens.length" class="table-responsive">
          <table class="table table-master align-middle mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Ten dang nhap</th>
                <th>Ho ten</th>
                <th>Vai tro</th>
                <th>Trang thai</th>
                <th class="text-end">Thao tac</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="nhanVien in nhanViens"
                :key="nhanVien.id_nhan_vien"
                :class="{ 'table-active': selectedId === nhanVien.id_nhan_vien }"
              >
                <td>{{ nhanVien.id_nhan_vien }}</td>
                <td>
                  <div class="fw-semibold">{{ nhanVien.ten_dang_nhap }}</div>
                  <div class="small text-secondary">{{ nhanVien.bang_cap?.ten_bang_cap || "-" }}</div>
                </td>
                <td>{{ nhanVien.ho_ten }}</td>
                <td>{{ nhanVien.vai_tro?.ten_vai_tro || "-" }}</td>
                <td>
                  <span
                    class="soft-badge"
                    :class="nhanVien.trang_thai === 'active' ? 'soft-badge--teal' : 'soft-badge--orange'"
                  >
                    {{ nhanVien.trang_thai }}
                  </span>
                </td>
                <td>
                  <div class="d-flex justify-content-end flex-wrap gap-2">
                    <button class="btn btn-sm btn-outline-primary" @click="selectNhanVien(nhanVien)">Sua</button>
                    <button
                      class="btn btn-sm btn-outline-danger"
                      @click="handleDelete(nhanVien)"
                      :disabled="loading.deleteId === nhanVien.id_nhan_vien"
                    >
                      <span
                        v-if="loading.deleteId === nhanVien.id_nhan_vien"
                        class="spinner-border spinner-border-sm me-1"
                      ></span>
                      Xoa
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chua co du lieu de hien thi.</p>
          <p class="mb-0 text-secondary">Dang nhap bang admin roi bam "Tai danh sach" de FE goi API.</p>
        </div>
      </article>
    </div>

    <div class="col-xl-4">
      <div class="vstack gap-4">
        <article class="content-card h-100">
          <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
            <div>
              <h3 class="panel-title">{{ isEditing ? "Cap nhat nhan vien" : "Tao nhan vien" }}</h3>
              <p class="panel-subtitle mb-0">
                {{ isEditing ? "Chinh sua thong tin co ban cua nhan vien da chon." : "Nhap day du thong tin de tao moi." }}
              </p>
            </div>
            <span class="soft-badge" :class="isEditing ? 'soft-badge--orange' : 'soft-badge--teal'">
              {{ isEditing ? "Edit" : "Create" }}
            </span>
          </div>

          <form class="vstack gap-3" @submit.prevent="handleSubmit">
            <div>
              <label class="form-label fw-semibold">Ten dang nhap</label>
              <input v-model.trim="form.ten_dang_nhap" class="form-control" />
            </div>

            <div>
              <label class="form-label fw-semibold">Ho ten</label>
              <input v-model.trim="form.ho_ten" class="form-control" />
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Vai tro</label>
                <select v-model="form.id_vai_tro" class="form-select">
                  <option value="">Chon vai tro</option>
                  <option v-for="vaiTro in vaiTros" :key="vaiTro.id_vai_tro" :value="String(vaiTro.id_vai_tro)">
                    {{ vaiTro.ten_vai_tro }}
                  </option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Bang cap</label>
                <select v-model="form.id_bang_cap" class="form-select">
                  <option value="">Chon bang cap</option>
                  <option v-for="bangCap in bangCaps" :key="bangCap.id_bang_cap" :value="String(bangCap.id_bang_cap)">
                    {{ bangCap.ten_bang_cap }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="form-label fw-semibold">Trang thai</label>
              <select v-model="form.trang_thai" class="form-select">
                <option value="active">active</option>
                <option value="inactive">inactive</option>
              </select>
            </div>

            <div v-if="!isEditing">
              <label class="form-label fw-semibold">Mat khau</label>
              <input v-model="form.mat_khau" type="password" class="form-control" />
            </div>

            <div v-if="!isEditing">
              <label class="form-label fw-semibold">Xac nhan mat khau</label>
              <input v-model="form.mat_khau_confirmation" type="password" class="form-control" />
            </div>

            <div class="d-flex flex-wrap gap-2 pt-2">
              <button class="btn btn-primary" type="submit" :disabled="loading.submit">
                <span v-if="loading.submit" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? "Luu cap nhat" : "Tao nhan vien" }}
              </button>
              <button class="btn btn-outline-secondary" type="button" @click="resetForm">Xoa form</button>
            </div>
          </form>
        </article>

        <article class="content-card h-100">
          <div class="mb-4">
            <h3 class="panel-title">Doi mat khau</h3>
            <p class="panel-subtitle mb-0">Ap dung cho nhan vien dang duoc chon o bang ben trai.</p>
          </div>

          <div v-if="selectedNhanVien" class="small text-secondary mb-3">
            Dang doi mat khau cho <strong>{{ selectedNhanVien.ho_ten }}</strong>
          </div>

          <form class="vstack gap-3" @submit.prevent="handleChangePassword">
            <div>
              <label class="form-label fw-semibold">Mat khau moi</label>
              <input v-model="passwordForm.new_password" type="password" class="form-control" />
            </div>

            <div>
              <label class="form-label fw-semibold">Xac nhan mat khau moi</label>
              <input v-model="passwordForm.new_password_confirmation" type="password" class="form-control" />
            </div>

            <button class="btn btn-outline-primary" type="submit" :disabled="loading.password || !selectedNhanVien">
              <span v-if="loading.password" class="spinner-border spinner-border-sm me-2"></span>
              Doi mat khau
            </button>
          </form>
        </article>
      </div>
    </div>
  </section>
</template>

<script>
import { getBangCaps, getVaiTros } from "../../../api/referenceApi";
import {
  changeNhanVienPassword,
  createNhanVien,
  deleteNhanVien,
  getNhanViens,
  searchNhanViens,
  updateNhanVien,
} from "../../../api/nhanVienApi";
import { getStoredUser } from "../../../lib/authStorage";
export default {
  data() {
    return {
      currentUser: getStoredUser(),
      keyword: "",
      nhanViens: [],
      vaiTros: [],
      bangCaps: [],
      message: "",
      error: "",
      selectedId: null,
      form: {
        ten_dang_nhap: "",
        ho_ten: "",
        id_vai_tro: "",
        id_bang_cap: "",
        trang_thai: "active",
        mat_khau: "",
        mat_khau_confirmation: "",
      },
      passwordForm: {
        new_password: "",
        new_password_confirmation: "",
      },
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
      const admins = this.nhanViens.filter((item) => item.vai_tro?.ten_vai_tro === "admin").length;

      return [
        {
          label: "Tong nhan vien",
          value: total,
          note: "Du lieu dang duoc render",
          deltaClass: "is-positive",
          icon: "bi bi-people",
          iconClass: "metric-card__icon--blue",
        },
        {
          label: "Dang hoat dong",
          value: active,
          note: "Tai khoan active",
          deltaClass: "is-positive",
          icon: "bi bi-person-check",
          iconClass: "metric-card__icon--teal",
        },
        {
          label: "Tam khoa",
          value: inactive,
          note: "Tai khoan inactive",
          deltaClass: "is-warning",
          icon: "bi bi-person-lock",
          iconClass: "metric-card__icon--orange",
        },
        {
          label: "Quan tri vien",
          value: admins,
          note: "Co quyen admin",
          deltaClass: "is-positive",
          icon: "bi bi-shield-lock",
          iconClass: "metric-card__icon--red",
        },
      ];
    },
  },
  methods: {
    normalizeError(err) {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(" | ");
      }

      return err?.message || "Da xay ra loi.";
    },
    applySelectedToForm(nhanVien) {
      this.form.ten_dang_nhap = nhanVien.ten_dang_nhap || "";
      this.form.ho_ten = nhanVien.ho_ten || "";
      this.form.id_vai_tro = nhanVien.id_vai_tro ? String(nhanVien.id_vai_tro) : "";
      this.form.id_bang_cap = nhanVien.id_bang_cap ? String(nhanVien.id_bang_cap) : "";
      this.form.trang_thai = nhanVien.trang_thai || "active";
      this.form.mat_khau = "";
      this.form.mat_khau_confirmation = "";
    },
    resetForm() {
      this.selectedId = null;
      this.form.ten_dang_nhap = "";
      this.form.ho_ten = "";
      this.form.id_vai_tro = "";
      this.form.id_bang_cap = "";
      this.form.trang_thai = "active";
      this.form.mat_khau = "";
      this.form.mat_khau_confirmation = "";
      this.passwordForm.new_password = "";
      this.passwordForm.new_password_confirmation = "";
    },
    selectNhanVien(nhanVien) {
      this.selectedId = nhanVien.id_nhan_vien;
      this.applySelectedToForm(nhanVien);
      this.passwordForm.new_password = "";
      this.passwordForm.new_password_confirmation = "";
      this.message = `Da nap nhan vien ${nhanVien.ho_ten} vao form.`;
      this.error = "";
    },
    startCreate() {
      this.resetForm();
      this.message = "Dang o che do tao moi nhan vien.";
      this.error = "";
    },
    async loadMeta() {
      this.loading.meta = true;

      try {
        const [vaiTroData, bangCapData] = await Promise.all([getVaiTros(), getBangCaps()]);
        this.vaiTros = vaiTroData;
        this.bangCaps = bangCapData;
      } finally {
        this.loading.meta = false;
      }
    },
    async loadNhanViens() {
      this.loading.list = true;
      this.error = "";

      try {
        this.nhanViens = await getNhanViens();

        if (this.selectedId) {
          const refreshed = this.nhanViens.find((item) => item.id_nhan_vien === this.selectedId);

          if (refreshed) {
            this.applySelectedToForm(refreshed);
          } else {
            this.resetForm();
          }
        }

        this.message = `Da tai ${this.nhanViens.length} nhan vien tu backend.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.list = false;
      }
    },
    async handleSearch() {
      if (!this.keyword) {
        return;
      }

      this.loading.search = true;
      this.error = "";

      try {
        this.nhanViens = await searchNhanViens(this.keyword);
        this.message = `Tim thay ${this.nhanViens.length} nhan vien phu hop.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.search = false;
      }
    },
    async handleSubmit() {
      this.loading.submit = true;
      this.error = "";

      try {
        if (this.isEditing) {
          await updateNhanVien(this.selectedId, {
            ten_dang_nhap: this.form.ten_dang_nhap,
            ho_ten: this.form.ho_ten,
            id_vai_tro: Number(this.form.id_vai_tro),
            id_bang_cap: Number(this.form.id_bang_cap),
            trang_thai: this.form.trang_thai,
          });

          this.message = "Cap nhat nhan vien thanh cong.";
        } else {
          await createNhanVien({
            ten_dang_nhap: this.form.ten_dang_nhap,
            mat_khau: this.form.mat_khau,
            mat_khau_confirmation: this.form.mat_khau_confirmation,
            ho_ten: this.form.ho_ten,
            id_vai_tro: Number(this.form.id_vai_tro),
            id_bang_cap: Number(this.form.id_bang_cap),
            trang_thai: this.form.trang_thai,
          });

          this.message = "Tao nhan vien thanh cong.";
          this.resetForm();
        }

        await this.loadNhanViens();
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.submit = false;
      }
    },
    async handleDelete(nhanVien) {
      const confirmed = window.confirm(`Ban co chac muon xoa nhan vien ${nhanVien.ho_ten}?`);

      if (!confirmed) {
        return;
      }

      this.loading.deleteId = nhanVien.id_nhan_vien;
      this.error = "";

      try {
        await deleteNhanVien(nhanVien.id_nhan_vien);

        if (this.selectedId === nhanVien.id_nhan_vien) {
          this.resetForm();
        }

        this.message = `Da xoa nhan vien ${nhanVien.ho_ten}.`;
        await this.loadNhanViens();
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.deleteId = null;
      }
    },
    async handleChangePassword() {
      if (!this.selectedNhanVien) {
        this.error = "Hay chon nhan vien truoc khi doi mat khau.";
        return;
      }

      this.loading.password = true;
      this.error = "";

      try {
        await changeNhanVienPassword(this.selectedNhanVien.id_nhan_vien, {
          new_password: this.passwordForm.new_password,
          new_password_confirmation: this.passwordForm.new_password_confirmation,
        });

        this.passwordForm.new_password = "";
        this.passwordForm.new_password_confirmation = "";
        this.message = `Da doi mat khau cho ${this.selectedNhanVien.ho_ten}.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.password = false;
      }
    },
    resetState() {
      this.keyword = "";
      this.nhanViens = [];
      this.message = "";
      this.error = "";
      this.resetForm();
      this.loadNhanViens();
    },
  },
  async mounted() {
    try {
      await Promise.all([this.loadMeta(), this.loadNhanViens()]);
    } catch (err) {
      this.error = this.normalizeError(err);
    }
  },
};
</script>

