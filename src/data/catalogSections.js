export const catalogSections = [
  {
    id: "thuoc",
    label: "Thuốc",
    description: "Danh mục thuốc và thực phẩm chức năng đang có trên hệ thống.",
    cards: [
      {
        slug: "khong-ke-don",
        label: "Thuốc không kê đơn",
        description: "Các sản phẩm hỗ trợ triệu chứng thông thường, có thể mua nhanh tại nhà thuốc.",
        symptoms: ["Cảm sốt nhẹ", "Đau đầu", "Đau họng", "Đầy bụng"],
        icon: "bi bi-capsule-pill",
        tone: "tone-blue",
        keywords: ["không kê đơn", "khong ke don", "otc", "cảm", "cam", "sốt", "sot", "đau đầu", "dau dau"],
      },
      {
        slug: "ke-don",
        label: "Thuốc kê đơn",
        description: "Nhóm thuốc cần chỉ định điều trị và theo dõi đúng liệu trình.",
        symptoms: ["Nhiễm khuẩn", "Viêm", "Điều trị chuyên khoa"],
        icon: "bi bi-journal-medical",
        tone: "tone-green",
        keywords: ["kê đơn", "ke don", "kháng sinh", "khang sinh", "điều trị", "dieu tri"],
      },
      {
        slug: "thuoc-khac",
        label: "Thuốc khác",
        description: "Các dòng thuốc hỗ trợ điều trị và chăm sóc sức khỏe không nằm trong nhóm chính.",
        symptoms: ["Điều trị hỗ trợ", "Chăm sóc toàn diện"],
        icon: "bi bi-capsule",
        tone: "tone-purple",
        keywords: ["thuốc", "thuoc", "điều trị", "dieu tri"],
      },
      {
        slug: "vitamin-thuc-pham-chuc-nang",
        label: "Vitamin và thực phẩm chức năng",
        description: "Nhóm bổ sung dinh dưỡng, tăng đề kháng và hỗ trợ thể trạng.",
        symptoms: ["Mệt mỏi", "Thiếu vi chất", "Cần tăng đề kháng"],
        icon: "bi bi-stars",
        tone: "tone-yellow",
        keywords: ["vitamin", "thực phẩm chức năng", "thuc pham chuc nang", "bổ sung", "bo sung"],
      },
      {
        slug: "tat-ca",
        label: "Xem tất cả",
        description: "Xem toàn bộ sản phẩm thuộc nhóm thuốc đang có trên hệ thống.",
        symptoms: [],
        icon: "bi bi-arrow-right",
        tone: "tone-soft",
        keywords: [],
      },
    ],
  },
  {
    id: "tra-cuu-benh",
    label: "Tra cứu bệnh",
    description: "Gợi ý sản phẩm theo nhóm triệu chứng và nhu cầu chăm sóc sức khỏe.",
    cards: [
      {
        slug: "tai-mui-hong",
        label: "Tai - Mũi - Họng",
        description: "Các sản phẩm hỗ trợ đau họng, nghẹt mũi, viêm mũi và khó chịu vùng họng.",
        symptoms: ["Ho", "Đau họng", "Sổ mũi", "Nghẹt mũi"],
        icon: "bi bi-earbuds",
        tone: "tone-blue",
        keywords: ["tai", "mũi", "mui", "họng", "hong", "ho", "đau họng", "dau hong"],
      },
      {
        slug: "ho-hap",
        label: "Hô hấp",
        description: "Nhóm sản phẩm hỗ trợ hô hấp, giảm khó thở, hỗ trợ viêm đường thở.",
        symptoms: ["Khó thở", "Ho kéo dài", "Viêm đường hô hấp"],
        icon: "bi bi-lungs",
        tone: "tone-green",
        keywords: ["hô hấp", "ho hap", "khó thở", "kho tho", "viêm họng", "viem hong"],
      },
      {
        slug: "co-xuong-khop",
        label: "Cơ - Xương - Khớp",
        description: "Nhóm sản phẩm cho đau mỏi cơ, nhức khớp, vận động khó chịu.",
        symptoms: ["Đau nhức", "Mỏi cơ", "Cứng khớp"],
        icon: "bi bi-person-standing",
        tone: "tone-purple",
        keywords: ["cơ", "co", "xương", "xuong", "khớp", "khop", "đau nhức", "dau nhuc"],
      },
      {
        slug: "di-ung",
        label: "Dị ứng",
        description: "Các dòng hỗ trợ ngứa, nổi mẩn, kích ứng da và cơ địa dị ứng.",
        symptoms: ["Ngứa", "Mẩn đỏ", "Kích ứng", "Hắt hơi"],
        icon: "bi bi-shield-plus",
        tone: "tone-yellow",
        keywords: ["dị ứng", "di ung", "ngứa", "ngua", "mẩn đỏ", "man do"],
      },
      {
        slug: "tat-ca",
        label: "Xem tất cả",
        description: "Xem toàn bộ thuốc và sản phẩm phù hợp cho nhóm tra cứu bệnh.",
        symptoms: [],
        icon: "bi bi-arrow-right",
        tone: "tone-soft",
        keywords: [],
      },
    ],
  },
  {
    id: "me-va-be",
    label: "Mẹ và Bé",
    description: "Sản phẩm chăm sóc mẹ, bé và các dòng dinh dưỡng phù hợp cho gia đình trẻ.",
    cards: [
      {
        slug: "sua-dinh-duong",
        label: "Sữa và dinh dưỡng",
        description: "Nhóm sữa, bột dinh dưỡng và sản phẩm hỗ trợ phát triển thể chất.",
        symptoms: ["Cần bổ sung dinh dưỡng", "Biếng ăn", "Cần tăng đề kháng"],
        icon: "bi bi-cup-straw",
        tone: "tone-blue",
        keywords: ["sữa", "sua", "dinh dưỡng", "dinh duong", "trẻ em", "tre em"],
      },
      {
        slug: "cham-soc-be",
        label: "Chăm sóc bé",
        description: "Các sản phẩm vệ sinh, chăm sóc thường ngày và hỗ trợ sinh hoạt cho bé.",
        symptoms: ["Chăm sóc hằng ngày", "Da nhạy cảm", "Cần vệ sinh dịu nhẹ"],
        icon: "bi bi-balloon-heart",
        tone: "tone-green",
        keywords: ["bé", "be", "trẻ", "tre", "em bé", "em be"],
      },
      {
        slug: "cham-soc-me",
        label: "Chăm sóc mẹ",
        description: "Nhóm sản phẩm hỗ trợ mẹ bầu, sau sinh và giai đoạn phục hồi.",
        symptoms: ["Mệt mỏi sau sinh", "Cần bổ sung dưỡng chất", "Chăm sóc mẹ bầu"],
        icon: "bi bi-heart-pulse",
        tone: "tone-purple",
        keywords: ["mẹ", "me", "mang thai", "sau sinh"],
      },
      {
        slug: "vitamin-cho-be",
        label: "Vitamin cho bé",
        description: "Các vitamin và vi chất hỗ trợ phát triển, ăn ngon và tăng đề kháng cho bé.",
        symptoms: ["Thiếu vi chất", "Cần bổ sung vitamin", "Sức đề kháng yếu"],
        icon: "bi bi-stars",
        tone: "tone-yellow",
        keywords: ["vitamin", "bé", "be", "trẻ em", "tre em"],
      },
      {
        slug: "tat-ca",
        label: "Xem tất cả",
        description: "Xem toàn bộ sản phẩm dành cho mẹ và bé.",
        symptoms: [],
        icon: "bi bi-arrow-right",
        tone: "tone-soft",
        keywords: [],
      },
    ],
  },
  {
    id: "cham-soc-sac-dep",
    label: "Chăm sóc sắc đẹp",
    description: "Các sản phẩm làm đẹp, vệ sinh cá nhân và chăm sóc da tóc hằng ngày.",
    cards: [
      {
        slug: "cham-soc-da",
        label: "Chăm sóc da",
        description: "Nhóm serum, kem dưỡng và sản phẩm phục hồi nền da khỏe hơn mỗi ngày.",
        symptoms: ["Da khô", "Da thiếu ẩm", "Da nhạy cảm"],
        icon: "bi bi-droplet-half",
        tone: "tone-blue",
        keywords: ["da", "serum", "kem", "dưỡng", "duong"],
      },
      {
        slug: "cham-soc-toc",
        label: "Chăm sóc tóc",
        description: "Các sản phẩm gội xả, dưỡng tóc và chăm sóc da đầu.",
        symptoms: ["Tóc khô xơ", "Rụng tóc", "Da đầu nhạy cảm"],
        icon: "bi bi-scissors",
        tone: "tone-green",
        keywords: ["tóc", "toc", "gội", "goi", "dầu xả", "dau xa"],
      },
      {
        slug: "chong-nang",
        label: "Chống nắng",
        description: "Sản phẩm chống nắng, bảo vệ da khi hoạt động ngoài trời.",
        symptoms: ["Da dễ bắt nắng", "Cần chống tia UV"],
        icon: "bi bi-sun",
        tone: "tone-purple",
        keywords: ["chống nắng", "chong nang", "uv", "spf"],
      },
      {
        slug: "ve-sinh-ca-nhan",
        label: "Vệ sinh cá nhân",
        description: "Nhóm làm sạch, chăm sóc cơ thể và vệ sinh dịu nhẹ mỗi ngày.",
        symptoms: ["Cần làm sạch dịu nhẹ", "Chăm sóc cá nhân hằng ngày"],
        icon: "bi bi-shield-check",
        tone: "tone-yellow",
        keywords: ["rửa", "rua", "sạch", "sach", "vệ sinh", "ve sinh"],
      },
      {
        slug: "tat-ca",
        label: "Xem tất cả",
        description: "Xem toàn bộ sản phẩm chăm sóc sắc đẹp.",
        symptoms: [],
        icon: "bi bi-arrow-right",
        tone: "tone-soft",
        keywords: [],
      },
    ],
  },
  {
    id: "tu-thuoc",
    label: "Danh mục tủ thuốc",
    description: "Chọn nhanh theo từng nhóm triệu chứng thường gặp để xem thuốc và sản phẩm phù hợp.",
    cards: [
      {
        slug: "suc-khoe-sinh-san",
        label: "Sức khỏe sinh sản",
        description: "Gợi ý sản phẩm cho nhu cầu phụ khoa, chăm sóc sinh sản và cân bằng cơ thể.",
        symptoms: ["Khó chịu phụ khoa", "Rối loạn kinh nguyệt", "Cần chăm sóc sinh sản"],
        icon: "bi bi-gender-ambiguous",
        tone: "tone-blue",
        keywords: ["sinh sản", "sinh san", "phụ khoa", "phu khoa", "kinh nguyệt", "kinh nguyet"],
      },
      {
        slug: "tai-mui-hong",
        label: "Tai - Mũi - Họng",
        description: "Danh mục thuốc và sản phẩm cho các triệu chứng vùng tai, mũi và họng.",
        symptoms: ["Đau họng", "Nghẹt mũi", "Sổ mũi", "Ho nhẹ"],
        icon: "bi bi-earbuds",
        tone: "tone-green",
        keywords: ["tai", "mũi", "mui", "họng", "hong", "đau họng", "dau hong", "sổ mũi", "so mui"],
      },
      {
        slug: "co-xuong-khop",
        label: "Cơ - Xương - Khớp",
        description: "Gợi ý sản phẩm hỗ trợ xương khớp, đau cơ và khó chịu khi vận động.",
        symptoms: ["Đau cơ", "Đau khớp", "Mỏi vai gáy", "Khó vận động"],
        icon: "bi bi-person-standing",
        tone: "tone-purple",
        keywords: ["cơ", "co", "xương", "xuong", "khớp", "khop", "đau nhức", "dau nhuc"],
      },
      {
        slug: "truyen-nhiem",
        label: "Truyền nhiễm",
        description: "Nhóm sản phẩm hỗ trợ triệu chứng sốt, viêm và các tình trạng nhiễm khuẩn thường gặp.",
        symptoms: ["Sốt", "Mệt mỏi", "Viêm nhiễm", "Đau nhức cơ thể"],
        icon: "bi bi-virus",
        tone: "tone-yellow",
        keywords: ["truyền nhiễm", "truyen nhiem", "sốt", "sot", "viêm", "viem", "nhiễm khuẩn", "nhiem khuan"],
      },
      {
        slug: "than-tiet-nieu",
        label: "Thận - Tiết niệu",
        description: "Danh mục cho các nhu cầu hỗ trợ tiết niệu và chăm sóc hệ bài tiết.",
        symptoms: ["Tiểu buốt", "Khó chịu tiết niệu", "Tiểu nhiều lần"],
        icon: "bi bi-droplet",
        tone: "tone-soft",
        keywords: ["thận", "than", "tiết niệu", "tiet nieu", "tiểu", "tieu"],
      },
      {
        slug: "mau",
        label: "Máu",
        description: "Các sản phẩm bổ sung, hỗ trợ thể trạng và nhóm có liên quan tới máu huyết.",
        symptoms: ["Thiếu máu", "Chóng mặt", "Mệt mỏi kéo dài"],
        icon: "bi bi-plus-circle",
        tone: "tone-blue",
        keywords: ["máu", "mau", "thiếu máu", "thieu mau", "sắt", "sat"],
      },
      {
        slug: "mat",
        label: "Mắt",
        description: "Các dòng hỗ trợ khô mắt, mỏi mắt và chăm sóc thị lực mỗi ngày.",
        symptoms: ["Khô mắt", "Mỏi mắt", "Nhức mắt", "Cần chăm sóc thị lực"],
        icon: "bi bi-eye",
        tone: "tone-green",
        keywords: ["mắt", "mat", "khô mắt", "kho mat", "mỏi mắt", "moi mat"],
      },
      {
        slug: "ho-hap",
        label: "Hô hấp",
        description: "Danh mục thuốc và sản phẩm cho ho, đờm, khó thở và viêm đường hô hấp.",
        symptoms: ["Ho", "Có đờm", "Khó thở", "Rát họng"],
        icon: "bi bi-lungs",
        tone: "tone-purple",
        keywords: ["hô hấp", "ho hap", "ho", "đờm", "dom", "khó thở", "kho tho"],
      },
      {
        slug: "tam-than",
        label: "Tâm thần",
        description: "Nhóm sản phẩm hỗ trợ thư giãn, cải thiện giấc ngủ và cân bằng tinh thần.",
        symptoms: ["Mất ngủ", "Căng thẳng", "Lo âu", "Khó thư giãn"],
        icon: "bi bi-activity",
        tone: "tone-yellow",
        keywords: ["tâm thần", "tam than", "mất ngủ", "mat ngu", "căng thẳng", "cang thang", "lo âu", "lo au"],
      },
      {
        slug: "di-ung",
        label: "Dị ứng",
        description: "Danh mục gợi ý khi gặp ngứa, mẩn đỏ, kích ứng và phản ứng cơ địa.",
        symptoms: ["Ngứa", "Nổi mẩn", "Hắt hơi", "Kích ứng da"],
        icon: "bi bi-hand-index-thumb",
        tone: "tone-soft",
        keywords: ["dị ứng", "di ung", "ngứa", "ngua", "mẩn đỏ", "man do", "kích ứng", "kich ung"],
      },
      {
        slug: "tim-mach",
        label: "Tim mạch",
        description: "Nhóm sản phẩm hỗ trợ tuần hoàn và chăm sóc sức khỏe tim mạch hằng ngày.",
        symptoms: ["Mệt tim", "Tuần hoàn kém", "Huyết áp không ổn định"],
        icon: "bi bi-heart",
        tone: "tone-blue",
        keywords: ["tim mạch", "tim mach", "huyết áp", "huyet ap", "tuần hoàn", "tuan hoan"],
      },
      {
        slug: "vitamin-khoang-chat",
        label: "Vitamin - khoáng chất",
        description: "Danh mục bổ sung vitamin và khoáng chất cho cơ thể trong các giai đoạn khác nhau.",
        symptoms: ["Thiếu vi chất", "Mệt mỏi", "Cần bổ sung vitamin"],
        icon: "bi bi-prescription2",
        tone: "tone-green",
        keywords: ["vitamin", "khoáng chất", "khoang chat", "bổ sung", "bo sung"],
      },
      {
        slug: "tat-ca",
        label: "Xem tất cả",
        description: "Xem toàn bộ sản phẩm theo nhóm triệu chứng trong tủ thuốc.",
        symptoms: [],
        icon: "bi bi-arrow-right",
        tone: "tone-soft",
        keywords: [],
      },
    ],
  },
];

export function normalizeCatalogText(value = "") {
  return value
    .toString()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

export function getCatalogSection(sectionId) {
  return catalogSections.find((section) => section.id === sectionId) || catalogSections[0];
}

export function getCatalogCard(sectionId, cardSlug = "tat-ca") {
  const section = getCatalogSection(sectionId);
  return section.cards.find((card) => card.slug === cardSlug) || section.cards.find((card) => card.slug === "tat-ca") || section.cards[0];
}

function buildProductText(product) {
  return normalizeCatalogText(
    [
      product.ten_thuoc,
      product.loai_thuoc,
      product.nha_san_xuat,
      product.mo_ta,
      ...(product.trieu_chung || []),
      ...(product.tac_dung_phu || []),
    ].join(" ")
  );
}

function matchesKeywords(product, keywords = []) {
  if (!keywords.length) {
    return true;
  }

  const haystack = buildProductText(product);
  return keywords.some((keyword) => haystack.includes(normalizeCatalogText(keyword)));
}

export function filterProductsForSection(products, sectionId, cardSlug = "") {
  const section = getCatalogSection(sectionId);

  if (!cardSlug || cardSlug === "tat-ca") {
    const cardsWithRules = section.cards.filter((item) => item.slug !== "tat-ca");
    if (!cardsWithRules.length) {
      return products;
    }

    return products.filter((product) => cardsWithRules.some((card) => matchesKeywords(product, card.keywords)));
  }

  const activeCard = getCatalogCard(sectionId, cardSlug);
  return products.filter((product) => matchesKeywords(product, activeCard.keywords));
}

export function countProductsForCard(products, sectionId, cardSlug) {
  return filterProductsForSection(products, sectionId, cardSlug).length;
}
