import { apiClient } from "../lib/apiClient";

export function getAdminCustomerNotifications(keyword = "") {
  const query = keyword ? `?q=${encodeURIComponent(keyword)}` : "";
  return apiClient(`/thong-bao-khach-hangs${query}`);
}

export function createAdminCustomerNotification(payload) {
  return apiClient("/thong-bao-khach-hangs", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function updateAdminCustomerNotification(id, payload) {
  return apiClient(`/thong-bao-khach-hangs/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export function deleteAdminCustomerNotification(id) {
  return apiClient(`/thong-bao-khach-hangs/${id}`, {
    method: "DELETE",
  });
}

export function getCustomerBroadcastNotifications() {
  return apiClient("/thong-bao-khach-hangs/customer-list");
}

export function markCustomerNotificationRead(id) {
  return apiClient(`/thong-bao-khach-hangs/${id}/mark-read`, {
    method: "POST",
  });
}

export function markAllCustomerNotificationsRead(group = "") {
  return apiClient("/thong-bao-khach-hangs/mark-all-read", {
    method: "POST",
    body: JSON.stringify(group ? { nhom: group } : {}),
  });
}
