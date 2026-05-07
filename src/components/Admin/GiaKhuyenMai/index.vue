<template>
  <div class="vstack gap-4">
    <section class="content-card">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
          <div class="soft-badge soft-badge--blue mb-3">
            <i class="bi bi-tags"></i>
            Giá và khuyến mãi
          </div>
          <h2 class="page-section-title mb-2">Quản lý giá bán, khuyến mãi thuốc và mã giảm giá</h2>
          <p class="page-section-copy mb-0">
            Khuyến mãi thuốc dùng để giảm trực tiếp trên từng sản phẩm. Mã giảm giá dùng để giảm trên tổng hóa đơn
            khi khách thanh toán.
          </p>
        </div>
      </div>
    </section>

    <section class="content-card">
      <div class="row g-3 align-items-end">
        <div class="col-lg-5">
          <label class="form-label fw-semibold">Tìm kiếm</label>
          <input
            v-model.trim="keyword"
            class="form-control"
            placeholder="Tên thuốc, mã thuốc, tên khuyến mãi hoặc mã giảm giá"
          />
        </div>

        <div class="col-lg-7">
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" @click="loadData" :disabled="loading.sync">
              <span v-if="loading.sync" class="spinner-border spinner-border-sm me-2"></span>
              Đồng bộ dữ liệu
            </button>
            <button class="btn btn-outline-primary" @click="openPromotionModal()">
              <i class="bi bi-plus-circle me-2"></i>
              Tạo khuyến mãi thuốc
            </button>
            <button class="btn btn-outline-success" @click="openCodeModal()">
              <i class="bi bi-ticket-perforated me-2"></i>
              Tạo mã giảm giá
            </button>
            <button class="btn btn-outline-secondary" @click="openPromotionListModal()">
              Danh sách khuyến mãi thuốc
            </button>
            <button class="btn btn-outline-secondary" @click="openCodeListModal()">
              Danh sách mã giảm giá
            </button>
          </div>
        </div>
      </div>

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

    <section class="content-card">
      <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h3 class="panel-title mb-1">Bảng giá thuốc</h3>
          <p class="panel-subtitle mb-0">Cập nhật giá bán và theo dõi khuyến mãi đang áp dụng trên từng thuốc.</p>
        </div>
        <span class="soft-badge soft-badge--teal">{{ filteredThuocs.length }} thuốc</span>
      </div>

      <div v-if="filteredThuocs.length">
        <div class="table-responsive">
          <table class="table table-master align-middle mb-0">
            <thead>
              <tr>
                <th>Thuốc</th>
                <th>Loại</th>
                <th>Giá bán</th>
                <th>Khuyến mãi thuốc</th>
                <th class="text-end">Tác vụ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="thuoc in visibleThuocs" :key="thuoc.ma_thuoc">
                <td>
                  <div class="fw-semibold">{{ thuoc.ten_thuoc }}</div>
                  <div class="small text-secondary">{{ thuoc.ma_thuoc }}</div>
                </td>
                <td>{{ thuoc.loaiThuoc?.ten_loai || "-" }}</td>
                <td>{{ formatCurrency(thuoc.gia_ban) }}</td>
                <td>
                  <div v-if="promotionCountForThuoc(thuoc.ma_thuoc)" class="vstack gap-1">
                    <span
                      v-for="item in promotionsForThuoc(thuoc.ma_thuoc).slice(0, 2)"
                      :key="item.id"
                      class="soft-badge soft-badge--blue justify-content-start"
                    >
                      {{ item.ten_khuyen_mai }} - {{ promotionValueLabel(item) }}
                    </span>
                    <span v-if="promotionCountForThuoc(thuoc.ma_thuoc) > 2" class="small text-secondary">
                      +{{ promotionCountForThuoc(thuoc.ma_thuoc) - 2 }} khuyến mãi khác
                    </span>
                  </div>
                  <span v-else class="text-secondary small">Chưa có khuyến mãi</span>
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end flex-wrap gap-2">
                    <button class="btn btn-sm btn-outline-primary" @click="openPriceModal(thuoc)">Cập nhật giá</button>
                    <button class="btn btn-sm btn-outline-success" @click="openPromotionModal(thuoc)">Tạo khuyến mãi</button>
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
        <p class="mb-2 fw-semibold">Không có dữ liệu phù hợp.</p>
        <p class="mb-0 text-secondary">Thử đổi từ khóa tìm kiếm hoặc đồng bộ lại dữ liệu.</p>
      </div>
    </section>
  </div>

  <div ref="priceModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Cập nhật giá bán</h5>
            <p class="mb-0 text-secondary small">{{ priceForm.ten_thuoc }} - {{ priceForm.ma_thuoc }}</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <label class="form-label fw-semibold">Giá bán mới</label>
          <input
            :value="formatPriceInput(priceForm.gia_ban)"
            type="text"
            inputmode="numeric"
            class="form-control"
            placeholder="Nhập giá bán"
            @input="priceForm.gia_ban = parsePriceInput($event)"
          />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="btn btn-primary" @click="savePrice" :disabled="loading.savePrice">
            <span v-if="loading.savePrice" class="spinner-border spinner-border-sm me-2"></span>
            Lưu giá bán
          </button>
        </div>
      </div>
    </div>
  </div>
  <div ref="promotionModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">{{ promotionForm.id ? "Cập nhật khuyến mãi thuốc" : "Tạo khuyến mãi thuốc" }}</h5>
            <p class="mb-0 text-secondary small">Khuyến mãi thuốc giảm trực tiếp trên giá bán của sản phẩm.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Thuốc áp dụng</label>
              <input
                v-model.trim="promotionThuocInput"
                class="form-control"
                list="promotion-thuoc-options"
                placeholder="Nhập hoặc dán tên thuốc / mã thuốc"
              />
              <datalist id="promotion-thuoc-options">
                <option v-for="item in thuocs" :key="item.ma_thuoc" :value="formatPromotionThuocOption(item)"></option>
              </datalist>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Mã nội bộ khuyến mãi</label>
              <div class="input-group">
                <input v-model.trim="promotionForm.ma_khuyen_mai" class="form-control text-uppercase" placeholder="Ví dụ: KM-THUOC-01" />
                <button class="btn btn-outline-secondary" type="button" @click="generatePromotionCode">Tạo mã</button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tên khuyến mãi</label>
              <input v-model.trim="promotionForm.ten_khuyen_mai" class="form-control" placeholder="Nhập tên khuyến mãi" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nhãn hiển thị</label>
              <input v-model.trim="promotionForm.nhan_hien_thi" class="form-control" placeholder="Ví dụ: Giảm 15%" />
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Mô tả</label>
              <textarea v-model.trim="promotionForm.mo_ta" class="form-control" rows="3" placeholder="Mô tả ngắn cho khuyến mãi thuốc"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Loại áp dụng</label>
              <select v-model="promotionForm.loai_ap_dung" class="form-select">
                <option value="phan_tram">Giảm theo phần trăm</option>
                <option value="so_tien">Giảm số tiền</option>
                <option value="gia_co_dinh">Đặt giá cố định</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Giá trị áp dụng</label>
              <input
                :value="formatPriceInput(promotionForm.gia_tri)"
                type="text"
                inputmode="numeric"
                class="form-control"
                placeholder="Nhập giá trị"
                @input="promotionForm.gia_tri = parsePriceInput($event)"
              />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Trạng thái</label>
              <select v-model="promotionForm.trang_thai" class="form-select">
                <option value="draft">Nháp</option>
                <option value="active">Đang áp dụng</option>
                <option value="inactive">Tạm dừng</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Bắt đầu</label>
              <DateTimePickerInput
                v-model="promotionForm.ngay_bat_dau"
                placeholder="dd/mm/yyyy HH:mm"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Kết thúc</label>
              <DateTimePickerInput
                v-model="promotionForm.ngay_ket_thuc"
                placeholder="dd/mm/yyyy HH:mm"
              />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="btn btn-primary" @click="savePromotion" :disabled="loading.savePromotion">
            <span v-if="loading.savePromotion" class="spinner-border spinner-border-sm me-2"></span>
            {{ promotionForm.id ? "Lưu thay đổi" : "Tạo khuyến mãi" }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <div ref="promotionListModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Danh sách khuyến mãi thuốc</h5>
            <p class="mb-0 text-secondary small">Theo dõi toàn bộ chương trình giảm giá trực tiếp trên từng thuốc.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <div v-if="filteredKhuyenMais.length" class="table-responsive">
            <table class="table table-master align-middle mb-0">
              <thead>
                <tr>
                  <th>Chương trình</th>
                  <th>Thuốc áp dụng</th>
                  <th>Giá trị</th>
                  <th>Thời gian</th>
                  <th>Trạng thái</th>
                  <th class="text-end">Tác vụ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in filteredKhuyenMais" :key="item.id">
                  <td>
                    <div class="fw-semibold">{{ item.ten_khuyen_mai }}</div>
                    <div class="small text-secondary">{{ item.ma_khuyen_mai || "Không có mã nội bộ" }}</div>
                  </td>
                  <td>
                    <div>{{ item.ten_thuoc || "-" }}</div>
                    <div class="small text-secondary">{{ item.ma_thuoc || "-" }}</div>
                  </td>
                  <td>{{ promotionValueLabel(item) }}</td>
                  <td>{{ rangeLabel(item.ngay_bat_dau, item.ngay_ket_thuc) }}</td>
                  <td>
                    <span class="soft-badge" :class="statusBadgeClass(item.trang_thai)">{{ statusLabel(item.trang_thai) }}</span>
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end flex-wrap gap-2">
                      <button class="btn btn-sm btn-outline-primary" @click="editPromotion(item)">Sửa</button>
                      <button
                        class="btn btn-sm btn-outline-danger"
                        @click="openPromotionDeleteModal(item)"
                        :disabled="loading.deletePromotionId === item.id"
                      >
                        <span v-if="loading.deletePromotionId === item.id" class="spinner-border spinner-border-sm me-2"></span>
                        Xóa
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="master-empty">
            <p class="mb-2 fw-semibold">Chưa có khuyến mãi thuốc.</p>
            <p class="mb-0 text-secondary">Tạo khuyến mãi mới để bắt đầu áp dụng cho sản phẩm.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div ref="promotionDeleteModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Xóa khuyến mãi thuốc</h5>
            <p class="mb-0 text-secondary small">Thao tác này sẽ xóa chương trình giảm giá khỏi thuốc áp dụng.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <p class="mb-2">
            Bạn có chắc muốn xóa khuyến mãi
            <strong>{{ promotionDeleteTarget?.ten_khuyen_mai || "đã chọn" }}</strong>
            không?
          </p>
          <p v-if="promotionDeleteTarget?.ten_thuoc" class="mb-1 text-secondary small">
            Thuốc áp dụng: {{ promotionDeleteTarget.ten_thuoc }}
          </p>
          <p v-if="promotionDeleteTarget?.ma_thuoc" class="mb-0 text-secondary small">
            Mã thuốc: {{ promotionDeleteTarget.ma_thuoc }}
          </p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button
            type="button"
            class="btn btn-danger"
            @click="confirmDeletePromotion"
            :disabled="loading.deletePromotionId !== null || !promotionDeleteTarget"
          >
            <span v-if="loading.deletePromotionId !== null" class="spinner-border spinner-border-sm me-2"></span>
            Xóa khuyến mãi
          </button>
        </div>
      </div>
    </div>
  </div>
  <div ref="codeModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">{{ codeForm.id ? "Cập nhật mã giảm giá" : "Tạo mã giảm giá" }}</h5>
            <p class="mb-0 text-secondary small">Mã giảm giá áp dụng trên tổng hóa đơn tại bước thanh toán.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Mã giảm giá</label>
              <div class="input-group">
                <input v-model.trim="codeForm.ma_giam_gia" class="form-control text-uppercase" placeholder="Ví dụ: GIAM50K" />
                <button class="btn btn-outline-secondary" type="button" @click="generateOrderCode">Tạo mã</button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tên chương trình</label>
              <input v-model.trim="codeForm.ten_ma" class="form-control" placeholder="Ví dụ: Giảm 50K cho đơn đầu" />
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Mô tả</label>
              <textarea v-model.trim="codeForm.mo_ta" class="form-control" rows="3" placeholder="Mô tả ngắn về mã giảm giá"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Loại áp dụng</label>
              <select v-model="codeForm.loai_ap_dung" class="form-select">
                <option value="so_tien">Giảm số tiền</option>
                <option value="phan_tram">Giảm theo phần trăm</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Giá trị giảm</label>
              <input
                :value="formatPriceInput(codeForm.gia_tri)"
                type="text"
                inputmode="numeric"
                class="form-control"
                placeholder="Nhập giá trị giảm"
                @input="codeForm.gia_tri = parsePriceInput($event)"
              />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Trạng thái</label>
              <select v-model="codeForm.trang_thai" class="form-select">
                <option value="draft">Nháp</option>
                <option value="active">Đang áp dụng</option>
                <option value="inactive">Tạm dừng</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Giá trị đơn tối thiểu</label>
              <input
                :value="formatPriceInput(codeForm.gia_tri_don_toi_thieu)"
                type="text"
                inputmode="numeric"
                class="form-control"
                placeholder="Ví dụ: 300,000"
                @input="codeForm.gia_tri_don_toi_thieu = parsePriceInput($event)"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Số lần dùng tối đa mỗi khách</label>
              <input v-model.number="codeForm.gioi_han_moi_khach" type="number" min="1" class="form-control" placeholder="Để trống nếu không giới hạn" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Bắt đầu</label>
              <DateTimePickerInput
                v-model="codeForm.ngay_bat_dau"
                placeholder="dd/mm/yyyy HH:mm"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Kết thúc</label>
              <DateTimePickerInput
                v-model="codeForm.ngay_ket_thuc"
                placeholder="dd/mm/yyyy HH:mm"
              />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="btn btn-success" @click="saveCode" :disabled="loading.saveCode">
            <span v-if="loading.saveCode" class="spinner-border spinner-border-sm me-2"></span>
            {{ codeForm.id ? "Lưu thay đổi" : "Tạo mã giảm giá" }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <div ref="codeListModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Danh sách mã giảm giá</h5>
            <p class="mb-0 text-secondary small">Theo dõi mã áp dụng cho tổng hóa đơn, đơn tối thiểu và số lần sử dụng.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <div v-if="filteredOrderCodes.length" class="table-responsive">
            <table class="table table-master align-middle mb-0">
              <thead>
                <tr>
                  <th>Mã</th>
                  <th>Giá trị</th>
                  <th>Điều kiện</th>
                  <th>Thời gian</th>
                  <th>Trạng thái</th>
                  <th class="text-end">Tác vụ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in filteredOrderCodes" :key="item.id">
                  <td>
                    <div class="fw-semibold">{{ item.ma_giam_gia }}</div>
                    <div class="small text-secondary">{{ item.ten_ma }}</div>
                  </td>
                  <td>{{ promotionValueLabel(item) }}</td>
                  <td>
                    <div>Đơn tối thiểu: {{ formatCurrency(item.gia_tri_don_toi_thieu) }}</div>
                    <div class="small text-secondary">
                      {{ item.gioi_han_moi_khach ? `Mỗi khách: ${item.gioi_han_moi_khach} lần` : "Không giới hạn lượt dùng" }}
                    </div>
                  </td>
                  <td>{{ rangeLabel(item.ngay_bat_dau, item.ngay_ket_thuc) }}</td>
                  <td>
                    <span class="soft-badge" :class="statusBadgeClass(item.trang_thai)">{{ statusLabel(item.trang_thai) }}</span>
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end flex-wrap gap-2">
                      <button class="btn btn-sm btn-outline-primary" @click="editCode(item)">Sửa</button>
                      <button class="btn btn-sm btn-outline-danger" @click="removeCode(item)">Xóa</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="master-empty">
            <p class="mb-2 fw-semibold">Chưa có mã giảm giá.</p>
            <p class="mb-0 text-secondary">Tạo mã mới để khách áp dụng khi thanh toán.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { Modal } from "bootstrap";
import DateTimePickerInput from "../../Common/DateTimePickerInput.vue";
import {
  createKhuyenMai,
  createOrderDiscountCode,
  deleteKhuyenMai,
  deleteOrderDiscountCode,
  getKhuyenMais,
  getOrderDiscountCodes,
  updateKhuyenMai,
  updateOrderDiscountCode,
  updateThuocPrice,
} from "../../../api/pricingApi";
import { getThuocList } from "../../../api/thuocManagementApi";
import { formatDateTimeInputValue, parseDateTimeInputValue } from "../../../lib/dateInput";
import { normalizeApiError } from "../../../lib/errorMessages";
import { formatIntegerInput, parseFormattedInteger } from "../../../lib/numberInput";
import { showToast } from "../../../lib/toast";

function nowAsInput() {
  return formatDateTimeInputValue(new Date());
}

function toInputDateTime(value) {
  return formatDateTimeInputValue(value);
}

const THUOC_TABLE_BATCH_SIZE = 15;

export default {
  name: "GiaKhuyenMaiAdmin",
  components: {
    DateTimePickerInput,
  },
  data() {
    return {
      keyword: "",
      thuocs: [],
      khuyenMais: [],
      orderCodes: [],
      error: "",
      visibleThuocCount: THUOC_TABLE_BATCH_SIZE,
      loading: {
        sync: false,
        savePrice: false,
        savePromotion: false,
        saveCode: false,
        deletePromotionId: null,
      },
      priceModal: null,
      promotionModal: null,
      promotionListModal: null,
      promotionDeleteModal: null,
      promotionDeleteTarget: null,
      codeModal: null,
      codeListModal: null,
      priceForm: {
        ma_thuoc: "",
        ten_thuoc: "",
        gia_ban: "",
      },
      promotionForm: { id: null, ma_thuoc: "", ma_khuyen_mai: "", ten_khuyen_mai: "", mo_ta: "", loai_ap_dung: "phan_tram", gia_tri: "", nhan_hien_thi: "", ngay_bat_dau: nowAsInput(), ngay_ket_thuc: "", trang_thai: "active" },
      promotionThuocInput: "",
      codeForm: { id: null, ma_giam_gia: "", ten_ma: "", mo_ta: "", loai_ap_dung: "so_tien", gia_tri: "", gia_tri_don_toi_thieu: 0, gioi_han_moi_khach: "", ngay_bat_dau: nowAsInput(), ngay_ket_thuc: "", trang_thai: "active" },
    };
  },
  computed: {
    filteredThuocs() {
      const keyword = this.keyword.toLowerCase();
      if (!keyword) return this.thuocs;
      return this.thuocs.filter((thuoc) => {
        const promoText = this.promotionsForThuoc(thuoc.ma_thuoc)
          .map((item) => `${item.ten_khuyen_mai} ${item.ma_khuyen_mai || ""}`)
          .join(" ")
          .toLowerCase();
        return [thuoc.ten_thuoc, thuoc.ma_thuoc, thuoc.loaiThuoc?.ten_loai, promoText]
          .filter(Boolean)
          .join(" ")
          .toLowerCase()
          .includes(keyword);
      });
    },
    visibleThuocs() {
      return this.filteredThuocs.slice(0, this.visibleThuocCount);
    },
    canLoadMoreThuocs() {
      return this.filteredThuocs.length > this.visibleThuocCount;
    },
    filteredKhuyenMais() {
      const keyword = this.keyword.toLowerCase();
      if (!keyword) return this.khuyenMais;
      return this.khuyenMais.filter((item) =>
        [item.ten_khuyen_mai, item.ma_khuyen_mai, item.ten_thuoc, item.ma_thuoc, item.nhan_hien_thi]
          .filter(Boolean)
          .join(" ")
          .toLowerCase()
          .includes(keyword),
      );
    },
    filteredOrderCodes() {
      const keyword = this.keyword.toLowerCase();
      if (!keyword) return this.orderCodes;
      return this.orderCodes.filter((item) =>
        [item.ma_giam_gia, item.ten_ma, item.mo_ta].filter(Boolean).join(" ").toLowerCase().includes(keyword),
      );
    },
    metrics() {
      const khuyenMaiDangApDung = this.khuyenMais.filter((item) => item.trang_thai === "active").length;
      const maDangApDung = this.orderCodes.filter((item) => item.trang_thai === "active").length;
      const maCoDieuKien = this.orderCodes.filter((item) => Number(item.gia_tri_don_toi_thieu || 0) > 0).length;
      return [
        { label: "Thuốc có thể cập nhật giá", value: this.thuocs.length, note: "Nguồn dữ liệu từ danh mục thuốc", deltaClass: "is-positive", icon: "bi bi-capsule-pill", iconClass: "metric-card__icon--blue" },
        { label: "Khuyến mãi thuốc đang áp dụng", value: khuyenMaiDangApDung, note: "Giảm trực tiếp trên từng thuốc", deltaClass: "is-positive", icon: "bi bi-bag-check", iconClass: "metric-card__icon--teal" },
        { label: "Mã giảm giá đơn hàng", value: maDangApDung, note: "Áp trên tổng hóa đơn", deltaClass: "is-positive", icon: "bi bi-ticket-perforated", iconClass: "metric-card__icon--orange" },
        { label: "Mã có điều kiện tối thiểu", value: maCoDieuKien, note: "Yêu cầu giá trị đơn hàng", deltaClass: "is-warning", icon: "bi bi-shield-check", iconClass: "metric-card__icon--violet" },
      ];
    },
  },
  watch: {
    keyword() {
      this.resetThuocPagination();
    },
  },
  mounted() {
    this.promotionForm = this.createPromotionForm();
    this.codeForm = this.createCodeForm();
    this.priceModal = new Modal(this.$refs.priceModalEl);
    this.promotionModal = new Modal(this.$refs.promotionModalEl);
    this.promotionListModal = new Modal(this.$refs.promotionListModalEl);
    this.promotionDeleteModal = new Modal(this.$refs.promotionDeleteModalEl);
    this.codeModal = new Modal(this.$refs.codeModalEl);
    this.codeListModal = new Modal(this.$refs.codeListModalEl);
    this.loadData();
  },
  methods: {
    formatPriceInput(value) {
      return formatIntegerInput(value);
    },

    parsePriceInput(event) {
      return parseFormattedInteger(event?.target?.value);
    },

    parseDateTimePayload(value, label) {
      if (!value) {
        return { valid: true, value: null };
      }

      const parsedValue = parseDateTimeInputValue(value);
      if (!parsedValue) {
        showToast(`${label} phải theo định dạng dd/mm/yyyy HH:mm.`, "error");
        return { valid: false, value: null };
      }

      return { valid: true, value: parsedValue };
    },

    createPromotionForm() {
      return { id: null, ma_thuoc: "", ma_khuyen_mai: "", ten_khuyen_mai: "", mo_ta: "", loai_ap_dung: "phan_tram", gia_tri: "", nhan_hien_thi: "", ngay_bat_dau: nowAsInput(), ngay_ket_thuc: "", trang_thai: "active" };
    },
    formatPromotionThuocOption(item) {
      return `${item.ten_thuoc} - ${item.ma_thuoc}`;
    },
    resolvePromotionThuocInput() {
      const raw = (this.promotionThuocInput || "").trim();

      if (!raw) {
        this.promotionForm.ma_thuoc = "";
        return null;
      }

      const normalized = raw.toLowerCase();
      const found = this.thuocs.find((item) => {
        const option = this.formatPromotionThuocOption(item).toLowerCase();
        return item.ma_thuoc?.toLowerCase() === normalized
          || item.ten_thuoc?.toLowerCase() === normalized
          || option === normalized;
      });

      if (!found) {
        this.promotionForm.ma_thuoc = "";
        return null;
      }

      this.promotionForm.ma_thuoc = found.ma_thuoc;
      this.promotionThuocInput = this.formatPromotionThuocOption(found);
      return found;
    },
    createCodeForm() {
      return { id: null, ma_giam_gia: "", ten_ma: "", mo_ta: "", loai_ap_dung: "so_tien", gia_tri: "", gia_tri_don_toi_thieu: 0, gioi_han_moi_khach: "", ngay_bat_dau: nowAsInput(), ngay_ket_thuc: "", trang_thai: "active" };
    },
    resetThuocPagination() {
      this.visibleThuocCount = THUOC_TABLE_BATCH_SIZE;
    },
    showMoreThuocs() {
      this.visibleThuocCount += THUOC_TABLE_BATCH_SIZE;
    },
    async loadData() {
      this.loading.sync = true;
      this.error = "";
      try {
        const [thuocs, khuyenMaisResponse, orderCodesResponse] = await Promise.all([getThuocList(), getKhuyenMais(), getOrderDiscountCodes()]);
        this.thuocs = Array.isArray(thuocs) ? thuocs : [];
        this.khuyenMais = Array.isArray(khuyenMaisResponse?.data) ? khuyenMaisResponse.data : [];
        this.orderCodes = Array.isArray(orderCodesResponse?.data) ? orderCodesResponse.data : [];
        this.resetThuocPagination();
      } catch (error) {
        this.error = this.normalizeError(error, "Không thể tải dữ liệu giá và khuyến mãi.");
      } finally {
        this.loading.sync = false;
      }
    },
    promotionsForThuoc(maThuoc) {
      return this.khuyenMais.filter((item) => item.ma_thuoc === maThuoc);
    },
    promotionCountForThuoc(maThuoc) {
      return this.promotionsForThuoc(maThuoc).length;
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 }).format(Number(value || 0));
    },
    formatDateTime(value) {
      if (!value) return "Không giới hạn";
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return "Không hợp lệ";
      return new Intl.DateTimeFormat("vi-VN", { dateStyle: "short", timeStyle: "short" }).format(date);
    },
    rangeLabel(start, end) {
      return `${this.formatDateTime(start)} - ${this.formatDateTime(end)}`;
    },
    promotionValueLabel(item) {
      if (item.loai_ap_dung === "phan_tram") return `Giảm ${Number(item.gia_tri || 0)}%`;
      if (item.loai_ap_dung === "gia_co_dinh") return `Giá còn ${this.formatCurrency(item.gia_tri)}`;
      return `Giảm ${this.formatCurrency(item.gia_tri)}`;
    },
    statusLabel(status) {
      const map = { draft: "Nháp", active: "Đang áp dụng", inactive: "Tạm dừng" };
      return map[status] || status || "Không xác định";
    },
    statusBadgeClass(status) {
      if (status === "active") return "soft-badge--teal";
      if (status === "inactive") return "soft-badge--orange";
      return "soft-badge--blue";
    },
    normalizeError(error, fallback) {
      return normalizeApiError(error, fallback, {
        ma_thuoc: "thuốc áp dụng",
        ma_khuyen_mai: "mã nội bộ khuyến mãi",
        ten_khuyen_mai: "tên khuyến mãi",
        nhan_hien_thi: "nhãn hiển thị",
        ma_giam_gia: "mã giảm giá",
        ten_ma: "tên chương trình",
      });
    },
    openPriceModal(thuoc) {
      this.priceForm = { ma_thuoc: thuoc.ma_thuoc, ten_thuoc: thuoc.ten_thuoc, gia_ban: Number(thuoc.gia_ban || 0) };
      this.priceModal.show();
    },
    async savePrice() {
      if (!this.priceForm.ma_thuoc || Number(this.priceForm.gia_ban) <= 0) {
        showToast("Giá bán phải lớn hơn 0.", "error");
        return;
      }
      this.loading.savePrice = true;
      try {
        await updateThuocPrice(this.priceForm.ma_thuoc, { gia_ban: Number(this.priceForm.gia_ban) });
        this.priceModal.hide();
        showToast("Cập nhật giá bán thành công.");
        await this.loadData();
      } catch (error) {
        showToast(this.normalizeError(error, "Không thể cập nhật giá bán."), "error");
      } finally {
        this.loading.savePrice = false;
      }
    },
    openPromotionModal(thuoc = null) {
      this.promotionForm = this.createPromotionForm();
      this.promotionThuocInput = "";
      if (thuoc) {
        this.promotionForm.ma_thuoc = thuoc.ma_thuoc;
        this.promotionThuocInput = this.formatPromotionThuocOption(thuoc);
      }
      this.promotionModal.show();
    },
    editPromotion(item) {
      this.promotionListModal.hide();
      this.promotionForm = { id: item.id, ma_thuoc: item.ma_thuoc || "", ma_khuyen_mai: item.ma_khuyen_mai || "", ten_khuyen_mai: item.ten_khuyen_mai || "", mo_ta: item.mo_ta || "", loai_ap_dung: item.loai_ap_dung || "phan_tram", gia_tri: Number(item.gia_tri || 0), nhan_hien_thi: item.nhan_hien_thi || "", ngay_bat_dau: toInputDateTime(item.ngay_bat_dau), ngay_ket_thuc: toInputDateTime(item.ngay_ket_thuc), trang_thai: item.trang_thai || "draft" };
      const matchedThuoc = this.thuocs.find((thuoc) => thuoc.ma_thuoc === item.ma_thuoc);
      this.promotionThuocInput = matchedThuoc
        ? this.formatPromotionThuocOption(matchedThuoc)
        : (item.ten_thuoc ? `${item.ten_thuoc} - ${item.ma_thuoc}` : item.ma_thuoc || "");
      this.promotionModal.show();
    },
    async savePromotion() {
      const matchedThuoc = this.resolvePromotionThuocInput();

      if (!matchedThuoc) {
        showToast("Không tìm thấy thuốc khớp với tên hoặc mã đã nhập.", "error");
        return;
      }

      if (!this.promotionForm.ma_thuoc || !this.promotionForm.ten_khuyen_mai || Number(this.promotionForm.gia_tri) <= 0) {
        showToast("Cần chọn thuốc, nhập tên và giá trị khuyến mãi hợp lệ.", "error");
        return;
      }
      const startDate = this.parseDateTimePayload(this.promotionForm.ngay_bat_dau, "Ngày bắt đầu");
      const endDate = this.parseDateTimePayload(this.promotionForm.ngay_ket_thuc, "Ngày kết thúc");
      if (!startDate.valid || !endDate.valid) {
        return;
      }

      if (startDate.value && endDate.value && new Date(endDate.value) <= new Date(startDate.value)) {
        showToast("Ngày kết thúc phải sau ngày bắt đầu.", "error");
        return;
      }

      const payload = { ma_thuoc: this.promotionForm.ma_thuoc, ma_khuyen_mai: this.promotionForm.ma_khuyen_mai || null, ten_khuyen_mai: this.promotionForm.ten_khuyen_mai, mo_ta: this.promotionForm.mo_ta || null, loai_ap_dung: this.promotionForm.loai_ap_dung, gia_tri: Number(this.promotionForm.gia_tri), nhan_hien_thi: this.promotionForm.nhan_hien_thi || null, ngay_bat_dau: startDate.value, ngay_ket_thuc: endDate.value, trang_thai: this.promotionForm.trang_thai };
      this.loading.savePromotion = true;
      try {
        if (this.promotionForm.id) {
          await updateKhuyenMai(this.promotionForm.id, payload);
          showToast("Cập nhật khuyến mãi thuốc thành công.");
        } else {
          await createKhuyenMai(payload);
          showToast("Tạo khuyến mãi thuốc thành công.");
        }
        this.promotionModal.hide();
        await this.loadData();
      } catch (error) {
        showToast(this.normalizeError(error, "Không thể lưu khuyến mãi thuốc."), "error");
      } finally {
        this.loading.savePromotion = false;
      }
    },
    openPromotionDeleteModal(item) {
      this.promotionDeleteTarget = item;
      this.promotionDeleteModal.show();
    },
    async confirmDeletePromotion() {
      const item = this.promotionDeleteTarget;
      if (!item) return;

      this.loading.deletePromotionId = item.id;
      try {
        await deleteKhuyenMai(item.id);
        showToast("Xóa khuyến mãi thuốc thành công.");
        await this.loadData();
        this.promotionDeleteTarget = null;
        this.promotionDeleteModal.hide();
      } catch (error) {
        showToast(this.normalizeError(error, "Không thể xóa khuyến mãi thuốc."), "error");
      } finally {
        this.loading.deletePromotionId = null;
      }
    },
    openPromotionListModal() {
      this.promotionListModal.show();
    },
    openCodeModal() {
      this.codeForm = this.createCodeForm();
      this.codeModal.show();
    },
    editCode(item) {
      this.codeListModal.hide();
      this.codeForm = { id: item.id, ma_giam_gia: item.ma_giam_gia || "", ten_ma: item.ten_ma || "", mo_ta: item.mo_ta || "", loai_ap_dung: item.loai_ap_dung || "so_tien", gia_tri: Number(item.gia_tri || 0), gia_tri_don_toi_thieu: Number(item.gia_tri_don_toi_thieu || 0), gioi_han_moi_khach: item.gioi_han_moi_khach || "", ngay_bat_dau: toInputDateTime(item.ngay_bat_dau), ngay_ket_thuc: toInputDateTime(item.ngay_ket_thuc), trang_thai: item.trang_thai || "draft" };
      this.codeModal.show();
    },
    async saveCode() {
      if (!this.codeForm.ma_giam_gia || !this.codeForm.ten_ma || Number(this.codeForm.gia_tri) <= 0) {
        showToast("Cần nhập mã, tên và giá trị giảm hợp lệ.", "error");
        return;
      }
      const startDate = this.parseDateTimePayload(this.codeForm.ngay_bat_dau, "Ngày bắt đầu");
      const endDate = this.parseDateTimePayload(this.codeForm.ngay_ket_thuc, "Ngày kết thúc");
      if (!startDate.valid || !endDate.valid) {
        return;
      }

      if (startDate.value && endDate.value && new Date(endDate.value) <= new Date(startDate.value)) {
        showToast("Ngày kết thúc phải sau ngày bắt đầu.", "error");
        return;
      }

      const payload = { ma_giam_gia: this.codeForm.ma_giam_gia, ten_ma: this.codeForm.ten_ma, mo_ta: this.codeForm.mo_ta || null, loai_ap_dung: this.codeForm.loai_ap_dung, gia_tri: Number(this.codeForm.gia_tri), gia_tri_don_toi_thieu: Number(this.codeForm.gia_tri_don_toi_thieu || 0), gioi_han_moi_khach: this.codeForm.gioi_han_moi_khach ? Number(this.codeForm.gioi_han_moi_khach) : null, ngay_bat_dau: startDate.value, ngay_ket_thuc: endDate.value, trang_thai: this.codeForm.trang_thai };
      this.loading.saveCode = true;
      try {
        if (this.codeForm.id) {
          await updateOrderDiscountCode(this.codeForm.id, payload);
          showToast("Cập nhật mã giảm giá thành công.");
        } else {
          await createOrderDiscountCode(payload);
          showToast("Tạo mã giảm giá thành công.");
        }
        this.codeModal.hide();
        await this.loadData();
      } catch (error) {
        showToast(this.normalizeError(error, "Không thể lưu mã giảm giá."), "error");
      } finally {
        this.loading.saveCode = false;
      }
    },
    async removeCode(item) {
      if (!window.confirm(`Xóa mã giảm giá "${item.ma_giam_gia}"?`)) return;
      try {
        await deleteOrderDiscountCode(item.id);
        showToast("Xóa mã giảm giá thành công.");
        await this.loadData();
      } catch (error) {
        showToast(this.normalizeError(error, "Không thể xóa mã giảm giá."), "error");
      }
    },
    openCodeListModal() {
      this.codeListModal.show();
    },
    generatePromotionCode() {
      this.promotionForm.ma_khuyen_mai = `KM-${Date.now().toString().slice(-6)}`;
    },
    generateOrderCode() {
      this.codeForm.ma_giam_gia = `MAGIAM-${Date.now().toString().slice(-6)}`;
    },
  },
};
</script>

<style scoped>
.table-master td {
  vertical-align: top;
}
</style>

