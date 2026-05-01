import { API_BASE_URL, apiClient } from "../lib/apiClient";

const GUEST_SUPPORT_SESSION_KEY = "pharmago_guest_support_session_id";

function createGuestSupportSessionId() {
  const randomValue =
    typeof crypto !== "undefined" && typeof crypto.randomUUID === "function"
      ? crypto.randomUUID()
      : `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`;

  return `guest-${randomValue}`.slice(0, 80);
}

export function getGuestSupportSessionId() {
  if (typeof window === "undefined") {
    return createGuestSupportSessionId();
  }

  const existing = window.sessionStorage.getItem(GUEST_SUPPORT_SESSION_KEY);

  if (existing) {
    return existing;
  }

  const nextSessionId = createGuestSupportSessionId();
  window.sessionStorage.setItem(GUEST_SUPPORT_SESSION_KEY, nextSessionId);

  return nextSessionId;
}

export function clearGuestSupportSessionId() {
  if (typeof window === "undefined") {
    return;
  }

  window.sessionStorage.removeItem(GUEST_SUPPORT_SESSION_KEY);
}

export function disconnectGuestSupportConversation() {
  if (typeof window === "undefined") {
    return false;
  }

  const guestSessionId = window.sessionStorage.getItem(GUEST_SUPPORT_SESSION_KEY);

  if (!guestSessionId) {
    return false;
  }

  const url = `${API_BASE_URL}/support/conversation/guest/disconnect`;
  const payload = new URLSearchParams({
    guest_session_id: guestSessionId,
  });

  clearGuestSupportSessionId();

  if (typeof navigator !== "undefined" && navigator.sendBeacon && navigator.sendBeacon(url, payload)) {
    return true;
  }

  fetch(url, {
    method: "POST",
    body: payload,
    keepalive: true,
  }).catch(() => {});

  return true;
}

function buildQuery(params = {}) {
  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === "") {
      return;
    }

    query.set(key, String(value));
  });

  const serialized = query.toString();
  return serialized ? `?${serialized}` : "";
}

export function getCustomerSupportConversation(params = {}) {
  return apiClient(`/support/conversation${buildQuery({
    guest_session_id: getGuestSupportSessionId(),
    ...params,
  })}`);
}

export function sendCustomerSupportMessage(noiDung) {
  return apiClient("/support/conversation/messages", {
    method: "POST",
    body: JSON.stringify({
      noi_dung: noiDung,
      guest_session_id: getGuestSupportSessionId(),
    }),
  });
}

export function getSupportConversations() {
  return apiClient("/support/conversations");
}

export function getSupportConversation(id) {
  return apiClient(`/support/conversations/${id}`);
}

export function sendSupportMessage(id, noiDung) {
  return apiClient(`/support/conversations/${id}/messages`, {
    method: "POST",
    body: JSON.stringify({
      noi_dung: noiDung,
    }),
  });
}

export function closeSupportConversation(id) {
  return apiClient(`/support/conversations/${id}/close`, {
    method: "POST",
  });
}

export function deleteSupportConversation(id) {
  return apiClient(`/support/conversations/${id}`, {
    method: "DELETE",
  });
}
