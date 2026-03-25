import { apiClient } from "../lib/apiClient";

export function getVaiTros() {
  return apiClient("/vai-tros");
}

export function getBangCaps() {
  return apiClient("/bang-caps");
}
