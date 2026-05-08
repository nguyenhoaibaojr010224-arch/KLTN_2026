<?php

namespace App\Services;

use App\Models\KhuyenMai;
use App\Models\Thuoc;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SupportAiService
{
    public function replyToCustomerQuestion(string $question): array
    {
        $normalizedQuestion = trim(preg_replace('/\s+/u', ' ', $question) ?? '');

        if ($this->isClearlyOutOfScopeQuestion($normalizedQuestion)) {
            return [
                'reply' => $this->outOfScopeReply(),
                'needs_pharmacist' => true,
                'intent' => 'tu_van_duoc_si',
                'purchase_action' => null,
                'source' => 'out_of_scope',
                'matched_products' => [],
            ];
        }

        if ($this->shouldAskProductNameBeforeSearch($normalizedQuestion)) {
            return [
                'reply' => 'Khách hàng muốn tìm kiếm sản phẩm nào ạ?',
                'needs_pharmacist' => false,
                'intent' => 'tra_cuu_thuoc',
                'purchase_action' => null,
                'source' => 'structured',
                'matched_products' => [],
            ];
        }

        $matchedProducts = $this->findRelevantProducts($normalizedQuestion);
        $needsPharmacist = $this->shouldEscalateToPharmacist($normalizedQuestion, $matchedProducts);
        $intent = $this->resolveIntent($normalizedQuestion, $needsPharmacist);

        if ($intent === 'khuyen_mai') {
            $matchedProducts = $matchedProducts
                ->filter(fn (array $product): bool => $this->hasRealPromotionDiscount($product))
                ->values();

            if ($matchedProducts->isEmpty()) {
                $matchedProducts = $this->findPromotionProducts();
            }
        }

        $purchaseAction = $this->buildPurchaseAction(
            $normalizedQuestion,
            $matchedProducts,
            $needsPharmacist,
            $intent
        );

        if ($intent === 'dat_hang' && $matchedProducts->isEmpty()) {
            return [
                'reply' => 'Khách hàng muốn tìm kiếm sản phẩm nào ạ?',
                'needs_pharmacist' => false,
                'intent' => 'tra_cuu_thuoc',
                'purchase_action' => null,
                'source' => 'structured',
                'matched_products' => [],
            ];
        }

        if ($this->shouldUseOutOfScopeReply($normalizedQuestion, $intent, $matchedProducts)) {
            return [
                'reply' => $this->outOfScopeReply(),
                'needs_pharmacist' => true,
                'intent' => 'tu_van_duoc_si',
                'purchase_action' => null,
                'source' => 'out_of_scope',
                'matched_products' => [],
            ];
        }

        $fallbackReply = $this->buildFallbackReply($normalizedQuestion, $matchedProducts, $needsPharmacist, $intent);

        if ($intent === 'dat_hang') {
            return [
                'reply' => $this->buildStructuredPurchaseReply($purchaseAction, $matchedProducts),
                'needs_pharmacist' => $needsPharmacist,
                'intent' => $intent,
                'purchase_action' => $purchaseAction,
                'source' => 'structured',
                'matched_products' => $matchedProducts->values()->all(),
            ];
        }

        if (in_array($intent, ['tra_cuu_thuoc', 'khuyen_mai'], true) && $matchedProducts->isNotEmpty()) {
            return [
                'reply' => $this->buildStructuredCatalogReply($intent, $matchedProducts),
                'needs_pharmacist' => $needsPharmacist,
                'intent' => $intent,
                'purchase_action' => null,
                'source' => 'structured',
                'matched_products' => $matchedProducts->values()->all(),
            ];
        }

        if (! $this->isGroqConfigured()) {
            return [
                'reply' => $fallbackReply,
                'needs_pharmacist' => $needsPharmacist,
                'intent' => $intent,
                'purchase_action' => $purchaseAction,
                'source' => 'fallback',
                'matched_products' => $matchedProducts->values()->all(),
            ];
        }

        try {
            $aiReply = $this->generateGroqReply($normalizedQuestion, $matchedProducts, $needsPharmacist);

            return [
                'reply' => $aiReply !== '' ? $aiReply : $fallbackReply,
                'needs_pharmacist' => $needsPharmacist,
                'intent' => $intent,
                'purchase_action' => $purchaseAction,
                'source' => $aiReply !== '' ? 'groq' : 'fallback',
                'matched_products' => $matchedProducts->values()->all(),
            ];
        } catch (\Throwable $exception) {
            Log::warning('Groq support fallback activated.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'reply' => $fallbackReply,
                'needs_pharmacist' => $needsPharmacist,
                'intent' => $intent,
                'purchase_action' => $purchaseAction,
                'source' => 'fallback',
                'matched_products' => $matchedProducts->values()->all(),
            ];
        }
    }

    private function isGroqConfigured(): bool
    {
        return filled(config('services.groq.api_key'));
    }

    private function findRelevantProducts(string $question): Collection
    {
        $normalizedQuestion = $this->normalizeText($question);
        $tokens = $this->tokenize($normalizedQuestion);

        if ($normalizedQuestion === '') {
            return collect();
        }

        KhuyenMai::deleteExpired();

        return Thuoc::query()
            ->with('nhaSanXuat')
            ->with(['khuyenMais' => fn ($query) => $query->dangHoatDong()->latest()])
            ->withSum('loThuocs as so_luong_ton_co_so', 'so_luong_con')
            ->orderBy('ten_thuoc')
            ->get()
            ->filter(fn (Thuoc $thuoc): bool => ! str_contains($this->normalizeText((string) $thuoc->trang_thai), 'ngung ban'))
            ->map(function (Thuoc $thuoc) use ($normalizedQuestion, $tokens): array {
                $product = $this->transformProduct($thuoc);

                return [
                    'product' => $product,
                    'score' => $this->scoreProduct($product, $normalizedQuestion, $tokens),
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] >= 120)
            ->sortByDesc('score')
            ->take(5)
            ->pluck('product')
            ->values();
    }

    private function findPromotionProducts(): Collection
    {
        KhuyenMai::deleteExpired();

        return Thuoc::query()
            ->with('nhaSanXuat')
            ->with(['khuyenMais' => fn ($query) => $query->dangHoatDong()->latest()])
            ->withSum('loThuocs as so_luong_ton_co_so', 'so_luong_con')
            ->orderBy('ten_thuoc')
            ->get()
            ->filter(fn (Thuoc $thuoc): bool => ! str_contains($this->normalizeText((string) $thuoc->trang_thai), 'ngung ban'))
            ->map(fn (Thuoc $thuoc): array => $this->transformProduct($thuoc))
            ->filter(fn (array $product): bool => $this->hasRealPromotionDiscount($product))
            ->sortByDesc(fn (array $product): int => $this->promotionDiscountAmount($product))
            ->take(5)
            ->values();
    }

    private function hasRealPromotionDiscount(array $product): bool
    {
        return (bool) ($product['co_khuyen_mai'] ?? false)
            && $this->promotionDiscountAmount($product) > 0;
    }

    private function promotionDiscountAmount(array $product): int
    {
        $listPrice = (int) ($product['gia_niem_yet'] ?? 0);
        $salePrice = (int) ($product['gia_ban'] ?? 0);

        if ($listPrice <= 0 || $salePrice <= 0) {
            return 0;
        }

        return max($listPrice - $salePrice, 0);
    }

    private function transformProduct(Thuoc $thuoc): array
    {
        $baseStock = (int) ($thuoc->so_luong_ton_co_so ?? 0);
        $defaultUnit = collect($thuoc->buildUnitOptions())->first(fn (array $item): bool => (bool) ($item['mac_dinh'] ?? false))
            ?? ($thuoc->buildUnitOptions()[0] ?? null);
        $stockDivider = max(1, (int) ($defaultUnit['so_luong_quy_doi'] ?? 1));
        $nhanItems = $this->resolveNhanItems($thuoc);
        $moTa = trim((string) ($thuoc->mo_ta ?? ''));
        $activePromotion = $this->resolveActivePromotion($thuoc);
        $giaNiemYet = (int) round((float) ($defaultUnit['gia_ban'] ?? $thuoc->gia_ban ?? 0));
        $giaBan = $activePromotion ? $activePromotion->tinhGiaSauGiam($giaNiemYet) : $giaNiemYet;
        $hasPromotionDiscount = $activePromotion && $giaBan < $giaNiemYet;

        return [
            'ma_thuoc' => $thuoc->ma_thuoc,
            'ten_thuoc' => $thuoc->ten_thuoc,
            'nhan' => $nhanItems->implode(', '),
            'nhan_items' => $nhanItems->values()->all(),
            'nha_san_xuat' => $thuoc->nhaSanXuat?->ten_nha_san_xuat,
            'mo_ta' => $moTa,
            'lieu_luong' => trim((string) ($thuoc->lieu_luong ?? '')),
            'ham_luong' => trim((string) ($thuoc->ham_luong ?? '')),
            'danh_muc_thuoc_slug' => (string) ($thuoc->danh_muc_thuoc_slug ?? ''),
            'la_thuoc_ke_don' => Str::startsWith((string) ($thuoc->danh_muc_thuoc_slug ?? ''), 'ke-don-'),
            'gia_ban' => $giaBan,
            'gia_niem_yet' => $giaNiemYet,
            'don_vi_hien_thi' => $defaultUnit['ten_don_vi'] ?? $thuoc->don_vi_tinh,
            'so_luong_ton' => (int) floor($baseStock / $stockDivider),
            'don_vi_ton' => $defaultUnit['ten_don_vi'] ?? $thuoc->don_vi_tinh,
            'don_vi_options' => collect($thuoc->buildUnitOptions())
                ->map(fn (array $item): array => [
                    'ten_don_vi' => $item['ten_don_vi'] ?? '',
                    'gia_ban' => (int) round((float) ($item['gia_ban'] ?? 0)),
                    'gia_niem_yet' => (int) round((float) ($item['gia_niem_yet'] ?? $item['gia_ban'] ?? 0)),
                    'mac_dinh' => (bool) ($item['mac_dinh'] ?? false),
                    'so_luong_ton' => (int) ($item['so_luong_ton'] ?? 0),
                    'so_luong_quy_doi' => max(1, (int) ($item['so_luong_quy_doi'] ?? 1)),
                    'don_vi_co_so' => (string) ($item['don_vi_co_so'] ?? ''),
                ])
                ->filter(fn (array $item): bool => $item['ten_don_vi'] !== '')
                ->values()
                ->all(),
            'hinh_anh_url' => $thuoc->hinh_anh_url,
            'co_khuyen_mai' => (bool) $hasPromotionDiscount,
            'khuyen_mai_nhan_hien_thi' => $hasPromotionDiscount
                ? ($activePromotion?->nhan_hien_thi ?: $activePromotion?->ten_khuyen_mai)
                : '',
        ];
    }

    public function buildSuggestedProductsPayload(Collection $matchedProducts): array
    {
        return $matchedProducts
            ->take(3)
            ->map(fn (array $product): array => [
                'ma_thuoc' => $product['ma_thuoc'] ?? null,
                'ten_thuoc' => $product['ten_thuoc'] ?? '',
                'nhan' => $product['nhan'] ?? '',
                'gia_ban' => (int) ($product['gia_ban'] ?? 0),
                'gia_niem_yet' => (int) ($product['gia_niem_yet'] ?? $product['gia_ban'] ?? 0),
                'don_vi_hien_thi' => $product['don_vi_hien_thi'] ?? '',
                'so_luong_ton' => (int) ($product['so_luong_ton'] ?? 0),
                'don_vi_ton' => $product['don_vi_ton'] ?? '',
                'don_vi_options' => $product['don_vi_options'] ?? [],
                'la_thuoc_ke_don' => (bool) ($product['la_thuoc_ke_don'] ?? false),
                'hinh_anh_url' => $product['hinh_anh_url'] ?? null,
                'co_khuyen_mai' => (bool) ($product['co_khuyen_mai'] ?? false),
                'khuyen_mai_nhan_hien_thi' => $product['khuyen_mai_nhan_hien_thi'] ?? '',
            ])
            ->values()
            ->all();
    }

    private function scoreProduct(array $product, string $normalizedQuestion, Collection $tokens): int
    {
        $fields = collect([
            $product['ten_thuoc'] ?? '',
            $product['nhan'] ?? '',
            $product['nha_san_xuat'] ?? '',
            $product['mo_ta'] ?? '',
            str_replace('-', ' ', (string) ($product['danh_muc_thuoc_slug'] ?? '')),
            implode(' ', $product['nhan_items'] ?? []),
        ])->map(fn (string $value): string => $this->normalizeText($value));

        $score = 0;

        $tenThuoc = $fields->first() ?? '';
        $nhan = $fields->get(1, '');
        $moTa = $fields->get(3, '');
        $danhMuc = $fields->get(4, '');

        if ($tenThuoc !== '' && str_contains($tenThuoc, $normalizedQuestion)) {
            $score += 800;
        }

        if ($nhan !== '' && str_contains($nhan, $normalizedQuestion)) {
            $score += 520;
        }

        if ($danhMuc !== '' && str_contains($danhMuc, $normalizedQuestion)) {
            $score += 260;
        }

        if ($moTa !== '' && str_contains($moTa, $normalizedQuestion)) {
            $score += 180;
        }

        if ($tokens->isNotEmpty()) {
            $wordPool = $fields
                ->reject(fn (string $value, int $key): bool => $key === 3)
                ->flatMap(fn (string $value): Collection => $this->tokenize($value))
                ->unique()
                ->values();

            $matchedTokenCount = $tokens->filter(fn (string $token): bool => $wordPool->contains($token))->count();

            if ($matchedTokenCount > 0) {
                $score += $matchedTokenCount * 70;
            }

            if ($matchedTokenCount === $tokens->count()) {
                $score += 120;
            }
        }

        return $score;
    }

    private function shouldEscalateToPharmacist(string $question, Collection $matchedProducts): bool
    {
        $normalizedQuestion = $this->normalizeText($question);
        $sensitiveSignals = [
            'ke don',
            'khang sinh',
            'mang thai',
            'cho con bu',
            'tre em',
            'em be',
            'benh nen',
            'tac dung phu',
            'uong bao nhieu',
            'lieu dung',
            'dung duoc khong',
            'co dung duoc khong',
            'tu van',
            'bac si',
            'duoc si',
        ];

        if (collect($sensitiveSignals)->contains(fn (string $signal): bool => str_contains($normalizedQuestion, $signal))) {
            return true;
        }

        return $matchedProducts->contains(fn (array $product): bool => (bool) ($product['la_thuoc_ke_don'] ?? false));
    }

    private function buildFallbackReply(string $question, Collection $matchedProducts, bool $needsPharmacist, string $intent): string
    {
        if ($needsPharmacist) {
            $productNames = $matchedProducts
                ->pluck('ten_thuoc')
                ->filter()
                ->take(3)
                ->implode(', ');

            $productLine = $productNames !== ''
                ? " Tôi thấy một số thuốc liên quan như {$productNames}."
                : '';

            return "Đây là thuốc kê đơn hoặc câu hỏi cần tư vấn chuyên môn.{$productLine} Bạn vui lòng chờ dược sĩ hỗ trợ thêm trong khung chat này, hoặc nhắn rõ tên thuốc và tình trạng đang gặp để tôi chuyển tiếp đầy đủ hơn.";
        }

        if ($intent === 'don_hang') {
            return "Tôi có thể hỗ trợ bước đầu về thuốc, còn tra cứu đơn hàng hiện nên xem ở mục Lịch sử đơn hàng hoặc cung cấp mã đơn để nhân viên hỗ trợ nhanh hơn trong khung chat này.";
        }

        if ($intent === 'khuyen_mai' && $matchedProducts->isEmpty()) {
            return "Hiện tôi chưa thấy sản phẩm ưu đãi phù hợp để gợi ý ngay. Bạn có thể nhắn tên thuốc cụ thể để tôi kiểm tra khuyến mãi, giá và tồn kho cho đúng sản phẩm.";
        }

        if ($intent === 'dat_hang' && $matchedProducts->isNotEmpty()) {
            $product = $matchedProducts->first();
            $quantity = $this->resolveRequestedQuantity($question);
            $unit = $this->resolveRequestedUnit($question, $product);
            $unitLabel = $unit !== '' ? $unit : ($product['don_vi_hien_thi'] ?? 'đơn vị');

            return "Tôi đã chuẩn bị sẵn {$quantity} {$unitLabel} của {$product['ten_thuoc']} cho bạn. Bạn bấm nút đi đến thanh toán để xem lại giỏ hàng và xác nhận đơn.";
        }

        if ($matchedProducts->isEmpty()) {
            return "Tôi chưa thấy thuốc khớp rõ với câu hỏi của bạn. Bạn có thể nhắn tên thuốc, nhãn như \"thuốc ho\", \"hạ sốt\", \"dị ứng\", hoặc mô tả ngắn triệu chứng để tôi gợi ý chính xác hơn.";
        }

        $lines = $matchedProducts
            ->take(3)
            ->map(function (array $product): string {
                $price = $this->formatCurrency((int) ($product['gia_ban'] ?? 0));
                $stock = (int) ($product['so_luong_ton'] ?? 0);
                $unit = $product['don_vi_hien_thi'] ?: 'sản phẩm';
                $promo = ! empty($product['co_khuyen_mai']) && ! empty($product['khuyen_mai_nhan_hien_thi'])
                    ? " ({$product['khuyen_mai_nhan_hien_thi']})"
                    : '';

                return "- {$product['ten_thuoc']}: {$price}/{$unit}, còn {$stock} {$unit}{$promo}";
            })
            ->implode("\n");

        if ($intent === 'khuyen_mai') {
            return "Tôi thấy một số sản phẩm đang có ưu đãi hoặc liên quan đến khuyến mãi để bạn tham khảo:\n{$lines}\nBạn có thể bấm xem chi tiết từng thuốc hoặc nhắn tiếp tên thuốc để tôi lọc ưu đãi chính xác hơn.";
        }

        return "Tôi tìm thấy một số thuốc liên quan để bạn tham khảo:\n{$lines}\nBạn có thể bấm xem chi tiết từng thuốc hoặc nhắn tiếp tên thuốc muốn xem kỹ hơn.";
    }

    private function shouldUseOutOfScopeReply(string $question, string $intent, Collection $matchedProducts): bool
    {
        if ($matchedProducts->isNotEmpty()) {
            return false;
        }

        if (in_array($intent, ['don_hang', 'khuyen_mai'], true)) {
            return false;
        }

        return ! $this->hasPharmacistRequestSignal($question);
    }

    private function isClearlyOutOfScopeQuestion(string $question): bool
    {
        $normalized = $this->normalizeText($question);
        $signals = [
            'thoi tiet',
            'bong da',
            'tin tuc',
            'chung khoan',
            'bitcoin',
            'game',
            'phim',
            'nhac',
            'nau an',
            'du lich',
            'lap trinh',
            'toan hoc',
            'chinh tri',
        ];

        return collect($signals)->contains(fn (string $signal): bool => str_contains($normalized, $signal));
    }

    private function shouldAskProductNameBeforeSearch(string $question): bool
    {
        $normalized = $this->normalizeText($question);

        if ($normalized === '' || $this->hasPharmacistRequestSignal($normalized)) {
            return false;
        }

        $promotionSignals = ['khuyen mai', 'uu dai', 'giam gia', 'sale', 'ma giam'];
        if (collect($promotionSignals)->contains(fn (string $signal): bool => str_contains($normalized, $signal))) {
            return false;
        }

        $genericSignals = ['mua', 'dat', 'dat hang', 'tim', 'tim kiem', 'thuoc', 'san pham'];
        $hasGenericIntent = collect($genericSignals)->contains(
            fn (string $signal): bool => str_contains($normalized, $signal)
        );

        return $hasGenericIntent && $this->tokenize($normalized)->isEmpty();
    }

    private function hasPharmacistRequestSignal(string $question): bool
    {
        $normalized = $this->normalizeText($question);

        $signals = [
            'duoc si',
            'tu van',
            'bac si',
            'hoi duoc si',
            'can tu van',
        ];

        return collect($signals)->contains(fn (string $signal): bool => str_contains($normalized, $signal));
    }

    private function outOfScopeReply(): string
    {
        return 'Câu hỏi không nằm trong phạm vi trả lời, mong khách hàng đợi dược sĩ trong ít phút.';
    }

    private function generateGroqReply(string $question, Collection $matchedProducts, bool $needsPharmacist): string
    {
        $payload = [
            'model' => config('services.groq.model', 'openai/gpt-oss-20b'),
            'input' => [
                [
                    'role' => 'system',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => $this->systemPrompt(),
                    ]],
                ],
                [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => $this->buildUserPrompt($question, $matchedProducts, $needsPharmacist),
                    ]],
                ],
            ],
            'max_output_tokens' => 320,
        ];

        $headers = array_filter([
            'Content-Type' => 'application/json',
            'X-Client-Request-Id' => (string) Str::uuid(),
        ], fn ($value) => filled($value));

        $response = Http::withToken((string) config('services.groq.api_key'))
            ->withHeaders($headers)
            ->timeout(25)
            ->post(rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/') . '/responses', $payload)
            ->throw()
            ->json();

        return $this->extractResponseText($response);
    }

    private function systemPrompt(): string
    {
        return implode("\n", [
            'Bạn là trợ lý AI bước đầu của nhà thuốc PharmaGo.',
            'Chỉ dùng dữ liệu thuốc và ngữ cảnh được cung cấp.',
            'Không chẩn đoán bệnh, không kê đơn, không bịa thông tin ngoài dữ liệu.',
            'Nếu câu hỏi cần dược sĩ hoặc có thuốc kê đơn, phải nói rõ cần dược sĩ hỗ trợ thêm và không hướng khách tự mua.',
            'Trả lời bằng tiếng Việt, ngắn gọn, thân thiện, ưu tiên 3-5 câu rõ ý.',
        ]);
    }

    private function buildUserPrompt(string $question, Collection $matchedProducts, bool $needsPharmacist): string
    {
        $context = $matchedProducts->isEmpty()
            ? 'Không tìm thấy thuốc khớp rõ trong catalog hiện tại.'
            : $matchedProducts
                ->map(function (array $product): string {
                    return implode(' | ', array_filter([
                        "Tên: {$product['ten_thuoc']}",
                        "Nhãn: " . ($product['nhan'] ?: 'Đang cập nhật'),
                        "Hãng: " . ($product['nha_san_xuat'] ?: 'Đang cập nhật'),
                        "Giá: " . $this->formatCurrency((int) ($product['gia_ban'] ?? 0)) . '/' . ($product['don_vi_hien_thi'] ?: 'đơn vị'),
                        "Tồn: " . ((int) ($product['so_luong_ton'] ?? 0)) . ' ' . ($product['don_vi_ton'] ?: 'đơn vị'),
                        "Kê đơn: " . ((bool) ($product['la_thuoc_ke_don'] ?? false) ? 'Có' : 'Không'),
                        "Mô tả: " . ($product['mo_ta'] ?: 'Đang cập nhật'),
                    ]));
                })
                ->implode("\n");

        return implode("\n\n", [
            "Câu hỏi khách: {$question}",
            'Có cần chuyển dược sĩ: ' . ($needsPharmacist ? 'Có' : 'Không'),
            "Dữ liệu catalog liên quan:\n{$context}",
        ]);
    }

    private function extractResponseText(array $response): string
    {
        $outputText = trim((string) ($response['output_text'] ?? ''));

        if ($outputText !== '') {
            return $outputText;
        }

        return collect($response['output'] ?? [])
            ->flatMap(function (array $outputItem): Collection {
                return collect($outputItem['content'] ?? [])
                    ->map(function (array $contentItem): string {
                        if (($contentItem['type'] ?? '') === 'output_text') {
                            return trim((string) ($contentItem['text'] ?? ''));
                        }

                        if (is_array($contentItem['text'] ?? null)) {
                            return trim((string) ($contentItem['text']['value'] ?? ''));
                        }

                        return '';
                    });
            })
            ->filter()
            ->implode("\n");
    }

    private function resolveNhanItems(Thuoc $thuoc): Collection
    {
        return collect(preg_split('/[\r\n,;|]+/u', trim((string) ($thuoc->nhan ?? ''))) ?: [])
            ->map(fn (string $item): string => trim(preg_replace('/\s+/u', ' ', $item) ?? ''))
            ->filter()
            ->unique(fn (string $item): string => $this->normalizeText($item))
            ->values();
    }

    private function resolveIntent(string $question, bool $needsPharmacist): string
    {
        if ($needsPharmacist) {
            return 'tu_van_duoc_si';
        }

        $normalized = $this->normalizeText($question);

        if ($normalized === '') {
            return 'tra_cuu_thuoc';
        }

        $purchaseSignals = [
            'mua',
            'mua them',
            'dat hang',
            'dat them',
            'thanh toan',
            'them vao gio',
            'them san pham',
            'them thuoc',
            'lay cho toi',
            'toi muon lay',
        ];
        if (collect($purchaseSignals)->contains(fn (string $signal): bool => str_contains($normalized, $signal))) {
            return 'dat_hang';
        }

        $orderSignals = ['don hang', 'ma don', 'giao hang', 'van chuyen', 'thanh toan', 'ship'];
        if (collect($orderSignals)->contains(fn (string $signal): bool => str_contains($normalized, $signal))) {
            return 'don_hang';
        }

        $promotionSignals = ['khuyen mai', 'uu dai', 'giam gia', 'sale', 'ma giam'];
        if (collect($promotionSignals)->contains(fn (string $signal): bool => str_contains($normalized, $signal))) {
            return 'khuyen_mai';
        }

        return 'tra_cuu_thuoc';
    }

    private function resolveActivePromotion(Thuoc $thuoc): mixed
    {
        return $thuoc->khuyenMais
            ?->first(fn ($item) => $item->isDangHoatDong());
    }

    private function normalizeText(string $value): string
    {
        $normalized = Str::of($value)
            ->replace('#', ' ')
            ->ascii()
            ->lower()
            ->toString();

        $normalized = preg_replace('/[^a-z0-9\s]/', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        return trim($normalized);
    }

    private function tokenize(string $value): Collection
    {
        $stopWords = [
            'toi',
            'minh',
            'em',
            'anh',
            'chi',
            'ban',
            'khach',
            'hang',
            'muon',
            'can',
            'tim',
            'kiem',
            'mua',
            'dat',
            'lay',
            'cho',
            'thuoc',
            'san',
            'pham',
            'nao',
            'giup',
            'tro',
            'voi',
            'gia',
            'bao',
            'nhieu',
            'con',
            'khong',
            'co',
            'la',
            'cua',
            've',
        ];

        $rawTokens = explode(' ', $this->normalizeText($value));

        return collect($rawTokens)
            ->filter()
            ->reject(function (string $token, int $index) use ($rawTokens, $stopWords): bool {
                if ($token === 'ho' && ($rawTokens[$index + 1] ?? '') === 'tro') {
                    return true;
                }

                return in_array($token, $stopWords, true);
            })
            ->values();
    }

    private function formatCurrency(int $value): string
    {
        return number_format($value, 0, ',', '.') . 'đ';
    }

    private function buildPurchaseAction(
        string $question,
        Collection $matchedProducts,
        bool $needsPharmacist,
        string $intent
    ): ?array {
        if ($needsPharmacist || $intent !== 'dat_hang' || $matchedProducts->isEmpty()) {
            return null;
        }

        $product = $matchedProducts->first();

        if (! is_array($product) || empty($product['ma_thuoc'])) {
            return null;
        }

        $requestedUnit = $this->resolveRequestedUnit($question, $product);
        $quantity = $this->resolveRequestedQuantity($question);
        $selectedUnit = $requestedUnit !== '' ? $requestedUnit : (string) ($product['don_vi_hien_thi'] ?? '');
        $selectedUnitOption = collect($product['don_vi_options'] ?? [])
            ->first(fn (array $item): bool => (string) ($item['ten_don_vi'] ?? '') === $selectedUnit);

        $resolvedUnit = (string) ($selectedUnitOption['ten_don_vi'] ?? $selectedUnit);
        $resolvedPrice = (int) ($selectedUnitOption['gia_ban'] ?? $product['gia_ban'] ?? 0);
        $resolvedListPrice = (int) ($selectedUnitOption['gia_niem_yet'] ?? $product['gia_niem_yet'] ?? $resolvedPrice);
        $resolvedStock = (int) ($selectedUnitOption['so_luong_ton'] ?? $product['so_luong_ton'] ?? 0);

        if ($resolvedStock <= 0) {
            return null;
        }

        return [
            'loai' => 'tao_gio_va_thanh_toan',
            'checkout_link' => '/thanh-toan',
            'san_pham' => [
                'ma_thuoc' => $product['ma_thuoc'],
                'ten_thuoc' => $product['ten_thuoc'] ?? '',
                'nhan' => $product['nhan'] ?? '',
                'mo_ta' => $product['mo_ta'] ?? '',
                'nha_san_xuat' => $product['nha_san_xuat'] ?? '',
                'hinh_anh_url' => $product['hinh_anh_url'] ?? '',
                'gia_ban' => $resolvedPrice,
                'gia_niem_yet' => $resolvedListPrice,
                'don_vi_hien_thi' => $resolvedUnit,
                'don_vi_tinh' => $resolvedUnit,
                'selected_don_vi' => $resolvedUnit,
                'so_luong_ton' => $resolvedStock,
                'don_vi_ton' => $resolvedUnit,
                'don_vi_options' => $product['don_vi_options'] ?? [],
                'so_luong_de_xuat' => $quantity,
            ],
        ];
    }

    private function resolveRequestedQuantity(string $question): int
    {
        $normalized = $this->normalizeText($question);

        if (preg_match('/(?:mua|lay|dat hang|them vao gio|thanh toan)\s+(\d{1,3})/u', $normalized, $matches)) {
            return max(1, (int) ($matches[1] ?? 1));
        }

        if (preg_match('/(\d{1,3})\s+(hop|vi|vien|chai|goi|ong|tuyp|lo|lọ|ml|g)\b/u', $normalized, $matches)) {
            return max(1, (int) ($matches[1] ?? 1));
        }

        return 1;
    }

    private function resolveRequestedUnit(string $question, array $product): string
    {
        $normalized = $this->normalizeText($question);
        $availableUnits = collect($product['don_vi_options'] ?? [])
            ->pluck('ten_don_vi')
            ->filter()
            ->map(fn ($item) => (string) $item)
            ->values();

        foreach ($availableUnits as $unit) {
            $normalizedUnit = $this->normalizeText($unit);

            if ($normalizedUnit !== '' && preg_match('/\b' . preg_quote($normalizedUnit, '/') . '\b/u', $normalized)) {
                return $unit;
            }
        }

        return '';
    }

    private function buildStructuredCatalogReply(string $intent, Collection $matchedProducts): string
    {
        $visibleProducts = $matchedProducts->take(3)->values();
        $totalProducts = $matchedProducts->count();
        $visibleCount = $visibleProducts->count();
        $keywordHint = $visibleProducts
            ->flatMap(fn (array $product): array => $product['nhan_items'] ?? [])
            ->filter()
            ->unique()
            ->take(2)
            ->implode(', ');

        if ($intent === 'khuyen_mai') {
            $lead = $visibleCount > 1
                ? "Tôi đã lọc được {$visibleCount} sản phẩm đang có ưu đãi phù hợp nhất để bạn xem nhanh."
                : "Tôi đã lọc được 1 sản phẩm đang có ưu đãi phù hợp để bạn xem nhanh.";

            return $keywordHint !== ''
                ? "{$lead} Bạn xem các gợi ý bên dưới, chủ yếu thuộc nhóm {$keywordHint}."
                : "{$lead} Bạn xem các gợi ý bên dưới để mở chi tiết từng sản phẩm.";
        }

        $lead = $visibleCount > 1
            ? "Tôi tìm thấy {$visibleCount} sản phẩm phù hợp nhất để bạn xem nhanh."
            : "Tôi tìm thấy 1 sản phẩm phù hợp để bạn xem nhanh.";

        if ($totalProducts > $visibleCount) {
            $lead .= " Hiện tôi đang ưu tiên hiển thị {$visibleCount} lựa chọn rõ thông tin nhất trước.";
        }

        return $keywordHint !== ''
            ? "{$lead} Bạn xem các gợi ý bên dưới, chủ yếu thuộc nhóm {$keywordHint}."
            : "{$lead} Bạn có thể bấm Xem chi tiết từng thuốc hoặc nhắn tên cụ thể hơn để tôi lọc sát hơn.";
    }

    private function buildStructuredPurchaseReply(?array $purchaseAction, Collection $matchedProducts): string
    {
        if ($purchaseAction && is_array($purchaseAction['san_pham'] ?? null)) {
            $product = $purchaseAction['san_pham'];
            $quantity = max(1, (int) ($product['so_luong_de_xuat'] ?? 1));
            $unit = (string) ($product['don_vi_hien_thi'] ?? 'đơn vị');
            $productName = (string) ($product['ten_thuoc'] ?? 'sản phẩm');

            return "Tôi đã chuẩn bị sẵn {$quantity} {$unit} của {$productName} cho bạn. "
                . "Bạn bấm nút Thêm vào giỏ và thanh toán để chuyển thẳng sang trang checkout.";
        }

        $productName = (string) (($matchedProducts->first()['ten_thuoc'] ?? '') ?: 'sản phẩm bạn đang hỏi');

        return "Tôi đã kiểm tra {$productName}, nhưng hiện chưa thể tự tạo giỏ hàng cho sản phẩm này. "
            . "Bạn có thể thử thuốc khác còn hàng, hoặc nhắn lại tên thuốc/đơn vị cụ thể để tôi kiểm tra lại.";
    }
}
