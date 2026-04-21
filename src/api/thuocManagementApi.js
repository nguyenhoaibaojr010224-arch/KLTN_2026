import { apiClient } from "../lib/apiClient";

export function getThuocList() {
  return apiClient("/thuocs");
}

export function searchThuocList(keyword) {
  const query = new URLSearchParams({ q: keyword });
  return apiClient(`/thuocs/search?${query.toString()}`);
}

export function getNhaSanXuatOptions() {
  return apiClient("/nha-san-xuats");
}

export function createNhaSanXuat(payload) {
  return apiClient("/admin/nha-san-xuats", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function createThuoc(payload) {
  return apiClient("/admin/thuocs", {
    method: "POST",
    body: payload instanceof FormData ? payload : JSON.stringify(payload),
  });
}

export function getNextThuocCode() {
  return apiClient("/admin/thuocs/next-code");
}

export function updateThuoc(id, payload) {
  const isFormData = payload instanceof FormData;
  return apiClient(`/admin/thuocs/${id}`, {
    method: isFormData ? "POST" : "PUT",
    body: isFormData ? payload : JSON.stringify(payload),
  });
}

export function deleteThuoc(id) {
  return apiClient(`/admin/thuocs/${id}`, {
    method: "DELETE",
  });
}
