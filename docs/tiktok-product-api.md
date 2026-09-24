# TikTok Shop Product Data API (check hoa hồng + thông tin sản phẩm)

API lấy thông tin sản phẩm TikTok Shop (affiliate) kèm hoa hồng, **lưu data lại** giống Shopee/Lazada Product Data API. Dữ liệu cache trong database mặc định **24 giờ** (`cacheTtlHours=24`) trước khi gọi lại nguồn.

## Tuyên bố pháp lý & phạm vi sử dụng

- Chỉ dành cho **học tập, nghiên cứu kỹ thuật, vận hành nội bộ phi thương mại**.
- Nguồn là **API không chính thống** của TikTok Shop, dữ liệu có thể sai số / chậm cập nhật / thay đổi bất kỳ lúc nào.
- Người dùng tự kiểm chứng và tự chịu trách nhiệm.

**Base URL:** `https://data.addlivetag.com/tiktok/product.php`

> ⚠️ **API Key bắt buộc từ 01/10/2026** — gửi header `X-API-Key: <key>` hoặc `&key=<key>`.
> Trước mốc đó, request chưa có key chỉ còn 40% hạn mức. Xem [api-key.md](api-key.md).

---

## Nguồn dữ liệu

Dữ liệu lấy qua **nguồn không chính thống** của TikTok Shop, do server đứng ra gọi bằng cấu
hình riêng — bên gọi API không cần cung cấp thông tin đăng nhập nào. Nguồn có thể đổi hoặc
ngừng hoạt động bất kỳ lúc nào, nên đừng xây nghiệp vụ quan trọng chỉ dựa vào nó.

---

## Cách gọi

- **GET** hoặc **POST**, hỗ trợ CORS (`Access-Control-Allow-Origin: *`).

| Tham số         | Bắt buộc         | Mô tả                                                                 |
| --------------- | ---------------- | -------------------------------------------------------------------- |
| `url`           | một trong ba     | 1 URL sản phẩm / link `vt.tiktok.com` / link `shop.tiktok.com/vn/pdp/<id>`. |
| `urls`          | một trong ba     | Nhiều URL, phân tách bằng dấu phẩy hoặc xuống dòng (tối đa 20).       |
| `product_id`    | một trong ba     | Chỉ **tra cache DB** (check cần URL nên product_id không gọi API).   |
| `clear_cache`   | không            | `=1` → bỏ qua cache DB, luôn gọi TikTok API.                          |
| `cacheTtlHours` | không            | Thời hạn cache, mặc định `24`. `0` = luôn coi cache là hợp lệ.        |
| `debugDb`       | không            | `=1` → kèm khối `db_debug`.                                           |

### Ví dụ

```
GET  /tiktok/product.php?url=https://vt.tiktok.com/XXXXXXXX/
GET  /tiktok/product.php?urls=https://vt.tiktok.com/AAA/,https://vt.tiktok.com/BBB/
GET  /tiktok/product.php?product_id=1732150295561143564
POST /tiktok/product.php     (body: url=https://vt.tiktok.com/XXXXXXXX/)
```

---

## Response

### 1 URL → `productInfo` (object)

```json
{
  "status": "success",
  "count": 1,
  "productInfo": {
    "itemId": "1732150295561143564",
    "productId": "1732150295561143564",
    "productName": "Tên sản phẩm ...",
    "shopName": "Tên shop",
    "storeName": "Tên shop",
    "price": 11850000,
    "formatPrice": "11.850.000₫",
    "currency": "₫",
    "imageUrl": null,
    "productLink": "https://vt.tiktok.com/XXXXXXXX/",
    "stockStatus": 1,
    "addStatus": 2,
    "productType": 1,
    "source": "Affiliate",
    "hasCommission": true,
    "commission": 118500,
    "commissionWithCurrency": "118.500₫",
    "commissionRateRaw": 100,
    "commissionRatePercent": 1,
    "lastUpdate": "2026-07-24 11:00:00",
    "dataSource": "api"
  },
  "tiktokAccountNote": "acc1"
}
```

### Nhiều URL → `products[]`

```json
{ "status": "success", "count": 2, "products": [ { "...": "..." } ], "notFoundUrls": [ "..." ] }
```

### Giải thích trường `productInfo`

| Trường                  | Kiểu    | Mô tả                                                                    |
| ----------------------- | ------- | ------------------------------------------------------------------------ |
| `itemId` / `productId`  | string  | `product_id` TikTok (chuỗi số).                                          |
| `productName`           | string  | `title`.                                                                 |
| `shopName` / `storeName`| string  | `store_name`.                                                            |
| `price`                 | number  | Giá parse từ `format_price` (VNĐ).                                       |
| `formatPrice`           | string  | Giá dạng chuỗi gốc, vd `11.850.000₫`.                                    |
| `productLink`           | string  | URL gốc đã check (hoặc `shop.tiktok.com/view/product/<id>`).             |
| `stockStatus`           | number  | Trạng thái kho từ API.                                                    |
| `addStatus`             | number  | Trạng thái thêm showcase.                                                |
| `source`                | string  | vd `Affiliate`.                                                          |
| `hasCommission`         | boolean | Có hoa hồng affiliate hay không.                                        |
| `commission`            | number  | Hoa hồng ước tính (VNĐ), parse từ `est_commission_expense`.             |
| `commissionWithCurrency`| string  | Hoa hồng dạng chuỗi, vd `118.500₫`.                                      |
| `commissionRateRaw`     | number  | `commission_rate` raw từ TikTok.                                         |
| `commissionRatePercent` | number  | `commissionRateRaw / 100` (quan sát: đây là % — vd 100 → 1%).           |
| `lastUpdate`            | string  | Thời điểm cập nhật dữ liệu.                                              |
| `dataSource`            | string  | `"api"` (mới từ TikTok) hoặc `"db"` (cache).                             |

**Cách xác định có hoa hồng:** tồn tại `affiliate_info` và `commission_rate > 0` (hoặc có `est_commission_expense` / `commission_with_currency`). Không có `closed_loop_product` / không `affiliate_info` → sản phẩm **không hoa hồng affiliate** (URL được liệt kê trong `notFoundUrls`).

---

## Cache & nguồn dữ liệu

1. Có bản ghi trong DB và còn hạn (`cacheTtlHours`) → trả từ **db**, không gọi TikTok.
2. Hết hạn / chưa có → gọi TikTok API, lưu DB, trả từ **api**.
3. `clear_cache=1` → luôn gọi API.
4. API lỗi nhưng có bản ghi cũ → trả cache kèm `warning`.

Tra cache khi input là URL: ưu tiên trích `product_id` từ URL (`/view/product/<id>`, `?product_id=`) để tra theo id; nếu không trích được thì tra theo `product_url` đã lưu.

---

## Lưu data (DB)

Dữ liệu được lưu lại phía server để phục vụ cache và lịch sử giá/hoa hồng.

## Rate limit

Theo IP (Cloudflare / X-Forwarded-For / REMOTE_ADDR), lưu file trong `rate_limit/tiktok/`:

- Khi gọi TikTok API: **60 request/phút** (thấp để tránh acc bị khoá).
- Khi trả từ cache DB: **600 request/phút**.
- Vượt: HTTP **429**.

---

## Lỗi

| Tình huống                         | HTTP | Response                                                             |
| ---------------------------------- | ---- | ------------------------------------------------------------------- |
| Thiếu `url`/`urls`/`product_id`    | 400  | `{"status":"error","message":"url (hoặc urls / product_id) is required..."}` |
| DB lỗi (khi chỉ tra product_id)    | 500  | `{"status":"error","message":"Database connection failed"}`         |
| Rate limit                         | 429  | `{"status":"error","message":"Rate limit exceeded..."}`             |
| Phiên nguồn hết hạn | 200  | `status:"success"`, `warning` mô tả lỗi API, sản phẩm vào `notFoundUrls` |

---

## Tóm tắt endpoint

| Mục        | Giá trị                                                    |
| ---------- | ---------------------------------------------------------- |
| URL        | `https://data.addlivetag.com/tiktok/product.php`           |
| Method     | GET, POST, OPTIONS                                         |
| Input      | `url` / `urls` / `product_id` (+ `clear_cache`, `cacheTtlHours`) |
| Output     | JSON `status` + `productInfo` (1 sp) / `products[]` (nhiều sp) |
| Rate limit | 60/phút (api), 600/phút (db), theo IP                     |
| Timezone   | Asia/Ho_Chi_Minh                                          |
