const TOKEN_KEY = "pharmacity_token";
const USER_KEY = "pharmacity_user";
const TYPE_KEY = "pharmacity_type";

export function getAccessToken() {
  return localStorage.getItem(TOKEN_KEY) || "";
}

export function isAuthenticated() {
  return Boolean(getAccessToken());
}

export function setAuthSession({ token, user, type }) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token);
  }

  if (user) {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  }

  if (type) {
    localStorage.setItem(TYPE_KEY, type);
  }
}

export function getStoredUser() {
  const raw = localStorage.getItem(USER_KEY);

  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

export function getAuthType() {
  return localStorage.getItem(TYPE_KEY) || "";
}

export function isSystemUser() {
  return ["admin", "staff"].includes(getAuthType());
}

export function clearAuthSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  localStorage.removeItem(TYPE_KEY);
}
