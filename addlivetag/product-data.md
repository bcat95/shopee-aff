# Shopee Product Data API

API lấy thông tin sản phẩm Shopee kèm chi tiết hoa hồng (commission). Dữ liệu được cache trong database 24 giờ trước khi làm mới.

**Base URL:** `https://data.addlivetag.com/product-data/product-data.php`

---

## Thông báo bảo trì – Link rút gọn & Product Data API

Hiện tại mình tạm ngưng xử lý các link rút gọn (như s.shopee.vn, shp.ee…) do lưu lượng tăng mạnh, gây ảnh hưởng trực tiếp đến hiệu năng của Product Data API.

## Hai cách sử dụng ổn định nhất

- **item_id (khuyến nghị):**  
  Gửi trực tiếp mã sản phẩm → nhanh, chính xác, ít lỗi  

- **Link gốc:**  
- URL đầy đủ: `https://shopee.vn/product/<shop_id>/<item_id>`
- Dạng path: `-i.<shop_id>.<item_id>`, `/product/<shop_id>/<item_id>`, `/opaanlp/<shop_id>/<item_id>`

---

## Vì sao tạm dừng link rút gọn?

Link rút gọn không chứa thông tin sản phẩm ngay từ đầu. Hệ thống phải đi qua nhiều bước chuyển hướng để tìm ra link gốc:

- Tốn tài nguyên  
- Tăng độ trễ  
- Dễ phát sinh lỗi khi traffic cao  

Để đảm bảo API hoạt động ổn định cho số đông, mình tạm thời chặn dạng link này trong giai đoạn bảo trì.

---

## Bạn nên làm gì lúc này?

- Ưu tiên dùng **item_id**  
- Hoặc dùng **link đầy đủ** copy trực tiếp từ trình duyệt  
- Tránh dùng link rút gọn từ tin nhắn, bài đăng  

---

## Kế hoạch sắp tới

Sẽ tách riêng một dịch vụ chuyên xử lý chuyển đổi link rút gọn → link gốc, độc lập với API chính.  

Khi hoàn tất, hệ thống sẽ mở lại hỗ trợ link rút gọn với giới hạn phù hợp để đảm bảo hiệu năng.

---

> Nếu đang làm affiliate hoặc build tool, nên chuyển luôn sang item_id để tối ưu tốc độ và tránh lỗi về lâu dài.

## Cách gọi API

### Phương thức

- **GET** hoặc **POST**
- Hỗ trợ CORS: `Access-Control-Allow-Origin: *`

### Tham số

| Tham số   | Bắt buộc      | Mô tả                                         |
| --------- | ------------- | --------------------------------------------- |
| `item_id` | Một trong hai | ID sản phẩm Shopee (số).                      |
| `url`     | Một trong hai | URL sản phẩm Shopee (đầy đủ hoặc short link). |

**Lưu ý:** Cần truyền **ít nhất một** trong hai: `item_id` hoặc `url`. Nếu truyền `url`, API sẽ tự trích `item_id` từ URL.

### Định dạng URL được hỗ trợ

- URL đầy đủ: `https://shopee.vn/product/<shop_id>/<item_id>`
- Dạng path: `-i.<shop_id>.<item_id>`, `/product/<shop_id>/<item_id>`, `/opaanlp/<shop_id>/<item_id>`
- Query: `?item_id=...` hoặc `?itemId=...`
- **Short link:** `s.shopee.vn`, `vn.shp.ee` — API sẽ resolve sang URL gốc (timeout 3s) rồi lấy `item_id`.

---

## Ví dụ request

### Theo item_id

```
GET https://data.addlivetag.com/product-data/product-data.php?item_id=1589295236
```

### Theo URL sản phẩm

```
GET https://data.addlivetag.com/product-data/product-data.php?url=https://shopee.vn/product/38003654/1589295236
```

### POST (tùy chọn)

```
POST https://data.addlivetag.com/product-data/product-data.php
Content-Type: application/x-www-form-urlencoded

item_id=1589295236
```

hoặc

```
url=https://shopee.vn/product/38003654/1589295236
```

---

## Response

### Content-Type

`application/json; charset=utf-8`

### Thành công (có dữ liệu sản phẩm)

**Ví dụ:** `?item_id=1589295236`

```json
{
  "status": "success",
  "productInfo": {
    "itemId": 1589295236,
    "productName": "Áo Len Nam Nữ Cổ Lọ Quảng Châu Form Basic Dài Tay Dày Dặn Mềm Mịn Cực Ấm Hàn Quốc Nhiều Màu DYACI AL83",
    "shopName": "DYACI",
    "price": 122200,
    "sales": 990,
    "imageUrl": "https://cf.shopee.vn/file/vn-11134207-7r98o-lpg62kjcq15n6b",
    "productLink": "https://shopee.vn/product/38003654/1589295236",
    "rating": "4.80",
    "commission": 21996,
    "sellerComFinal": 16497,
    "shopeeComFinal": 5499,
    "isXtra": true,
    "hasSellerCommission": true,
    "hasShopeeCommission": true,
    "isCapped": false,
    "isLimitCap": false,
    "cap": 50000,
    "capRaw": 50000,
    "capAfterRate": 50000,
    "lastUpdate": "2026-03-12 07:39:03",
    "dataSource": "db"
  }
}
```

### Giải thích trường `productInfo`

| Trường                | Kiểu          | Mô tả                                                                             |
| --------------------- | ------------- | --------------------------------------------------------------------------------- |
| `itemId`              | number        | ID sản phẩm Shopee.                                                               |
| `productName`         | string        | Tên sản phẩm.                                                                     |
| `shopName`            | string        | Tên shop.                                                                         |
| `price`               | number        | Giá hiện tại (VNĐ).                                                               |
| `sales`               | number        | Đã bán (historical sold).                                                         |
| `imageUrl`            | string        | URL ảnh chính.                                                                    |
| `productLink`         | string        | Link sản phẩm Shopee.                                                             |
| `rating`              | string/number | Đánh giá sao.                                                                     |
| `commission`          | number        | Tổng hoa hồng sau thuế/user rate (VNĐ).                                           |
| `sellerComFinal`      | number        | Hoa hồng seller (sau user rate & tax).                                            |
| `shopeeComFinal`      | number        | Hoa hồng Shopee (sau cap 50k & giới hạn 4.5%).                                    |
| `isXtra`              | boolean       | Có tham gia Xtra (seller commission).                                             |
| `hasSellerCommission` | boolean       | Có hoa hồng từ seller.                                                            |
| `hasShopeeCommission` | boolean       | Có hoa hồng từ Shopee.                                                            |
| `isCapped`            | boolean       | Hoa hồng Shopee bị giới hạn cap.                                                  |
| `isLimitCap`          | boolean       | Trùng logic với `isCapped`.                                                       |
| `cap`                 | number        | Cap hoa hồng áp dụng (sau rate).                                                  |
| `capRaw`              | number        | Cap gốc (50,000 VNĐ).                                                             |
| `capAfterRate`        | number        | Cap sau khi áp dụng user rate.                                                    |
| `lastUpdate`          | string        | Thời điểm cập nhật dữ liệu (datetime).                                            |
| `dataSource`          | string        | Nguồn: `"api"` (mới từ Shopee), `"db"` (cache), `"fallback"` (không có chi tiết). |

### Khi dùng cache hoặc API lỗi

- Nếu có dữ liệu cache: vẫn trả `status: "success"` và có thể kèm `"warning": "Using cached data - API fetch failed"`.
- Nếu không tìm thấy sản phẩm và API lỗi: vẫn `status: "success"`, `productInfo` với các field chính = `null` hoặc 0, và `"warning": "Product not found in database and API fetch failed"`.

### Lỗi (HTTP 4xx/5xx)

**Thiếu tham số (400):**

```json
{
  "status": "error",
  "message": "item_id or valid Shopee URL is required"
}
```

**Rate limit (429):**

```json
{
  "status": "error",
  "message": "Rate limit exceeded. Please try again later."
}
```

**Lỗi server (500):**

```json
{
  "status": "error",
  "message": "Internal server error",
  "error": "..."
}
```

---

## Rate limit

- Giới hạn theo **IP** (qua Cloudflare / X-Forwarded-For / REMOTE_ADDR).
- **Khi lấy từ API Shopee:** 300 request/phút.
- **Khi lấy từ database (cache):** 2000 request/phút.
- Vượt giới hạn: HTTP **429** và JSON `status: "error"` như trên.

---

## Cache & nguồn dữ liệu

- Dữ liệu sản phẩm (giá, hoa hồng, v.v.) được lưu DB và **cache ~24 giờ** (cấu hình bằng `CACHE_DURATION`).
- Luồng xử lý:
  1. Có bản ghi trong DB và chưa hết hạn cache → trả từ **db** (`dataSource: "db"`), không gọi Shopee API.
  2. Hết hạn hoặc chưa có trong DB → gọi Shopee API, lưu DB, trả từ **api** (`dataSource: "api"`).
  3. Gọi API Shopee lỗi nhưng có bản ghi cũ → trả cache kèm `warning`.
  4. Không có DB và API lỗi → trả `productInfo` tối thiểu và `dataSource: "fallback"` với `warning`.

---

## Hoa hồng (commission)

- **Seller commission:** không cap, tính theo tỷ lệ seller và áp dụng user rate & tax.
- **Shopee commission:** cap 50,000 VNĐ (raw), và giới hạn tối đa 4.5% giá sản phẩm; giá trị cuối là mức thấp hơn trong các giới hạn đó (và sau user rate).
- `commission` = `sellerComFinal` + `shopeeComFinal` (đơn vị VNĐ).

---

## OPTIONS (CORS)

- Method: `OPTIONS` được hỗ trợ; server trả HTTP 200 không body để preflight.

---

## Tóm tắt endpoint

| Mục        | Giá trị                                                     |
| ---------- | ----------------------------------------------------------- |
| URL        | `https://data.addlivetag.com/product-data/product-data.php` |
| Method     | GET, POST, OPTIONS                                          |
| Query/body | `item_id` (số) **hoặc** `url` (URL Shopee)                  |
| Response   | JSON, `status` + `productInfo` hoặc `message` (khi lỗi)     |
| Rate limit | 300/phút (api), 2000/phút (db), theo IP                     |
| Timezone   | Asia/Ho_Chi_Minh (cho `lastUpdate`)                         |
