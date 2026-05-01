import { apiClient } from "../lib/apiClient";

export function getKhachHangs() {
  return apiClient("/admin/khach-hangs");
}

export function searchKhachHangs(keyword) {
  const query = new URLSearchParams({ q: keyword });

  return apiClient(`/admin/khach-hangs/search?${query.toString()}`);
}

export function getKhachHang(id) {
  return apiClient(`/admin/khach-hangs/${id}`);
}
