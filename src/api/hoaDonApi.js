import { apiClient } from "../lib/apiClient";

export function getHoaDons() {
  return apiClient("/hoa-dons");
}

export function searchHoaDons(keyword) {
  const query = new URLSearchParams({ q: keyword });

  return apiClient(`/hoa-dons/search?${query.toString()}`);
}

export function getHoaDonStatistics() {
  return apiClient("/hoa-dons/statistics");
}

export function getPendingHoaDonNotifications() {
  return apiClient("/hoa-dons/pending-notifications");
}

export function confirmHoaDon(id, payload = {}) {
  return apiClient(`/hoa-dons/${id}/confirm`, {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function rejectHoaDon(id, payload = {}) {
  return apiClient(`/hoa-dons/${id}/reject`, {
    method: "POST",
    body: JSON.stringify(payload),
  });
}
