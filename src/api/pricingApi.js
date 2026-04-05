import { apiClient } from "../lib/apiClient";

export function updateThuocPrice(maThuoc, payload) {
  return apiClient(`/thuocs/${maThuoc}/price`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export function getKhuyenMais(keyword = "") {
  const query = keyword ? `?q=${encodeURIComponent(keyword)}` : "";

  return apiClient(`/khuyen-mais${query}`);
}

export function createKhuyenMai(payload) {
  return apiClient("/khuyen-mais", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function updateKhuyenMai(id, payload) {
  return apiClient(`/khuyen-mais/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export function deleteKhuyenMai(id) {
  return apiClient(`/khuyen-mais/${id}`, {
    method: "DELETE",
  });
}

export function validatePromotionCode(payload) {
  return apiClient("/ma-giam-gias/validate-code", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function getAvailableOrderDiscountCodes(subtotal, keyword = "") {
  const params = new URLSearchParams({
    tong_tam_tinh: String(subtotal),
  });

  if (keyword) {
    params.set("q", keyword);
  }

  return apiClient(`/ma-giam-gias/customer-available?${params.toString()}`);
}

export function redeemPromotionCode(payload) {
  return apiClient("/ma-giam-gias/redeem-code", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function getOrderDiscountCodes(keyword = "") {
  const query = keyword ? `?q=${encodeURIComponent(keyword)}` : "";
  return apiClient(`/ma-giam-gias${query}`);
}

export function getCustomerOrderDiscountCodes(subtotal = 0, keyword = "") {
  const params = new URLSearchParams({
    tong_tam_tinh: String(subtotal),
  });

  if (keyword) {
    params.set("q", keyword);
  }

  return apiClient(`/ma-giam-gias/customer-available?${params.toString()}`);
}

export function createOrderDiscountCode(payload) {
  return apiClient("/ma-giam-gias", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function updateOrderDiscountCode(id, payload) {
  return apiClient(`/ma-giam-gias/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export function deleteOrderDiscountCode(id) {
  return apiClient(`/ma-giam-gias/${id}`, {
    method: "DELETE",
  });
}
