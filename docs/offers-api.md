# Offers API — hoa hồng theo sản phẩm / shop / chiến dịch

Nhóm endpoint đọc dữ liệu **ưu đãi hoa hồng** của Shopee Affiliate: sản phẩm có hoa hồng,
shop/brand có hoa hồng, chiến dịch của sàn, hồ sơ shop, và lịch sử thay đổi hoa hồng.

**Base URL:** `https://data.addlivetag.com/offers/`

> ⚠️ **API Key bắt buộc từ 01/10/2026** — gửi `X-API-Key: <key>` hoặc `&key=<key>`.
> Xem [api-key.md](api-key.md).

> Phạm vi sử dụng: học tập, nghiên cứu kỹ thuật, vận hành nội bộ phi thương mại. Dữ liệu
> qua nguồn không chính thống, có thể sai số hoặc chậm cập nhật — tự kiểm chứng trước khi
> ra quyết định.

---

## Danh sách endpoint

| Endpoint | Trả về | Có gọi Shopee không |
| --- | --- | --- |
| [`product-offer.php`](#1-product-offerphp) | Sản phẩm có hoa hồng, theo từ khoá | Có (cache 30 phút) |
| [`shop-offer.php`](#2-shop-offerphp) | Shop/brand có hoa hồng, theo từ khoá | Có (cache 30 phút) |
| [`shopee-offer.php`](#3-shopee-offerphp) | Ưu đãi/chiến dịch của sàn | Có (cache 30 phút) |
| [`shop-products.php`](#4-shop-productsphp) | Sản phẩm có hoa hồng **của một shop** | Có (cache 10 phút) |
| [`shop-info.php`](#5-shop-infophp) | Hồ sơ công khai của shop (follower, tuổi shop, rating thật) | Có (cache 7 ngày) |
| [`shop-check.php`](#6-shop-checkphp) | Tra hoa hồng shop theo `shopId` | Ưu tiên DB, gọi nguồn khi cần |
| [`shop-changes.php`](#7-shop-changesphp) | Shop có hoa hồng **thay đổi** trong N ngày | Không — thuần đọc DB |

---

## Quy ước chung

- **Method:** GET hoặc POST. Có CORS (`Access-Control-Allow-Origin: *`) và `OPTIONS`.
- **Content-Type:** `application/json; charset=utf-8`.
- **`status`:** `"success"` hoặc `"error"`.
- **`dataSource`:** `"api"` (vừa gọi nguồn) hoặc `"db"` (trả từ cache).
- **Fallback:** nguồn lỗi mà DB còn bản lưu → vẫn trả bản lưu thay vì trả rỗng.
- **`shopeeAccountNote`:** ký hiệu tài khoản Shopee đã phục vụ request (`bc`, `mp`…).
  Hữu ích khi đối chiếu vì mỗi account ở một tier hoa hồng khác nhau.
- **Không trả `offer_link`.** Đó là link affiliate dựng bằng tài khoản của hệ thống — trả ra
  ngoài thì hoa hồng chạy về chủ hệ thống chứ không về bạn. Dùng `link`/`originalLink` rồi tự
  dựng link affiliate bằng affiliate_id của mình.

### Rate limit

| Luồng | Trần (request/phút/IP) |
| --- | --- |
| Trả từ cache | 1.000 |
| Phải gọi API nguồn | 100 |

Chưa có API Key thì còn 40% các con số trên. Vượt trần → HTTP **429**.

### Tham số dùng chung cho 3 endpoint danh sách

| Tham số | Mặc định | Mô tả |
| --- | --- | --- |
| `keyword` | `""` | Từ khoá. Bỏ trống = lấy danh sách chung. |
| `sortType` | `1` | Kiểu sắp xếp của Shopee. |
| `page` | `1` | Trang. |
| `limit` | `10` | Số dòng/trang, **tối đa 50**. |

---

## 1. `product-offer.php`

Sản phẩm có hoa hồng theo từ khoá.

```http
GET /offers/product-offer.php?keyword=ao%20len&sortType=1&page=1&limit=10
```

```json
{
    "status": "success",
    "dataSource": "api",
    "page": 1,
    "limit": 2,
    "hasNextPage": true,
    "count": 2,
    "products": [
        {
            "itemId": 41013426581,
            "name": "Áo len Giáng sinh …",
            "link": "https://shopee.vn/product/1608579574/41013426581",
            "image": "https://cf.shopee.vn/file/sg-11134201-22100-mgovp2ruplivd5",
            "catIds": [100011, 100049, 0],
            "commissionRate": 0.07,
            "price": 425520,
            "priceMin": 425520,
            "priceMax": 549720,
            "sales": 1,
            "rating": 4.7,
            "shopId": 1608579574,
            "shopName": "twsxin6134g.vn",
            "startTime": 1788195600,
            "endTime": 1790787599
        }
    ],
    "shopeeAccountNote": "mp"
}
```

| Trường | Kiểu | Mô tả |
| --- | --- | --- |
| `itemId` | number | ID sản phẩm. |
| `commissionRate` | number | Tỷ lệ hoa hồng dạng thập phân (`0.07` = 7%). |
| `price` / `priceMin` / `priceMax` | number | Giá hiện tại và khoảng giá theo biến thể (VNĐ). |
| `sales` | number | Đã bán (luỹ kế). |
| `catIds` | number[] | Đường dẫn danh mục Shopee (nguyên bản, **có thể còn số `0` đệm**). |
| `startTime` / `endTime` | number | Unix timestamp hiệu lực của mức hoa hồng. |

> Cần **giá + hoa hồng đã tính ra tiền** cho một sản phẩm cụ thể thì dùng
> [Product Data API](../product-data-api.md), không dùng endpoint này.

---

## 2. `shop-offer.php`

Shop/brand có hoa hồng theo từ khoá.

```http
GET /offers/shop-offer.php?keyword=dyaci&limit=10
```

```json
{
    "status": "success",
    "dataSource": "api",
    "page": 1,
    "limit": 2,
    "hasNextPage": false,
    "count": 1,
    "shops": [
        {
            "shopId": 38003654,
            "name": "DYACI",
            "type": [2],
            "commissionRate": 0.205,
            "rating": 4.8,
            "remainingBudget": 0,
            "image": "https://cf.shopee.vn/file/vn-11134216-7r98o-lmiegtowj8infd",
            "link": "https://shopee.vn/shop/38003654",
            "startTime": 1700067600,
            "endTime": 32503651199
        }
    ],
    "shopeeAccountNote": "bc"
}
```

`remainingBudget = 0` nghĩa là ngân sách hoa hồng của chiến dịch đã cạn (hoặc Shopee không
công bố), **không** phải shop hết hoa hồng.

---

## 3. `shopee-offer.php`

Ưu đãi/chiến dịch do chính sàn chạy (không gắn với một shop cụ thể).

```http
GET /offers/shopee-offer.php?limit=10
```

```json
{
    "status": "success",
    "dataSource": "api",
    "page": 1, "limit": 2, "hasNextPage": true, "count": 2,
    "offers": [
        {
            "name": "Selected KOLs - Special commission rate in Sep'2026 - Health",
            "type": 2,
            "commissionRate": 0.04,
            "image": "https://cf.shopee.vn/file/49119e891a44fa135f5f6f5fd4cfc747",
            "link": "https://shopee.vn/Health-cat.11036345",
            "startTime": 1788800400,
            "endTime": 1790787599
        }
    ],
    "shopeeAccountNote": "bc"
}
```

---

## 4. `shop-products.php`

Sản phẩm có hoa hồng **của một shop**. Khác `product-offer.php` ở chỗ lọc theo `shopId`
thay vì từ khoá.

```http
GET /offers/shop-products.php?shopId=38003654&page=1&limit=50&sortType=1
```

| Tham số | Bắt buộc | Mô tả |
| --- | --- | --- |
| `shopId` | ✔ | ID shop. |
| `page` / `limit` / `sortType` | | Như bảng chung. **`limit` tối đa 50** — vượt là Shopee trả lỗi `11001`. |

- **Cache 10 phút** (ngắn hơn mặc định 30 phút vì cron theo dõi shop quét mỗi 15 phút).
- Response: `status`, `dataSource`, `shopId`, `shopName`, `page`, `limit`, `hasNextPage`,
  `count`, `products[]` — `products[]` cùng dạng với `product-offer.php`.

**Ứng dụng:** API nguồn **không có field ngày tạo sản phẩm**, nên cách phát hiện "sản phẩm mới
của shop" là quét định kỳ rồi diff tập `itemId` giữa hai lần.

---

## 5. `shop-info.php`

Hồ sơ công khai của shop: số người theo dõi, tuổi shop, rating nhiều số lẻ.

```http
GET /offers/shop-info.php?shopId=123456
GET /offers/shop-info.php?shopIds=123,456,789        # tối đa 20 shop/lượt
```

**Vì sao cần endpoint riêng:** `shopOfferV2` chỉ trả `ratingStar` làm tròn 1 số lẻ và
**không có** số follower hay số đánh giá. Hệ quả: shop mở 2 tuần, 0 follower, đúng 1 đánh giá
5 sao trông y hệt shop 5.000 follower rating 4,79 — không chấm chất lượng được.

- Nguồn: `get_shop_base` — endpoint public của Shopee, không dính anti-bot `90309999` như
  `get_shop_info`/`search_items` (đo 07/08/2026).
- **Cache 7 ngày**: hồ sơ shop đổi rất chậm, TTL ngắn chỉ tốn request mà không đổi kết quả.
- Trần: **20 shop/request**, timeout 12 giây/shop.

```json
{
    "status": "success",
    "count": 1,
    "shops": [
        { "shopId": 38003654, "found": false, "dataSource": "error", "cachedAt": null }
    ]
}
```

Shop lấy được thì `found: true` kèm các trường:

| Trường | Kiểu | Mô tả |
| --- | --- | --- |
| `name` | string | Tên shop. |
| `followers` | number | Số người theo dõi — tín hiệu tách shop thật khỏi shop dropship dựng hàng loạt rõ nhất. |
| `itemCount` | number | Số sản phẩm đang bán. |
| `ratingStar` | number | Rating **nhiều số lẻ**: `5` chẵn ⇒ rất ít đánh giá; `4.792711` ⇒ đánh giá dày. |
| `responseRate` | number | Tỷ lệ phản hồi chat (%). |
| `responseTimeSec` | number | Thời gian phản hồi trung bình (giây). |
| `createdAt` | number | Unix timestamp ngày mở shop — dùng tính tuổi shop. |
| `lastActiveAt` | number | Lần hoạt động gần nhất (unix). |
| `verified` / `officialShop` / `preferredPlus` / `choiceShop` | boolean | Các nhãn của Shopee. |
| `vacation` | boolean | Shop đang tạm nghỉ. |
| `fetchedAt` | number | Thời điểm lấy dữ liệu (unix). |

**Luôn kiểm tra `found` trước khi đọc** — Shopee có lúc chặn hoặc shop không tồn tại, khi đó
`found: false` và `dataSource: "error"`.

---

## 6. `shop-check.php`

Tra hoa hồng của shop **theo `shopId`** — dùng khi đã biết shop cần kiểm tra, không phải đi
tìm theo từ khoá.

```http
GET  /offers/shop-check.php?shopId=327887078
GET  /offers/shop-check.php?shopIds=1,2,3&fresh=21600&history=30
POST /offers/shop-check.php          # shopIds=1,2,3… — danh sách dài thì dùng POST
```

| Tham số | Mặc định | Mô tả |
| --- | --- | --- |
| `shopId` / `shopIds` | — (bắt buộc) | Một hoặc nhiều id, ngăn cách bằng dấu phẩy. |
| `fresh` | `21600` (6 giờ) | Dữ liệu trong DB mới hơn mốc này (giây) thì dùng luôn, không gọi nguồn. |
| `live` | `1` | `0` = **chỉ đọc DB nội bộ**, rất nhanh nhưng có thể thiếu shop. |
| `history` | `0` | Số ngày lấy tóm tắt biến động hoa hồng. `0` = bỏ qua. |

```json
{
    "status": "success",
    "requested": 1,
    "count": 1,
    "live": { "used": 0, "limit": 60, "rateLimited": false },
    "pending": [],
    "shops": [
        {
            "shopId": 38003654,
            "name": "DYACI",
            "hasCommission": true,
            "commissionRate": 0.205,
            "commissionPercent": 20.5,
            "commissionAccount": "bc",
            "rating": 4.8,
            "remainingBudget": 0,
            "image": "https://cf.shopee.vn/file/…",
            "link": "https://shopee.vn/shop/38003654",
            "startTime": 1700067600,
            "endTime": 32503651199,
            "source": "db",
            "lastSeen": "2026-09-24 01:40:30",
            "history": {
                "firstRate": 0.205, "lastRate": 0.205, "delta": 0,
                "minRate": 0.205, "maxRate": 0.205, "points": 1,
                "from": "2026-09-24", "to": "2026-09-24"
            }
        }
    ]
}
```

### ⚠️ `pending[]` — đừng hiểu nhầm

`pending[]` chứa id **chưa kiểm được trong request này** (hết ngân sách thời gian hoặc nguồn
lỗi). Đây **không phải** "shop không có hoa hồng". Gọi lại đúng các id đó ở lượt sau.

Shop thật sự không có hoa hồng thì nằm trong `shops[]` với `hasCommission: false`.

---

## 7. `shop-changes.php`

Shop có hoa hồng **thay đổi** trong N ngày gần nhất. Nguồn là bảng lịch sử snapshot theo
ngày, tự tích luỹ mỗi khi bất kỳ endpoint offers nào chạm tới shop.

**Chỉ đọc DB, không gọi Shopee** ⇒ không đụng hạn mức nguồn, gọi thoải mái cho cron cảnh báo.

```http
GET /offers/shop-changes.php?days=7&direction=down&minDelta=1&limit=50&page=1
GET /offers/shop-changes.php?shopIds=1,2,3&days=30&detail=1
```

| Tham số | Mặc định | Mô tả |
| --- | --- | --- |
| `days` | `7` | Cửa sổ so sánh, 1–365. |
| `shopIds` | *(trống = toàn bộ)* | Giới hạn trong danh sách id, tối đa 500. |
| `direction` | `any` | `up` \| `down` \| `any`. |
| `minDelta` | `0.5` | Biên độ tối thiểu tính bằng **điểm phần trăm** (`1` = 1%). |
| `limit` / `page` | `50` / `1` | Phân trang, `limit` tối đa 500. |
| `detail` | `0` | `1` = kèm mảng điểm dữ liệu từng ngày (`series[]`). |

```json
{
    "status": "success",
    "days": 7, "direction": "any", "minDelta": 0.5,
    "page": 1, "limit": 2, "count": 2, "hasNextPage": true,
    "changes": [
        {
            "shopId": 76125677,
            "name": "Minimal Vietnam",
            "fromRate": 0.12,
            "toRate": 0.57,
            "delta": 0.45,
            "deltaPercent": 45,
            "direction": "up",
            "minRate": 0.12,
            "maxRate": 0.57,
            "points": 3,
            "from": "2026-09-18",
            "to": "2026-09-20",
            "image": "https://cf.shopee.vn/file/…",
            "link": "https://shopee.vn/shop/76125677",
            "lastSeen": "2026-09-19 18:12:27"
        }
    ]
}
```

`points` là số ngày **có dữ liệu** trong cửa sổ — `points: 1` nghĩa là chỉ thấy shop đó đúng
một ngày, đừng coi là xu hướng.

---

## Lỗi

```json
{ "status": "error", "message": "Thiếu hoặc sai tham số shopId / shopIds." }
```

| HTTP | Khi nào |
| --- | --- |
| 400 | Thiếu tham số bắt buộc, hoặc sai định dạng. |
| 429 | Vượt rate limit (xem bảng ở trên). |
| 500 | Lỗi phía server / nguồn không phản hồi và không có bản lưu nào. |
