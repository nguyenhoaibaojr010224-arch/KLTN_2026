export const PASSWORD_SPECIAL_CHARACTER_PATTERN = /[!@#$%^&*(),.?":{}|<>_\-[\]\\/+=~`';]/;

export function validateStrongPassword(value, label = "Mật khẩu") {
  const password = String(value || "");

  if (!password) {
    return `Vui lòng nhập ${label.toLowerCase()}.`;
  }

  if (password.length < 8) {
    return `${label} phải có ít nhất 8 ký tự.`;
  }

  if (!/[A-Z]/.test(password)) {
    return `${label} phải có ít nhất 1 chữ in hoa.`;
  }

  if (!PASSWORD_SPECIAL_CHARACTER_PATTERN.test(password)) {
    return `${label} phải có ít nhất 1 ký tự đặc biệt.`;
  }

  return "";
}
