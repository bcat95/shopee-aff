# Code Examples

Hai bộ mẫu cho **hai hệ API khác nhau** — đừng trộn cấu hình của chúng:

| Thư mục | Gọi tới | Xác thực |
| --- | --- | --- |
| `php/`, `nodejs/` | Shopee Affiliate **Open API chính thống** (GraphQL) | `SHOPEE_API_APP_ID` + `SHOPEE_API_SECRET` |
| [`data-api/`](data-api/) | **data.addlivetag.com** (product-data, offers, market, history…) | `ADDLIVETAG_API_KEY` — **bắt buộc từ 01/10/2026** |

---

## `php/` và `nodejs/` — Shopee Open API

### Cấu hình nhanh

1. Vào từng thư mục (`php` hoặc `nodejs`).
2. Copy `.env.example` thành `.env`.
3. Điền thông tin thật:
   - `SHOPEE_API_APP_ID`
   - `SHOPEE_API_SECRET`
4. Chạy script theo hướng dẫn trong README từng thư mục.

### Lưu ý

- Đây là ví dụ sử dụng API chính thống của Shopee Affiliate.
- Bộ sample hiện tại hỗ trợ các API: `shopeeOfferV2`, `brandOfferV2`, `productOfferV2`, `generateShortLink`, `conversionReportV2`, `validationReportV2`.

---

## `data-api/` — Data API

Xem [data-api/README.md](data-api/README.md). Tài liệu endpoint nằm ở
[`../product-data-api.md`](../product-data-api.md) và thư mục [`../docs/`](../docs/).
