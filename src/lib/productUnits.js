function normalizeUnitValue(value) {
  return String(value || "").trim();
}

function normalizeUnitKey(value) {
  return normalizeUnitValue(value).toLowerCase();
}

function buildFallbackOption(product) {
  const unitName = normalizeUnitValue(product?.don_vi_tinh || product?.donVi || "");

  if (!unitName) {
    return null;
  }

  return {
    ten_don_vi: unitName,
    gia_ban: Number(product?.gia_ban || product?.gia || 0),
    gia_niem_yet: Number(product?.gia_niem_yet || product?.giaGoc || product?.gia_ban || product?.gia || 0),
    mac_dinh: true,
    lookup_key: normalizeUnitKey(unitName),
    so_luong_ton: Number(product?.so_luong_ton || 0),
    so_luong_quy_doi: Math.max(1, Number(product?.he_so_quy_doi || 1)),
    don_vi_co_so: normalizeUnitValue(product?.don_vi_co_so || unitName),
  };
}

export function getProductUnitOptions(product) {
  const rawOptions = Array.isArray(product?.don_vi_options) ? product.don_vi_options : [];
  const fallbackOption = buildFallbackOption(product);
  const normalizedOptions = rawOptions
    .map((item, index) => {
      const unitName = normalizeUnitValue(item?.ten_don_vi || item?.donVi || item?.don_vi_tinh || "");

      if (!unitName) {
        return null;
      }

      return {
        ten_don_vi: unitName,
        gia_ban: Number(item?.gia_ban || item?.gia || 0),
        gia_niem_yet: Number(item?.gia_niem_yet || item?.giaGoc || item?.gia_ban || item?.gia || 0),
        mac_dinh: Boolean(item?.mac_dinh || index === 0),
        lookup_key: normalizeUnitKey(unitName),
        so_luong_ton: Number(item?.so_luong_ton ?? product?.so_luong_ton ?? 0),
        so_luong_quy_doi: Math.max(1, Number(item?.so_luong_quy_doi || 1)),
        don_vi_co_so: normalizeUnitValue(item?.don_vi_co_so || product?.don_vi_co_so || product?.don_vi_tinh || ""),
      };
    })
    .filter(Boolean);

  if (fallbackOption) {
    normalizedOptions.unshift(fallbackOption);
  }

  const seen = new Set();

  return normalizedOptions.filter((item) => {
    if (seen.has(item.lookup_key)) {
      return false;
    }

    seen.add(item.lookup_key);
    return true;
  });
}

export function getDefaultProductUnit(product) {
  const options = getProductUnitOptions(product);
  return options.find((item) => item.mac_dinh)?.ten_don_vi || options[0]?.ten_don_vi || "";
}

export function getProductUnitOption(product, requestedUnit = "") {
  const options = getProductUnitOptions(product);

  if (!options.length) {
    return null;
  }

  const requestedKey = normalizeUnitKey(requestedUnit);

  if (!requestedKey) {
    return options.find((item) => item.mac_dinh) || options[0];
  }

  return options.find((item) => item.lookup_key === requestedKey) || options.find((item) => item.mac_dinh) || options[0];
}

export function getProductUnitStock(product, requestedUnit = "") {
  const option = getProductUnitOption(product, requestedUnit);
  return Number(option?.so_luong_ton ?? product?.so_luong_ton ?? 0);
}

export function getProductUnitLabel(product, requestedUnit = "") {
  const option = getProductUnitOption(product, requestedUnit);
  return option?.ten_don_vi || normalizeUnitValue(product?.don_vi_tinh || product?.donVi || "");
}

export function applyProductUnitSelection(product, requestedUnit = "") {
  const option = getProductUnitOption(product, requestedUnit);

  if (!option) {
    return { ...product };
  }

  return {
    ...product,
    selected_don_vi: option.ten_don_vi,
    selected_unit_key: option.lookup_key,
    don_vi_tinh: option.ten_don_vi,
    donVi: option.ten_don_vi,
    gia_ban: option.gia_ban,
    gia_niem_yet: option.gia_niem_yet,
    gia: option.gia_ban,
    giaGoc: option.gia_niem_yet,
    so_luong_ton: Number(option?.so_luong_ton ?? product?.so_luong_ton ?? 0),
  };
}

export function buildProductUnitCartKey(maThuoc, unitName = "") {
  const baseKey = normalizeUnitValue(maThuoc);
  const unitKey = normalizeUnitKey(unitName);
  return unitKey ? `${baseKey}::${unitKey}` : baseKey;
}
