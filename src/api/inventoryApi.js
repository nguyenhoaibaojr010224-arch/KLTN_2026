import { apiClient } from "../lib/apiClient";

export function getThuocs() {
  return apiClient("/thuocs");
}

export function searchThuocs(keyword) {
  const query = new URLSearchParams({ q: keyword });

  return apiClient(`/thuocs/search?${query.toString()}`);
}

export function getLoThuocs() {
  return apiClient("/admin/lo-thuocs");
}

export function searchLoThuocs(keyword) {
  const query = new URLSearchParams({ q: keyword });

  return apiClient(`/admin/lo-thuocs/search?${query.toString()}`);
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
