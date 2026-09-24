# Code mẫu — Data API (data.addlivetag.com)

Gọi các endpoint của `data.addlivetag.com` kèm **API Key**. Khác với `../php` và `../nodejs`
(những thư mục đó gọi **Shopee Affiliate Open API chính thống**, xác thực bằng
`app_id` + `secret`, không dùng key này).

## Chuẩn bị

```bash
cp .env.example .env
# điền ADDLIVETAG_API_KEY — lấy tại addlivetag.com → API Key → Tạo Key
```

## Chạy

```bash
# Node.js 18+
node index.js product 1589295236
node index.js batch 1589295236,41013426581
node index.js market "ao len"
node index.js shop-check 38003654
node index.js history 1589295236

# PHP 8
php data-api.php product 1589295236
php data-api.php batch 1589295236,41013426581
php data-api.php market "ao len"
php data-api.php shop-check 38003654
```

## Điều hai file mẫu này minh hoạ

- **Key đi bằng header `X-API-Key`**, không nhét vào URL — URL hay bị ghi vào access log.
- **Đọc khối `apiKeyNotice`** trong response và in cảnh báo ra stderr. Chưa có key thì mới
  có khối này; có key hợp lệ thì nó biến mất. Đừng đợi tới ngày bị chặn mới biết.
- **Phân biệt 401 với 429**: `401` = thiếu/sai key, `429` = vượt hạn mức (có key thì hạn mức
  cao hơn 2,5 lần).
- **Batch thay cho vòng lặp**: `batch()` gửi tới 100 item_id trong một request; gọi endpoint
  đơn vài nghìn lần là cách nhanh nhất để tự đâm vào rate limit.

Tài liệu đầy đủ: [../../docs/api-key.md](../../docs/api-key.md) ·
[../../product-data-api.md](../../product-data-api.md)
