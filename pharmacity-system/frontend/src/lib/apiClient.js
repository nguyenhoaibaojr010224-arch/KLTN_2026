import { getAccessToken } from "./authStorage";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api";

async function parseResponse(response) {
  const contentType = response.headers.get("content-type") || "";
  const isJson = contentType.includes("application/json");
  const payload = isJson ? await response.json() : await response.text();

  if (!response.ok) {
    const message =
      (isJson && (payload.message || payload.error)) || "Yeu cau that bai. Vui long thu lai.";

    throw {
      status: response.status,
      message,
      payload,
    };
  }

  return payload;
}

export async function apiClient(path, options = {}) {
  const token = getAccessToken();
  const isFormData = options.body instanceof FormData;
  const headers = {
    Accept: "application/json",
    ...(!isFormData && options.body ? { "Content-Type": "application/json" } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(options.headers || {}),
  };

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  });

  return parseResponse(response);
}

export { API_BASE_URL };
