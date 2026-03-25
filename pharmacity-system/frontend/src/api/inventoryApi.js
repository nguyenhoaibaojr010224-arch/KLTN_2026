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

export function searchLoThuocs(keyword) {
  const query = new URLSearchParams({ q: keyword });

  return apiClient(`/lo-thuocs/search?${query.toString()}`);
}
