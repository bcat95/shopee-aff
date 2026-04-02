<?php

declare(strict_types=1);

const API_URL = 'https://open-api.affiliate.shopee.vn/graphql';

function loadEnv(string $path): array
{
    if (!file_exists($path)) {
        throw new RuntimeException('.env not found. Copy .env.example to .env first.');
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        throw new RuntimeException('Cannot read .env file.');
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $vars[$key] = trim($value, "\"'");
    }

    return $vars;
}

function buildPayload(): string
{
    $apiName = $GLOBALS['argv'][1] ?? 'shopeeOfferV2';
    $inputUrl = $GLOBALS['argv'][2] ?? 'https://shopee.vn';

    $queries = [
        'shopeeOfferV2' => <<<'GQL'
{
  shopeeOfferV2(keyword: "phone", sortType: 1, page: 1, limit: 5) {
    nodes { offerName offerLink commissionRate }
    pageInfo { page limit hasNextPage }
  }
}
GQL,
        'brandOfferV2' => <<<'GQL'
{
  brandOffer(keyword: "phone", sortType: 1, page: 1, limit: 5) {
    nodes { offerName offerLink commissionRate }
    pageInfo { page limit hasNextPage }
  }
}
GQL,
        'productOfferV2' => <<<'GQL'
{
  productOfferV2(keyword: "phone", sortType: 1, page: 1, limit: 5) {
    nodes { productName offerLink commissionRate sales }
    pageInfo { page limit hasNextPage }
  }
}
GQL,
        'generateShortLink' => <<<GQL
mutation {
  generateShortLink(input: { originUrl: "{$inputUrl}", subIds: ["s1"] }) {
    shortLink
  }
}
GQL,
        'conversionReportV2' => <<<'GQL'
{
  conversionReport(limit: 5) {
    nodes { conversionId purchaseTime totalCommission }
    pageInfo { scrollId }
  }
}
GQL,
        'validationReportV2' => <<<'GQL'
{
  validatedReport(validationId: 1, limit: 5) {
    nodes { conversionId purchaseTime totalCommission }
    pageInfo { scrollId }
  }
}
GQL,
    ];

    if (!isset($queries[$apiName])) {
        $supported = implode(', ', array_keys($queries));
        throw new RuntimeException("Unsupported api name: {$apiName}. Supported: {$supported}");
    }

    return json_encode(
        ['query' => $queries[$apiName]],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}

function callShopeeApi(string $appId, string $secret, string $payload): array
{
    $timestamp = time();
    $signatureBase = $appId . $timestamp . $payload . $secret;
    $signature = hash('sha256', $signatureBase);

    $ch = curl_init(API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            "Authorization: SHA256 Credential={$appId}, Timestamp={$timestamp}, Signature={$signature}",
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('cURL error: ' . $error);
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid JSON response from Shopee API.');
    }

    return [
        'http_code' => $httpCode,
        'response' => $decoded,
    ];
}

try {
    $env = loadEnv(__DIR__ . '/.env');
    $appId = $env['SHOPEE_API_APP_ID'] ?? '';
    $secret = $env['SHOPEE_API_SECRET'] ?? '';

    if ($appId === '' || $secret === '') {
        throw new RuntimeException('Missing SHOPEE_API_APP_ID or SHOPEE_API_SECRET in .env');
    }

    $payload = buildPayload();
    $result = callShopeeApi($appId, $secret, $payload);
    $result['api'] = $GLOBALS['argv'][1] ?? 'shopeeOfferV2';

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'usage' => 'php index.php [apiName] [originUrl-for-generateShortLink]',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
