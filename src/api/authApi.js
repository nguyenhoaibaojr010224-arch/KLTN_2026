import { apiClient } from "../lib/apiClient";

export function login(payload) {
  return apiClient("/login", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function logout() {
  return apiClient("/logout", {
    method: "POST",
  });
}

export function register(payload) {
  return apiClient("/register", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function verifyEmailCode(payload) {
  return apiClient("/email-verifications/verify-code", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function resendEmailVerification(payload) {
  return apiClient("/email-verifications/resend", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function requestPasswordReset(payload) {
  return apiClient("/password-resets/request", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function resetPasswordWithCode(payload) {
  return apiClient("/password-resets/reset", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}
