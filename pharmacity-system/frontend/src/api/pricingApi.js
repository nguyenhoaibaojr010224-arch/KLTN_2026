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
