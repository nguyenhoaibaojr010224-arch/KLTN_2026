import { apiClient } from "../lib/apiClient";

export function login(payload) {
  return apiClient("/login", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function register(payload) {
  return apiClient("/register", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}
