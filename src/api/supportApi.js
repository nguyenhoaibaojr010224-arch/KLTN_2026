import { apiClient } from "../lib/apiClient";

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
  return apiClient(`/support/conversation${buildQuery(params)}`);
}

export function sendCustomerSupportMessage(noiDung) {
  return apiClient("/support/conversation/messages", {
    method: "POST",
    body: JSON.stringify({
      noi_dung: noiDung,
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
