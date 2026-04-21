const THUOC_KHONG_KE_DON_CHILDREN = [
  {
    slug: "khong-ke-don-ngua-thai",
    label: "Ngừa thai",
    icon: "bi bi-gender-female",
    tone: "tone-blue",
    description: "Thuốc không kê đơn hỗ trợ nhu cầu ngừa thai và chăm sóc sinh sản thường gặp.",
    keywords: ["ngua thai", "ngừa thai", "tranh thai", "tránh thai", "sinh san", "sinh sản"],
  },
  {
    slug: "khong-ke-don-khang-di-ung",
    label: "Kháng dị ứng",
    icon: "bi bi-hand-index-thumb",
    tone: "tone-green",
    description: "Thuốc không kê đơn dùng cho các triệu chứng dị ứng thông thường.",
    keywords: ["khang di ung", "kháng dị ứng", "di ung", "dị ứng"],
  },
  {
    slug: "khong-ke-don-khang-viem",
    label: "Kháng viêm",
    icon: "bi bi-shield-plus",
    tone: "tone-purple",
    description: "Nhóm thuốc không kê đơn hỗ trợ giảm viêm, giảm sưng ở mức độ thông thường.",
    keywords: ["khang viem", "kháng viêm", "viem", "viêm"],
  },
  {
    slug: "khong-ke-don-cam-lanh",
    label: "Cảm lạnh",
    icon: "bi bi-thermometer-snow",
    tone: "tone-yellow",
    description: "Các thuốc hỗ trợ giảm triệu chứng cảm lạnh, nghẹt mũi và mệt mỏi.",
    keywords: ["cam lanh", "cảm lạnh", "cam cum", "cảm cúm", "so mui", "sổ mũi"],
  },
  {
    slug: "khong-ke-don-giam-can",
    label: "Giảm cân",
    icon: "bi bi-fire",
    tone: "tone-orange",
    description: "Sản phẩm hỗ trợ kiểm soát cân nặng và chuyển hóa.",
    keywords: ["giam can", "giảm cân", "kiem soat can nang", "kiểm soát cân nặng"],
  },
  {
    slug: "khong-ke-don-mat-tai-mui",
    label: "Mắt/tai/mũi",
    icon: "bi bi-eye",
    tone: "tone-green",
    description: "Thuốc không kê đơn cho các vấn đề thường gặp về mắt, tai và mũi.",
    keywords: ["mat", "mắt", "tai", "mui", "mũi", "nho mat", "nhỏ mắt", "xit mui", "xịt mũi"],
  },
  {
    slug: "khong-ke-don-tieu-hoa",
    label: "Tiêu hóa",
    icon: "bi bi-cup-hot",
    tone: "tone-yellow",
    description: "Thuốc không kê đơn hỗ trợ dạ dày, tiêu hóa và nhu cầu đi ngoài thông thường.",
    keywords: ["tieu hoa", "tiêu hóa", "da day", "dạ dày", "tao bon", "táo bón"],
  },
  {
    slug: "khong-ke-don-giam-dau-ha-sot",
    label: "Giảm đau, hạ sốt",
    icon: "bi bi-activity",
    tone: "tone-red",
    description: "Thuốc không kê đơn hỗ trợ hạ sốt, giảm đau và giảm nhức mỏi thông thường.",
    keywords: ["giam dau", "giảm đau", "ha sot", "hạ sốt", "dau dau", "đau đầu", "sot", "sốt"],
  },
  {
    slug: "khong-ke-don-da-lieu",
    label: "Da liễu",
    icon: "bi bi-droplet-half",
    tone: "tone-pink",
    description: "Nhóm thuốc và sản phẩm dùng ngoài cho các vấn đề da liễu thường gặp.",
    keywords: ["da lieu", "da liễu", "sat khuan", "sát khuẩn", "nam da", "nấm da"],
  },
  {
    slug: "khong-ke-don-danh-cho-nam",
    label: "Dành cho nam",
    icon: "bi bi-gender-male",
    tone: "tone-blue",
    description: "Các thuốc và sản phẩm chăm sóc sức khỏe dành cho nam giới.",
    keywords: ["danh cho nam", "dành cho nam", "nam gioi", "nam giới"],
  },
  {
    slug: "khong-ke-don-danh-cho-nu",
    label: "Dành cho nữ",
    icon: "bi bi-gender-female",
    tone: "tone-purple",
    description: "Các thuốc và sản phẩm chăm sóc sức khỏe dành cho nữ giới.",
    keywords: ["danh cho nu", "dành cho nữ", "nu gioi", "nữ giới"],
  },
  {
    slug: "khong-ke-don-than-kinh",
    label: "Thần kinh",
    icon: "bi bi-lightning-charge",
    tone: "tone-yellow",
    description: "Thuốc không kê đơn hỗ trợ đau đầu, căng thẳng và thần kinh thông thường.",
    keywords: ["than kinh", "thần kinh", "chong mat", "chóng mặt", "say tau xe", "say tàu xe"],
  },
  {
    slug: "khong-ke-don-xuong-khop",
    label: "Xương khớp",
    icon: "bi bi-person-standing",
    tone: "tone-purple",
    description: "Nhóm thuốc không kê đơn cho cơ xương khớp và đau nhức ngoài da.",
    keywords: ["xuong khop", "xương khớp", "co xuong khop", "cơ xương khớp", "khop", "khớp"],
  },
  {
    slug: "khong-ke-don-dau-cao-xoa-bop",
    label: "Dầu, cao xoa bóp",
    icon: "bi bi-moisture",
    tone: "tone-orange",
    description: "Sản phẩm dầu và cao xoa bóp dùng ngoài hỗ trợ giảm đau, thư giãn.",
    keywords: ["dau xoa bop", "dầu xoa bóp", "cao xoa bop", "cao xoa bóp", "dau", "dầu"],
  },
];

const THUOC_KE_DON_CHILDREN = [
  {
    slug: "ke-don-khang-sinh",
    label: "Kháng sinh",
    icon: "bi bi-shield-check",
    tone: "tone-green",
    description: "Thuốc kê đơn nhóm kháng sinh và điều trị nhiễm khuẩn.",
    keywords: ["khang sinh", "kháng sinh", "nhiem khuan", "nhiễm khuẩn"],
  },
  {
    slug: "ke-don-ung-thu",
    label: "Ung thư",
    icon: "bi bi-shield-plus",
    tone: "tone-purple",
    description: "Thuốc kê đơn thuộc nhóm hỗ trợ điều trị ung thư.",
    keywords: ["ung thu", "ung thư"],
  },
  {
    slug: "ke-don-tiet-nieu",
    label: "Tiết niệu",
    icon: "bi bi-droplet",
    tone: "tone-blue",
    description: "Thuốc kê đơn thuộc nhóm tiết niệu và đường tiết niệu.",
    keywords: ["tiet nieu", "tiết niệu", "than tiet nieu", "thận tiết niệu"],
  },
  {
    slug: "ke-don-danh-cho-nam",
    label: "Dành cho nam",
    icon: "bi bi-gender-male",
    tone: "tone-blue",
    description: "Thuốc kê đơn cho sức khỏe nam giới.",
    keywords: ["danh cho nam", "dành cho nam", "nam gioi", "nam giới"],
  },
  {
    slug: "ke-don-danh-cho-nu",
    label: "Dành cho nữ",
    icon: "bi bi-gender-female",
    tone: "tone-purple",
    description: "Thuốc kê đơn cho sức khỏe nữ giới.",
    keywords: ["danh cho nu", "dành cho nữ", "nu gioi", "nữ giới"],
  },
  {
    slug: "ke-don-mat-tai-mui",
    label: "Mắt/tai/mũi",
    icon: "bi bi-eye",
    tone: "tone-green",
    description: "Thuốc kê đơn dùng cho mắt, tai và mũi.",
    keywords: ["mat", "mắt", "tai", "mui", "mũi"],
  },
  {
    slug: "ke-don-da-lieu",
    label: "Da liễu",
    icon: "bi bi-droplet-half",
    tone: "tone-pink",
    description: "Thuốc kê đơn trong nhóm da liễu và điều trị ngoài da.",
    keywords: ["da lieu", "da liễu"],
  },
  {
    slug: "ke-don-thuoc-ho-cam-lanh",
    label: "Thuốc ho, cảm lạnh",
    icon: "bi bi-lungs",
    tone: "tone-yellow",
    description: "Thuốc kê đơn điều trị ho, cảm lạnh và các triệu chứng hô hấp trên.",
    keywords: ["thuoc ho", "thuốc ho", "cam lanh", "cảm lạnh", "ho", "cảm"],
  },
  {
    slug: "ke-don-ngua-thai",
    label: "Ngừa thai",
    icon: "bi bi-gender-female",
    tone: "tone-blue",
    description: "Thuốc kê đơn nhóm ngừa thai và nội tiết sinh sản.",
    keywords: ["ngua thai", "ngừa thai", "tranh thai", "tránh thai"],
  },
  {
    slug: "ke-don-tim-mach-huyet-ap",
    label: "Tim mạch, huyết áp",
    icon: "bi bi-heart",
    tone: "tone-red",
    description: "Thuốc kê đơn cho tim mạch và huyết áp.",
    keywords: ["tim mach", "tim mạch", "huyet ap", "huyết áp"],
  },
  {
    slug: "ke-don-tieu-hoa",
    label: "Tiêu hóa",
    icon: "bi bi-cup-hot",
    tone: "tone-yellow",
    description: "Thuốc kê đơn thuộc nhóm dạ dày, tiêu hóa và đường ruột.",
    keywords: ["tieu hoa", "tiêu hóa", "da day", "dạ dày"],
  },
  {
    slug: "ke-don-tieu-duong",
    label: "Tiểu đường",
    icon: "bi bi-activity",
    tone: "tone-orange",
    description: "Thuốc kê đơn cho tiểu đường và chuyển hóa.",
    keywords: ["tieu duong", "tiểu đường", "duong huyet", "đường huyết"],
  },
  {
    slug: "ke-don-khang-di-ung",
    label: "Kháng dị ứng",
    icon: "bi bi-hand-index-thumb",
    tone: "tone-green",
    description: "Thuốc kê đơn điều trị dị ứng.",
    keywords: ["khang di ung", "kháng dị ứng", "di ung", "dị ứng"],
  },
  {
    slug: "ke-don-khang-viem",
    label: "Kháng viêm",
    icon: "bi bi-shield-plus",
    tone: "tone-purple",
    description: "Thuốc kê đơn nhóm kháng viêm.",
    keywords: ["khang viem", "kháng viêm", "viem", "viêm"],
  },
  {
    slug: "ke-don-than-kinh",
    label: "Thần kinh",
    icon: "bi bi-lightning-charge",
    tone: "tone-yellow",
    description: "Thuốc kê đơn cho các vấn đề thần kinh.",
    keywords: ["than kinh", "thần kinh"],
  },
  {
    slug: "ke-don-giam-dau-ha-sot",
    label: "Giảm đau hạ sốt",
    icon: "bi bi-activity",
    tone: "tone-red",
    description: "Thuốc kê đơn giảm đau, hạ sốt và hỗ trợ chống viêm.",
    keywords: ["giam dau", "giảm đau", "ha sot", "hạ sốt"],
  },
  {
    slug: "ke-don-he-ho-hap",
    label: "Hệ hô hấp",
    icon: "bi bi-lungs",
    tone: "tone-purple",
    description: "Thuốc kê đơn cho các bệnh đường hô hấp.",
    keywords: ["ho hap", "hô hấp", "phe quan", "phế quản"],
  },
  {
    slug: "ke-don-co-xuong-khop",
    label: "Cơ xương khớp",
    icon: "bi bi-person-standing",
    tone: "tone-blue",
    description: "Thuốc kê đơn cho cơ, xương và khớp.",
    keywords: ["co xuong khop", "cơ xương khớp", "xuong khop", "xương khớp"],
  },
];

const THUOC_PARENT_CARDS = [
  {
    slug: "thuoc-khong-ke-don",
    label: "Thuốc không kê đơn",
    icon: "bi bi-capsule-pill",
    tone: "tone-blue",
    description: "Nhóm thuốc có thể mua trực tiếp tại nhà thuốc mà không cần đơn.",
    keywords: ["thuoc khong ke don", "thuốc không kê đơn", "otc"],
    children: THUOC_KHONG_KE_DON_CHILDREN,
  },
  {
    slug: "thuoc-ke-don",
    label: "Thuốc kê đơn",
    icon: "bi bi-journal-medical",
    tone: "tone-green",
    description: "Nhóm thuốc cần theo chỉ định bác sĩ hoặc dược sĩ chuyên môn.",
    keywords: ["thuoc ke don", "thuốc kê đơn", "rx"],
    children: THUOC_KE_DON_CHILDREN,
  },
  {
    slug: "vitamin-thuc-pham-chuc-nang",
    label: "Vitamin và thực phẩm chức năng",
    icon: "bi bi-stars",
    tone: "tone-yellow",
    description: "Click để xem toàn bộ thuốc và sản phẩm bổ trợ thuộc nhóm vitamin và thực phẩm chức năng.",
    keywords: [
      "vitamin",
      "thuc pham chuc nang",
      "thực phẩm chức năng",
      "khoang chat",
      "khoáng chất",
    ],
  },
];

const TRA_CUU_BENH_CARDS = [
  {
    slug: "suc-khoe-sinh-san",
    label: "Sức khỏe sinh sản",
    icon: "bi bi-gender-female",
    tone: "tone-blue",
    description: "Nhóm thuốc thường dùng cho sức khỏe sinh sản và nhu cầu phụ khoa thông thường.",
    symptoms: ["Ngừa thai", "Khó chịu phụ khoa", "Rối loạn nội tiết nhẹ"],
    keywords: ["suc khoe sinh san", "sức khỏe sinh sản", "ngua thai", "ngừa thai", "phu khoa", "phụ khoa"],
    matchSlugs: ["suc-khoe-sinh-san"],
  },
  {
    slug: "tai-mui-hong",
    label: "Tai - Mũi - Họng",
    icon: "bi bi-ear",
    tone: "tone-green",
    description: "Các thuốc hỗ trợ đau họng, sổ mũi, nghẹt mũi và kích ứng vùng tai mũi họng.",
    symptoms: ["Đau họng", "Viêm họng", "Sổ mũi", "Xịt mũi"],
    keywords: ["tai mui hong", "tai - mui - hong", "tai mũi họng", "dau hong", "đau họng", "so mui", "sổ mũi"],
    matchSlugs: ["tai-mui-hong"],
  },
  {
    slug: "co-xuong-khop",
    label: "Cơ - Xương - Khớp",
    icon: "bi bi-person-standing",
    tone: "tone-purple",
    description: "Nhóm thuốc và sản phẩm hỗ trợ đau nhức cơ, xương và khớp.",
    symptoms: ["Đau lưng", "Mỏi cơ", "Đau khớp"],
    keywords: ["co xuong khop", "cơ xương khớp", "xuong khop", "xương khớp", "dau khop", "đau khớp"],
    matchSlugs: ["co-xuong-khop"],
  },
  {
    slug: "truyen-nhiem",
    label: "Truyền nhiễm",
    icon: "bi bi-virus",
    tone: "tone-yellow",
    description: "Các sản phẩm thường dùng trong nhóm bệnh truyền nhiễm và phòng ngừa lây nhiễm.",
    symptoms: ["Nhiễm khuẩn", "Sát khuẩn", "Truyền nhiễm"],
    keywords: ["truyen nhiem", "truyền nhiễm", "sat khuan", "sát khuẩn", "nhiem khuan", "nhiễm khuẩn"],
    matchSlugs: ["truyen-nhiem"],
  },
  {
    slug: "than-tiet-nieu",
    label: "Thận - Tiết niệu",
    icon: "bi bi-droplet",
    tone: "tone-blue",
    description: "Nhóm thuốc hỗ trợ các vấn đề tiết niệu, thận và đường tiểu.",
    symptoms: ["Tiểu buốt", "Tiểu rắt", "Khó chịu tiết niệu"],
    keywords: ["than tiet nieu", "thận tiết niệu", "tiet nieu", "tiết niệu"],
    matchSlugs: ["than-tiet-nieu"],
  },
  {
    slug: "mau",
    label: "Máu",
    icon: "bi bi-plus-circle",
    tone: "tone-blue",
    description: "Các thuốc và sản phẩm bổ sung cho máu, thiếu máu và tuần hoàn.",
    symptoms: ["Thiếu máu", "Bổ máu", "Tuần hoàn"],
    keywords: ["mau", "máu", "bo mau", "bổ máu", "thieu mau", "thiếu máu"],
    matchSlugs: ["mau"],
  },
  {
    slug: "mat",
    label: "Mắt",
    icon: "bi bi-eye",
    tone: "tone-green",
    description: "Nhóm thuốc và sản phẩm thường dùng cho khô mắt, kích ứng và chăm sóc mắt.",
    symptoms: ["Nhỏ mắt", "Khô mắt", "Dị ứng mắt"],
    keywords: ["mat", "mắt", "nho mat", "nhỏ mắt", "kho mat", "khô mắt"],
    matchSlugs: ["mat"],
  },
  {
    slug: "ho-hap",
    label: "Hô hấp",
    icon: "bi bi-lungs",
    tone: "tone-purple",
    description: "Các thuốc hỗ trợ ho, đờm, khó thở và các vấn đề hô hấp thường gặp.",
    symptoms: ["Ho", "Cảm", "Sổ mũi", "Khó thở"],
    keywords: ["ho hap", "hô hấp", "ho", "cam", "cảm", "dom", "đờm"],
    matchSlugs: ["ho-hap"],
  },
  {
    slug: "tam-than",
    label: "Tâm thần",
    icon: "bi bi-activity",
    tone: "tone-yellow",
    description: "Nhóm thuốc thường dùng cho thần kinh, căng thẳng và tâm thần.",
    symptoms: ["Mất ngủ", "Căng thẳng", "Lo âu"],
    keywords: ["tam than", "tâm thần", "than kinh", "thần kinh", "mat ngu", "mất ngủ"],
    matchSlugs: ["tam-than"],
  },
  {
    slug: "di-ung",
    label: "Dị ứng",
    icon: "bi bi-hand-index-thumb",
    tone: "tone-blue",
    description: "Nhóm thuốc và sản phẩm dùng cho dị ứng thời tiết, nổi mề đay, ngứa.",
    symptoms: ["Ngứa", "Dị ứng", "Mề đay"],
    keywords: ["di ung", "dị ứng", "ngua", "ngứa", "me day", "mề đay"],
    matchSlugs: ["di-ung"],
  },
  {
    slug: "tim-mach",
    label: "Tim mạch",
    icon: "bi bi-heart",
    tone: "tone-blue",
    description: "Các thuốc và sản phẩm hỗ trợ tuần hoàn và tim mạch.",
    symptoms: ["Tuần hoàn", "Tim mạch", "Huyết áp"],
    keywords: ["tim mach", "tim mạch", "huyet ap", "huyết áp", "tuan hoan", "tuần hoàn"],
    matchSlugs: ["tim-mach"],
  },
  {
    slug: "vitamin-khoang-chat",
    label: "Vitamin - khoáng chất",
    icon: "bi bi-capsule",
    tone: "tone-green",
    description: "Nhóm vitamin, khoáng chất và sản phẩm bổ sung cho sức khỏe hằng ngày.",
    symptoms: ["Bổ sung vitamin", "Khoáng chất", "Sức khỏe tổng quát"],
    keywords: ["vitamin", "khoang chat", "khoáng chất", "bo sung", "bổ sung"],
    matchSlugs: ["vitamin-khoang-chat"],
  },
];

const CHAM_SOC_SAC_DEP_CARDS = [
  {
    slug: "cham-soc-da",
    label: "Chăm sóc da",
    icon: "bi bi-droplet-half",
    tone: "tone-blue",
    description: "Nhóm sản phẩm chăm sóc da và làm dịu da mỗi ngày.",
    keywords: ["cham soc da", "chăm sóc da", "da mat", "da mặt"],
  },
  {
    slug: "cham-soc-toc",
    label: "Chăm sóc tóc",
    icon: "bi bi-scissors",
    tone: "tone-green",
    description: "Nhóm sản phẩm chăm sóc tóc và da đầu.",
    keywords: ["cham soc toc", "chăm sóc tóc", "toc", "tóc"],
  },
  {
    slug: "chong-nang",
    label: "Chống nắng",
    icon: "bi bi-sun",
    tone: "tone-purple",
    description: "Nhóm sản phẩm chống nắng và bảo vệ da.",
    keywords: ["chong nang", "chống nắng"],
  },
  {
    slug: "ve-sinh-ca-nhan",
    label: "Vệ sinh cá nhân",
    icon: "bi bi-shield-check",
    tone: "tone-yellow",
    description: "Nhóm sản phẩm vệ sinh cá nhân dùng hằng ngày.",
    keywords: ["ve sinh ca nhan", "vệ sinh cá nhân"],
  },
];

const KHAC_CARD = {
  slug: "tat-ca-thuoc-khac",
  label: "Xem tất cả",
  icon: "bi bi-grid-3x3-gap",
  tone: "tone-blue",
  description: "Hiển thị các thuốc chưa nằm trong bất kỳ danh mục nào đã khai báo.",
};

export const catalogSections = [
  {
    id: "thuoc",
    label: "Thuốc",
    description: "Nhóm thuốc chính gồm thuốc kê đơn, thuốc không kê đơn và vitamin thực phẩm chức năng.",
    cards: THUOC_PARENT_CARDS,
  },
  {
    id: "tra-cuu-benh",
    label: "Vấn đề sức khỏe",
    description: "Tra cứu nhanh theo nhóm triệu chứng và nhu cầu bệnh lý thường gặp.",
    cards: TRA_CUU_BENH_CARDS,
  },
  {
    id: "cham-soc-sac-dep",
    label: "Chăm sóc sắc đẹp",
    description: "Nhóm sản phẩm chăm sóc cá nhân, da, tóc và làm đẹp hằng ngày.",
    cards: CHAM_SOC_SAC_DEP_CARDS,
  },
  {
    id: "khac",
    label: "Khác",
    description: "Hiển thị các thuốc chưa được gán vào các danh mục chính.",
    cards: [KHAC_CARD],
  },
];

const SECTION_ALIAS = {
  "tu-thuoc": "tra-cuu-benh",
};

const FALLBACK_SECTION = catalogSections[0];
const ALL_SECTION_IDS = new Set(catalogSections.map((section) => section.id));

function normalizeSlug(value) {
  const raw = String(value || "").trim();
  return SECTION_ALIAS[raw] || raw;
}

export function normalizeCatalogText(value) {
  return String(value || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D")
    .toLowerCase();
}

function asTextArray(value) {
  if (Array.isArray(value)) {
    return value.filter(Boolean).map((item) => String(item));
  }

  if (!value) {
    return [];
  }

  return [String(value)];
}

function buildSearchText(product) {
  return normalizeCatalogText(
    [
      product?.ten_thuoc,
      product?.nhan,
      product?.loai_thuoc,
      product?.mo_ta,
      product?.lieu_luong,
      product?.danh_muc_thuoc_slug,
      product?.nha_san_xuat,
      ...asTextArray(product?.hashtags),
      ...asTextArray(product?.nhan_items),
      ...asTextArray(product?.lieu_luong_items),
    ]
      .filter(Boolean)
      .join(" | ")
  );
}

function collectChildren(cards) {
  return cards.flatMap((card) => [card, ...(card.children || [])]);
}

function flattenKnownCards() {
  return catalogSections
    .filter((section) => section.id !== "khac")
    .flatMap((section) => collectChildren(section.cards))
    .filter((card) => card.slug !== "tat-ca");
}

const KNOWN_CARDS = flattenKnownCards();

function getSectionCards(sectionId) {
  return getCatalogSection(sectionId).cards || [];
}

function findCard(sectionId, slug) {
  const normalized = normalizeSlug(slug);
  const cards = getSectionCards(sectionId);

  for (const card of cards) {
    if (card.slug === normalized) {
      return card;
    }

    const child = (card.children || []).find((item) => item.slug === normalized);
    if (child) {
      return child;
    }
  }

  return null;
}

function findParentCard(sectionId, slug) {
  const normalized = normalizeSlug(slug);
  const cards = getSectionCards(sectionId);
  return (
    cards.find((card) => card.slug === normalized || (card.children || []).some((child) => child.slug === normalized)) ||
    null
  );
}

function cardMatchesProduct(product, card) {
  if (!card) {
    return false;
  }

  if (card.children && card.children.length) {
    return card.children.some((child) => cardMatchesProduct(product, child));
  }

  const currentSlug = normalizeCatalogText(product?.danh_muc_thuoc_slug);
  const matchSlugs = (card.matchSlugs || [card.slug]).map((slug) => normalizeCatalogText(slug));
  const cardSlug = normalizeCatalogText(card.slug);

  if (cardSlug.startsWith("ke-don-") && !currentSlug.startsWith("ke-don-")) {
    return false;
  }

  if (cardSlug.startsWith("khong-ke-don-") && !currentSlug.startsWith("khong-ke-don-")) {
    return false;
  }

  if (currentSlug && matchSlugs.includes(currentSlug)) {
    return true;
  }

  if (currentSlug) {
    return false;
  }

  const haystack = buildSearchText(product);
  return (card.keywords || []).some((keyword) => haystack.includes(normalizeCatalogText(keyword)));
}

function productBelongsToOtherBucket(product) {
  return !KNOWN_CARDS.some((card) => cardMatchesProduct(product, card));
}

function getSyntheticAllCard(sectionId, parentCard = null) {
  if (sectionId === "thuoc" && parentCard) {
    return {
      slug: parentCard.slug,
      label: parentCard.label,
      icon: parentCard.icon,
      tone: parentCard.tone,
      description: parentCard.description,
      keywords: parentCard.keywords || [],
    };
  }

  const section = getCatalogSection(sectionId);
  return {
    slug: "tat-ca",
    label: section.label,
    icon: section.cards[0]?.icon || "bi bi-grid",
    tone: section.cards[0]?.tone || "tone-blue",
    description: section.description,
  };
}

export function getCatalogSection(sectionId) {
  const normalized = normalizeSlug(sectionId);
  return catalogSections.find((section) => section.id === normalized) || FALLBACK_SECTION;
}

export function getCatalogCard(sectionId, slug = "tat-ca") {
  const normalizedSection = normalizeSlug(sectionId);
  const normalizedSlug = normalizeSlug(slug || "tat-ca");
  const section = getCatalogSection(normalizedSection);

  if (normalizedSlug === "tat-ca") {
    return getSyntheticAllCard(section.id);
  }

  const directCard = findCard(section.id, normalizedSlug);
  if (directCard) {
    return directCard;
  }

  return getSyntheticAllCard(section.id);
}

export function getCatalogCardsForView(sectionId, slug = "tat-ca") {
  const section = getCatalogSection(sectionId);
  const normalizedSlug = normalizeSlug(slug || "tat-ca");

  if (section.id === "thuoc" && normalizedSlug !== "tat-ca") {
    const parentCard = findParentCard(section.id, normalizedSlug);

    if (parentCard?.children?.length) {
      return [getSyntheticAllCard(section.id, parentCard), ...parentCard.children];
    }
  }

  return section.cards;
}

export function countProductsForCard(products, sectionId, slug = "tat-ca") {
  return filterProductsForSection(products, sectionId, slug).length;
}

export function filterProductsForSection(products = [], sectionId, slug = "tat-ca") {
  const section = getCatalogSection(sectionId);
  const normalizedSlug = normalizeSlug(slug || "tat-ca");

  if (!Array.isArray(products)) {
    return [];
  }

  if (section.id === "khac") {
    return products.filter((product) => productBelongsToOtherBucket(product));
  }

  if (section.id === "thuoc") {
    const parentCards = section.cards;

    if (normalizedSlug === "tat-ca") {
      return products.filter((product) => parentCards.some((card) => cardMatchesProduct(product, card)));
    }

    const activeCard = findCard(section.id, normalizedSlug) || findParentCard(section.id, normalizedSlug);
    return products.filter((product) => cardMatchesProduct(product, activeCard));
  }

  if (normalizedSlug === "tat-ca") {
    return products.filter((product) => section.cards.some((card) => cardMatchesProduct(product, card)));
  }

  const activeCard = findCard(section.id, normalizedSlug);
  return products.filter((product) => cardMatchesProduct(product, activeCard));
}

export function isCatalogSection(sectionId) {
  return ALL_SECTION_IDS.has(normalizeSlug(sectionId));
}
