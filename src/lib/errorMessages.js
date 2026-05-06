const DEFAULT_FIELD_LABELS = {
  ten_khach_hang: "tên khách hàng",
  ho_ten: "họ tên",
  ten: "tên",
  email: "email",
  so_dien_thoai: "số điện thoại",
  dia_chi: "địa chỉ",
  mat_khau: "mật khẩu",
  password: "mật khẩu",
  password_confirmation: "xác nhận mật khẩu",
  mat_khau_confirmation: "xác nhận mật khẩu",
  new_password: "mật khẩu mới",
  new_password_confirmation: "xác nhận mật khẩu mới",
  id_vai_tro: "vai trò",
  id_bang_cap: "bằng cấp",
  trang_thai: "trạng thái",
  ten_thuoc: "tên thuốc",
  ma_thuoc: "mã thuốc",
  nhan: "nhãn",
  danh_muc_cha_slug: "danh mục",
  danh_muc_con_slug: "nhánh danh mục",
  danh_muc_thuoc_slug: "danh mục",
  nha_san_xuat: "nhà sản xuất",
  id_nha_san_xuat: "nhà cung cấp",
  don_vi_tinh: "đơn vị bán",
  don_vi_co_so: "đơn vị tồn kho",
  don_vi_nhap: "đơn vị nhập",
  he_so_quy_doi: "hệ số quy đổi",
  gia_ban: "giá bán",
  gia_nhap: "giá nhập",
  so_luong_nhap_goc: "số lượng nhập",
  so_luong: "số lượng",
  so_lo: "số lô",
  ngay_san_xuat: "ngày sản xuất",
  han_su_dung: "hạn sử dụng",
  ngay_nhap: "ngày nhập hàng",
  ngay_hoa_don: "ngày trên hóa đơn",
  ma_khuyen_mai: "mã khuyến mãi",
  ten_khuyen_mai: "tên khuyến mãi",
  ma_giam_gia: "mã giảm giá",
  ten_ma: "tên chương trình",
  loai_ap_dung: "loại áp dụng",
  gia_tri: "giá trị",
  gia_tri_don_toi_thieu: "giá trị đơn tối thiểu",
  gioi_han_moi_khach: "số lần dùng tối đa mỗi khách",
  ngay_bat_dau: "ngày bắt đầu",
  ngay_ket_thuc: "ngày kết thúc",
  ly_do_tu_choi: "lý do từ chối",
  noi_dung: "nội dung",
  message: "nội dung",
};

const VIETNAMESE_MARKS = /[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i;

function normalizeFieldName(field = "") {
  return String(field)
    .replace(/\.\d+\./g, ".")
    .replace(/\.\d+$/g, "")
    .split(".")
    .pop();
}

export function fieldLabel(field, customLabels = {}) {
  const normalized = normalizeFieldName(field);
  return customLabels[field] || customLabels[normalized] || DEFAULT_FIELD_LABELS[normalized] || "trường thông tin này";
}

function isSelectField(field = "") {
  return [
    "id_vai_tro",
    "id_bang_cap",
    "trang_thai",
    "danh_muc_cha_slug",
    "danh_muc_con_slug",
    "danh_muc_thuoc_slug",
    "id_nha_san_xuat",
    "id_thuoc",
    "don_vi_nhap",
    "loai_ap_dung",
  ].includes(normalizeFieldName(field));
}

function extractFieldFromEnglishMessage(message = "") {
  const match = String(message).match(/(?:the\s+)?([a-zA-Z0-9_.]+)\s+field/i);
  return match?.[1] || "";
}

function inferFieldFromMessage(message = "") {
  const text = String(message);
  const columnMatch = text.match(/column\s+'([^']+)'/i) || text.match(/key\s+'([^']+)'/i);
  if (columnMatch?.[1]) {
    const columnName = columnMatch[1].replace(/.*\./, "");
    return Object.keys(DEFAULT_FIELD_LABELS).find((field) => columnName.includes(field)) || columnName;
  }

  return Object.keys(DEFAULT_FIELD_LABELS).find((field) => text.includes(field)) || "";
}

export function translateErrorMessage(message, field = "", customLabels = {}) {
  const rawMessage = String(message || "").trim();

  if (!rawMessage) {
    return "Đã xảy ra lỗi. Vui lòng thử lại.";
  }

  if (VIETNAMESE_MARKS.test(rawMessage) && !rawMessage.includes("SQLSTATE")) {
    return rawMessage;
  }

  const inferredField = field || extractFieldFromEnglishMessage(rawMessage) || inferFieldFromMessage(rawMessage);
  const normalizedField = normalizeFieldName(inferredField);
  const label = fieldLabel(inferredField, customLabels);
  const lower = rawMessage.toLowerCase();

  if (lower.includes("sqlstate") || lower.includes("integrity constraint violation")) {
    if (lower.includes("duplicate") || lower.includes("1062") || lower.includes("unique")) {
      return `${label.charAt(0).toUpperCase()}${label.slice(1)} đã tồn tại trong hệ thống.`;
    }

    if (lower.includes("cannot be null") || lower.includes("1048")) {
      return `Vui lòng nhập ${label}.`;
    }

    if (lower.includes("foreign key")) {
      return "Dữ liệu được chọn không hợp lệ hoặc không còn tồn tại.";
    }

    return "Không thể lưu dữ liệu. Vui lòng kiểm tra lại thông tin đã nhập.";
  }

  if (lower.includes("required")) {
    return isSelectField(normalizedField) ? `Vui lòng chọn ${label}.` : `Vui lòng nhập ${label}.`;
  }

  if (lower.includes("already been taken") || lower.includes("unique") || lower.includes("duplicate")) {
    return `${label.charAt(0).toUpperCase()}${label.slice(1)} đã tồn tại trong hệ thống.`;
  }

  if (lower.includes("valid email") || (lower.includes("email") && lower.includes("invalid"))) {
    return "Email không đúng định dạng.";
  }

  if (lower.includes("confirmed") || lower.includes("confirmation")) {
    return "Nội dung xác nhận không khớp.";
  }

  if (lower.includes("numeric") || lower.includes("integer") || lower.includes("number")) {
    return `${label.charAt(0).toUpperCase()}${label.slice(1)} phải là số hợp lệ.`;
  }

  if (lower.includes("min")) {
    if (["gia_ban", "gia_nhap", "gia_tri", "so_luong", "so_luong_nhap_goc", "he_so_quy_doi"].includes(normalizedField)) {
      return `${label.charAt(0).toUpperCase()}${label.slice(1)} phải lớn hơn 0.`;
    }

    if (["mat_khau", "password", "new_password"].includes(normalizedField)) {
      return "Mật khẩu phải có ít nhất 6 ký tự.";
    }

    return `${label.charAt(0).toUpperCase()}${label.slice(1)} chưa đủ độ dài yêu cầu.`;
  }

  if (lower.includes("max")) {
    return `${label.charAt(0).toUpperCase()}${label.slice(1)} vượt quá giới hạn cho phép.`;
  }

  if (lower.includes("exists")) {
    return "Dữ liệu được chọn không hợp lệ hoặc không còn tồn tại.";
  }

  if (lower.includes("date")) {
    return `${label.charAt(0).toUpperCase()}${label.slice(1)} không đúng định dạng ngày.`;
  }

  if (lower.includes("invalid") || lower.includes("in:")) {
    return `${label.charAt(0).toUpperCase()}${label.slice(1)} không hợp lệ.`;
  }

  return "Không thể xử lý yêu cầu. Vui lòng kiểm tra lại thông tin.";
}

export function normalizeApiError(error, fallback = "Đã xảy ra lỗi. Vui lòng thử lại.", customLabels = {}) {
  const errors = error?.payload?.errors || error?.response?.data?.errors || error?.errors;

  if (errors && typeof errors === "object") {
    return Object.entries(errors)
      .flatMap(([field, messages]) => (Array.isArray(messages) ? messages : [messages]).map((message) => translateErrorMessage(message, field, customLabels)))
      .filter(Boolean)
      .join(" | ");
  }

  const message = error?.payload?.message || error?.response?.data?.message || error?.data?.message || error?.message;
  return message ? translateErrorMessage(message, "", customLabels) : fallback;
}
