import { apiClient } from "../lib/apiClient";

export function getHoaDons() {
  return apiClient("/admin/hoa-dons");
}

export function searchHoaDons(keyword) {
  const query = new URLSearchParams({ q: keyword });

  return apiClient(`/admin/hoa-dons/search?${query.toString()}`);
}

export function getHoaDonStatistics() {
  return apiClient("/admin/hoa-dons/statistics");
}
