export function normalizeProductText(value = "") {
  return String(value)
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

export function isPrescriptionProduct(product) {
  const danhMucSlug = normalizeProductText(product?.danh_muc_thuoc_slug);

  if (danhMucSlug.startsWith("ke-don")) {
    return true;
  }

  if (danhMucSlug.startsWith("khong-ke-don")) {
    return false;
  }

  const nhan = normalizeProductText(product?.nhan || product?.loai_thuoc);
  return nhan.includes("ke don");
}

export function buildPrescriptionConsultMessage(product) {
  const tenThuoc = String(product?.ten_thuoc || "thuốc này").trim();
  const maThuoc = String(product?.ma_thuoc || "").trim();
  return `Tôi cần tư vấn thuốc ${tenThuoc}${maThuoc ? ` (${maThuoc})` : ""}.`;
}
