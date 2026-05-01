export function parseFormattedInteger(value) {
  const digits = String(value ?? "").replace(/\D/g, "");
  return digits ? Number(digits) : null;
}

export function formatIntegerInput(value) {
  if (value === null || value === undefined || value === "") {
    return "";
  }

  const digits = String(value).replace(/\D/g, "");
  if (!digits) {
    return "";
  }

  return new Intl.NumberFormat("en-US").format(Number(digits));
}
