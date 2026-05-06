import { apiClient } from "../lib/apiClient";

export function createCounterSale(payload) {
  return apiClient("/ban-tai-quay/hoa-don", {
    method: "POST",
    body: JSON.stringify(payload),
    timeoutMs: 20000,
  });
}

export function createCounterPayosPayment(payload) {
  return apiClient("/ban-tai-quay/payos", {
    method: "POST",
    body: JSON.stringify(payload),
    timeoutMs: 20000,
  });
}

export function getCounterPayosStatus(sessionKey) {
  return apiClient(`/ban-tai-quay/payos/${encodeURIComponent(sessionKey)}`);
}

export function cancelCounterPayosPayment(sessionKey) {
  return apiClient(`/ban-tai-quay/payos/${encodeURIComponent(sessionKey)}/cancel`, {
    method: "POST",
  });
}

export function loginCounterCustomer(payload) {
  return apiClient("/ban-tai-quay/khach-hang/dang-nhap", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function findCounterCustomerByPhone(payload) {
  return apiClient("/ban-tai-quay/khach-hang/so-dien-thoai", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}
