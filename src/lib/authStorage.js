import { computed, reactive } from "vue";

const TOKEN_KEY = "pharmacity_token";
const USER_KEY = "pharmacity_user";
const TYPE_KEY = "pharmacity_type";

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

export function isSystemUser() {
  return isSystemUserState.value;
}

export function isAdminUser() {
  return isAdminState.value;
}

export function setAuthSession({ token = "", user = null, type = "" }) {
  authState.token = token;
  authState.user = user;
  authState.type = type;

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
  });
}

window.addEventListener("storage", (event) => {
  if (![TOKEN_KEY, USER_KEY, TYPE_KEY].includes(event.key)) {
    return;
  }

  authState.token = localStorage.getItem(TOKEN_KEY) || "";
  authState.user = readStoredUser();
  authState.type = localStorage.getItem(TYPE_KEY) || "";
});
