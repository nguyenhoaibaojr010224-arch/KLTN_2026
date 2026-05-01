<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--teal mb-3">
          <i class="bi bi-box-seam"></i>
          Kho và lô thuốc
        </div>
        <h2 class="page-section-title">Theo dõi tồn kho và lô thuốc</h2>
        <p class="page-section-copy mb-0">
          Theo dõi tổng tồn theo đơn vị kho chuẩn, danh sách lô, hạn sử dụng và số lượng còn lại.
        </p>
      </div>

      <div class="soft-badge">
        <i class="bi bi-person-badge"></i>
        {{ currentUser?.ho_ten || "Tài khoản hệ thống" }}
      </div>
    </div>
  </section>

  <section class="content-card mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-lg-6">
        <label class="form-label fw-semibold">Tìm thuốc hoặc số lô</label>
        <input
          v-model.trim="keyword"
          class="form-control"
          placeholder="Nhập tên thuốc, mã thuốc hoặc số lô"
          @keyup.enter="handleSearch"
        />
      </div>

      <div class="col-lg-6">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary" @click="loadInventory({ showSuccessToast: true })" :disabled="loading.inventory">
            <span v-if="loading.inventory" class="spinner-border spinner-border-sm me-2"></span>
            Đồng bộ dữ liệu
          </button>
          <button class="btn btn-outline-primary" @click="handleSearch" :disabled="loading.search || !keyword">
            <span v-if="loading.search" class="spinner-border spinner-border-sm me-2"></span>
            Tìm kiếm
          </button>
          <button class="btn btn-outline-secondary" @click="resetView">Làm mới</button>
          <button class="btn btn-outline-dark" @click="openReceiptList">
            <i class="bi bi-receipt me-2"></i>
            Xem phiếu nhập
          </button>
          <button class="btn btn-success" @click="openReceiptForm()" :disabled="!isAdminUser">
            <i class="bi bi-plus-circle me-2"></i>
            Tạo phiếu nhập
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-md-6 col-xl-3" v-for="metric in metrics" :key="metric.label">
      <article
        class="metric-card h-100"
        :class="{ 'metric-card--clickable': metric.alertType }"
        :role="metric.alertType ? 'button' : null"
        :tabindex="metric.alertType ? 0 : null"
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

  <section class="row g-4 mt-1">
    <div class="col-12">
      <article class="content-card h-100">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
          <div>
            <h3 class="panel-title">Tổng tồn theo thuốc</h3>
            <p class="panel-subtitle mb-0">Tồn kho được quy về đơn vị kho chuẩn của từng thuốc.</p>
          </div>
          <span class="soft-badge soft-badge--blue">{{ thuocRows.length }} thuốc</span>
        </div>

        <div v-if="thuocRows.length" class="table-responsive">
          <table class="table table-master align-middle mb-0">
            <thead>
              <tr>
                <th>Thuốc</th>
                <th>Loại</th>
                <th>Giá bán</th>
                <th>Số lượng còn</th>
                <th class="text-end">Tác vụ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="thuoc in thuocRows" :key="thuoc.ma_thuoc">
                <td>
                  <div class="fw-semibold">{{ thuoc.ten_thuoc }}</div>
                  <div class="small text-secondary">{{ thuoc.ma_thuoc }}</div>
                  <div class="small text-secondary">Đơn vị kho: {{ thuoc.don_vi_co_so || thuoc.don_vi_tinh || "-" }}</div>
                </td>
                <td>{{ getLoaiThuocName(thuoc) }}</td>
                <td>{{ formatCurrency(thuoc.gia_ban) }} / {{ thuoc.don_vi_tinh || "-" }}</td>
                <td>
                  <div class="fw-semibold">{{ formatQuantity(thuoc.so_luong_con, thuoc.don_vi_co_so || thuoc.don_vi_tinh) }}</div>
                  <div class="small text-secondary">
                    Bán mặc định: {{ Math.floor(Number(thuoc.so_luong_con || 0) / Math.max(1, Number(thuoc.he_so_quy_doi || 1))) }}
                    {{ thuoc.don_vi_tinh || "" }}
                  </div>
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-sm btn-outline-primary" @click="openLotModal(thuoc)">Chi tiết lô</button>
                    <button class="btn btn-sm btn-outline-success" :disabled="!isAdminUser" @click="openReceiptForm({ id_thuoc: thuoc.ma_thuoc })">
                      Nhập hàng
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chưa có dữ liệu tồn kho.</p>
          <p class="mb-0 text-secondary">Hãy đồng bộ dữ liệu hoặc thử lại với từ khóa khác.</p>
        </div>
      </article>
    </div>
  </section>

  <div ref="alertModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">{{ activeAlertTitle }}</h5>
            <p class="mb-0 text-secondary small">{{ activeAlertSubtitle }}</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div v-if="activeAlertType === 'expiring'">
            <div v-if="expiringLotAlerts.length" class="row g-3">
              <div class="col-md-6 col-xl-4" v-for="lo in expiringLotAlerts" :key="`alert-expiring-${lo.id_lo}`">
                <article class="inventory-alert-card h-100">
                  <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                      <div class="fw-bold">{{ lo.thuoc?.ten_thuoc || findThuocById(lo.id_thuoc)?.ten_thuoc || lo.id_thuoc }}</div>
                      <div class="small text-secondary">Lô {{ lo.so_lo }}</div>
                    </div>
                    <span class="soft-badge soft-badge--orange">{{ daysUntilExpiry(lo) }} ngày</span>
                  </div>

                  <div class="inventory-alert-card__body">
                    <div>
                      <span>Hạn sử dụng</span>
                      <strong>{{ formatDate(lo.han_su_dung) }}</strong>
                    </div>
                    <div>
                      <span>Số lượng còn</span>
                      <strong>{{ formatQuantity(lo.so_luong_con, lo.don_vi_co_so || findThuocById(lo.id_thuoc)?.don_vi_co_so || findThuocById(lo.id_thuoc)?.don_vi_tinh) }}</strong>
                    </div>
                  </div>

                  <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                    <button class="btn btn-sm btn-outline-primary" @click="openRelatedLotDetail(lo.id_thuoc)">Xem lô</button>
                    <button class="btn btn-sm btn-success" :disabled="!isAdminUser" @click="openReceiptFormFromAlert(lo.id_thuoc)">Nhập thêm</button>
                  </div>
                </article>
              </div>
            </div>

            <div v-else class="master-empty">
              <p class="mb-2 fw-semibold">Không có lô thuốc nào sắp hết hạn.</p>
              <p class="mb-0 text-secondary">Hệ thống đang theo dõi các lô có hạn dùng trong 30 ngày tới.</p>
            </div>
          </div>

          <div v-else>
            <div v-if="lowStockAlerts.length" class="row g-3">
              <div class="col-md-6" v-for="thuoc in lowStockAlerts" :key="`alert-low-${thuoc.ma_thuoc}`">
                <article class="inventory-alert-card h-100">
                  <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                      <div class="fw-bold">{{ thuoc.ten_thuoc }}</div>
                      <div class="small text-secondary">{{ thuoc.ma_thuoc }}</div>
                    </div>
                    <span class="soft-badge soft-badge--orange">Còn {{ formatQuantity(thuoc.so_luong_con, thuoc.don_vi_co_so || thuoc.don_vi_tinh) }}</span>
                  </div>

                  <div class="inventory-alert-card__lots">
                    <div v-for="lo in getLotsForThuoc(thuoc)" :key="`low-lot-${lo.id_lo}`" class="inventory-alert-card__lot">
                      <span>Lô {{ lo.so_lo }}</span>
                      <strong>{{ formatQuantity(lo.so_luong_con, lo.don_vi_co_so || thuoc.don_vi_co_so || thuoc.don_vi_tinh) }}</strong>
                      <small>HSD {{ formatDate(lo.han_su_dung) }}</small>
                    </div>
                    <div v-if="!getLotsForThuoc(thuoc).length" class="text-secondary small">
                      Chưa có lô còn tồn cho thuốc này.
                    </div>
                  </div>

                  <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                    <button class="btn btn-sm btn-outline-primary" @click="openRelatedLotDetail(thuoc.ma_thuoc)">Xem lô</button>
                    <button class="btn btn-sm btn-success" :disabled="!isAdminUser" @click="openReceiptFormFromAlert(thuoc.ma_thuoc)">Nhập thêm</button>
                  </div>
                </article>
              </div>
            </div>

            <div v-else class="master-empty">
              <p class="mb-2 fw-semibold">Không có thuốc nào gần hết hàng.</p>
              <p class="mb-0 text-secondary">Ngưỡng cảnh báo hiện tại là tổng tồn dưới hoặc bằng 20.</p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <div ref="lotModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Chi tiết lô thuốc</h5>
            <p class="mb-0 text-secondary small">
              {{ selectedThuoc ? `${selectedThuoc.ten_thuoc} - ${selectedThuoc.ma_thuoc}` : "Chưa chọn thuốc" }}
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div v-if="selectedThuocLots.length" class="row g-3">
            <div class="col-md-6 col-xl-4" v-for="lo in selectedThuocLots" :key="lo.id_lo">
              <article class="inventory-lot-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                  <div>
                    <div class="fw-bold">Số lô {{ lo.so_lo }}</div>
                    <div class="small text-secondary">Mã lô: {{ lo.id_lo }}</div>
                  </div>
                  <span class="soft-badge" :class="lotBadgeClass(lo)">
                    {{ lotStatus(lo) }}
                  </span>
                </div>

                <div class="row g-3 small">
                  <div class="col-6">
                    <div class="text-secondary mb-1">Nhập gốc</div>
                    <div class="fw-semibold">{{ formatQuantity(lo.so_luong_nhap_goc || lo.so_luong_nhap, lo.don_vi_nhap || selectedThuoc?.don_vi_tinh) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Quy về kho</div>
                    <div class="fw-semibold">{{ formatQuantity(lo.so_luong_nhap, lo.don_vi_co_so || selectedThuoc?.don_vi_co_so || selectedThuoc?.don_vi_tinh) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Số lượng còn</div>
                    <div class="fw-semibold">{{ formatQuantity(lo.so_luong_con, lo.don_vi_co_so || selectedThuoc?.don_vi_co_so || selectedThuoc?.don_vi_tinh) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Quy đổi</div>
                    <div class="fw-semibold">{{ formatLotConversion(lo) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Ngày sản xuất</div>
                    <div class="fw-semibold">{{ formatDate(lo.ngay_san_xuat) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Hạn sử dụng</div>
                    <div class="fw-semibold">{{ formatDate(lo.han_su_dung) }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Giá nhập</div>
                    <div class="fw-semibold">{{ formatCurrency(lo.gia_nhap) }} / {{ lo.don_vi_nhap || "-" }}</div>
                  </div>
                  <div class="col-6">
                    <div class="text-secondary mb-1">Giá quy đổi</div>
                    <div class="fw-semibold">{{ formatCurrency(lo.gia_nhap_quy_doi || 0) }} / {{ lo.don_vi_co_so || "-" }}</div>
                  </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                  <button class="btn btn-sm btn-outline-primary" @click="openLotForm(lo)">Sửa lô</button>
                </div>
              </article>
            </div>
          </div>

          <div v-else class="master-empty">
            <p class="mb-2 fw-semibold">Thuốc này hiện chưa có lô nào.</p>
            <p class="mb-0 text-secondary">Hệ thống chưa ghi nhận lô nhập cho thuốc đang chọn.</p>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <div ref="receiptListModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Phiếu nhập hàng đã lưu</h5>
            <p class="mb-0 text-secondary small">Xem lại các hóa đơn giấy/lần nhập hàng đã ghi nhận vào hệ thống.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div v-if="phieuNhaps.length" class="receipt-list">
            <article v-for="phieu in phieuNhaps" :key="phieu.id_phieu_nhap" class="receipt-card">
              <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                  <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="soft-badge soft-badge--blue">{{ phieu.ma_phieu_nhap || `PN${phieu.id_phieu_nhap}` }}</span>
                    <span v-if="phieu.so_hoa_don_giay" class="soft-badge">HĐ giấy: {{ phieu.so_hoa_don_giay }}</span>
                  </div>
                  <h6 class="fw-bold mb-1">{{ phieu.nha_san_xuat?.ten_nha_san_xuat || "Chưa có nhà cung cấp" }}</h6>
                  <div class="text-secondary small">
                    Ngày nhập {{ formatDate(phieu.ngay_nhap) }}
                    <span v-if="phieu.ngay_hoa_don"> · Ngày hóa đơn {{ formatDate(phieu.ngay_hoa_don) }}</span>
                  </div>
                  <p v-if="phieu.ghi_chu" class="text-secondary small mb-0 mt-2">{{ phieu.ghi_chu }}</p>
                </div>
                <div class="text-lg-end">
                  <div class="fw-bold fs-5">{{ formatCurrency(phieu.tong_tien) }}</div>
                  <div class="small text-secondary">{{ phieu.chi_tiets?.length || 0 }} dòng thuốc/lô</div>
                  <a v-if="phieu.chung_tu_url" :href="phieu.chung_tu_url" target="_blank" rel="noreferrer" class="btn btn-sm btn-outline-primary mt-2">
                    Xem chứng từ
                  </a>
                </div>
              </div>

              <div v-if="phieu.chi_tiets?.length" class="table-responsive mt-3">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Thuốc</th>
                      <th>Số lô</th>
                      <th>Nhập</th>
                      <th>Quy về kho</th>
                      <th>Giá nhập</th>
                      <th class="text-end">Thành tiền</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="detail in phieu.chi_tiets" :key="detail.id">
                      <td>{{ detail.lo_thuoc?.thuoc?.ten_thuoc || detail.lo_thuoc?.id_thuoc || "-" }}</td>
                      <td>{{ detail.lo_thuoc?.so_lo || "-" }}</td>
                      <td>{{ formatQuantity(detail.so_luong_nhap_goc || detail.so_luong, detail.don_vi_nhap || detail.lo_thuoc?.don_vi_nhap) }}</td>
                      <td>{{ formatQuantity(detail.so_luong, detail.don_vi_co_so || detail.lo_thuoc?.don_vi_co_so) }}</td>
                      <td>{{ formatCurrency(detail.gia_nhap) }}</td>
                      <td class="text-end fw-semibold">{{ formatCurrency(detail.thanh_tien || Number(detail.so_luong || 0) * Number(detail.gia_nhap || 0)) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </article>
          </div>

          <div v-else class="master-empty">
            <p class="mb-2 fw-semibold">Chưa có phiếu nhập hàng.</p>
            <p class="mb-0 text-secondary">Khi tạo phiếu nhập mới, hệ thống sẽ lưu lại tại đây để đối chiếu hóa đơn giấy.</p>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <div ref="receiptFormModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">Tạo phiếu nhập hàng</h5>
            <p class="mb-0 text-secondary small">Lưu thông tin hóa đơn giấy và các lô thuốc được giao trong cùng một lần nhập.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nhà cung cấp / nhà sản xuất</label>
              <select v-model="receiptForm.id_nha_san_xuat" class="form-select">
                <option value="">Chọn nhà cung cấp</option>
                <option v-for="supplier in nhaSanXuats" :key="supplier.id" :value="supplier.id">
                  {{ supplier.ten_nha_san_xuat }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Số hóa đơn giấy</label>
              <input v-model.trim="receiptForm.so_hoa_don_giay" class="form-control" placeholder="Ví dụ: HDN-000128" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ngày nhập hàng</label>
              <input v-model="receiptForm.ngay_nhap" type="date" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ngày trên hóa đơn</label>
              <input v-model="receiptForm.ngay_hoa_don" type="date" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ảnh/PDF hóa đơn giấy</label>
              <input
                ref="chungTuFileInput"
                type="file"
                class="form-control"
                accept="image/*,.pdf"
                @change="handleChungTuFile"
              />
              <div v-if="receiptForm.chung_tu_file_name" class="form-text text-success mt-1">
                <i class="bi bi-paperclip me-1"></i>{{ receiptForm.chung_tu_file_name }}
              </div>
              <div v-else-if="receiptForm.chung_tu_url" class="form-text mt-1">
                <a :href="receiptForm.chung_tu_url" target="_blank" rel="noreferrer">
                  <i class="bi bi-file-earmark me-1"></i>Xem chứng từ hiện tại
                </a>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ghi chú</label>
              <input v-model.trim="receiptForm.ghi_chu" class="form-control" placeholder="Ví dụ: giao buổi sáng, đã kiểm đủ hàng" />
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center gap-3 mt-4 mb-3">
            <div>
              <h6 class="fw-bold mb-1">Danh sách thuốc trong phiếu</h6>
              <p class="text-secondary small mb-0">Mỗi dòng sẽ tạo một lô thuốc và tự cộng tồn kho sau quy đổi.</p>
            </div>
            <button type="button" class="btn btn-outline-primary" @click="addReceiptLine">
              <i class="bi bi-plus-lg me-2"></i>
              Thêm dòng thuốc
            </button>
          </div>

          <div class="receipt-line-list">
            <article v-for="(line, index) in receiptForm.chi_tiets" :key="line.uid" class="receipt-line-card">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div class="fw-bold">Dòng {{ index + 1 }}</div>
                <button type="button" class="btn btn-outline-danger btn-sm" :disabled="receiptForm.chi_tiets.length === 1" @click="removeReceiptLine(index)">
                  Xóa
                </button>
              </div>

              <div class="row g-3">
                <div class="col-lg-6">
                  <label class="form-label fw-semibold">Thuốc áp dụng</label>
                  <input
                    v-model.trim="line.thuoc_keyword"
                    class="form-control"
                    :list="`receipt-thuoc-options-${line.uid}`"
                    placeholder="Nhập tên thuốc hoặc mã thuốc"
                    @input="handleReceiptLineThuocInput(line)"
                  />
                  <datalist :id="`receipt-thuoc-options-${line.uid}`">
                    <option v-for="thuoc in receiptLineThuocSuggestions(line)" :key="thuoc.ma_thuoc" :value="formatThuocSuggestion(thuoc)" />
                  </datalist>
                  <div v-if="line.id_thuoc" class="form-text text-success">
                    Đã chọn: {{ receiptLineThuoc(line)?.ten_thuoc }} ({{ line.id_thuoc }})
                  </div>
                  <div v-else class="form-text">
                    Gõ từ khóa để hệ thống gợi ý thuốc gần giống.
                  </div>
                </div>
                <div class="col-lg-6">
                  <label class="form-label fw-semibold">Số lô</label>
                  <input v-model.trim="line.so_lo" class="form-control" placeholder="Ví dụ: LO-2026-001" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Ngày sản xuất</label>
                  <input v-model="line.ngay_san_xuat" type="date" class="form-control" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Hạn sử dụng</label>
                  <input v-model="line.han_su_dung" type="date" class="form-control" />
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Đơn vị nhập</label>
                  <select v-model="line.don_vi_nhap" class="form-select">
                    <option value="">Chọn đơn vị</option>
                    <option v-for="option in receiptLineUnitOptions(line)" :key="option.lookup_key" :value="option.ten_don_vi">
                      {{ option.ten_don_vi }}
                    </option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Số lượng nhập</label>
                  <input v-model.number="line.so_luong_nhap_goc" type="number" min="1" class="form-control" placeholder="Ví dụ: 5" />
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Giá nhập / đơn vị</label>
                  <input
                    :value="formatPriceInput(line.gia_nhap)"
                    type="text"
                    inputmode="numeric"
                    class="form-control"
                    placeholder="Ví dụ: 90,000"
                    @input="line.gia_nhap = parsePriceInput($event)"
                  />
                </div>
              </div>

              <div class="receipt-line-summary mt-3">
                <div>
                  <span>Quy đổi</span>
                  <strong>{{ receiptLineConversionPreview(line) }}</strong>
                </div>
                <div>
                  <span>Tồn kho tăng</span>
                  <strong>{{ formatQuantity(receiptLineConvertedQuantity(line), receiptLineBaseUnitName(line)) }}</strong>
                </div>
                <div>
                  <span>Thành tiền</span>
                  <strong>{{ formatCurrency(receiptLineTotal(line)) }}</strong>
                </div>
              </div>
            </article>
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <div class="fw-bold">Tổng tiền nhập: {{ formatCurrency(receiptTotal) }}</div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
            <button type="button" class="btn btn-primary" @click="saveReceipt" :disabled="loading.saveReceipt || !isAdminUser">
              <span v-if="loading.saveReceipt" class="spinner-border spinner-border-sm me-2"></span>
              Lưu phiếu nhập
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div ref="lotFormModalEl" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-1">{{ lotForm.id_lo ? "Cập nhật lô thuốc" : "Nhập lô thuốc" }}</h5>
            <p class="mb-0 text-secondary small">
              {{ lotForm.id_lo ? "Cập nhật thông tin và nhập thêm theo đúng đơn vị của lô." : "Nhập lô mới và quy đổi về đơn vị kho chuẩn." }}
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Thuốc áp dụng</label>
              <select v-model="lotForm.id_thuoc" class="form-select" :disabled="lockThuocSelect" @change="handleLotThuocChange">
                <option value="">Chọn thuốc</option>
                <option v-for="thuoc in thuocRows" :key="thuoc.ma_thuoc" :value="thuoc.ma_thuoc">
                  {{ thuoc.ten_thuoc }} ({{ thuoc.ma_thuoc }})
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Số lô</label>
              <input v-model.trim="lotForm.so_lo" class="form-control" placeholder="Ví dụ: LO-2026-001" />
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ngày sản xuất</label>
              <input v-model="lotForm.ngay_san_xuat" type="date" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Hạn sử dụng</label>
              <input v-model="lotForm.han_su_dung" type="date" class="form-control" />
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Đơn vị nhập</label>
              <select v-model="lotForm.don_vi_nhap" class="form-select" :disabled="Boolean(lotForm.id_lo)">
                <option value="">Chọn đơn vị nhập</option>
                <option v-for="option in lotUnitOptions" :key="option.lookup_key" :value="option.ten_don_vi">
                  {{ option.ten_don_vi }}
                </option>
              </select>
            </div>
            <div class="col-md-4" v-if="!lotForm.id_lo">
              <label class="form-label fw-semibold">Số lượng nhập</label>
              <input v-model.number="lotForm.so_luong_nhap_goc" type="number" min="1" class="form-control" />
              <div class="form-text">Nhập theo đơn vị đang chọn.</div>
            </div>
            <div class="col-md-4" v-else>
              <label class="form-label fw-semibold">Tổng đã nhập</label>
              <input :value="formatQuantity(lotForm.so_luong_nhap_goc || 0, lotForm.don_vi_nhap)" class="form-control" disabled />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Giá nhập / {{ lotForm.don_vi_nhap || "đơn vị nhập" }}</label>
              <input
                :value="formatPriceInput(lotForm.gia_nhap)"
                type="text"
                inputmode="numeric"
                class="form-control"
                @input="lotForm.gia_nhap = parsePriceInput($event)"
              />
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Quy đổi kho</label>
              <input :value="lotConversionPreview" class="form-control" disabled />
            </div>
            <div class="col-md-6" v-if="!lotForm.id_lo">
              <label class="form-label fw-semibold">Tồn kho sẽ tăng</label>
              <input :value="formatQuantity(lotConvertedQuantity, lotBaseUnitName)" class="form-control" disabled />
            </div>
            <div class="col-md-6" v-else>
              <label class="form-label fw-semibold">Số lượng còn hiện tại</label>
              <input :value="formatQuantity(lotForm.so_luong_con || 0, lotBaseUnitName)" class="form-control" disabled />
            </div>
          </div>

          <div class="row g-3 mt-1" v-if="lotForm.id_lo">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Số lượng nhập thêm</label>
              <input v-model.number="lotForm.so_luong_nhap_them_goc" type="number" min="1" class="form-control" />
              <div class="form-text">Hệ thống sẽ tự quy đổi số lượng nhập thêm về {{ lotBaseUnitName || "đơn vị kho" }}.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tồn kho tăng thêm</label>
              <input :value="formatQuantity(lotConvertedQuantity, lotBaseUnitName)" class="form-control" disabled />
              <div class="form-text" v-if="lotSelectedUnitOption">
                Giá quy đổi: {{ formatCurrency(lotUnitBasePrice) }} / {{ lotBaseUnitName || "đơn vị kho" }}
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="btn btn-primary" @click="saveLot" :disabled="loading.saveLot || !isAdminUser">
            <span v-if="loading.saveLot" class="spinner-border spinner-border-sm me-2"></span>
            {{ lotForm.id_lo ? "Lưu thay đổi" : "Nhập lô" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from "bootstrap";
import {
  createLoThuoc,
  createPhieuNhap,
  getLoThuocs,
  getNhaSanXuats,
  getPhieuNhaps,
  getThuocs,
  searchLoThuocs,
  searchThuocs,
  updateLoThuoc,
} from "../../../api/inventoryApi";
import { getStoredUser, isAdminUser } from "../../../lib/authStorage";
import { formatIntegerInput, parseFormattedInteger } from "../../../lib/numberInput";
import { showToast } from "../../../lib/toast";

function normalizeUnitName(value) {
  return String(value || "").trim();
}

function normalizeUnitKey(value) {
  return normalizeUnitName(value).toLowerCase();
}

function normalizeSearchText(value) {
  return String(value || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[đĐ]/g, "d")
    .toLowerCase()
    .trim();
}

function buildThuocUnitOptions(thuoc) {
  const mainUnit = normalizeUnitName(thuoc?.don_vi_tinh);
  const baseUnit = normalizeUnitName(thuoc?.don_vi_co_so || thuoc?.don_vi_tinh);
  const options = [];

  if (mainUnit) {
    options.push({
      ten_don_vi: mainUnit,
      so_luong_quy_doi: Math.max(1, Number(thuoc?.he_so_quy_doi || 1)),
      lookup_key: normalizeUnitKey(mainUnit),
      mac_dinh: true,
      don_vi_co_so: baseUnit,
    });
  }

  if (Array.isArray(thuoc?.quy_cach_don_vi)) {
    for (const entry of thuoc.quy_cach_don_vi) {
      const unitName = normalizeUnitName(entry?.ten_don_vi);
      if (!unitName) {
        continue;
      }

      options.push({
        ten_don_vi: unitName,
        so_luong_quy_doi: Math.max(1, Number(entry?.so_luong_quy_doi || 1)),
        lookup_key: normalizeUnitKey(unitName),
        mac_dinh: false,
        don_vi_co_so: baseUnit,
      });
    }
  }

  const seen = new Set();

  return options.filter((item) => {
    if (seen.has(item.lookup_key)) {
      return false;
    }

    seen.add(item.lookup_key);
    return true;
  });
}

export default {
  data() {
    return {
      currentUser: getStoredUser(),
      keyword: "",
      thuocs: [],
      loThuocs: [],
      phieuNhaps: [],
      nhaSanXuats: [],
      selectedThuoc: null,
      activeAlertType: "expiring",
      alertModal: null,
      lotModal: null,
      lotFormModal: null,
      receiptFormModal: null,
      receiptListModal: null,
      loading: {
        inventory: false,
        search: false,
        saveLot: false,
        saveReceipt: false,
      },
      receiptForm: {
        id_nha_san_xuat: "",
        so_hoa_don_giay: "",
        ngay_hoa_don: "",
        ngay_nhap: "",
        chung_tu_url: "",
        chung_tu_file: null,       // File object đã chọn
        chung_tu_file_name: "",    // Tên file hiển thị
        ghi_chu: "",
        chi_tiets: [],
      },
      lotForm: {
        id_lo: null,
        id_thuoc: "",
        so_lo: "",
        ngay_san_xuat: "",
        han_su_dung: "",
        don_vi_nhap: "",
        so_luong_nhap_goc: null,
        so_luong_con: null,
        so_luong_nhap_them_goc: null,
        gia_nhap: null,
      },
      lockThuocSelect: false,
    };
  },
  computed: {
    isAdminUser() {
      return isAdminUser();
    },
    selectedLotThuoc() {
      return this.thuocs.find((thuoc) => thuoc.ma_thuoc === this.lotForm.id_thuoc) || null;
    },
    lotUnitOptions() {
      return buildThuocUnitOptions(this.selectedLotThuoc);
    },
    lotSelectedUnitOption() {
      if (!this.lotForm.don_vi_nhap) {
        return this.lotUnitOptions[0] || null;
      }

      return this.lotUnitOptions.find((item) => item.lookup_key === normalizeUnitKey(this.lotForm.don_vi_nhap)) || this.lotUnitOptions[0] || null;
    },
    lotBaseUnitName() {
      return normalizeUnitName(this.lotSelectedUnitOption?.don_vi_co_so || this.selectedLotThuoc?.don_vi_co_so || this.selectedLotThuoc?.don_vi_tinh);
    },
    lotConvertedQuantity() {
      const quantity = this.lotForm.id_lo ? Number(this.lotForm.so_luong_nhap_them_goc || 0) : Number(this.lotForm.so_luong_nhap_goc || 0);
      const factor = Math.max(1, Number(this.lotSelectedUnitOption?.so_luong_quy_doi || 1));
      return quantity * factor;
    },
    lotConversionPreview() {
      if (!this.lotSelectedUnitOption) {
        return "Chọn thuốc và đơn vị nhập để xem quy đổi.";
      }

      return `1 ${this.lotSelectedUnitOption.ten_don_vi} = ${this.lotSelectedUnitOption.so_luong_quy_doi} ${this.lotBaseUnitName || "đơn vị kho"}`;
    },
    lotUnitBasePrice() {
      const factor = Math.max(1, Number(this.lotSelectedUnitOption?.so_luong_quy_doi || 1));
      return Number(this.lotForm.gia_nhap || 0) / factor;
    },
    receiptTotal() {
      return this.receiptForm.chi_tiets.reduce((sum, line) => sum + this.receiptLineTotal(line), 0);
    },
    thuocRows() {
      return this.thuocs
        .map((thuoc) => {
          const relatedLots = this.loThuocs.filter((lo) => lo.id_thuoc === thuoc.ma_thuoc);

          return {
            ...thuoc,
            so_luong_con: relatedLots.reduce((sum, lo) => sum + Number(lo.so_luong_con || 0), 0),
          };
        })
        .sort((a, b) => a.so_luong_con - b.so_luong_con);
    },
    selectedThuocLots() {
      if (!this.selectedThuoc) {
        return [];
      }

      return this.loThuocs
        .filter((lo) => lo.id_thuoc === this.selectedThuoc.ma_thuoc)
        .sort((a, b) => new Date(a.han_su_dung) - new Date(b.han_su_dung));
    },
    expiringLotAlerts() {
      return this.loThuocs
        .filter((lo) => Number(lo.so_luong_con || 0) > 0 && this.daysUntilExpiry(lo) >= 0 && this.daysUntilExpiry(lo) <= 30)
        .sort((a, b) => new Date(a.han_su_dung) - new Date(b.han_su_dung));
    },
    lowStockAlerts() {
      return this.thuocRows.filter((thuoc) => Number(thuoc.so_luong_con || 0) > 0 && Number(thuoc.so_luong_con || 0) <= 20);
    },
    activeAlertTitle() {
      return this.activeAlertType === "expiring" ? "Lô thuốc sắp hết hạn" : "Thuốc gần hết hàng";
    },
    activeAlertSubtitle() {
      return this.activeAlertType === "expiring"
        ? "Danh sách lô còn tồn và có hạn sử dụng trong 30 ngày tới."
        : "Danh sách thuốc có tổng tồn dưới hoặc bằng 20 để nhập hàng sớm.";
    },
    metrics() {
      const totalTon = this.loThuocs.reduce((sum, lo) => sum + Number(lo.so_luong_con || 0), 0);
      const sapHetHan = this.expiringLotAlerts.length;
      const ganHetTon = this.lowStockAlerts.length;

      return [
        {
          label: "Tổng thuốc",
          value: this.thuocRows.length,
          note: "Đang có trong kho",
          deltaClass: "is-positive",
          icon: "bi bi-capsule-pill",
          iconClass: "metric-card__icon--blue",
        },
        {
          label: "Tổng tồn",
          value: totalTon,
          note: "Quy về đơn vị kho",
          deltaClass: "is-positive",
          icon: "bi bi-box-seam",
          iconClass: "metric-card__icon--teal",
        },
        {
          label: "Lô sắp hết hạn",
          value: sapHetHan,
          note: "Cần theo dõi sớm",
          deltaClass: "is-warning",
          icon: "bi bi-exclamation-diamond",
          iconClass: "metric-card__icon--orange",
          alertType: "expiring",
        },
        {
          label: "Thuốc gần hết tồn",
          value: ganHetTon,
          note: "Tổng tồn dưới hoặc bằng 20",
          deltaClass: "is-warning",
          icon: "bi bi-clipboard2-pulse",
          iconClass: "metric-card__icon--red",
          alertType: "low-stock",
        },
      ];
    },
  },
  methods: {
    formatPriceInput(value) {
      return formatIntegerInput(value);
    },

    parsePriceInput(event) {
      return parseFormattedInteger(event?.target?.value);
    },

    normalizeError(err) {
      if (err?.payload?.errors) {
        return Object.values(err.payload.errors).flat().join(" | ");
      }

      return err?.message || "Không thể tải dữ liệu tồn kho.";
    },
    getLoaiThuocName(thuoc) {
      return (
        thuoc?.loaiThuoc?.ten_loai ||
        thuoc?.loaiThuoc?.ten_loai_thuoc ||
        thuoc?.loai_thuoc?.ten_loai ||
        thuoc?.loai_thuoc?.ten_loai_thuoc ||
        thuoc?.loai_thuoc ||
        "-"
      );
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
        maximumFractionDigits: 0,
      }).format(Number(value || 0));
    },
    formatQuantity(value, unitName = "") {
      const quantity = Number(value || 0);
      const formatted = Number.isInteger(quantity) ? quantity : Number(quantity.toFixed(2));
      const normalizedUnit = normalizeUnitName(unitName);
      return normalizedUnit ? `${formatted} ${normalizedUnit}` : String(formatted);
    },
    formatDate(value) {
      if (!value) {
        return "-";
      }

      return new Intl.DateTimeFormat("vi-VN", { dateStyle: "short" }).format(new Date(value));
    },
    daysUntilExpiry(lo) {
      if (!lo?.han_su_dung) {
        return 9999;
      }

      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const expiry = new Date(lo.han_su_dung);
      expiry.setHours(0, 0, 0, 0);

      return Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
    },
    getLotsForThuoc(thuoc) {
      return this.loThuocs
        .filter((lo) => lo.id_thuoc === thuoc?.ma_thuoc && Number(lo.so_luong_con || 0) > 0)
        .sort((a, b) => Number(a.so_luong_con || 0) - Number(b.so_luong_con || 0));
    },
    formatLotConversion(lo) {
      const donViNhap = normalizeUnitName(lo?.don_vi_nhap);
      const donViCoSo = normalizeUnitName(lo?.don_vi_co_so);
      const factor = Math.max(1, Number(lo?.he_so_quy_doi_nhap || 1));

      if (!donViNhap || !donViCoSo) {
        return "-";
      }

      return `1 ${donViNhap} = ${factor} ${donViCoSo}`;
    },
    lotStatus(lo) {
      const diffDays = this.daysUntilExpiry(lo);

      if (Number(lo.so_luong_con || 0) <= 0) {
        return "Hết tồn";
      }

      if (diffDays >= 0 && diffDays <= 30) {
        return "Sắp hết hạn";
      }

      return "Ổn định";
    },
    lotBadgeClass(lo) {
      const status = this.lotStatus(lo);

      if (status === "Hết tồn") {
        return "soft-badge--orange";
      }

      if (status === "Sắp hết hạn") {
        return "soft-badge--blue";
      }

      return "soft-badge--teal";
    },
    ensureModal() {
      if (!this.alertModal && this.$refs.alertModalEl) {
        this.alertModal = new Modal(this.$refs.alertModalEl);
      }
      if (!this.lotModal && this.$refs.lotModalEl) {
        this.lotModal = new Modal(this.$refs.lotModalEl);
      }
      if (!this.lotFormModal && this.$refs.lotFormModalEl) {
        this.lotFormModal = new Modal(this.$refs.lotFormModalEl);
      }
      if (!this.receiptFormModal && this.$refs.receiptFormModalEl) {
        this.receiptFormModal = new Modal(this.$refs.receiptFormModalEl);
      }
      if (!this.receiptListModal && this.$refs.receiptListModalEl) {
        this.receiptListModal = new Modal(this.$refs.receiptListModalEl);
      }
    },
    findThuocById(idThuoc) {
      return this.thuocs.find((item) => item.ma_thuoc === idThuoc) || null;
    },
    defaultUnitForThuoc(thuoc) {
      return buildThuocUnitOptions(thuoc)[0]?.ten_don_vi || "";
    },
    todayInputValue() {
      const today = new Date();
      today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
      return today.toISOString().slice(0, 10);
    },
    createEmptyReceiptLine(seed = {}) {
      const resolvedThuocId = seed.id_thuoc || "";
      const thuoc = this.findThuocById(resolvedThuocId);

      return {
        uid: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
        id_thuoc: resolvedThuocId,
        thuoc_keyword: thuoc ? this.formatThuocSuggestion(thuoc) : "",
        so_lo: seed.so_lo || "",
        ngay_san_xuat: seed.ngay_san_xuat || "",
        han_su_dung: seed.han_su_dung || "",
        don_vi_nhap: seed.don_vi_nhap || this.defaultUnitForThuoc(thuoc),
        so_luong_nhap_goc: seed.so_luong_nhap_goc || null,
        gia_nhap: seed.gia_nhap || null,
      };
    },
    resetReceiptForm(seed = {}) {
      const today = this.todayInputValue();

      this.receiptForm = {
        id_nha_san_xuat: "",
        so_hoa_don_giay: "",
        ngay_hoa_don: today,
        ngay_nhap: today,
        chung_tu_url: "",
        chung_tu_file: null,
        chung_tu_file_name: "",
        ghi_chu: "",
        chi_tiets: [this.createEmptyReceiptLine(seed)],
      };

      // Reset input file
      if (this.$refs.chungTuFileInput) {
        this.$refs.chungTuFileInput.value = "";
      }
    },

    // Xử lý khi user chọn file
    handleChungTuFile(event) {
      const file = event.target.files?.[0];

      if (!file) {
        this.receiptForm.chung_tu_file = null;
        this.receiptForm.chung_tu_file_name = "";
        return;
      }

      this.receiptForm.chung_tu_file = file;
      this.receiptForm.chung_tu_file_name = file.name;
      // Tạo object URL tạm để có thể xem trước / gửi kèm form
      this.receiptForm.chung_tu_url = URL.createObjectURL(file);
    },
    receiptLineThuoc(line) {
      return this.findThuocById(line?.id_thuoc) || null;
    },
    formatThuocSuggestion(thuoc) {
      if (!thuoc) {
        return "";
      }

      return `${thuoc.ten_thuoc} (${thuoc.ma_thuoc})`;
    },
    receiptLineThuocSuggestions(line) {
      const keyword = normalizeSearchText(line?.thuoc_keyword);
      const source = this.thuocRows;

      if (!keyword) {
        return source.slice(0, 12);
      }

      return source
        .map((thuoc) => {
          const name = normalizeSearchText(thuoc.ten_thuoc);
          const code = normalizeSearchText(thuoc.ma_thuoc);
          const label = normalizeSearchText(this.formatThuocSuggestion(thuoc));
          const startsWithScore = name.startsWith(keyword) || code.startsWith(keyword) ? 0 : 1;
          const includesScore = name.includes(keyword) || code.includes(keyword) || label.includes(keyword) ? 0 : 1;

          return {
            thuoc,
            score: includesScore * 10 + startsWithScore,
          };
        })
        .filter((item) => item.score < 10)
        .sort((a, b) => a.score - b.score || a.thuoc.ten_thuoc.localeCompare(b.thuoc.ten_thuoc, "vi"))
        .slice(0, 12)
        .map((item) => item.thuoc);
    },
    handleReceiptLineThuocInput(line) {
      const keyword = normalizeSearchText(line?.thuoc_keyword);
      const matchedThuoc = this.thuocRows.find((thuoc) => {
        const suggestion = normalizeSearchText(this.formatThuocSuggestion(thuoc));
        const code = normalizeSearchText(thuoc.ma_thuoc);
        const name = normalizeSearchText(thuoc.ten_thuoc);

        return keyword && (suggestion === keyword || code === keyword || name === keyword);
      });

      if (!matchedThuoc) {
        line.id_thuoc = "";
        line.don_vi_nhap = "";
        return;
      }

      line.id_thuoc = matchedThuoc.ma_thuoc;
      line.thuoc_keyword = this.formatThuocSuggestion(matchedThuoc);
      line.don_vi_nhap = this.defaultUnitForThuoc(matchedThuoc);
    },
    receiptLineUnitOptions(line) {
      return buildThuocUnitOptions(this.receiptLineThuoc(line));
    },
    receiptLineSelectedUnitOption(line) {
      const options = this.receiptLineUnitOptions(line);

      if (!line?.don_vi_nhap) {
        return options[0] || null;
      }

      return options.find((item) => item.lookup_key === normalizeUnitKey(line.don_vi_nhap)) || options[0] || null;
    },
    receiptLineBaseUnitName(line) {
      const thuoc = this.receiptLineThuoc(line);
      const option = this.receiptLineSelectedUnitOption(line);

      return normalizeUnitName(option?.don_vi_co_so || thuoc?.don_vi_co_so || thuoc?.don_vi_tinh);
    },
    receiptLineConvertedQuantity(line) {
      const quantity = Number(line?.so_luong_nhap_goc || 0);
      const factor = Math.max(1, Number(this.receiptLineSelectedUnitOption(line)?.so_luong_quy_doi || 1));

      return quantity * factor;
    },
    receiptLineConversionPreview(line) {
      const option = this.receiptLineSelectedUnitOption(line);

      if (!option) {
        return "Chọn thuốc và đơn vị nhập để xem quy đổi.";
      }

      return `1 ${option.ten_don_vi} = ${option.so_luong_quy_doi} ${this.receiptLineBaseUnitName(line) || "đơn vị tồn kho"}`;
    },
    receiptLineTotal(line) {
      return Number(line?.so_luong_nhap_goc || 0) * Number(line?.gia_nhap || 0);
    },
    handleReceiptLineThuocChange(line) {
      const thuoc = this.receiptLineThuoc(line);
      line.don_vi_nhap = this.defaultUnitForThuoc(thuoc);
    },
    addReceiptLine(seed = {}) {
      this.receiptForm.chi_tiets.push(this.createEmptyReceiptLine(seed));
    },
    removeReceiptLine(index) {
      if (this.receiptForm.chi_tiets.length <= 1) {
        return;
      }

      this.receiptForm.chi_tiets.splice(index, 1);
    },
    openReceiptForm(seed = {}) {
      this.resetReceiptForm(seed);
      this.ensureModal();
      this.receiptFormModal?.show();
    },
    openReceiptList() {
      this.ensureModal();
      this.receiptListModal?.show();
    },
    openReceiptFormFromAlert(idThuoc) {
      this.alertModal?.hide();
      this.openReceiptForm({ id_thuoc: idThuoc });
    },
    validateReceiptForm() {
      if (!this.receiptForm.id_nha_san_xuat) {
        return "Vui lòng chọn nhà cung cấp.";
      }

      if (!this.receiptForm.ngay_nhap) {
        return "Vui lòng chọn ngày nhập hàng.";
      }

      for (const [index, line] of this.receiptForm.chi_tiets.entries()) {
        const lineNumber = index + 1;

        if (!line.id_thuoc) {
          return `Dòng ${lineNumber}: vui lòng chọn thuốc.`;
        }

        if (!line.so_lo) {
          return `Dòng ${lineNumber}: vui lòng nhập số lô.`;
        }

        if (!line.ngay_san_xuat || !line.han_su_dung) {
          return `Dòng ${lineNumber}: vui lòng nhập ngày sản xuất và hạn sử dụng.`;
        }

        if (!line.don_vi_nhap) {
          return `Dòng ${lineNumber}: vui lòng chọn đơn vị nhập.`;
        }

        if (!line.so_luong_nhap_goc || Number(line.so_luong_nhap_goc) < 1) {
          return `Dòng ${lineNumber}: vui lòng nhập số lượng nhập hợp lệ.`;
        }

        if (!line.gia_nhap || Number(line.gia_nhap) < 1) {
          return `Dòng ${lineNumber}: vui lòng nhập giá nhập hợp lệ.`;
        }
      }

      return "";
    },
    async saveReceipt() {
      if (!this.isAdminUser) {
        showToast("Bạn không có quyền tạo phiếu nhập hàng.", "error");
        return;
      }

      const validationMessage = this.validateReceiptForm();
      if (validationMessage) {
        showToast(validationMessage, "error");
        return;
      }

      const payload = {
        id_nha_san_xuat: this.receiptForm.id_nha_san_xuat,
        so_hoa_don_giay: this.receiptForm.so_hoa_don_giay || null,
        ngay_hoa_don: this.receiptForm.ngay_hoa_don || null,
        ngay_nhap: this.receiptForm.ngay_nhap,
        chung_tu_url: this.receiptForm.chung_tu_url || null,
        ghi_chu: this.receiptForm.ghi_chu || null,
        chi_tiets: this.receiptForm.chi_tiets.map((line) => ({
          id_thuoc: line.id_thuoc,
          so_lo: line.so_lo,
          ngay_san_xuat: line.ngay_san_xuat,
          han_su_dung: line.han_su_dung,
          don_vi_nhap: line.don_vi_nhap,
          so_luong_nhap_goc: Number(line.so_luong_nhap_goc),
          gia_nhap: Number(line.gia_nhap),
        })),
      };

      this.loading.saveReceipt = true;

      try {
        await createPhieuNhap(payload);
        showToast("Đã lưu phiếu nhập hàng và cập nhật tồn kho.", "success");
        await this.loadInventory();
        this.receiptFormModal?.hide();
      } catch (err) {
        showToast(this.normalizeError(err), "error");
      } finally {
        this.loading.saveReceipt = false;
      }
    },
    handleLotThuocChange() {
      if (this.lotForm.id_lo) {
        return;
      }

      const thuoc = this.findThuocById(this.lotForm.id_thuoc);
      this.lotForm.don_vi_nhap = this.defaultUnitForThuoc(thuoc);
    },
    openLotModal(thuoc) {
      this.selectedThuoc = thuoc;
      this.ensureModal();
      this.lotModal?.show();
    },
    handleMetricClick(metric) {
      if (!metric?.alertType) {
        return;
      }

      this.activeAlertType = metric.alertType;
      this.ensureModal();
      this.alertModal?.show();
    },
    openRelatedLotDetail(idThuoc) {
      const thuoc = this.thuocRows.find((item) => item.ma_thuoc === idThuoc);

      if (!thuoc) {
        return;
      }

      this.alertModal?.hide();
      this.openLotModal(thuoc);
    },
    openLotFormFromAlert(idThuoc) {
      this.alertModal?.hide();
      this.openLotForm({ id_thuoc: idThuoc });
    },
    openAlertFromRouteQuery() {
      const alertType = this.$route?.query?.alert;

      if (!["expiring", "low-stock"].includes(alertType)) {
        return;
      }

      this.activeAlertType = alertType;
      this.ensureModal();
      this.$nextTick(() => {
        this.alertModal?.show();
      });
    },
    openLotForm(lot = {}) {
      const resolvedThuocId = lot.id_thuoc || this.selectedThuoc?.ma_thuoc || "";
      const thuoc = this.findThuocById(resolvedThuocId);
      const defaultUnit = this.defaultUnitForThuoc(thuoc);

      this.lotForm = {
        id_lo: lot.id_lo || null,
        id_thuoc: resolvedThuocId,
        so_lo: lot.so_lo || "",
        ngay_san_xuat: lot.ngay_san_xuat || "",
        han_su_dung: lot.han_su_dung || "",
        don_vi_nhap: lot.don_vi_nhap || defaultUnit,
        so_luong_nhap_goc: lot.so_luong_nhap_goc || null,
        so_luong_con: lot.so_luong_con ?? lot.so_luong_nhap ?? null,
        so_luong_nhap_them_goc: null,
        gia_nhap: lot.gia_nhap || null,
      };

      this.lockThuocSelect = Boolean(lot.id_thuoc || lot.id_lo);
      this.ensureModal();
      this.lotFormModal?.show();
    },
    async saveLot() {
      if (!this.isAdminUser) {
        showToast("Bạn không có quyền thao tác lô thuốc.", "error");
        return;
      }

      if (!this.lotForm.id_thuoc) {
        showToast("Vui lòng chọn thuốc.", "error");
        return;
      }

      if (!this.lotForm.so_lo) {
        showToast("Vui lòng nhập số lô.", "error");
        return;
      }

      if (!this.lotForm.ngay_san_xuat || !this.lotForm.han_su_dung) {
        showToast("Vui lòng nhập ngày sản xuất và hạn sử dụng.", "error");
        return;
      }

      if (!this.lotForm.don_vi_nhap) {
        showToast("Vui lòng chọn đơn vị nhập.", "error");
        return;
      }

      if (!this.lotForm.gia_nhap || Number(this.lotForm.gia_nhap) < 1) {
        showToast("Vui lòng nhập giá nhập hợp lệ.", "error");
        return;
      }

      if (!this.lotForm.id_lo && (!this.lotForm.so_luong_nhap_goc || Number(this.lotForm.so_luong_nhap_goc) < 1)) {
        showToast("Vui lòng nhập số lượng nhập.", "error");
        return;
      }

      if (this.lotForm.id_lo && (!this.lotForm.so_luong_nhap_them_goc || Number(this.lotForm.so_luong_nhap_them_goc) < 1)) {
        showToast("Vui lòng nhập số lượng nhập thêm.", "error");
        return;
      }

      const payload = {
        id_thuoc: this.lotForm.id_thuoc,
        so_lo: this.lotForm.so_lo,
        ngay_san_xuat: this.lotForm.ngay_san_xuat,
        han_su_dung: this.lotForm.han_su_dung,
        don_vi_nhap: this.lotForm.don_vi_nhap,
        gia_nhap: Number(this.lotForm.gia_nhap),
      };

      if (this.lotForm.id_lo) {
        payload.so_luong_nhap_them_goc = Number(this.lotForm.so_luong_nhap_them_goc);
      } else {
        payload.so_luong_nhap_goc = Number(this.lotForm.so_luong_nhap_goc);
      }

      this.loading.saveLot = true;

      try {
        if (this.lotForm.id_lo) {
          await updateLoThuoc(this.lotForm.id_lo, payload);
          showToast("Đã cập nhật lô thuốc.", "success");
        } else {
          await createLoThuoc(payload);
          showToast("Đã nhập lô thuốc mới.", "success");
        }

        await this.loadInventory();
        this.lotFormModal?.hide();
        this.lockThuocSelect = false;
      } catch (err) {
        showToast(this.normalizeError(err), "error");
      } finally {
        this.loading.saveLot = false;
      }
    },
    async loadInventory(options = {}) {
      const { showSuccessToast = false } = options;

      this.loading.inventory = true;

      try {
        const [thuocData, loData, phieuNhapData, nhaSanXuatData] = await Promise.all([getThuocs(), getLoThuocs(), getPhieuNhaps(), getNhaSanXuats()]);
        this.thuocs = thuocData;
        this.loThuocs = loData;
        this.phieuNhaps = Array.isArray(phieuNhapData) ? phieuNhapData : phieuNhapData?.data || [];
        this.nhaSanXuats = Array.isArray(nhaSanXuatData) ? nhaSanXuatData : nhaSanXuatData?.data || [];

        if (this.selectedThuoc) {
          const refreshed = thuocData.find((item) => item.ma_thuoc === this.selectedThuoc.ma_thuoc);
          this.selectedThuoc = refreshed || null;
        }

        if (showSuccessToast) {
          showToast(`Đã đồng bộ ${this.thuocs.length} thuốc và ${this.loThuocs.length} lô thuốc.`, "success");
        }
      } catch (err) {
        showToast(this.normalizeError(err), "error");
      } finally {
        this.loading.inventory = false;
      }
    },
    async handleSearch() {
      if (!this.keyword) {
        return;
      }

      this.loading.search = true;

      try {
        const [thuocData, loData] = await Promise.all([searchThuocs(this.keyword), searchLoThuocs(this.keyword)]);
        this.thuocs = thuocData;
        this.loThuocs = loData;
        this.selectedThuoc = null;
        showToast(`Tìm thấy ${this.thuocs.length} thuốc và ${this.loThuocs.length} lô phù hợp.`, "success");
      } catch (err) {
        showToast(this.normalizeError(err), "error");
      } finally {
        this.loading.search = false;
      }
    },
    resetView() {
      this.keyword = "";
      this.selectedThuoc = null;
      this.loadInventory();
    },
  },
  watch: {
    "$route.query.alert"() {
      this.openAlertFromRouteQuery();
    },
  },
  async mounted() {
    this.ensureModal();
    await this.loadInventory();
    this.openAlertFromRouteQuery();
  },
  beforeUnmount() {
    if (this.alertModal) {
      this.alertModal.dispose();
    }
    if (this.lotModal) {
      this.lotModal.dispose();
    }
    if (this.lotFormModal) {
      this.lotFormModal.dispose();
    }
    if (this.receiptFormModal) {
      this.receiptFormModal.dispose();
    }
    if (this.receiptListModal) {
      this.receiptListModal.dispose();
    }
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
  transform: translateY(-3px);
  border-color: rgba(22, 82, 197, 0.24);
  box-shadow: 0 18px 38px rgba(15, 31, 79, 0.14);
  outline: none;
}

.inventory-alert-card {
  padding: 18px;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 20px;
  background: #fff;
}

.inventory-alert-card__body {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.inventory-alert-card__body > div,
.inventory-alert-card__lot {
  padding: 12px;
  border-radius: 14px;
  background: #f5f8ff;
}

.inventory-alert-card__body span,
.inventory-alert-card__lot span,
.inventory-alert-card__lot small {
  display: block;
  color: #64748b;
  font-size: 0.85rem;
}

.inventory-alert-card__body strong,
.inventory-alert-card__lot strong {
  display: block;
  margin-top: 4px;
  color: #0f2654;
}

.inventory-alert-card__lots {
  display: grid;
  gap: 10px;
}

.inventory-alert-card__lot {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 4px 12px;
}

.inventory-alert-card__lot small {
  grid-column: 1 / -1;
}

.receipt-list,
.receipt-line-list {
  display: grid;
  gap: 16px;
}

.receipt-card,
.receipt-line-card {
  padding: 18px;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 20px;
  background: #fff;
}

.receipt-line-card {
  background: #f8fbff;
}

.receipt-line-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.receipt-line-summary > div {
  padding: 12px 14px;
  border-radius: 16px;
  background: #fff;
  border: 1px solid rgba(22, 82, 197, 0.1);
}

.receipt-line-summary span {
  display: block;
  color: #64748b;
  font-size: 0.85rem;
  margin-bottom: 4px;
}

.receipt-line-summary strong {
  color: #0f2654;
}

@media (max-width: 767.98px) {
  .receipt-line-summary {
    grid-template-columns: 1fr;
  }
}
</style>
