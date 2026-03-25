import { reactive } from "vue";

let closeTimer = null;

export const toastState = reactive({
  visible: false,
  message: "",
  type: "success",
});

export function showToast(message, type = "success", duration = 2600) {
  toastState.message = message;
  toastState.type = type;
  toastState.visible = true;

  if (closeTimer) {
    clearTimeout(closeTimer);
  }

  closeTimer = window.setTimeout(() => {
    toastState.visible = false;
  }, duration);
}

export function hideToast() {
  toastState.visible = false;

  if (closeTimer) {
    clearTimeout(closeTimer);
    closeTimer = null;
  }
}
