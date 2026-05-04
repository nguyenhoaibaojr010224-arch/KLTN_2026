import { computed, reactive } from "vue";

const TOKEN_KEY = "pharmacity_token";
const USER_KEY = "pharmacity_user";
const TYPE_KEY = "pharmacity_type";
const SESSION_CHANNEL_KEY = "pharmacity_session_channel";
const SESSION_EXPIRES_AT_KEY = "pharmacity_session_expires_at";
let sessionExpiryTimer = null;

function readStoredUser() {
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

export const authState = reactive({
  token: localStorage.getItem(TOKEN_KEY) || "",
  user: readStoredUser(),
  type: localStorage.getItem(TYPE_KEY) || "",
  sessionChannel: localStorage.getItem(SESSION_CHANNEL_KEY) || "",
  sessionExpiresAt: localStorage.getItem(SESSION_EXPIRES_AT_KEY) || "",
});

export const isAuthenticatedState = computed(() => Boolean(authState.token));
export const isSystemUserState = computed(() =>
  ["admin", "staff", "nhan_vien", "nhanvien"].includes(authState.type)
);
export const isAdminState = computed(() => authState.type === "admin");

export function getAccessToken() {
  return authState.token;
}

export function isAuthenticated() {
  return isAuthenticatedState.value;
}

export function getStoredUser() {
  return authState.user;
}

export function getAuthType() {
  return authState.type;
}

export function getSessionChannel() {
  return authState.sessionChannel;
}

export function getSessionExpiresAt() {
  return authState.sessionExpiresAt;
}

export function isSessionExpired() {
  if (!authState.sessionExpiresAt) {
    return false;
  }

  const expiresAt = Date.parse(authState.sessionExpiresAt);

  return Number.isFinite(expiresAt) && Date.now() >= expiresAt;
}

export function isSystemUser() {
  return isSystemUserState.value;
}

export function isAdminUser() {
  return isAdminState.value;
}

export function isCounterSession() {
  return isSystemUserState.value && authState.sessionChannel === "tai_quay";
}

export function isSystemChannelSession() {
  return isSystemUserState.value && authState.sessionChannel !== "tai_quay";
}

export function setAuthSession({
  token = "",
  user = null,
  type = "",
  loginChannel = "",
  login_channel = "",
  sessionChannel = "",
  expiresAt = "",
  expires_at = "",
  session_expires_at = "",
}) {
  const normalizedExpiresAt = expiresAt || expires_at || session_expires_at || "";
  const normalizedSessionChannel = sessionChannel || loginChannel || login_channel || "";

  authState.token = token;
  authState.user = user;
  authState.type = type;
  authState.sessionChannel = normalizedSessionChannel;
  authState.sessionExpiresAt = normalizedExpiresAt;

  if (token) {
    localStorage.setItem(TOKEN_KEY, token);
  } else {
    localStorage.removeItem(TOKEN_KEY);
  }

  if (user) {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  } else {
    localStorage.removeItem(USER_KEY);
  }

  if (type) {
    localStorage.setItem(TYPE_KEY, type);
  } else {
    localStorage.removeItem(TYPE_KEY);
  }

  if (normalizedSessionChannel) {
    localStorage.setItem(SESSION_CHANNEL_KEY, normalizedSessionChannel);
  } else {
    localStorage.removeItem(SESSION_CHANNEL_KEY);
  }

  if (normalizedExpiresAt) {
    localStorage.setItem(SESSION_EXPIRES_AT_KEY, normalizedExpiresAt);
  } else {
    localStorage.removeItem(SESSION_EXPIRES_AT_KEY);
  }

  scheduleSessionExpiry();
}

export function updateAuthUser(user = null) {
  if (!user) {
    return;
  }

  authState.user = user;
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function clearAuthSession() {
  setAuthSession({
    token: "",
    user: null,
    type: "",
    sessionChannel: "",
    expiresAt: "",
  });
}

function clearSessionExpiryTimer() {
  if (sessionExpiryTimer) {
    window.clearTimeout(sessionExpiryTimer);
    sessionExpiryTimer = null;
  }
}

function handleSessionExpired() {
  clearAuthSession();

  if (typeof window !== "undefined" && !window.location.pathname.startsWith("/login")) {
    window.location.href = "/login?reason=session-expired";
  }
}

function scheduleSessionExpiry() {
  clearSessionExpiryTimer();

  if (!authState.token || !authState.sessionExpiresAt) {
    return;
  }

  const expiresAt = Date.parse(authState.sessionExpiresAt);

  if (!Number.isFinite(expiresAt)) {
    return;
  }

  const delay = expiresAt - Date.now();

  if (delay <= 0) {
    handleSessionExpired();
    return;
  }

  sessionExpiryTimer = window.setTimeout(handleSessionExpired, Math.min(delay, 2147483647));
}

window.addEventListener("storage", (event) => {
  if (![TOKEN_KEY, USER_KEY, TYPE_KEY, SESSION_CHANNEL_KEY, SESSION_EXPIRES_AT_KEY].includes(event.key)) {
    return;
  }

  authState.token = localStorage.getItem(TOKEN_KEY) || "";
  authState.user = readStoredUser();
  authState.type = localStorage.getItem(TYPE_KEY) || "";
  authState.sessionChannel = localStorage.getItem(SESSION_CHANNEL_KEY) || "";
  authState.sessionExpiresAt = localStorage.getItem(SESSION_EXPIRES_AT_KEY) || "";
  scheduleSessionExpiry();
});

scheduleSessionExpiry();
