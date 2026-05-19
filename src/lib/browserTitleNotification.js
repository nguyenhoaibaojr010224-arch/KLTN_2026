let baseTitle = typeof document !== "undefined" ? document.title : "";
let notificationTitle = "";

function applyBrowserTitle() {
  if (typeof document === "undefined") {
    return;
  }

  document.title = notificationTitle ? `${notificationTitle} | ${baseTitle}` : baseTitle;
}

export function setBrowserBaseTitle(title) {
  baseTitle = String(title || "");
  applyBrowserTitle();
}

export function setBrowserNotificationTitle(title) {
  notificationTitle = String(title || "");
  applyBrowserTitle();
}

export function clearBrowserNotificationTitle() {
  notificationTitle = "";
  applyBrowserTitle();
}
