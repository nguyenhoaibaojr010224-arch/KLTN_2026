import { apiClient } from "../lib/apiClient";

export function getCustomerAddresses() {
  return apiClient("/profile/addresses");
}

export function createCustomerAddress(payload) {
  return apiClient("/profile/addresses", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function updateCustomerAddress(addressId, payload) {
  return apiClient(`/profile/addresses/${addressId}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export function deleteCustomerAddress(addressId) {
  return apiClient(`/profile/addresses/${addressId}`, {
    method: "DELETE",
  });
}
