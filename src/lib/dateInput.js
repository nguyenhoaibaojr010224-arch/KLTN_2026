export function formatDateInputValue(value) {
  const raw = String(value || "").trim();

  if (!raw) {
    return "";
  }

  if (/^\d{2}\/\d{2}\/\d{4}$/.test(raw)) {
    return raw;
  }

  const isoMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (isoMatch) {
    return `${isoMatch[3]}/${isoMatch[2]}/${isoMatch[1]}`;
  }

  const date = value instanceof Date ? value : new Date(raw);
  if (Number.isNaN(date.getTime())) {
    return raw;
  }

  return [
    String(date.getDate()).padStart(2, "0"),
    String(date.getMonth() + 1).padStart(2, "0"),
    String(date.getFullYear()),
  ].join("/");
}

export function formatDateTyping(value) {
  const digits = String(value || "").replace(/\D/g, "").slice(0, 8);
  const day = digits.slice(0, 2);
  const month = digits.slice(2, 4);
  const year = digits.slice(4, 8);

  return [day, month, year].filter(Boolean).join("/");
}

export function parseDateInputValue(value) {
  const raw = String(value || "").trim();
  const match = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);

  if (!match) {
    return "";
  }

  const day = Number(match[1]);
  const month = Number(match[2]);
  const year = Number(match[3]);
  const date = new Date(year, month - 1, day);
  const isValid =
    date.getFullYear() === year &&
    date.getMonth() === month - 1 &&
    date.getDate() === day;

  if (!isValid) {
    return "";
  }

  return `${match[3]}-${match[2]}-${match[1]}`;
}

export function todayDateInputValue() {
  return formatDateInputValue(new Date());
}

export function formatDateTimeInputValue(value) {
  const raw = String(value || "").trim();

  if (!raw) {
    return "";
  }

  const displayMatch = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2}))?$/);
  if (displayMatch) {
    return displayMatch[4] ? raw : `${raw} 00:00`;
  }

  const isoMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})[T\s](\d{2}):(\d{2})/);
  if (isoMatch) {
    return `${isoMatch[3]}/${isoMatch[2]}/${isoMatch[1]} ${isoMatch[4]}:${isoMatch[5]}`;
  }

  const date = value instanceof Date ? value : new Date(raw);
  if (Number.isNaN(date.getTime())) {
    return raw;
  }

  return `${formatDateInputValue(date)} ${String(date.getHours()).padStart(2, "0")}:${String(date.getMinutes()).padStart(2, "0")}`;
}

export function formatDateTimeTyping(value) {
  const digits = String(value || "").replace(/\D/g, "").slice(0, 12);
  const day = digits.slice(0, 2);
  const month = digits.slice(2, 4);
  const year = digits.slice(4, 8);
  const hour = digits.slice(8, 10);
  const minute = digits.slice(10, 12);

  let result = [day, month, year].filter(Boolean).join("/");

  if (hour) {
    result += ` ${hour}`;
  }

  if (minute) {
    result += `:${minute}`;
  }

  return result;
}

export function parseDateTimeInputValue(value) {
  const raw = String(value || "").trim();
  const match = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})$/);

  if (!match) {
    return "";
  }

  const day = Number(match[1]);
  const month = Number(match[2]);
  const year = Number(match[3]);
  const hour = Number(match[4]);
  const minute = Number(match[5]);

  if (hour > 23 || minute > 59) {
    return "";
  }

  const date = new Date(year, month - 1, day, hour, minute);
  const isValid =
    date.getFullYear() === year &&
    date.getMonth() === month - 1 &&
    date.getDate() === day &&
    date.getHours() === hour &&
    date.getMinutes() === minute;

  if (!isValid) {
    return "";
  }

  return `${match[3]}-${match[2]}-${match[1]}T${match[4]}:${match[5]}`;
}
