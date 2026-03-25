import { apiClient } from "../lib/apiClient";

export function getProfile() {
  return apiClient("/profile");
}

export function updateProfileApi(payload) {
  const isFormData = payload instanceof FormData;

  return apiClient("/profile", {
    method: isFormData ? "POST" : "PUT",
    body: isFormData ? (() => {
      payload.append("_method", "PUT");
      return payload;
    })() : JSON.stringify(payload),
  });
}
