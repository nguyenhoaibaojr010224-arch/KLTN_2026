<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--blue mb-3">
          <i class="bi bi-capsule-pill"></i>
          Quản lý thuốc
        </div>
        <h2 class="page-section-title">Danh sách thuốc và cập nhật dữ liệu</h2>
        <p class="page-section-copy mb-0">
          Quản lý trực tiếp dữ liệu thuốc theo hệ thống hiện tại: mã thuốc, tên thuốc, nhãn, nhà sản xuất,
          giá bán, ảnh thuốc và trạng thái kinh doanh.
        </p>
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-5">
        <label class="form-label fw-semibold">Tìm thuốc</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Nhập tên thuốc, mã thuốc, nhãn hoặc nhà sản xuất"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-7">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary" @click="loadData" :disabled="loading.sync">
            <span v-if="loading.sync" class="spinner-border spinner-border-sm me-2"></span>
            Đồng bộ dữ liệu
          </button>
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tìm kiếm
          </button>
          <button class="btn btn-outline-secondary" @click="resetView">Làm mới bộ lọc</button>
          <button class="btn btn-success" @click="openCreateModal" :disabled="!isAdminUser">
            <i class="bi bi-plus-circle me-2"></i>
            Thêm thuốc
          </button>
        </div>
      </div>
    </div>

    <div v-if="!isAdminUser" class="alert alert-warning mt-4 mb-0">
      Tài khoản nhân viên chỉ có quyền xem và tìm kiếm. Thêm, sửa, xóa thuốc yêu cầu quyền quản trị viên.
    </div>
    <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
    <div v-if="error" class="alert alert-danger mt-4 mb-0">{{ error }}</div>
  </section>

  <section class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3" v-for="metric in metrics" :key="metric.label">
      <article
        class="metric-card h-100"
        :class="{ 'metric-card--clickable': metric.filterStatus, 'metric-card--active': activeStatusFilter === metric.filterStatus }"
        :role="metric.filterStatus ? 'button' : null"
        :tabindex="metric.filterStatus ? 0 : null"
        @click="handleMetricClick(metric)"
        @keydown.enter.prevent="handleMetricClick(metric)"
        @keydown.space.prevent="handleMetricClick(metric)"
      >
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

  <section class="content-card">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h3 class="panel-title">Danh sách thuốc</h3>
        <p class="panel-subtitle mb-0">Bấm sửa để mở modal và cập nhật thông tin thuốc.</p>
      </div>
      <span class="soft-badge soft-badge--teal">{{ filteredThuocs.length }} thuốc</span>
    </div>

    <div v-if="filteredThuocs.length">
      <div class="table-responsive">
        <table class="table table-master align-middle mb-0">
          <thead>
            <tr>
              <th>Thuốc</th>
              <th>Nhãn</th>
              <th>Đơn vị</th>
              <th>Giá bán</th>
              <th>Trạng thái</th>
              <th class="text-end">Tác vụ</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="thuoc in visibleThuocs"
              :key="thuoc.ma_thuoc"
              :class="{ 'table-active': selectedThuoc && selectedThuoc.ma_thuoc === thuoc.ma_thuoc }"
            >
              <td>
                <div class="d-flex align-items-start gap-3">
                  <img
                    v-if="shouldShowThuocImage(thuoc)"
                    :src="thuoc.hinh_anh_url"
                    alt="Ảnh thuốc"
                    style="width: 56px; height: 56px; object-fit: cover; border-radius: 14px; border: 1px solid #d9e4ff"
                    @error="handleThuocImageError(thuoc)"
                  />
                  <div
                    v-else
                    class="d-grid place-items-center text-primary bg-primary-subtle"
                    style="width: 56px; height: 56px; border-radius: 14px"
                  >
                    <i class="bi bi-capsule-pill fs-4"></i>
                  </div>

                  <div>
                    <div class="fw-semibold">{{ thuoc.ten_thuoc }}</div>
                    <div class="small text-secondary">{{ thuoc.ma_thuoc }}</div>
                    <div class="small text-secondary">{{ getNhaSanXuatName(thuoc) || "-" }}</div>
                  </div>
                </div>
              </td>
              <td>{{ getNhanDisplay(thuoc) || "-" }}</td>
              <td>{{ thuoc.don_vi_tinh || "-" }}</td>
              <td>{{ formatCurrency(thuoc.gia_ban) }}</td>
              <td>
                <span class="soft-badge" :class="statusBadgeClass(thuoc.trang_thai)">
                  {{ thuoc.trang_thai || "Còn bán" }}
                </span>
              </td>
              <td class="text-end">
                <div class="d-flex justify-content-end gap-2">
                  <button class="btn btn-sm btn-outline-primary" @click="openEditModal(thuoc)">Sửa</button>
                  <button
                    v-if="isAdminUser"
                    class="btn btn-sm btn-outline-danger"
                    @click="openDeleteModal(thuoc)"
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

      <div v-if="canLoadMoreThuocs" class="d-flex justify-content-center mt-4 pt-4 border-top">
        <button class="btn btn-outline-primary px-4" @click="showMoreThuocs">Xem thêm</button>
      </div>
    </div>

    <div v-else class="master-empty">
      <p class="mb-2 fw-semibold">Chưa có dữ liệu thuốc.</p>
      <p class="mb-0 text-secondary">Hãy đồng bộ dữ liệu hoặc thử lại với từ khóa khác.</p>
    </div>
  </section>

  <div ref="thuocModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">{{ form.originalId ? "Cập nhật thuốc" : "Thêm thuốc mới" }}</h5>
            <p class="mb-0 text-secondary small">
              {{
                form.originalId
                  ? `Đang chỉnh sửa thuốc ${form.ten_thuoc || form.ma_thuoc}`
                  : "Nhập thông tin cơ bản để tạo thuốc mới."
              }}
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div class="vstack gap-3">
            <div class="row g-3">
              <div class="col-md-5">
                <label class="form-label fw-semibold">Mã thuốc</label>
                <input class="form-control" :value="displayMaThuoc" disabled />
                <div class="form-text">Mã được cấp tự động.</div>
              </div>
              <div class="col-md-7">
                <label class="form-label fw-semibold">Tên thuốc</label>
                <input
                  v-model.trim="form.ten_thuoc"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.ten_thuoc }"
                  placeholder="Nhập tên thuốc"
                  @input="clearFieldError('ten_thuoc')"
                />
                <div v-if="formErrors.ten_thuoc" class="invalid-feedback d-block">{{ formErrors.ten_thuoc }}</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Nhãn</label>
                <input
                  v-model.trim="form.nhan"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.nhan }"
                  placeholder="Ví dụ: thuốc ho, đau họng, hạ sốt"
                  @input="clearFieldError('nhan')"
                />
                <div v-if="formErrors.nhan" class="invalid-feedback d-block">{{ formErrors.nhan }}</div>
                <div class="form-text">Có thể nhập nhiều nhãn, ngăn cách bằng dấu phẩy.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Danh mục</label>
                <select
                  v-model="form.danh_muc_cha_slug"
                  class="form-select"
                  :class="{ 'is-invalid': formErrors.danh_muc_cha_slug }"
                  @change="handleDanhMucChaChange($event.target.value)"
                >
                  <option value="">Chọn danh mục</option>
                  <option v-for="item in danhMucChaOptions" :key="item.slug" :value="item.slug">
                    {{ item.label }}
                  </option>
                </select>
                <div v-if="formErrors.danh_muc_cha_slug" class="invalid-feedback d-block">{{ formErrors.danh_muc_cha_slug }}</div>
              </div>
            </div>

            <div class="row g-3">
              <div v-if="showDanhMucCon" class="col-md-6">
                <label class="form-label fw-semibold">Nhánh</label>
                <select
                  :key="form.danh_muc_cha_slug || 'no-parent'"
                  v-model="form.danh_muc_con_slug"
                  class="form-select"
                  :class="{ 'is-invalid': formErrors.danh_muc_con_slug }"
                  @change="clearFieldError('danh_muc_con_slug')"
                >
                  <option value="">Chọn</option>
                  <option v-for="item in danhMucConOptions" :key="item.slug" :value="item.slug">
                    {{ item.label }}
                  </option>
                </select>
                <div v-if="formErrors.danh_muc_con_slug" class="invalid-feedback d-block">{{ formErrors.danh_muc_con_slug }}</div>
              </div>

              <div :class="showDanhMucCon ? 'col-md-6' : 'col-12'">
                <label class="form-label fw-semibold">Nhà sản xuất</label>
                <input
                  v-model.trim="nhaSanXuatInput"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.nha_san_xuat }"
                  list="nha-san-xuat-options"
                  placeholder="Nhập nhà sản xuất"
                  @input="clearFieldError('nha_san_xuat')"
                />
                <datalist id="nha-san-xuat-options">
                  <option v-for="item in nhaSanXuatSuggestions" :key="item" :value="item"></option>
                </datalist>
                <div v-if="formErrors.nha_san_xuat" class="invalid-feedback d-block">{{ formErrors.nha_san_xuat }}</div>
                <div class="form-text">Gợi ý chỉ hiện các nhà sản xuất đã lưu thuốc thành công trước đó.</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Đơn vị</label>
                <input
                  v-model.trim="form.don_vi_tinh"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.don_vi_tinh }"
                  placeholder="Ví dụ: vỉ, gói, tuýp, ống"
                  @input="clearFieldError('don_vi_tinh')"
                />
                <div v-if="formErrors.don_vi_tinh" class="invalid-feedback d-block">{{ formErrors.don_vi_tinh }}</div>
              </div>
              <div class="col-md-6 position-relative">
                <label class="form-label fw-semibold">Giá bán</label>
                <button
                  type="button"
                  class="btn btn-outline-primary btn-sm position-absolute top-0 end-0"
                  style="transform: translateY(-2px)"
                  @click="addAdditionalUnit"
                >
                  Thêm đơn vị
                </button>
                <input
                  :value="formatPriceInput(form.gia_ban)"
                  type="text"
                  inputmode="numeric"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.gia_ban }"
                  placeholder="Nhập giá bán"
                  @input="form.gia_ban = parsePriceInput($event); clearFieldError('gia_ban')"
                />
                <div v-if="formErrors.gia_ban" class="invalid-feedback d-block">{{ formErrors.gia_ban }}</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Đơn vị tồn kho</label>
                <input
                  v-model.trim="form.don_vi_co_so"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.don_vi_co_so }"
                  placeholder="Ví dụ: viên, ml, g"
                  @input="clearFieldError('don_vi_co_so')"
                />
                <div v-if="formErrors.don_vi_co_so" class="invalid-feedback d-block">{{ formErrors.don_vi_co_so }}</div>
                <div class="form-text">Kho và lô thuốc sẽ tính theo đơn vị này để cộng trừ tồn.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Quy đổi đơn vị chính</label>
                <div class="input-group">
                  <span class="input-group-text">1 {{ form.don_vi_tinh || "đơn vị" }} =</span>
                  <input
                    v-model.number="form.he_so_quy_doi"
                    type="number"
                    min="1"
                    class="form-control"
                    :class="{ 'is-invalid': formErrors.he_so_quy_doi }"
                    placeholder="Số lượng"
                    @input="clearFieldError('he_so_quy_doi')"
                  />
                  <span class="input-group-text">{{ form.don_vi_co_so || "đơn vị tồn kho" }}</span>
                </div>
                <div v-if="formErrors.he_so_quy_doi" class="invalid-feedback d-block">{{ formErrors.he_so_quy_doi }}</div>
                <div class="form-text">{{ formatUnitPreview(form.don_vi_tinh, form.he_so_quy_doi, form.don_vi_co_so) }}</div>
              </div>
            </div>

            <div v-if="additionalUnits.length">
              <div class="vstack gap-3">
                <div
                  v-for="(entry, index) in additionalUnits"
                  :key="`unit-${index}`"
                  class="border rounded-4 p-3"
                >
                  <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Đơn vị</label>
                      <input
                        v-model.trim="entry.ten_don_vi"
                        class="form-control"
                        :class="{ 'is-invalid': formErrors.additional_units }"
                        placeholder="Ví dụ: viên, hộp"
                        @input="clearFieldError('additional_units')"
                      />
                    </div>
                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Giá bán</label>
                      <input
                        :value="formatPriceInput(entry.gia_ban)"
                        type="text"
                        inputmode="numeric"
                        class="form-control"
                        :class="{ 'is-invalid': formErrors.additional_units }"
                        placeholder="Nhập giá bán cho đơn vị này"
                        @input="entry.gia_ban = parsePriceInput($event); clearFieldError('additional_units')"
                      />
                    </div>
                    <div class="col-md-4">
                      <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-danger px-3" @click="removeAdditionalUnit(index)">
                          Xóa
                        </button>
                      </div>
                    </div>
                  </div>

                </div>
                <div v-if="formErrors.additional_units" class="invalid-feedback d-block">{{ formErrors.additional_units }}</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Hàm lượng</label>
                <input
                  v-model.trim="form.ham_luong"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.ham_luong }"
                  placeholder="Ví dụ: 500mg"
                  @input="clearFieldError('ham_luong')"
                />
                <div v-if="formErrors.ham_luong" class="invalid-feedback d-block">{{ formErrors.ham_luong }}</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Trạng thái</label>
                <select
                  v-model="form.trang_thai"
                  class="form-select"
                  :class="{ 'is-invalid': formErrors.trang_thai }"
                  @change="clearFieldError('trang_thai')"
                >
                  <option value="còn bán">Còn bán</option>
                  <option value="ngừng bán">Ngừng bán</option>
                </select>
                <div v-if="formErrors.trang_thai" class="invalid-feedback d-block">{{ formErrors.trang_thai }}</div>
              </div>
            </div>

            <div>
              <label class="form-label fw-semibold">Mô tả thuốc</label>
              <textarea
                v-model.trim="form.mo_ta"
                class="form-control"
                :class="{ 'is-invalid': formErrors.mo_ta }"
                rows="4"
                placeholder="Nhập mô tả ngắn để hiển thị ở phần mô tả sản phẩm cho khách hàng"
                @input="clearFieldError('mo_ta')"
              ></textarea>
              <div v-if="formErrors.mo_ta" class="invalid-feedback d-block">{{ formErrors.mo_ta }}</div>
              <div class="form-text">Nội dung này sẽ hiển thị ở mục mô tả sản phẩm bên giao diện khách hàng.</div>
            </div>

            <div>
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <label class="form-label fw-semibold mb-0">Liều lượng</label>
                <button type="button" class="btn btn-outline-primary" @click="addDosageRow">
                  Thêm dòng
                </button>
              </div>
              <div class="vstack gap-3">
                <div
                  v-for="(entry, index) in dosageEntries"
                  :key="`dosage-${index}`"
                  class="border rounded-4 p-3"
                >
                  <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                      <label class="form-label fw-semibold mb-1">Số lượng</label>
                      <input
                        v-model.trim="entry.quantity"
                        class="form-control"
                        placeholder="Ví dụ: 1 viên"
                      />
                    </div>
                    <div class="col-md-6">
                      <div class="d-flex gap-2 align-items-end">
                        <div class="flex-grow-1">
                          <label class="form-label fw-semibold mb-1">Chu kỳ</label>
                          <input
                            v-model.trim="entry.interval"
                            class="form-control"
                            placeholder="Ví dụ: 1 ngày, 6 tiếng"
                          />
                        </div>
                        <button
                          type="button"
                          class="btn btn-outline-danger h-100 px-3"
                          @click="removeDosageRow(index)"
                        >
                          Xóa
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="form-label fw-semibold">Hình ảnh</label>
              <div class="row g-3">
                <div class="col-md-6">
                  <input
                    ref="imageFileInput"
                    class="form-control"
                    :class="{ 'is-invalid': formErrors.hinh_anh }"
                    type="file"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    @change="handleImageChange"
                  />
                  <div v-if="formErrors.hinh_anh" class="invalid-feedback d-block">{{ formErrors.hinh_anh }}</div>
                  <div class="form-text">Tải file JPG, PNG hoặc WEBP.</div>
                </div>
                <div class="col-md-6">
                  <input
                    v-model.trim="imageUrlInput"
                    type="url"
                    class="form-control"
                    :class="{ 'is-invalid': formErrors.hinh_anh_url }"
                    placeholder="https://example.com/image.jpg"
                    @input="handleImageUrlInput"
                  />
                  <div v-if="formErrors.hinh_anh_url" class="invalid-feedback d-block">{{ formErrors.hinh_anh_url }}</div>
                  <div class="form-text">Hoặc dán URL ảnh công khai.</div>
                </div>
              </div>
            </div>

            <div v-if="imagePreviewUrl && !imagePreviewLoadFailed" class="border rounded-4 p-3 d-flex align-items-center gap-3">
              <img
                :src="imagePreviewUrl"
                alt="Xem trước ảnh thuốc"
                style="width: 88px; height: 88px; object-fit: cover; border-radius: 16px; border: 1px solid #d9e4ff"
                @error="handleImagePreviewError"
              />
              <div class="flex-grow-1">
                <div class="fw-semibold">Ảnh xem trước</div>
                <div class="small text-secondary">
                  {{ imageFile ? imageFile.name : imageUrlInput || "Đang dùng ảnh đã lưu của thuốc." }}
                </div>
              </div>
              <button type="button" class="btn btn-outline-secondary btn-sm" @click="clearImageSelection">Bỏ ảnh mới</button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="btn btn-primary" @click="saveThuoc" :disabled="loading.save || !isAdminUser">
            <span v-if="loading.save" class="spinner-border spinner-border-sm me-2"></span>
            {{ form.originalId ? "Lưu cập nhật" : "Thêm thuốc" }}
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
            <h5 class="modal-title fw-bold mb-1">Xóa thuốc</h5>
            <p class="mb-0 text-secondary small">Thao tác này sẽ xóa thuốc khỏi danh sách quản lý.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <p class="mb-2">
            Bạn có chắc muốn xóa thuốc
            <strong>{{ deleteTarget?.ten_thuoc || "đã chọn" }}</strong>
            không?
          </p>
          <p v-if="deleteTarget?.ma_thuoc" class="mb-0 text-secondary small">
            Mã thuốc: {{ deleteTarget.ma_thuoc }}
          </p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="button" class="btn btn-danger" @click="confirmDeleteThuoc" :disabled="loading.deleteId !== null || !deleteTarget">
            <span v-if="loading.deleteId !== null" class="spinner-border spinner-border-sm me-2"></span>
            Xóa thuốc
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from "bootstrap";
import {
  createNhaSanXuat,
  createThuoc,
  deleteThuoc,
  getNhaSanXuatOptions,
  getNextThuocCode,
  getThuocList,
  searchThuocList,
  updateThuoc,
} from "../../../api/thuocManagementApi";
import { getCatalogSection } from "../../../data/catalogSections";
import { isAdminState } from "../../../lib/authStorage";
import { normalizeApiError, translateErrorMessage } from "../../../lib/errorMessages";
import { formatIntegerInput, parseFormattedInteger } from "../../../lib/numberInput";
import { showToast } from "../../../lib/toast";

const THUOC_TABLE_BATCH_SIZE = 15;
const NHA_SAN_XUAT_SUGGESTION_KEY = "pharmacity-admin-thuoc-nha-san-xuat-suggestions-v1";
const NHA_SAN_XUAT_SUGGESTION_LIMIT = 20;
function createDosageEntry(quantity = "", interval = "") {
  return {
    quantity: String(quantity || "").trim(),
    interval: String(interval || "").trim(),
  };
}

function createAdditionalUnitEntry(unit = "", price = null, factor = 1) {
  return {
    ten_don_vi: String(unit || "").trim(),
    gia_ban: price === null || price === "" ? null : Number(price),
    so_luong_quy_doi: factor === null || factor === "" ? 1 : Math.max(1, Number(factor) || 1),
  };
}

export default {
  name: "ThuocAdmin",

  data() {
    return {
      keyword: "",
      thuocs: [],
      activeStatusFilter: "",
      visibleThuocCount: THUOC_TABLE_BATCH_SIZE,
      nhaSanXuats: [],
      nhaSanXuatSuggestions: [],
      selectedThuoc: null,
      message: "",
      error: "",
      formErrors: {},
      imageFile: null,
      imagePreviewUrl: "",
      imagePreviewLoadFailed: false,
      imageUrlInput: "",
      imageLoadFailures: {},
      nhaSanXuatInput: "",
      dosageEntries: [createDosageEntry()],
      additionalUnits: [],
      thuocModal: null,
      deleteModal: null,
      deleteTarget: null,
      loading: {
        sync: false,
        search: false,
        save: false,
        deleteId: null,
      },
      form: {
        originalId: "",
        ma_thuoc: "",
        ten_thuoc: "",
        ham_luong: "",
        don_vi_tinh: "",
        don_vi_co_so: "",
        he_so_quy_doi: 1,
        gia_ban: null,
        mo_ta: "",
        lieu_luong: "",
        nhan: "",
        trang_thai: "còn bán",
        id_nha_san_xuat: "",
        danh_muc_cha_slug: "",
        danh_muc_con_slug: "",
      },
    };
  },

  computed: {
    isAdminUser() {
      return isAdminState.value;
    },

    displayMaThuoc() {
      return this.form.ma_thuoc || "Đang cấp mã...";
    },

    danhMucChaOptions() {
      return [
        { slug: "thuoc-khong-ke-don", label: "Thuốc không kê đơn" },
        { slug: "thuoc-ke-don", label: "Thuốc kê đơn" },
        { slug: "vitamin-thuc-pham-chuc-nang", label: "Vitamin và thực phẩm chức năng" },
        { slug: "tra-cuu-benh", label: "Vấn đề sức khỏe" },
        { slug: "cham-soc-sac-dep", label: "Chăm sóc sắc đẹp" },
        { slug: "khac", label: "Khác" },
      ];
    },

    danhMucConOptions() {
      return this.resolveDanhMucConOptions(this.form.danh_muc_cha_slug);
    },

    showDanhMucCon() {
      return this.danhMucConOptions.length > 0;
    },

    visibleThuocs() {
      return this.filteredThuocs.slice(0, this.visibleThuocCount);
    },

    canLoadMoreThuocs() {
      return this.filteredThuocs.length > this.visibleThuocCount;
    },

    filteredThuocs() {
      if (!this.activeStatusFilter) {
        return this.thuocs;
      }

      return this.thuocs.filter((item) => this.normalizeStatus(item.trang_thai) === this.activeStatusFilter);
    },

    selectedDanhMucChaLabel() {
      return this.danhMucChaOptions.find((item) => item.slug === this.form.danh_muc_cha_slug)?.label || "";
    },

    selectedDanhMucSlug() {
      const rawSlug = this.showDanhMucCon ? this.form.danh_muc_con_slug || "" : this.form.danh_muc_cha_slug || "";
      const directSlugMap = {
        "khac": "tat-ca-thuoc-khac",
      };

      return directSlugMap[rawSlug] || rawSlug;
    },

    metrics() {
      const dangBan = this.thuocs.filter((item) => (item.trang_thai || "còn bán") === "còn bán").length;
      const ngungBan = this.thuocs.filter((item) => item.trang_thai === "ngừng bán").length;
      const coAnh = this.thuocs.filter((item) => item.hinh_anh_url).length;

      return [
        {
          label: "Tổng thuốc",
          value: this.thuocs.length,
          note: "Đang có trong hệ thống",
          deltaClass: "is-positive",
          icon: "bi bi-capsule-pill",
          iconClass: "metric-card__icon--blue",
        },
        {
          label: "Đang bán",
          value: dangBan,
          note: "Hiển thị trên hệ thống",
          deltaClass: "is-positive",
          icon: "bi bi-bag-check",
          iconClass: "metric-card__icon--teal",
        },
        {
          label: "Ngừng bán",
          value: ngungBan,
          note: "Cần rà soát lại",
          deltaClass: "is-warning",
          icon: "bi bi-pause-circle",
          iconClass: "metric-card__icon--orange",
          filterStatus: "ngừng bán",
        },
        {
          label: "Có ảnh thuốc",
          value: coAnh,
          note: "Đã cập nhật hình minh họa",
          deltaClass: "is-positive",
          icon: "bi bi-image",
          iconClass: "metric-card__icon--red",
        },
      ];
    },
  },

  async mounted() {
    this.ensureModal();
    this.loadNhaSanXuatSuggestions();
    const routeKeyword = String(this.$route.query.q || "").trim();

    if (routeKeyword) {
      this.keyword = routeKeyword;
      await this.loadData();
      await this.handleSearch(true);
      return;
    }

    this.loadData();
  },

  beforeUnmount() {
    this.releaseObjectPreviewUrl();
    if (this.thuocModal) {
      this.thuocModal.dispose();
    }
    if (this.deleteModal) {
      this.deleteModal.dispose();
    }
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

        this.loadData();
      },
    },
  },

    methods: {
      ensureModal() {
        if (!this.thuocModal && this.$refs.thuocModalEl) {
          this.thuocModal = new Modal(this.$refs.thuocModalEl);
        }
        if (!this.deleteModal && this.$refs.deleteModalEl) {
          this.deleteModal = new Modal(this.$refs.deleteModalEl);
        }
      },

      createDosageEntry,
      createAdditionalUnitEntry,

      formatPriceInput(value) {
        return formatIntegerInput(value);
      },

      parsePriceInput(event) {
        return parseFormattedInteger(event?.target?.value);
      },

      parseDosageText(value) {
        const lines = String(value || "")
          .split(/\r\n|\r|\n/)
          .map((line) => line.trim())
          .filter(Boolean);

        if (!lines.length) {
          return [this.createDosageEntry()];
        }

        return lines.map((line) => {
          if (line.includes("|")) {
            const [quantity, interval] = line.split("|", 2).map((part) => part.trim());
            return this.createDosageEntry(quantity, interval);
          }

          return this.createDosageEntry(line, "");
        });
      },

      parseAdditionalUnits(value) {
        if (!Array.isArray(value)) {
          return [];
        }

        return value
          .map((item) => this.createAdditionalUnitEntry(item?.ten_don_vi, item?.gia_ban, item?.so_luong_quy_doi))
          .filter((item) => item.ten_don_vi || item.gia_ban);
      },

      serializeDosageEntries() {
        return this.dosageEntries
          .map((entry) => this.createDosageEntry(entry.quantity, entry.interval))
          .filter((entry) => entry.quantity || entry.interval)
          .map((entry) => [entry.quantity, entry.interval].filter(Boolean).join("|"))
          .join("\n");
      },

      serializeAdditionalUnits() {
        return this.additionalUnits
          .map((entry) => this.createAdditionalUnitEntry(entry.ten_don_vi, entry.gia_ban, entry.so_luong_quy_doi))
          .filter((entry) => entry.ten_don_vi || entry.gia_ban)
          .map((entry) => ({
            ten_don_vi: entry.ten_don_vi,
            gia_ban: Number(entry.gia_ban || 0),
            so_luong_quy_doi: Math.max(1, Number(entry.so_luong_quy_doi || 1)),
          }));
      },

      addDosageRow() {
        this.dosageEntries.push(this.createDosageEntry());
      },

      addAdditionalUnit() {
        this.additionalUnits.push(this.createAdditionalUnitEntry("", null, 1));
      },

      removeDosageRow(index) {
        if (this.dosageEntries.length === 1) {
          this.dosageEntries = [this.createDosageEntry()];
          return;
        }

        this.dosageEntries.splice(index, 1);
      },

    removeAdditionalUnit(index) {
      this.additionalUnits.splice(index, 1);
      this.clearFieldError("additional_units");
    },

    clearFormErrors() {
      this.formErrors = {};
    },

    clearFieldError(field) {
      if (!this.formErrors[field]) {
        return;
      }

      const nextErrors = { ...this.formErrors };
      delete nextErrors[field];
      this.formErrors = nextErrors;
    },

    setFormErrors(errors) {
      this.formErrors = { ...errors };
      this.$nextTick(() => {
        const firstInvalid = document.querySelector("#thuocModal .is-invalid");
        firstInvalid?.scrollIntoView?.({ block: "center", behavior: "smooth" });
        firstInvalid?.focus?.({ preventScroll: true });
      });
    },

    isValidHttpUrl(value) {
      const normalizedValue = String(value || "").trim();
      if (!normalizedValue) {
        return true;
      }

      try {
        const parsedUrl = new URL(normalizedValue);
        return ["http:", "https:"].includes(parsedUrl.protocol);
      } catch {
        return false;
      }
    },

    collectThuocFormErrors() {
      const errors = {};
      const tenThuoc = String(this.form.ten_thuoc || "").trim();
      const nhan = String(this.form.nhan || "").trim();
      const donViChinh = String(this.form.don_vi_tinh || "").trim();
      const donViCoSo = String(this.form.don_vi_co_so || "").trim();
      const giaBan = Number(this.form.gia_ban || 0);
      const heSoQuyDoi = Number(this.form.he_so_quy_doi || 0);
      const tenNhaSanXuat = this.sanitizeNhaSanXuatName(this.nhaSanXuatInput);

      if (!tenThuoc) {
        errors.ten_thuoc = "Vui lòng nhập tên thuốc.";
      } else if (tenThuoc.length < 5) {
        errors.ten_thuoc = "Tên thuốc phải có ít nhất 5 ký tự.";
      }

      if (!nhan) {
        errors.nhan = "Vui lòng nhập nhãn cho thuốc.";
      } else if (nhan.length < 2) {
        errors.nhan = "Nhãn thuốc phải có ít nhất 2 ký tự.";
      }

      if (!this.form.danh_muc_cha_slug) {
        errors.danh_muc_cha_slug = "Vui lòng chọn danh mục.";
      }

      if (this.showDanhMucCon && !this.form.danh_muc_con_slug) {
        errors.danh_muc_con_slug = `Vui lòng chọn nhánh cho nhóm ${this.selectedDanhMucChaLabel}.`;
      }

      if (
        this.form.danh_muc_cha_slug
        && this.selectedDanhMucSlug
        && !this.resolveAllowedDanhMucSlugs(this.form.danh_muc_cha_slug).includes(this.selectedDanhMucSlug)
      ) {
        const targetField = this.showDanhMucCon ? "danh_muc_con_slug" : "danh_muc_cha_slug";
        errors[targetField] = "Thuốc chỉ được lưu trong đúng mục con thuộc mục cha đã chọn.";
      }

      if (!tenNhaSanXuat) {
        errors.nha_san_xuat = "Vui lòng nhập nhà sản xuất.";
      }

      if (!donViChinh) {
        errors.don_vi_tinh = "Vui lòng nhập đơn vị bán chính.";
      }

      if (!donViCoSo) {
        errors.don_vi_co_so = "Vui lòng nhập đơn vị tồn kho.";
      }

      if (!heSoQuyDoi || heSoQuyDoi < 1) {
        errors.he_so_quy_doi = "Vui lòng nhập hệ số quy đổi hợp lệ.";
      }

      if (!giaBan || giaBan < 1) {
        errors.gia_ban = "Vui lòng nhập giá bán hợp lệ.";
      }

      if (String(this.form.ham_luong || "").length > 50) {
        errors.ham_luong = "Hàm lượng không được vượt quá 50 ký tự.";
      }

      if (String(this.form.mo_ta || "").length > 5000) {
        errors.mo_ta = "Mô tả thuốc không được vượt quá 5000 ký tự.";
      }

      if (this.imageFile) {
        const allowedTypes = new Set(["image/jpeg", "image/png", "image/webp"]);
        if (!allowedTypes.has(this.imageFile.type)) {
          errors.hinh_anh = "Ảnh thuốc phải là JPG, PNG hoặc WEBP.";
        } else if (this.imageFile.size > 2 * 1024 * 1024) {
          errors.hinh_anh = "Ảnh thuốc không được vượt quá 2MB.";
        }
      }

      if (this.imageUrlInput && !this.isValidHttpUrl(this.imageUrlInput)) {
        errors.hinh_anh_url = "URL ảnh phải bắt đầu bằng http:// hoặc https://.";
      }

      const usedUnits = new Set(donViChinh ? [donViChinh.toLowerCase()] : []);
      for (const [index, entry] of this.additionalUnits.entries()) {
        const tenDonVi = String(entry.ten_don_vi || "").trim();

        if (!tenDonVi) {
          errors.additional_units = `Vui lòng nhập tên đơn vị thêm ở dòng ${index + 1}.`;
          break;
        }

        if (usedUnits.has(tenDonVi.toLowerCase())) {
          errors.additional_units = `Đơn vị ${tenDonVi} đang bị trùng.`;
          break;
        }

        if (!entry.gia_ban || Number(entry.gia_ban) < 1) {
          errors.additional_units = `Vui lòng nhập giá bán hợp lệ cho đơn vị ${tenDonVi}.`;
          break;
        }

        entry.so_luong_quy_doi = Math.max(1, Number(entry.so_luong_quy_doi || 1));
        usedUnits.add(tenDonVi.toLowerCase());
      }

      return errors;
    },

    translateThuocValidationMessage(message, field) {
      const rawMessage = String(message || "").trim();
      const normalized = rawMessage.toLowerCase();
      const labels = {
        ma_thuoc: "mã thuốc",
        ten_thuoc: "tên thuốc",
        nhan: "nhãn",
        danh_muc_cha_slug: "danh mục",
        danh_muc_con_slug: "nhánh",
        danh_muc_thuoc_slug: "danh mục",
        nha_san_xuat: "nhà sản xuất",
        id_nha_san_xuat: "nhà sản xuất",
        don_vi_tinh: "đơn vị",
        don_vi_co_so: "đơn vị tồn kho",
        he_so_quy_doi: "hệ số quy đổi",
        gia_ban: "giá bán",
        ham_luong: "hàm lượng",
        mo_ta: "mô tả thuốc",
        trang_thai: "trạng thái",
        hinh_anh: "ảnh thuốc",
        hinh_anh_url: "URL ảnh",
        quy_cach_don_vi: "đơn vị thêm",
      };
      const label = labels[field] || "trường thông tin này";

      if (!rawMessage) {
        return `Vui lòng kiểm tra lại ${label}.`;
      }

      if (normalized.includes("required")) {
        return ["danh_muc_cha_slug", "danh_muc_con_slug", "trang_thai"].includes(field)
          ? `Vui lòng chọn ${label}.`
          : `Vui lòng nhập ${label}.`;
      }

      if (normalized.includes("must be an image") || normalized.includes("image")) {
        return "Ảnh thuốc phải là tệp hình ảnh hợp lệ.";
      }

      if (normalized.includes("mimes") || normalized.includes("jpg") || normalized.includes("jpeg") || normalized.includes("png") || normalized.includes("webp")) {
        return "Ảnh thuốc phải là JPG, PNG hoặc WEBP.";
      }

      if (normalized.includes("url")) {
        return "URL ảnh phải đúng định dạng và bắt đầu bằng http:// hoặc https://.";
      }

      if (normalized.includes("exists")) {
        return "Nhà sản xuất đã chọn không hợp lệ.";
      }

      if (normalized.includes("unique")) {
        return "Mã thuốc này đã tồn tại trong hệ thống.";
      }

      if (normalized.includes("min")) {
        if (["gia_ban", "he_so_quy_doi"].includes(field)) {
          return `${label.charAt(0).toUpperCase()}${label.slice(1)} phải lớn hơn 0.`;
        }

        return `${label.charAt(0).toUpperCase()}${label.slice(1)} chưa đủ độ dài yêu cầu.`;
      }

      return translateErrorMessage(rawMessage, field, labels);
    },

    mapBackendFieldToFormField(field) {
      const fieldMap = {
        id_nha_san_xuat: "nha_san_xuat",
        ten_nha_san_xuat: "nha_san_xuat",
        danh_muc_thuoc_slug: this.showDanhMucCon ? "danh_muc_con_slug" : "danh_muc_cha_slug",
        quy_cach_don_vi: "additional_units",
      };

      return fieldMap[field] || field;
    },

    applyFormErrorsFromError(err) {
      const backendErrors = err?.payload?.errors || err?.response?.data?.errors;

      if (backendErrors) {
        const nextErrors = {};
        Object.entries(backendErrors).forEach(([field, messages]) => {
          const formField = this.mapBackendFieldToFormField(field);
          const firstMessage = Array.isArray(messages) ? messages[0] : messages;
          nextErrors[formField] = this.translateThuocValidationMessage(firstMessage, field);
        });
        this.setFormErrors(nextErrors);
        return;
      }

      const message = this.normalizeError(err);
      const normalizedMessage = message.toLowerCase();
      let field = "ten_thuoc";

      if (normalizedMessage.includes("nhà sản xuất")) {
        field = "nha_san_xuat";
      } else if (normalizedMessage.includes("danh mục") || normalizedMessage.includes("mục cha")) {
        field = this.showDanhMucCon ? "danh_muc_con_slug" : "danh_muc_cha_slug";
      } else if (normalizedMessage.includes("nhãn")) {
        field = "nhan";
      } else if (normalizedMessage.includes("đơn vị")) {
        field = "additional_units";
      } else if (normalizedMessage.includes("giá")) {
        field = "gia_ban";
      }

      this.setFormErrors({ [field]: message });
    },

      validateAdditionalUnits() {
        const donViChinh = String(this.form.don_vi_tinh || "").trim();
        const donViCoSo = String(this.form.don_vi_co_so || "").trim();

        if (this.additionalUnits.length && !donViChinh) {
          throw new Error("Vui lòng nhập đơn vị chính trước khi thêm đơn vị phụ.");
        }

        if (!donViChinh) {
          throw new Error("Vui lòng nhập đơn vị bán chính.");
        }

        if (!donViCoSo) {
          throw new Error("Vui lòng nhập đơn vị tồn kho.");
        }

        if (!this.form.he_so_quy_doi || Number(this.form.he_so_quy_doi) < 1) {
          throw new Error(`Vui lòng nhập hệ số quy đổi hợp lệ cho đơn vị ${donViChinh}.`);
        }

        const daDungDonVi = new Set(donViChinh ? [donViChinh.toLowerCase()] : []);

        for (const entry of this.additionalUnits) {
          const tenDonVi = String(entry.ten_don_vi || "").trim();

          if (!tenDonVi) {
            throw new Error("Vui lòng nhập tên cho từng đơn vị thêm.");
          }

          if (daDungDonVi.has(tenDonVi.toLowerCase())) {
            throw new Error(`Đơn vị ${tenDonVi} đang bị trùng.`);
          }

          if (!entry.gia_ban || Number(entry.gia_ban) < 1) {
            throw new Error(`Vui lòng nhập giá bán hợp lệ cho đơn vị ${tenDonVi}.`);
          }
          entry.so_luong_quy_doi = Math.max(1, Number(entry.so_luong_quy_doi || 1));

          daDungDonVi.add(tenDonVi.toLowerCase());
        }
      },

      formatUnitPreview(unitName, factor, baseUnit) {
        const normalizedUnit = String(unitName || "").trim();
        const normalizedBaseUnit = String(baseUnit || "").trim();
        const normalizedFactor = Math.max(1, Number(factor || 1));

        if (!normalizedUnit || !normalizedBaseUnit) {
          return "Nhập đủ đơn vị bán và đơn vị tồn kho để xem quy đổi.";
        }

        return `1 ${normalizedUnit} = ${normalizedFactor} ${normalizedBaseUnit}`;
      },

      suggestBaseUnitFromMainUnit() {
        const normalizedMainUnit = String(this.form.don_vi_tinh || "").trim().toLowerCase();
        const suggestedBaseUnit = {
          chai: "ml",
          "tuýp": "g",
          tuyp: "g",
        }[normalizedMainUnit];

        if (suggestedBaseUnit && !String(this.form.don_vi_co_so || "").trim()) {
          this.form.don_vi_co_so = suggestedBaseUnit;
        }
      },

      normalizeError(err) {
        return normalizeApiError(err, "Không thể xử lý dữ liệu thuốc.", {
          id_nha_san_xuat: "nhà sản xuất",
          danh_muc_thuoc_slug: "danh mục",
          hinh_anh_url: "URL ảnh",
        });
    },

    releaseObjectPreviewUrl() {
      if (typeof this.imagePreviewUrl === "string" && this.imagePreviewUrl.startsWith("blob:")) {
        URL.revokeObjectURL(this.imagePreviewUrl);
      }
    },

    clearImageFileInput() {
      if (this.$refs.imageFileInput) {
        this.$refs.imageFileInput.value = "";
      }
    },

    sanitizeNhaSanXuatName(value) {
      return String(value || "")
        .trim()
        .replace(/\s+/g, " ");
    },

    resolveImagePreviewFromUrl(value) {
      const normalizedValue = String(value || "").trim();
      if (!normalizedValue) {
        return "";
      }

      try {
        const parsedUrl = new URL(normalizedValue);
        return ["http:", "https:"].includes(parsedUrl.protocol) ? parsedUrl.toString() : "";
      } catch (error) {
        return "";
      }
    },

    getThuocImageKey(thuoc) {
      return `${thuoc?.ma_thuoc || ""}:${thuoc?.hinh_anh_url || ""}`;
    },

    shouldShowThuocImage(thuoc) {
      const imageUrl = String(thuoc?.hinh_anh_url || "").trim();
      return Boolean(imageUrl && !this.imageLoadFailures[this.getThuocImageKey(thuoc)]);
    },

    handleThuocImageError(thuoc) {
      const key = this.getThuocImageKey(thuoc);
      if (!key) {
        return;
      }

      this.imageLoadFailures = {
        ...this.imageLoadFailures,
        [key]: true,
      };
    },

    formatCurrency(value) {
      const n = Number(value || 0);
      if (Math.abs(n) >= 1e9) {
        const ty = n / 1e9;
        return (Math.abs(ty) >= 10 ? Math.round(ty) : ty.toFixed(2).replace('.', ',')) + ' tỷ đ';
      }
      return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 }).format(n);
    },

    statusBadgeClass(status) {
      return this.normalizeStatus(status) === "ngừng bán" ? "soft-badge--orange" : "soft-badge--teal";
    },

      normalizeLookupText(value) {
        return this.sanitizeNhaSanXuatName(value).toLowerCase();
      },

      getNhanDisplay(thuoc) {
        const directNhan = String(thuoc?.nhan || "").trim();
        if (directNhan) {
          return directNhan;
        }

        const relationValue = thuoc?.loai_thuoc || thuoc?.loaiThuoc || null;
        if (typeof relationValue === "string") {
          return relationValue.trim();
        }

        return String(relationValue?.ten_loai || "").trim();
      },

      getNhaSanXuatRelation(thuoc) {
        return thuoc?.nha_san_xuat || thuoc?.nhaSanXuat || null;
      },

      getNhaSanXuatName(thuoc) {
        return this.getNhaSanXuatRelation(thuoc)?.ten_nha_san_xuat || "";
      },

    normalizeNhaSanXuatSuggestions(list) {
      if (!Array.isArray(list)) {
        return [];
      }

      const normalized = [];
      for (const item of list) {
        const cleaned = this.sanitizeNhaSanXuatName(item);
        if (!cleaned) {
          continue;
        }

        if (normalized.some((existing) => this.normalizeLookupText(existing) === this.normalizeLookupText(cleaned))) {
          continue;
        }

        normalized.push(cleaned);
        if (normalized.length >= NHA_SAN_XUAT_SUGGESTION_LIMIT) {
          break;
        }
      }

      return normalized;
    },

    loadNhaSanXuatSuggestions() {
      if (typeof window === "undefined") {
        return;
      }

      try {
        const rawValue = window.localStorage.getItem(NHA_SAN_XUAT_SUGGESTION_KEY);
        const parsedValue = rawValue ? JSON.parse(rawValue) : [];
        this.nhaSanXuatSuggestions = this.normalizeNhaSanXuatSuggestions(parsedValue);
      } catch (error) {
        this.nhaSanXuatSuggestions = [];
      }
    },

    persistNhaSanXuatSuggestions() {
      if (typeof window === "undefined") {
        return;
      }

      window.localStorage.setItem(
        NHA_SAN_XUAT_SUGGESTION_KEY,
        JSON.stringify(this.nhaSanXuatSuggestions)
      );
    },

    rememberNhaSanXuatSuggestion(name) {
      const cleanedName = this.sanitizeNhaSanXuatName(name);
      if (!cleanedName) {
        return;
      }

      const nextSuggestions = [
        cleanedName,
        ...this.nhaSanXuatSuggestions.filter(
          (item) => this.normalizeLookupText(item) !== this.normalizeLookupText(cleanedName)
        ),
      ];

      this.nhaSanXuatSuggestions = this.normalizeNhaSanXuatSuggestions(nextSuggestions);
      this.persistNhaSanXuatSuggestions();
    },

    findMatchingNhaSanXuat(name) {
      const normalizedName = this.normalizeLookupText(name);
      if (!normalizedName) {
        return null;
      }

      return this.nhaSanXuats.find((item) => this.normalizeLookupText(item.ten_nha_san_xuat) === normalizedName) || null;
    },

    resolveDanhMucConOptions(parentSlug) {
      const thuocCards = getCatalogSection("thuoc").cards || [];
      const parentCard = thuocCards.find((item) => item.slug === parentSlug);

      if (parentCard?.children?.length) {
        return parentCard.children;
      }

      if (parentSlug === "tra-cuu-benh") {
        return getCatalogSection("tra-cuu-benh").cards || [];
      }

      if (parentSlug === "cham-soc-sac-dep") {
        return getCatalogSection("cham-soc-sac-dep").cards || [];
      }

      return [];
    },

    resolveAllowedDanhMucSlugs(parentSlug) {
      const childOptions = this.resolveDanhMucConOptions(parentSlug);

      if (childOptions.length) {
        return childOptions.map((item) => item.slug);
      }

      const directSlugMap = {
        "khac": ["khac", "thuoc-khac", "tat-ca-thuoc-khac"],
      };

      return directSlugMap[parentSlug] || [parentSlug];
    },

    syncDanhMucFromSlug(slug) {
      const currentSlug = String(slug || "").trim();
      const legacyParentMap = {
        "khong-ke-don": "thuoc-khong-ke-don",
        "ke-don": "thuoc-ke-don",
        "thuoc-khac": "khac",
        "tat-ca-thuoc-khac": "khac",
      };
      const normalizedSlug = legacyParentMap[currentSlug] || currentSlug;
      const directParent = this.danhMucChaOptions.find((item) => item.slug === normalizedSlug);

      if (directParent) {
        this.form.danh_muc_cha_slug = directParent.slug;
        this.form.danh_muc_con_slug = "";
        return;
      }

      for (const parent of this.danhMucChaOptions) {
        const child = this.resolveDanhMucConOptions(parent.slug).find((item) => item.slug === normalizedSlug);
        if (child) {
          this.form.danh_muc_cha_slug = parent.slug;
          this.form.danh_muc_con_slug = child.slug;
          return;
        }
      }

      this.form.danh_muc_cha_slug = "";
      this.form.danh_muc_con_slug = "";
    },

    handleDanhMucChaChange(parentSlug = this.form.danh_muc_cha_slug) {
      this.clearFieldError("danh_muc_cha_slug");
      this.clearFieldError("danh_muc_con_slug");
      const childOptions = this.resolveDanhMucConOptions(parentSlug);

      if (!childOptions.some((item) => item.slug === this.form.danh_muc_con_slug)) {
        this.form.danh_muc_con_slug = "";
      }
    },

    fillForm(thuoc) {
      this.clearFormErrors();
      this.form.originalId = thuoc.ma_thuoc;
      this.form.ma_thuoc = thuoc.ma_thuoc;
      this.form.ten_thuoc = thuoc.ten_thuoc || "";
      this.form.ham_luong = thuoc.ham_luong || "";
      this.form.nhan = this.getNhanDisplay(thuoc);
      this.form.don_vi_tinh = thuoc.don_vi_tinh || "";
      this.form.don_vi_co_so = thuoc.don_vi_co_so || thuoc.don_vi_tinh || "";
      this.form.he_so_quy_doi = Math.max(1, Number(thuoc.he_so_quy_doi || 1));
      this.additionalUnits = this.parseAdditionalUnits(thuoc.quy_cach_don_vi);
      this.form.gia_ban = Number(thuoc.gia_ban || 0);
      this.form.mo_ta = thuoc.mo_ta || "";
      this.form.lieu_luong = thuoc.lieu_luong || "";
      this.dosageEntries = this.parseDosageText(this.form.lieu_luong);
      this.form.trang_thai = thuoc.trang_thai || "còn bán";
      this.form.id_nha_san_xuat = thuoc.id_nha_san_xuat || this.getNhaSanXuatRelation(thuoc)?.id || "";
      this.nhaSanXuatInput = this.getNhaSanXuatName(thuoc);
      this.syncDanhMucFromSlug(thuoc.danh_muc_thuoc_slug);
      this.releaseObjectPreviewUrl();
      this.imageFile = null;
      this.imagePreviewLoadFailed = false;
      this.imageUrlInput = "";
      this.imagePreviewUrl = "";
    },

    async assignNextCode() {
      if (!this.isAdminUser || this.form.originalId) {
        return;
      }

      const response = await getNextThuocCode();
      this.form.ma_thuoc = response?.ma_thuoc || "";
    },

    async resetForm() {
      this.clearFormErrors();
      this.selectedThuoc = null;
      this.form.originalId = "";
      this.form.ma_thuoc = "";
      this.form.ten_thuoc = "";
      this.form.ham_luong = "";
      this.form.don_vi_tinh = "";
      this.form.don_vi_co_so = "";
      this.form.he_so_quy_doi = 1;
      this.additionalUnits = [];
      this.form.gia_ban = null;
      this.form.mo_ta = "";
      this.form.lieu_luong = "";
      this.form.nhan = "";
      this.dosageEntries = [this.createDosageEntry()];
      this.form.trang_thai = "còn bán";
      this.form.id_nha_san_xuat = "";
      this.form.danh_muc_cha_slug = "";
      this.form.danh_muc_con_slug = "";
      this.nhaSanXuatInput = "";
      this.releaseObjectPreviewUrl();
      this.imageFile = null;
      this.imageUrlInput = "";
      this.imagePreviewUrl = "";
      this.imagePreviewLoadFailed = false;
      this.clearImageFileInput();

      await this.assignNextCode();
    },

    resetView() {
      this.keyword = "";
      this.activeStatusFilter = "";
      this.message = "";
      this.error = "";
      this.$router.replace({
        path: "/thuocs",
        query: {},
      });
      this.loadData();
    },

    normalizeStatus(status) {
      return String(status || "còn bán").trim().toLowerCase();
    },

    handleMetricClick(metric) {
      if (!metric?.filterStatus) {
        return;
      }

      this.activeStatusFilter = this.activeStatusFilter === metric.filterStatus ? "" : metric.filterStatus;
      this.resetThuocPagination();
      this.selectedThuoc = null;
      this.message = this.activeStatusFilter
        ? `Đang lọc thuốc ${metric.label.toLowerCase()}.`
        : "";
    },

    handleImageChange(event) {
      this.clearFieldError("hinh_anh");
      this.clearFieldError("hinh_anh_url");
      const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
      this.releaseObjectPreviewUrl();
      this.imageFile = file;
      this.imagePreviewLoadFailed = false;
      if (file) {
        this.imageUrlInput = "";
        this.imagePreviewUrl = URL.createObjectURL(file);
        return;
      }

      this.imagePreviewUrl = this.resolveImagePreviewFromUrl(this.imageUrlInput);
    },

    handleImageUrlInput() {
      this.clearFieldError("hinh_anh_url");
      this.clearFieldError("hinh_anh");
      this.imagePreviewLoadFailed = false;
      if (this.imageUrlInput) {
        this.releaseObjectPreviewUrl();
        this.imageFile = null;
        this.clearImageFileInput();
      }

      this.imagePreviewUrl = this.resolveImagePreviewFromUrl(this.imageUrlInput);
    },

    clearImageSelection() {
      this.clearFieldError("hinh_anh");
      this.clearFieldError("hinh_anh_url");
      this.releaseObjectPreviewUrl();
      this.imageFile = null;
      this.clearImageFileInput();
      this.imageUrlInput = "";
      this.imagePreviewUrl = "";
      this.imagePreviewLoadFailed = false;
    },

    handleImagePreviewError() {
      this.releaseObjectPreviewUrl();
      this.imagePreviewUrl = "";
      this.imagePreviewLoadFailed = true;
    },

    async resolveNhaSanXuatId() {
      const tenNhaSanXuat = this.sanitizeNhaSanXuatName(this.nhaSanXuatInput);

      if (!tenNhaSanXuat) {
        throw new Error("Vui lòng nhập nhà sản xuất.");
      }

      const matched = this.findMatchingNhaSanXuat(tenNhaSanXuat);
      if (matched) {
        this.form.id_nha_san_xuat = matched.id;
        this.nhaSanXuatInput = matched.ten_nha_san_xuat;
        return matched.id;
      }

      const created = await createNhaSanXuat({
        ten_nha_san_xuat: tenNhaSanXuat,
      });

      if (!created?.id) {
        throw new Error("Không thể tạo nhà sản xuất mới.");
      }

      this.nhaSanXuats = [...this.nhaSanXuats, created];
      this.form.id_nha_san_xuat = created.id;
      this.nhaSanXuatInput = created.ten_nha_san_xuat || tenNhaSanXuat;

      return created.id;
    },

    resetThuocPagination() {
      this.visibleThuocCount = THUOC_TABLE_BATCH_SIZE;
    },

    showMoreThuocs() {
      this.visibleThuocCount += THUOC_TABLE_BATCH_SIZE;
    },

    openCreateModal() {
      if (!this.isAdminUser) {
        this.error = "Chỉ quản trị viên mới có quyền thêm thuốc.";
        return;
      }

      this.resetForm().then(() => {
        this.error = "";
        this.message = "";
        this.ensureModal();
        this.thuocModal?.show();
      });
    },

    openEditModal(thuoc) {
      this.selectedThuoc = thuoc;
      this.fillForm(thuoc);
      this.error = "";
      this.message = `Đã nạp dữ liệu của ${thuoc.ten_thuoc} để chỉnh sửa.`;
      this.ensureModal();
      this.thuocModal?.show();
    },

    async loadData() {
      this.loading.sync = true;
      this.error = "";

      try {
        const requests = [getThuocList(), getNhaSanXuatOptions()];

        if (this.isAdminUser) {
          requests.push(getNextThuocCode());
        }

        const [thuocData, nhaSanXuatData, nextCodeData] = await Promise.all(requests);
        this.thuocs = thuocData;
        this.nhaSanXuats = nhaSanXuatData;

        if (this.selectedThuoc) {
          const refreshed = thuocData.find((item) => item.ma_thuoc === this.selectedThuoc.ma_thuoc);
          this.selectedThuoc = refreshed || null;

          if (refreshed) {
            this.fillForm(refreshed);
          }
        } else if (this.isAdminUser) {
          this.form.ma_thuoc = nextCodeData?.ma_thuoc || "";
        }

        this.resetThuocPagination();
        this.message = `Đã đồng bộ ${this.thuocs.length} thuốc.`;
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.sync = false;
      }
    },

    async handleSearch(silent = false) {
      if (!this.keyword) {
        return;
      }

      this.loading.search = true;
      this.error = "";

      try {
        this.thuocs = await searchThuocList(this.keyword);
        this.selectedThuoc = null;
        this.resetThuocPagination();
        this.message = `Tìm thấy ${this.thuocs.length} thuốc phù hợp.`;
        if (!silent) {
          this.$router.replace({
            path: "/thuocs",
            query: { q: this.keyword },
          });
        }
      } catch (err) {
        this.error = this.normalizeError(err);
      } finally {
        this.loading.search = false;
      }
    },

    buildPayload() {
      const payload = new FormData();
      this.form.lieu_luong = this.serializeDosageEntries();
      payload.append("ma_thuoc", this.form.ma_thuoc);
      payload.append("ten_thuoc", this.form.ten_thuoc);
      payload.append("ham_luong", this.form.ham_luong || "");
      payload.append("don_vi_tinh", this.form.don_vi_tinh);
      payload.append("don_vi_co_so", this.form.don_vi_co_so || "");
      payload.append("he_so_quy_doi", String(Math.max(1, Number(this.form.he_so_quy_doi || 1))));
      payload.append("quy_cach_don_vi", JSON.stringify(this.serializeAdditionalUnits()));
      payload.append("gia_ban", String(Number(this.form.gia_ban || 0)));
      payload.append("mo_ta", this.form.mo_ta || "");
      payload.append("lieu_luong", this.form.lieu_luong || "");
      payload.append("nhan", this.form.nhan || "");
      payload.append("trang_thai", this.form.trang_thai);
      payload.append("id_nha_san_xuat", String(Number(this.form.id_nha_san_xuat)));
      payload.append("danh_muc_cha_slug", this.form.danh_muc_cha_slug || "");
      payload.append("danh_muc_thuoc_slug", this.selectedDanhMucSlug);

      if (this.imageFile) {
        payload.append("hinh_anh", this.imageFile);
      } else if (this.imageUrlInput) {
        payload.append("hinh_anh_url", this.imageUrlInput);
      }

      return payload;
    },

    async saveThuoc() {
      this.clearFormErrors();
      this.error = "";

      if (!this.isAdminUser) {
        showToast("Chỉ quản trị viên mới có quyền thêm hoặc cập nhật thuốc.", "error");
        return;
      }

      this.suggestBaseUnitFromMainUnit();
      const formErrors = this.collectThuocFormErrors();
      if (Object.keys(formErrors).length) {
        this.setFormErrors(formErrors);
        showToast("Vui lòng kiểm tra lại các trường thông tin trong biểu mẫu.", "error");
        return;
      }

      this.loading.save = true;
      this.error = "";

      try {
        await this.resolveNhaSanXuatId();
        const payload = this.buildPayload();

        if (this.form.originalId) {
          payload.append("_method", "PUT");
          const response = await updateThuoc(this.form.originalId, payload);
          if (response?.data?.ma_thuoc) {
            this.form.ma_thuoc = response.data.ma_thuoc;
          }
          showToast(`Đã cập nhật thuốc ${this.form.ten_thuoc}.`);
        } else {
          const response = await createThuoc(payload);
          showToast(
            response?.data?.ma_thuoc
              ? `Đã thêm thuốc ${this.form.ten_thuoc} với mã ${response.data.ma_thuoc}.`
              : `Đã thêm thuốc ${this.form.ten_thuoc}.`
          );
        }

        this.rememberNhaSanXuatSuggestion(this.nhaSanXuatInput);
        await this.resetForm();
        await this.loadData();
        this.thuocModal?.hide();
      } catch (err) {
        this.applyFormErrorsFromError(err);
        showToast("Vui lòng kiểm tra lại các trường thông tin trong biểu mẫu.", "error");
      } finally {
        this.loading.save = false;
      }
    },

      openDeleteModal(thuoc) {
        if (!this.isAdminUser) {
          this.error = "Chỉ quản trị viên mới có quyền xóa thuốc.";
          showToast(this.error, "error");
          return;
        }

        this.deleteTarget = thuoc;
        this.error = "";
        this.ensureModal();
        this.deleteModal?.show();
      },

      async confirmDeleteThuoc() {
        const thuoc = this.deleteTarget;

        if (!thuoc) {
          return;
        }

      this.loading.deleteId = thuoc.ma_thuoc;
      this.error = "";

        try {
          await deleteThuoc(thuoc.ma_thuoc);
          if (this.selectedThuoc && this.selectedThuoc.ma_thuoc === thuoc.ma_thuoc) {
            await this.resetForm();
          }
          await this.loadData();
          showToast(`Đã xóa thuốc ${thuoc.ten_thuoc}.`);
          this.deleteTarget = null;
          this.deleteModal?.hide();
        } catch (err) {
          const message = this.normalizeError(err);
          this.error = message;
          showToast(message, "error");
        } finally {
          this.loading.deleteId = null;
        }
      },
  },
};
</script>

<style scoped>
.metric-card--clickable {
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.metric-card--clickable:hover,
.metric-card--clickable:focus-visible {
  border-color: #f59e0b;
  box-shadow: 0 16px 36px rgba(15, 23, 42, 0.12);
  transform: translateY(-2px);
}

.metric-card--active {
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18);
}
</style>

