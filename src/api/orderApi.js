import { apiClient } from "../lib/apiClient";

export function getCustomerOrders() {
  return apiClient("/checkout/orders");
}

export function createCheckoutOrder(payload) {
  return apiClient("/checkout/orders", {
    method: "POST",
    body: JSON.stringify(payload),
    timeoutMs: 30000,
  });
}

export function cancelPayosOrder(payload) {
  return apiClient("/checkout/orders/payos-cancel", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}
