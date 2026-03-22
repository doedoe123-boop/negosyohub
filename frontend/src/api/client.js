import axios from "axios";

const LOCALE_STORAGE_KEY = "negosyohub.locale";

const client = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? "",
  withCredentials: true, // send cookies for Sanctum SPA auth
  withXSRFToken: true,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
});

// ─── Request: inject bearer token if present (mobile / non-cookie flows) ──
client.interceptors.request.use((config) => {
  const token = sessionStorage.getItem("api_token");
  const locale = localStorage.getItem(LOCALE_STORAGE_KEY);

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  if (locale) {
    config.headers["X-Locale"] = locale;
  }

  return config;
});

// ─── Response: handle 401/419 globally ────────────────────────────────────
client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Let the router redirect to login via the auth store
      sessionStorage.removeItem("api_token");
      window.dispatchEvent(new Event("auth:unauthenticated"));
    }
    return Promise.reject(error);
  },
);

/**
 * Call GET /sanctum/csrf-cookie before any mutating request
 * when using cookie-based Sanctum SPA auth.
 */
export async function initCsrf() {
  await client.get("/sanctum/csrf-cookie");
}

export default client;
