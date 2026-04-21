export const SUPPORT_CHAT_OPEN_EVENT = "pc-open-support-chat";

export function openSupportChat(detail = {}) {
  if (typeof window === "undefined") {
    return;
  }

  window.dispatchEvent(new CustomEvent(SUPPORT_CHAT_OPEN_EVENT, { detail }));
}
