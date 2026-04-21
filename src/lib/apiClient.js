import { clearAuthSession, getAccessToken } from "./authStorage";

const DEFAULT_API_BASE_URL = "http://127.0.0.1:8000/api";
const DEFAULT_API_TIMEOUT_MS = 10000;
const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/+$/, "");

function buildApiUrl(path) {
  if (!path) {
    return API_BASE_URL;
  }

  return `${API_BASE_URL}${path.startsWith("/") ? path : `/${path}`}`;
}

async function parseResponse(response) {
  const contentType = response.headers.get("content-type") || "";
  const isJson = contentType.includes("application/json");
  const payload = isJson ? await response.json() : await response.text();

  if (!response.ok) {
    const message =
      (isJson && (payload.message || payload.error)) || "Yeu cau that bai. Vui long thu lai.";

    if (response.status === 401) {
      clearAuthSession();

      if (typeof window !== "undefined" && !window.location.pathname.startsWith("/login")) {
        window.location.href = "/login";
      }
    }

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
  const timeoutMs =
    typeof options.timeoutMs === "number" && options.timeoutMs > 0
      ? options.timeoutMs
      : DEFAULT_API_TIMEOUT_MS;
  const headers = {
    Accept: "application/json",
    ...(!isFormData && options.body ? { "Content-Type": "application/json" } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(options.headers || {}),
  };
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort("request_timeout"), timeoutMs);

  let response;

  try {
    response = await fetch(buildApiUrl(path), {
      ...options,
      headers,
      signal: options.signal || controller.signal,
    });
  } catch (error) {
    clearTimeout(timeoutId);

    if (error?.name === "AbortError" || error === "request_timeout") {
      throw {
        status: 0,
        message: `Khong nhan duoc phan hoi tu Laravel API tai ${API_BASE_URL} sau ${Math.round(
          timeoutMs / 1000
        )} giay. Kiem tra backend da chay.`,
        payload: null,
        cause: error,
      };
    }

    throw {
      status: 0,
      message: `Khong the ket noi den Laravel API tai ${API_BASE_URL}. Kiem tra backend da chay va VITE_API_BASE_URL.`,
      payload: null,
      cause: error,
    };
  } finally {
    clearTimeout(timeoutId);
  }

  return parseResponse(response);
}

export { API_BASE_URL };
