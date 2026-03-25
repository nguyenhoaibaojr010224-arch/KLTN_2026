const RECENT_SEARCHES_KEY = "pharmacity_recent_searches";
const RECENT_SEARCHES_LIMIT = 6;

function normalizeKeyword(value) {
  return String(value || "")
    .trim()
    .replace(/\s+/g, " ");
}

export function getRecentSearches() {
  try {
    const raw = localStorage.getItem(RECENT_SEARCHES_KEY);
    const parsed = raw ? JSON.parse(raw) : [];

    return Array.isArray(parsed)
      ? parsed.map(normalizeKeyword).filter(Boolean).slice(0, RECENT_SEARCHES_LIMIT)
      : [];
  } catch {
    return [];
  }
}

export function saveRecentSearch(keyword) {
  const normalizedKeyword = normalizeKeyword(keyword);

  if (!normalizedKeyword) {
    return getRecentSearches();
  }

  const nextSearches = [
    normalizedKeyword,
    ...getRecentSearches().filter(
      (item) => item.toLowerCase() !== normalizedKeyword.toLowerCase()
    ),
  ].slice(0, RECENT_SEARCHES_LIMIT);

  localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(nextSearches));
  return nextSearches;
}

export function clearRecentSearches() {
  localStorage.removeItem(RECENT_SEARCHES_KEY);
  return [];
}
