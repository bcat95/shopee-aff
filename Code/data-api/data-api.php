<?php

declare(strict_types=1);

/**
 * Ví dụ gọi Data API (data.addlivetag.com) bằng PHP 8.
 *
 * API Key bắt buộc từ 01/10/2026 — đặt ADDLIVETAG_API_KEY trong .env cạnh file này,
 * hoặc export ra biến môi trường.
 *
 *   php data-api.php product 1589295236
 *   php data-api.php batch 1589295236,41013426581
 *   php data-api.php market "ao len"
 *   php data-api.php shop-check 38003654
 */

const BASE_URL = 'https://data.addlivetag.com';

function loadEnv(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }
    $vars = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $vars[trim($parts[0])] = trim(trim($parts[1]), "'\"");
    }
    return $vars;
}

function apiKey(): string
{
    $env = loadEnv(__DIR__ . '/.env');
    return (string) (getenv('ADDLIVETAG_API_KEY') ?: ($env['ADDLIVETAG_API_KEY'] ?? ''));
}

/**
 * Gọi endpoint, trả mảng đã decode.
 *
 * Key đi bằng header X-API-Key chứ không nhét vào URL: URL hay bị ghi vào access log.
 *
 * @param array<string, mixed>      $query
 * @param array<string, mixed>|null $body  Truyền mảng để gửi POST JSON
 * @return array<string, mixed>
 */
function callDataApi(string $endpoint, array $query = [], ?array $body = null): array
{
    $url = BASE_URL . $endpoint;
    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    $headers = [];
    if (($key = apiKey()) !== '') {
        $headers[] = 'X-API-Key: ' . $key;
    }
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    unset($ch);   // curl_close() đã deprecated từ PHP 8.5 và không còn tác dụng từ 8.0

    if ($raw === false) {
        throw new RuntimeException('Lỗi kết nối: ' . $err);
    }
    if ($code === 401) {
        throw new RuntimeException('401 — thiếu API Key. Lấy key tại addlivetag.com → API Key → Tạo Key.');
    }
    if ($code === 429) {
        throw new RuntimeException('429 — vượt rate limit. Có API Key thì hạn mức cao hơn 2,5 lần.');
    }

    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Response không phải JSON hợp lệ (HTTP ' . $code . ').');
    }

    // Chưa có key thì response kèm khối nhắc — in ra để không bị bất ngờ vào ngày bị chặn.
    if (isset($data['apiKeyNotice'])) {
        fwrite(STDERR, '[api-key] ' . $data['apiKeyNotice']['status'] . ': '
            . $data['apiKeyNotice']['message'] . PHP_EOL);
    }

    return $data;
}

/** Một sản phẩm. Thêm base_rate/cap để tính hoa hồng theo tier account của bạn. */
function getProduct(string $itemId, array $opts = []): array
{
    return callDataApi('/product-data/product-data.php', ['item_id' => $itemId] + $opts);
}

/** Nhiều sản phẩm (tối đa 100/request). Luôn dùng cái này thay cho vòng lặp getProduct(). */
function getProductsBatch(array $itemIds, array $opts = []): array
{
    return callDataApi('/product-data/product-data-batch.php', [], ['item_ids' => $itemIds] + $opts);
}

/** Toàn cảnh thị trường theo từ khoá — không tiêu quota Shopee. */
function getMarket(string $keyword, array $opts = []): array
{
    return callDataApi('/search/market.php', ['q' => $keyword, 'limit' => 20] + $opts);
}

/** Tra hoa hồng shop theo shopId. Nhớ đọc pending[] trước khi kết luận. */
function checkShops(string $shopIds, array $opts = []): array
{
    return callDataApi('/offers/shop-check.php', ['shopIds' => $shopIds] + $opts);
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $command = $argv[1] ?? 'product';
    $arg     = $argv[2] ?? '1589295236';

    try {
        $result = match ($command) {
            'product'    => getProduct($arg),
            'batch'      => getProductsBatch(array_map('trim', explode(',', $arg))),
            'market'     => getMarket($arg),
            'shop-check' => checkShops($arg),
            default      => throw new RuntimeException(
                "Lệnh không hỗ trợ: $command. Dùng: product | batch | market | shop-check"
            ),
        };
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
