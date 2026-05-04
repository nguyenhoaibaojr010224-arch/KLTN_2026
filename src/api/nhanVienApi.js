import { apiClient } from "../lib/apiClient";

export function getNhanViens() {
  return apiClient("/admin/nhan-viens");
}

export function searchNhanViens(keyword) {
  const query = new URLSearchParams({ q: keyword });

  return apiClient(`/admin/nhan-viens/search?${query.toString()}`);
}

export function getNhanVienWorkSessions(id, filters = {}) {
  const query = new URLSearchParams();

  if (filters.date) {
    query.set("date", filters.date);
  }

  if (filters.month) {
    query.set("month", filters.month);
  }

  if (filters.year) {
    query.set("year", filters.year);
  }

  const suffix = query.toString() ? `?${query.toString()}` : "";

  return apiClient(`/admin/nhan-viens/${id}/work-sessions${suffix}`);
}

export function getNhanVienSalesByDate(id, date) {
  const query = new URLSearchParams();

  if (date) {
    query.set("date", date);
  }

  const suffix = query.toString() ? `?${query.toString()}` : "";

  return apiClient(`/admin/nhan-viens/${id}/sales-by-date${suffix}`);
}

export function createNhanVien(payload) {
  return apiClient("/admin/nhan-viens", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function updateNhanVien(id, payload) {
  return apiClient(`/admin/nhan-viens/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export function deleteNhanVien(id) {
  return apiClient(`/admin/nhan-viens/${id}`, {
    method: "DELETE",
  });
}

export function changeNhanVienPassword(id, payload) {
  return apiClient(`/admin/nhan-viens/${id}/change-password`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}
