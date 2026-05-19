import { apiClient } from "../lib/apiClient";

export function getThuocs() {
  return apiClient("/thuocs");
}

export function searchThuocs(keyword) {
  const query = new URLSearchParams({ q: keyword });

  return apiClient(`/thuocs/search?${query.toString()}`);
}

export function getLoThuocs() {
  return apiClient("/lo-thuocs");
}

export function getPhieuNhaps() {
  return apiClient("/phieu-nhaps");
}

export function createPhieuNhap(payload) {
  return apiClient("/phieu-nhaps", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function getNhaSanXuats() {
  return apiClient("/nha-san-xuats");
}

export function getInventoryAlerts(options = {}) {
  const query = new URLSearchParams({
    days: String(options.days ?? 30),
    low_stock: String(options.lowStock ?? 20),
  });

  return apiClient(`/lo-thuocs/alerts?${query.toString()}`);
}

export function searchLoThuocs(keyword) {
  const query = new URLSearchParams({ q: keyword });

  return apiClient(`/lo-thuocs/search?${query.toString()}`);
}

export function createLoThuoc(payload) {
  return apiClient("/admin/lo-thuocs", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function updateLoThuoc(id, payload) {
  return apiClient(`/admin/lo-thuocs/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export function deleteThuoc(id) {
  return apiClient(`/admin/thuocs/${id}`, {
    method: "DELETE",
  });
}

export function disposeLoThuoc(id, payload = {}) {
  return apiClient(`/admin/lo-thuocs/${id}/dispose`, {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function deleteLoThuoc(id) {
  return apiClient(`/admin/lo-thuocs/${id}`, {
    method: "DELETE",
  });
}
