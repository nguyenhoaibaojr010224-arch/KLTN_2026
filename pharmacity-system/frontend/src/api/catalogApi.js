import { apiClient } from "../lib/apiClient";

export function getCatalogThuocs(keyword = "") {
  const query = keyword ? `?q=${encodeURIComponent(keyword)}` : "";

  return apiClient(`/catalog/thuocs${query}`);
}

export function getCatalogThuoc(maThuoc) {
  return apiClient(`/catalog/thuocs/${maThuoc}`);
}
