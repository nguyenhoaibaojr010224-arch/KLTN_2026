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
                  @click="openWorkSessionModal(nhanVien)"
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
                :class="{ 'is-invalid': formErrors.so_dien_thoai }"
                type="tel"
                inputmode="numeric"
                maxlength="10"
                placeholder="Ví dụ: 0901234567"
                @input="clearFormFieldError('so_dien_thoai')"
              />
              <div v-if="formErrors.so_dien_thoai" class="invalid-feedback d-block">
                {{ formErrors.so_dien_thoai }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Họ tên</label>
              <input
                v-model.trim="form.ho_ten"
                class="form-control"
                :class="{ 'is-invalid': formErrors.ho_ten }"
                @input="clearFormFieldError('ho_ten')"
              />
              <div v-if="formErrors.ho_ten" class="invalid-feedback d-block">
                {{ formErrors.ho_ten }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Vai trò</label>
              <select
                v-model="form.id_vai_tro"
                class="form-select"
                :class="{ 'is-invalid': formErrors.id_vai_tro }"
                @change="clearFormFieldError('id_vai_tro')"
              >
                <option value="">Chọn vai trò</option>
                <option v-for="vaiTro in vaiTros" :key="vaiTro.id_vai_tro" :value="String(vaiTro.id_vai_tro)">
                  {{ roleLabel(vaiTro.ten_vai_tro) }}
                </option>
              </select>
              <div v-if="formErrors.id_vai_tro" class="invalid-feedback d-block">
                {{ formErrors.id_vai_tro }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Bằng cấp</label>
              <select
                v-model="form.id_bang_cap"
                class="form-select"
                :class="{ 'is-invalid': formErrors.id_bang_cap }"
                @change="clearFormFieldError('id_bang_cap')"
              >
                <option value="">Chọn bằng cấp</option>
                <option v-for="bangCap in bangCaps" :key="bangCap.id_bang_cap" :value="String(bangCap.id_bang_cap)">
                  {{ bangCap.ten_bang_cap }}
                </option>
              </select>
              <div v-if="formErrors.id_bang_cap" class="invalid-feedback d-block">
                {{ formErrors.id_bang_cap }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Trạng thái</label>
              <select
                v-model="form.trang_thai"
                class="form-select"
                :class="{ 'is-invalid': formErrors.trang_thai }"
                @change="clearFormFieldError('trang_thai')"
              >
                <option value="active">Đang hoạt động</option>
                <option value="inactive">Tạm khóa</option>
              </select>
              <div v-if="formErrors.trang_thai" class="invalid-feedback d-block">
                {{ formErrors.trang_thai }}
              </div>
            </div>

            <template v-if="!isEditing">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Mật khẩu</label>
                <input
                  v-model="form.mat_khau"
                  type="password"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.mat_khau }"
                  autocomplete="new-password"
                  @input="clearFormFieldError('mat_khau')"
                />
                <div v-if="formErrors.mat_khau" class="invalid-feedback d-block">
                  {{ formErrors.mat_khau }}
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Xác nhận mật khẩu</label>
                <input
                  v-model="form.mat_khau_confirmation"
                  type="password"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.mat_khau_confirmation }"
                  autocomplete="new-password"
                  @input="clearFormFieldError('mat_khau_confirmation')"
                />
                <div v-if="formErrors.mat_khau_confirmation" class="invalid-feedback d-block">
                  {{ formErrors.mat_khau_confirmation }}
                </div>
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
              <input
                v-model="passwordForm.new_password"
                type="password"
                class="form-control"
                :class="{ 'is-invalid': passwordErrors.new_password }"
                autocomplete="new-password"
                @input="clearPasswordFieldError('new_password')"
              />
              <div v-if="passwordErrors.new_password" class="invalid-feedback d-block">
                {{ passwordErrors.new_password }}
              </div>
            </div>

            <div>
              <label class="form-label fw-semibold">Xác nhận mật khẩu mới</label>
              <input
                v-model="passwordForm.new_password_confirmation"
                type="password"
                class="form-control"
                :class="{ 'is-invalid': passwordErrors.new_password_confirmation }"
                autocomplete="new-password"
                @input="clearPasswordFieldError('new_password_confirmation')"
              />
              <div v-if="passwordErrors.new_password_confirmation" class="invalid-feedback d-block">
                {{ passwordErrors.new_password_confirmation }}
              </div>
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

  <div ref="workSessionModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Lịch sử ra vào</h5>
            <p class="mb-0 text-secondary small">
              {{ selectedNhanVien?.ho_ten || "Nhân viên" }} - {{ workSessionFilterLabel }}
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3 align-items-end mb-4">
            <div class="col-md-5 col-lg-4">
              <label class="form-label fw-semibold">Chọn ngày làm việc</label>
              <DatePickerInput
                v-model="selectedWorkSessionDate"
                placeholder="dd/mm/yyyy"
                @change="handleWorkSessionDateChange"
              />
            </div>

            <div class="col-md-7 col-lg-8">
              <div class="d-flex flex-wrap gap-2">
                <button
                  class="btn btn-outline-secondary"
                  type="button"
                  :disabled="!isWorkSessionFiltered"
                  @click="clearWorkSessionDateFilter"
                >
                  Tất cả
                </button>
                <button
                  class="btn btn-outline-primary"
                  type="button"
                  :disabled="loading.workSessions"
                  @click="loadWorkSessions"
                >
                  <span v-if="loading.workSessions" class="spinner-border spinner-border-sm me-2"></span>
                  Tải lại
                </button>
              </div>
            </div>
          </div>

          <div v-if="loading.workSessions" class="master-empty">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Đang tải lịch sử ra vào...
          </div>

          <div v-else-if="workSessionData.tracking_enabled === false" class="alert alert-info mb-0">
            {{ workSessionData.message || "Tài khoản này không tính lịch sử ra vào làm việc." }}
          </div>

          <template v-else>
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100">
                  <div class="small text-secondary mb-1">Tổng phiên</div>
                  <div class="h4 fw-bold mb-0">{{ workSessionSummary.tong_phien }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100">
                  <div class="small text-secondary mb-1">Tổng thời gian làm</div>
                  <div class="h4 fw-bold mb-0">{{ formatDuration(workSessionSummary.tong_giay_lam) }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100">
                  <div class="small text-secondary mb-1">Trạng thái hiện tại</div>
                  <span class="soft-badge" :class="workSessionSummary.dang_lam ? 'soft-badge--teal' : 'soft-badge--blue'">
                    {{ workSessionSummary.dang_lam ? "Đang làm" : "Không có phiên mở" }}
                  </span>
                </div>
              </div>
            </div>

            <div v-if="workSessions.length" class="table-responsive">
              <table class="table table-master align-middle mb-0">
                <thead>
                  <tr>
                    <th>Ngày làm</th>
                    <th>Check in</th>
                    <th>Check out</th>
                    <th>Số giờ làm</th>
                    <th>Trạng thái</th>
                    <th>Lý do ra</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="session in workSessions"
                    :key="session.id"
                    :class="{ 'table-active': selectedSalesSessionId === session.id }"
                    style="cursor: pointer;"
                    @click="loadSessionSales(session)"
                  >
                    <td>{{ formatDate(session.ngay) }}</td>
                    <td>{{ formatDateTime(session.thoi_gian_vao) }}</td>
                    <td>{{ formatDateTime(session.thoi_gian_ra) }}</td>
                    <td>
                      <div class="fw-semibold">{{ formatDuration(session.thoi_luong_giay) }}</div>
                    </td>
                    <td>
                      <span class="soft-badge" :class="workSessionStatusBadgeClass(session.trang_thai)">
                        {{ workSessionStatusLabel(session.trang_thai) }}
                      </span>
                    </td>
                    <td>{{ logoutReasonLabel(session.ly_do_dang_xuat) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="master-empty">
              <p class="mb-0 fw-semibold">Không có ca làm việc trong ngày đã chọn.</p>
            </div>

            <section v-if="selectedSalesSessionId" class="mt-4 border-top pt-4">
              <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                  <h6 class="fw-bold mb-1">Đơn hàng đã bán trong ngày {{ selectedSalesDateLabel }}</h6>
                  <p class="text-secondary small mb-0">
                    {{ selectedNhanVien?.ho_ten || "Nhân viên" }} bán {{ salesSummary.tong_don_hang }} đơn,
                    {{ salesSummary.tong_san_pham }} sản phẩm.
                  </p>
                </div>
                <div class="text-lg-end">
                  <div class="small text-secondary">Tổng doanh thu</div>
                  <div class="h5 fw-bold mb-0">{{ formatCurrency(salesSummary.tong_doanh_thu) }}</div>
                </div>
              </div>

              <div v-if="loading.sales" class="master-empty">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Đang tải đơn hàng...
              </div>

              <div v-else-if="salesOrders.length" class="vstack gap-3">
                <article v-for="order in salesOrders" :key="order.id_hoa_don" class="border rounded-3 p-3">
                  <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                    <div>
                      <div class="fw-bold">{{ order.ma_hoa_don || `Hóa đơn #${order.id_hoa_don}` }}</div>
                      <div class="small text-secondary">
                        {{ formatDateTime(order.ngay_ban) }} ·
                        {{ order.khach_hang?.ten_khach_hang || "Khách lẻ" }}
                        <span v-if="order.khach_hang?.so_dien_thoai">· {{ order.khach_hang.so_dien_thoai }}</span>
                      </div>
                    </div>
                    <div class="text-lg-end">
                      <span class="soft-badge soft-badge--blue">{{ order.trang_thai }}</span>
                      <div class="fw-bold mt-2">{{ formatCurrency(order.tien_thanh_toan) }}</div>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead>
                        <tr>
                          <th>Sản phẩm</th>
                          <th>Số lô</th>
                          <th>Đơn vị</th>
                          <th class="text-end">SL</th>
                          <th class="text-end">Giá bán</th>
                          <th class="text-end">Thành tiền</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="item in order.items" :key="item.id">
                          <td>
                            <div class="fw-semibold">{{ item.ten_thuoc }}</div>
                            <div class="small text-secondary">{{ item.ma_thuoc }}{{ item.ham_luong ? ` · ${item.ham_luong}` : "" }}</div>
                          </td>
                          <td>{{ item.so_lo || "Không có" }}</td>
                          <td>{{ item.don_vi_ban || "Không có" }}</td>
                          <td class="text-end">{{ item.so_luong }}</td>
                          <td class="text-end">{{ formatCurrency(item.gia_ban) }}</td>
                          <td class="text-end fw-semibold">{{ formatCurrency(item.thanh_tien) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </article>
              </div>

              <div v-else class="master-empty">
                <p class="mb-0 fw-semibold">Ngày này nhân viên chưa bán đơn hàng nào.</p>
              </div>
            </section>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from "bootstrap";
import DatePickerInput from "../../Common/DatePickerInput.vue";
import { getBangCaps, getVaiTros } from "../../../api/referenceApi";
import {
  changeNhanVienPassword,
  createNhanVien,
  deleteNhanVien,
  getNhanVienSalesByDate,
  getNhanVienWorkSessions,
  getNhanViens,
  searchNhanViens,
  updateNhanVien,
} from "../../../api/nhanVienApi";
import { getStoredUser, updateAuthUser } from "../../../lib/authStorage";
import { parseDateInputValue } from "../../../lib/dateInput";
import { normalizeApiError } from "../../../lib/errorMessages";
import { validateStrongPassword } from "../../../lib/passwordRules";
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

function createEmptyFormErrors() {
  return {
    so_dien_thoai: "",
    ho_ten: "",
    id_vai_tro: "",
    id_bang_cap: "",
    trang_thai: "",
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

function createEmptyPasswordErrors() {
  return {
    new_password: "",
    new_password_confirmation: "",
  };
}

function createEmptyWorkSessionData() {
  return {
    tracking_enabled: true,
    message: "",
    filter: {
      type: "all",
      value: null,
      label: "Tất cả",
    },
    summary: {
      tong_phien: 0,
      tong_giay_lam: 0,
      tong_gio_lam: 0,
      dang_lam: false,
    },
    sessions: [],
  };
}

function createEmptySalesData() {
  return {
    filter: {
      date: "",
      label: "",
    },
    summary: {
      tong_don_hang: 0,
      tong_san_pham: 0,
      tong_doanh_thu: 0,
    },
    orders: [],
  };
}

export default {
  name: "NhanVienAdmin",
  components: {
    DatePickerInput,
  },
  data() {
    return {
      currentUser: getStoredUser(),
      keyword: "",
      nhanViens: [],
      vaiTros: [],
      bangCaps: [],
      selectedId: null,
      workSessionFilter: {
        type: "all",
        value: null,
      },
      workSessionData: createEmptyWorkSessionData(),
      salesData: createEmptySalesData(),
      selectedSalesSessionId: null,
      form: createEmptyForm(),
      formErrors: createEmptyFormErrors(),
      passwordForm: createEmptyPasswordForm(),
      passwordErrors: createEmptyPasswordErrors(),
      deleteTarget: null,
      formModal: null,
      passwordModal: null,
      deleteModal: null,
      workSessionModal: null,
      selectedWorkSessionDate: "",
      loading: {
        list: false,
        search: false,
        submit: false,
        password: false,
        deleteId: null,
        meta: false,
        workSessions: false,
        sales: false,
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
    workSessionSummary() {
      return this.workSessionData?.summary || createEmptyWorkSessionData().summary;
    },
    workSessions() {
      return Array.isArray(this.workSessionData?.sessions) ? this.workSessionData.sessions : [];
    },
    workSessionFilterLabel() {
      return this.workSessionData?.filter?.label || "Tất cả";
    },
    isWorkSessionFiltered() {
      return this.workSessionData?.filter?.type && this.workSessionData.filter.type !== "all";
    },
    salesOrders() {
      return Array.isArray(this.salesData?.orders) ? this.salesData.orders : [];
    },
    salesSummary() {
      return this.salesData?.summary || createEmptySalesData().summary;
    },
    selectedSalesDateLabel() {
      return this.salesData?.filter?.label || "";
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
    this.workSessionModal = new Modal(this.$refs.workSessionModalEl);
    const routeKeyword = String(this.$route.query.q || "").trim();
    if (routeKeyword) {
      this.keyword = routeKeyword;
    }
    this.bootstrapData();
  },
  beforeUnmount() {
    this.formModal?.dispose();
    this.passwordModal?.dispose();
    this.deleteModal?.dispose();
    this.workSessionModal?.dispose();
  },
  watch: {
    "$route.query.q": {
      handler(nextValue) {
        const nextKeyword = String(nextValue || "").trim();

        if (nextKeyword === this.keyword) {
          return;
        }

        this.keyword = nextKeyword;

        if (nextKeyword) {
          this.handleSearch(true);
          return;
        }

        this.selectedId = null;
        this.workSessionData = createEmptyWorkSessionData();
        this.salesData = createEmptySalesData();
        this.selectedSalesSessionId = null;
        this.loadNhanViens();
      },
    },
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
    workSessionStatusLabel(value) {
      const map = {
        dang_lam: "Đang làm",
        da_dang_xuat: "Đã đăng xuất",
        tu_het_han: "Tự hết hạn",
      };
      return map[value] || "Không xác định";
    },
    workSessionStatusBadgeClass(value) {
      const map = {
        dang_lam: "soft-badge--teal",
        da_dang_xuat: "soft-badge--blue",
        tu_het_han: "soft-badge--orange",
      };
      return map[value] || "";
    },
    logoutReasonLabel(value) {
      const map = {
        manual: "Đăng xuất",
        expired: "Tự hết hạn",
        relogin: "Đăng nhập lại",
      };
      return map[value] || "Đang mở";
    },
    formatDateTime(value) {
      if (!value) {
        return "Chưa có";
      }

      return new Intl.DateTimeFormat("vi-VN", {
        dateStyle: "short",
        timeStyle: "short",
      }).format(new Date(value));
    },
    formatDate(value) {
      if (!value) {
        return "Chưa có";
      }

      return new Intl.DateTimeFormat("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      }).format(new Date(`${value}T00:00:00`));
    },
    formatMonth(value) {
      if (!value) {
        return "Chưa có";
      }

      const [year, month] = String(value).split("-");

      return month && year ? `${month}/${year}` : value;
    },
    formatDuration(seconds = 0) {
      const totalMinutes = Math.max(0, Math.round(Number(seconds || 0) / 60));
      const hours = Math.floor(totalMinutes / 60);
      const minutes = totalMinutes % 60;

      if (!hours) {
        return `${minutes} phút`;
      }

      return minutes ? `${hours} giờ ${minutes} phút` : `${hours} giờ`;
    },
    formatCurrency(value = 0) {
      const n = Number(value || 0);
      if (Math.abs(n) >= 1e9) {
        const ty = n / 1e9;
        return (Math.abs(ty) >= 10 ? Math.round(ty) : ty.toFixed(2).replace('.', ',')) + ' tỷ đ';
      }
      return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
        maximumFractionDigits: 0,
      }).format(n);
    },
    normalizeError(err, fallback = "Đã xảy ra lỗi.") {
      if (err?.payload?.errors) {
        return Object.entries(err.payload.errors)
          .flatMap(([field, messages]) => (Array.isArray(messages) ? messages : [messages]).map((message) => this.translateValidationMessage(field, message)))
          .join(" | ");
      }
      return normalizeApiError(err, fallback, {
        so_dien_thoai: "số điện thoại đăng nhập",
        ho_ten: "họ tên",
        id_vai_tro: "vai trò",
        id_bang_cap: "bằng cấp",
      });
    },
    translateValidationMessage(field, message) {
      const text = String(message || "").trim();
      const lower = text.toLowerCase();
      const labels = {
        so_dien_thoai: "số điện thoại đăng nhập",
        ho_ten: "họ tên",
        id_vai_tro: "vai trò",
        id_bang_cap: "bằng cấp",
        trang_thai: "trạng thái",
        mat_khau: "mật khẩu",
        mat_khau_confirmation: "xác nhận mật khẩu",
        new_password: "mật khẩu mới",
        new_password_confirmation: "xác nhận mật khẩu mới",
      };
      const label = labels[field] || "thông tin";

      if (!text) {
        return "Dữ liệu không hợp lệ.";
      }

      if (lower.includes("required")) {
        return field === "id_vai_tro" || field === "id_bang_cap" || field === "trang_thai"
          ? `Vui lòng chọn ${label}.`
          : `Vui lòng nhập ${label}.`;
      }

      if (lower.includes("unique") || lower.includes("already been taken")) {
        return "Số điện thoại này đã được sử dụng.";
      }

      if (lower.includes("confirmed") || lower.includes("confirmation")) {
        return "Xác nhận mật khẩu không khớp.";
      }

      if (lower.includes("size") && field === "so_dien_thoai") {
        return "Số điện thoại đăng nhập phải có đúng 10 chữ số.";
      }

      if (lower.includes("regex") && (field.includes("password") || field.includes("mat_khau"))) {
        return "Mật khẩu phải có ít nhất 1 chữ in hoa và 1 ký tự đặc biệt.";
      }

      if (lower.includes("min") && (field.includes("password") || field.includes("mat_khau"))) {
        return "Mật khẩu phải có ít nhất 8 ký tự.";
      }

      if (lower.includes("min") && field === "ho_ten") {
        return "Họ tên phải có ít nhất 5 ký tự.";
      }

      if (lower.includes("exists")) {
        return "Dữ liệu được chọn không hợp lệ.";
      }

      if (lower.includes("invalid") || lower.includes("in:")) {
        return "Dữ liệu không hợp lệ.";
      }

      return text;
    },
    firstValidationMessage(messages) {
      if (Array.isArray(messages)) {
        return messages[0] || "";
      }

      return messages || "";
    },
    clearFormErrors() {
      this.formErrors = createEmptyFormErrors();
    },
    clearPasswordErrors() {
      this.passwordErrors = createEmptyPasswordErrors();
    },
    clearFormFieldError(field) {
      if (Object.prototype.hasOwnProperty.call(this.formErrors, field)) {
        this.formErrors[field] = "";
      }
    },
    clearPasswordFieldError(field) {
      if (Object.prototype.hasOwnProperty.call(this.passwordErrors, field)) {
        this.passwordErrors[field] = "";
      }
    },
    applyApiFieldErrors(errors, target = "form") {
      if (!errors || typeof errors !== "object") {
        return false;
      }

      const errorBag = target === "password" ? createEmptyPasswordErrors() : createEmptyFormErrors();

      Object.keys(errorBag).forEach((field) => {
        if (errors[field]) {
          const message = this.firstValidationMessage(errors[field]);
          const normalizedMessage = this.translateValidationMessage(field, message);
          const isConfirmationError = String(message || "").toLowerCase().includes("confirm")
            || normalizedMessage.toLowerCase().includes("xác nhận");

          if (isConfirmationError && field === "mat_khau" && errorBag.mat_khau_confirmation !== undefined) {
            errorBag.mat_khau_confirmation = normalizedMessage;
            return;
          }

          if (isConfirmationError && field === "new_password" && errorBag.new_password_confirmation !== undefined) {
            errorBag.new_password_confirmation = normalizedMessage;
            return;
          }

          errorBag[field] = normalizedMessage;
        }
      });

      if (target === "password") {
        this.passwordErrors = errorBag;
      } else {
        this.formErrors = errorBag;
      }

      return Object.values(errorBag).some(Boolean);
    },
    validateEmployeeForm() {
      const errors = createEmptyFormErrors();
      const phone = String(this.form.so_dien_thoai || "").trim();
      const fullName = String(this.form.ho_ten || "").trim();

      if (!phone) {
        errors.so_dien_thoai = "Vui lòng nhập số điện thoại đăng nhập.";
      } else if (!/^\d{10}$/.test(phone)) {
        errors.so_dien_thoai = "Số điện thoại đăng nhập phải có đúng 10 chữ số.";
      }

      if (!fullName) {
        errors.ho_ten = "Vui lòng nhập họ tên.";
      } else if (fullName.length < 5) {
        errors.ho_ten = "Họ tên phải có ít nhất 5 ký tự.";
      }

      if (!this.form.id_vai_tro) {
        errors.id_vai_tro = "Vui lòng chọn vai trò.";
      }

      if (!this.form.id_bang_cap) {
        errors.id_bang_cap = "Vui lòng chọn bằng cấp.";
      }

      if (!["active", "inactive"].includes(this.form.trang_thai)) {
        errors.trang_thai = "Vui lòng chọn trạng thái hợp lệ.";
      }

      if (!this.isEditing) {
        errors.mat_khau = validateStrongPassword(this.form.mat_khau, "Mật khẩu");

        if (!this.form.mat_khau_confirmation) {
          errors.mat_khau_confirmation = "Vui lòng nhập xác nhận mật khẩu.";
        } else if (this.form.mat_khau_confirmation !== this.form.mat_khau) {
          errors.mat_khau_confirmation = "Xác nhận mật khẩu không khớp.";
        }
      }

      this.formErrors = errors;

      return !Object.values(errors).some(Boolean);
    },
    validatePasswordForm() {
      const errors = createEmptyPasswordErrors();

      errors.new_password = validateStrongPassword(this.passwordForm.new_password, "Mật khẩu mới");

      if (!this.passwordForm.new_password_confirmation) {
        errors.new_password_confirmation = "Vui lòng nhập xác nhận mật khẩu mới.";
      } else if (this.passwordForm.new_password_confirmation !== this.passwordForm.new_password) {
        errors.new_password_confirmation = "Xác nhận mật khẩu mới không khớp.";
      }

      this.passwordErrors = errors;

      return !Object.values(errors).some(Boolean);
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
      this.clearFormErrors();
      this.passwordForm = createEmptyPasswordForm();
      this.clearPasswordErrors();
    },
    setSelectedNhanVien(nhanVien) {
      this.selectedId = nhanVien.id_nhan_vien;
    },
    async openWorkSessionModal(nhanVien) {
      this.setSelectedNhanVien(nhanVien);
      this.workSessionFilter = {
        type: "all",
        value: null,
      };
      this.selectedWorkSessionDate = "";
      this.workSessionData = createEmptyWorkSessionData();
      this.salesData = createEmptySalesData();
      this.selectedSalesSessionId = null;
      this.workSessionModal.show();
      await this.loadWorkSessions();
    },
    async bootstrapData() {
      try {
        await this.loadMeta();
        if (this.keyword) {
          await this.handleSearch(true);
          return;
        }

        await this.loadNhanViens();
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
            this.workSessionData = createEmptyWorkSessionData();
            this.salesData = createEmptySalesData();
            this.selectedSalesSessionId = null;
          } else {
            await this.loadWorkSessions();
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
    buildWorkSessionQuery() {
      if (this.workSessionFilter.type === "date") {
        return { date: this.workSessionFilter.value };
      }

      if (this.workSessionFilter.type === "month") {
        return { month: this.workSessionFilter.value };
      }

      if (this.workSessionFilter.type === "year") {
        return { year: this.workSessionFilter.value };
      }

      return {};
    },
    async loadWorkSessions() {
      if (!this.selectedId) {
        this.workSessionData = createEmptyWorkSessionData();
        this.salesData = createEmptySalesData();
        this.selectedSalesSessionId = null;
        return;
      }

      this.loading.workSessions = true;
      try {
        this.workSessionData = await getNhanVienWorkSessions(this.selectedId, this.buildWorkSessionQuery());
      } catch (err) {
        this.workSessionData = createEmptyWorkSessionData();
        this.salesData = createEmptySalesData();
        this.selectedSalesSessionId = null;
        showToast(this.normalizeError(err, "Không thể tải lịch sử ra vào của nhân viên."), "error");
      } finally {
        this.loading.workSessions = false;
      }
    },
    async applyWorkSessionFilter(type = "all", value = null) {
      this.workSessionFilter = {
        type,
        value,
      };
      this.salesData = createEmptySalesData();
      this.selectedSalesSessionId = null;
      await this.loadWorkSessions();
    },
    async loadSessionSales(session) {
      if (!this.selectedId || !session?.ngay) {
        return;
      }

      this.selectedSalesSessionId = session.id;
      this.salesData = {
        ...createEmptySalesData(),
        filter: {
          date: session.ngay,
          label: this.formatDate(session.ngay),
        },
      };
      this.loading.sales = true;

      try {
        this.salesData = await getNhanVienSalesByDate(this.selectedId, session.ngay);
      } catch (err) {
        this.salesData = createEmptySalesData();
        showToast(this.normalizeError(err, "Không thể tải đơn hàng của nhân viên trong ngày này."), "error");
      } finally {
        this.loading.sales = false;
      }
    },
    async handleWorkSessionDateChange() {
      if (!this.selectedWorkSessionDate) {
        await this.clearWorkSessionDateFilter();
        return;
      }

      const selectedDate = parseDateInputValue(this.selectedWorkSessionDate);
      if (!selectedDate) {
        showToast("Ngày làm việc phải theo định dạng dd/mm/yyyy.", "error");
        return;
      }

      await this.applyWorkSessionFilter("date", selectedDate);
    },
    async clearWorkSessionDateFilter() {
      this.selectedWorkSessionDate = "";
      await this.applyWorkSessionFilter();
    },
    async handleSearch(silent = false) {
      if (!this.keyword) {
        await this.loadNhanViens({ notifySuccess: true });
        return;
      }

      this.loading.search = true;
      try {
        this.nhanViens = await searchNhanViens(this.keyword);
        this.selectedId = null;
        this.workSessionData = createEmptyWorkSessionData();
        this.salesData = createEmptySalesData();
        this.selectedSalesSessionId = null;
        if (!silent) {
          showToast(`Tìm thấy ${this.nhanViens.length} nhân viên phù hợp.`);
          this.$router.replace({
            path: "/nhan-viens",
            query: this.keyword ? { q: this.keyword } : {},
          });
        }
      } catch (err) {
        if (!silent) {
          showToast(this.normalizeError(err, "Không thể tìm kiếm nhân viên."), "error");
        }
      } finally {
        this.loading.search = false;
      }
    },
    openCreateModal() {
      this.selectedId = null;
      this.workSessionData = createEmptyWorkSessionData();
      this.salesData = createEmptySalesData();
      this.selectedSalesSessionId = null;
      this.resetForm();
      this.formModal.show();
    },
    openEditModal(nhanVien) {
      this.setSelectedNhanVien(nhanVien);
      this.applySelectedToForm(nhanVien);
      this.clearFormErrors();
      this.formModal.show();
    },
    openPasswordModal(nhanVien) {
      this.setSelectedNhanVien(nhanVien);
      this.passwordForm = createEmptyPasswordForm();
      this.clearPasswordErrors();
      this.passwordModal.show();
    },
    openDeleteModal(nhanVien) {
      this.setSelectedNhanVien(nhanVien);
      this.deleteTarget = nhanVien;
      this.deleteModal.show();
    },
    async handleSubmit() {
      if (!this.validateEmployeeForm()) {
        showToast("Vui lòng kiểm tra lại các trường thông tin bên trên.", "error");
        return;
      }

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
        if (this.applyApiFieldErrors(err?.payload?.errors, "form")) {
          showToast("Vui lòng kiểm tra lại các trường thông tin bên trên.", "error");
        } else {
          showToast(this.normalizeError(err, "Không thể lưu thông tin nhân viên."), "error");
        }
      } finally {
        this.loading.submit = false;
      }
    },
    async handleChangePassword() {
      if (!this.selectedNhanVien) {
        showToast("Hãy chọn nhân viên trước khi đổi mật khẩu.", "error");
        return;
      }

      if (!this.validatePasswordForm()) {
        showToast("Vui lòng kiểm tra lại các trường thông tin bên trên.", "error");
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
        this.clearPasswordErrors();
        showToast(`Đã đổi mật khẩu cho ${this.selectedNhanVien.ho_ten}.`);
      } catch (err) {
        if (this.applyApiFieldErrors(err?.payload?.errors, "password")) {
          showToast("Vui lòng kiểm tra lại các trường thông tin bên trên.", "error");
        } else {
          showToast(this.normalizeError(err, "Không thể đổi mật khẩu nhân viên."), "error");
        }
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
          this.workSessionData = createEmptyWorkSessionData();
          this.salesData = createEmptySalesData();
          this.selectedSalesSessionId = null;
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
      this.workSessionFilter = {
        type: "all",
        value: null,
      };
      this.workSessionData = createEmptyWorkSessionData();
      this.salesData = createEmptySalesData();
      this.selectedSalesSessionId = null;
      this.resetForm();
      this.$router.replace({
        path: "/nhan-viens",
        query: {},
      });
      await this.loadNhanViens({ notifySuccess: true });
    },
  },
};
</script>
