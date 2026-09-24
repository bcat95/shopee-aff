# Market Search API — toàn cảnh thị trường theo từ khoá

Tìm sản phẩm theo tên và trả kèm **chỉ số phân tích thị trường** trong một request: tổng số
sản phẩm, tổng lượt bán, doanh thu, số shop, khoảng giá, mức độ tập trung (HHI), điểm đại
dương xanh, top shop, và danh sách sản phẩm.

**Endpoint:** `https://data.addlivetag.com/search/market.php`

> **Không tiêu quota Shopee.** Endpoint chạy hoàn toàn trên dữ liệu đã crawl (Manticore +
> MariaDB), nên mở rộng được mà không chạm trần quota như các endpoint có gọi nguồn.

> Phạm vi sử dụng: học tập, nghiên cứu kỹ thuật, vận hành nội bộ phi thương mại.

---

## ⚠️ Giới hạn phải hiểu trước khi dùng

Ba điều dưới đây **phải hiển thị trên UI** nếu bạn đưa số liệu này cho người dùng cuối:

1. **Phạm vi là kho dữ liệu đã thu thập, KHÔNG phải toàn sàn Shopee.** Con số là "thị trường
   trong phần dữ liệu mình có", không phải thị phần thật.
2. **`market.totalSold` là ước tính DƯỚI thực tế** — nguồn không trả lượt bán cho mọi sản phẩm.
3. **`market` là số LUỸ KẾ** (từ trước tới nay). Số theo kỳ nằm riêng ở `last30Days` kèm
   `coverage` — chỉ khoảng 10–32% sản phẩm có đủ lịch sử để tính. **Đừng cộng hai khối này.**

Response tự kèm `scopeNote` nhắc lại điều 1 và 2.

---

## Cách gọi

GET, POST form, hoặc POST JSON. Thứ tự ưu tiên tham số: GET > POST > body JSON.

| Tham số | Bắt buộc | Mặc định | Mô tả |
| --- | --- | --- | --- |
| `q` | ✔ | — | Từ khoá, tối đa 200 ký tự. Alias: `keyword`. |
| `price_min` / `price_max` | | — | Lọc khoảng giá (VNĐ). |
| `cat_id` | | — | Lọc theo danh mục cấp 1. |
| `commission_min` | | — | Hoa hồng tối thiểu, dạng thập phân (`0.1` = 10%). |
| `sales_min` | | — | Lượt bán tối thiểu. |
| `include_gifts` | | `0` | `1` = **gộp cả** sản phẩm quà tặng không bán riêng vào số tổng. |
| `sort` | | `revenue_all` | Xem bảng dưới. |
| `limit` | | `100` | Số sản phẩm trả về, tối đa **200**. |
| `offset` | | `0` | Phân trang. |
| `no_cache` | | `0` | `1` = bỏ qua cache Redis (TTL **6 giờ**). |

### `sort` hợp lệ

`revenue_all` (mặc định, doanh thu luỹ kế — phủ 100% sản phẩm) · `revenue_30d` · `sales` ·
`sold_30d` · `price` · `comm_rate` · `growth` · `rating_star`

> Mặc định **loại** quà tặng khỏi số tổng vì chúng thổi phồng quy mô thị trường — đo được:
> 15,3% doanh thu của từ khoá "nước tẩy trang" đến từ 0,7% số sản phẩm là quà tặng.

### Ví dụ

```bash
curl "https://data.addlivetag.com/search/market.php?q=ao%20len&limit=20&sort=revenue_30d"
```

```bash
curl -X POST https://data.addlivetag.com/search/market.php \
  -H 'Content-Type: application/json' \
  -d '{"q":"nước tẩy trang","price_min":50000,"price_max":300000,"commission_min":0.1,"limit":50}'
```

---

## Response

```json
{
    "query": { "raw": "ao len", "normalized": "ao len", "matchMode": "phrase", "relaxed": false, "accentMode": "loose" },
    "giftsExcluded": { "applied": true, "products": 3, "revenue": 144760000, "note": "…" },
    "market": {
        "totalProducts": 38979,
        "totalShops": 3693,
        "totalSold": 137767,
        "totalRevenue": 29859773535,
        "avgPrice": 279482,
        "minPrice": 1000,
        "maxPrice": 10750000,
        "avgCommissionRate": 0.1159,
        "maxCommissionRate": 0.28,
        "productsWithSales": 5099,
        "sellThroughRate": 0.1308,
        "hhi": 0.0335,
        "blueOceanScore": 32.1
    },
    "last30Days": {
        "totalSold": 14092,
        "totalRevenue": 4321790207,
        "trackedProducts": 12543,
        "coverage": 0.3218,
        "note": "Only counts products with 8+ days of history…"
    },
    "topShops": [ { "shopId": 1410303826, "revenue": 3543543871, "products": 94 } ],
    "products": [
        {
            "itemId": 47564633105,
            "productName": "Áo Len WHO.A.U Steve Cable R-neck Pullover_WHKAGB921F",
            "shopName": "WHO.A.U Việt Nam",
            "imageUrl": "https://cf.shopee.vn/file/…",
            "productLink": "https://shopee.vn/product/1334586912/47564633105",
            "price": 985150,
            "sales": 667,
            "sold7d": 266,
            "sold30d": 461,
            "revenueAll": 657095050,
            "revenue30d": 454154150,
            "priceMin": 950380,
            "priceMax": 1159000,
            "priceCv": 0.0612,
            "growth": 2.4729,
            "daysTracked": 29,
            "commissionRate": 0.09,
            "shopId": 1334586912,
            "catId": 100017,
            "rating": 4.9
        }
    ],
    "paging": { "limit": 1, "offset": 0, "sort": "revenue_all" },
    "status": "success",
    "cached": false,
    "tookMs": 293,
    "scopeNote": "Crawled data only, not the whole Shopee marketplace…"
}
```

### Khối `market` (luỹ kế)

| Trường | Mô tả |
| --- | --- |
| `totalProducts` / `totalShops` | Số sản phẩm / số shop khớp từ khoá trong kho dữ liệu. |
| `totalSold` | Tổng lượt bán luỹ kế — **ước tính dưới thực tế**. |
| `totalRevenue` | `giá × lượt bán` cộng dồn (VNĐ). |
| `avgPrice` / `minPrice` / `maxPrice` | Khoảng giá của tập khớp. |
| `avgCommissionRate` / `maxCommissionRate` | Hoa hồng trung bình / cao nhất (thập phân). |
| `productsWithSales` | Số sản phẩm thực sự có lượt bán. |
| `sellThroughRate` | `productsWithSales / totalProducts` — tỷ lệ sản phẩm bán được. |
| `hhi` | Chỉ số tập trung Herfindahl–Hirschman theo doanh thu shop. Càng nhỏ càng phân mảnh (nhiều shop nhỏ), càng lớn càng bị vài shop thống trị. **Chỉ tính trên 10 shop doanh thu cao nhất**, nên là ước lượng *dưới* của mức tập trung thật. |
| `blueOceanScore` | Điểm "đại dương xanh" 0–100: cao = nhu cầu có mà cạnh tranh còn thưa. Tổng hợp từ doanh thu/sản phẩm, `1 - hhi`, tỷ lệ sản phẩm bán được và số lượng sản phẩm. ⚠️ **Ngưỡng chuẩn hoá hiện là đặt tạm, chưa hiệu chỉnh bằng dữ liệu thật** — dùng để *so sánh tương đối giữa các từ khoá*, đừng coi là con số tuyệt đối. |

### Khối `last30Days` (theo kỳ)

Chỉ tính trên sản phẩm có **từ 8 ngày lịch sử trở lên**. `coverage` cho biết tỷ lệ sản phẩm
đủ điều kiện (ví dụ `0.3218` = 32,18%). Số ở đây **không so sánh trực tiếp** được với khối
`market`.

### Mỗi sản phẩm trong `products[]`

| Trường | Mô tả |
| --- | --- |
| `sold7d` / `sold30d` | Lượt bán trong 7 / 30 ngày, tính từ lịch sử crawl. |
| `revenueAll` / `revenue30d` | Doanh thu luỹ kế / 30 ngày (VNĐ). |
| `priceCv` | Hệ số biến thiên giá — cao = giá nhảy nhiều (hay chạy sale). |
| `growth` | Tốc độ tăng trưởng lượt bán. |
| `daysTracked` | Số ngày có dữ liệu. **Nhỏ thì mọi chỉ số theo kỳ đều yếu**, đừng kết luận vội. |

> Không trả `offer_link`: đó là link affiliate dựng bằng tài khoản của hệ thống. Dùng
> `productLink` rồi tự gắn affiliate_id của mình (xem [Product Data API](../product-data-api.md)
> tham số `affid`).

---

## Lỗi

```json
{ "status": "error", "error": "Thiếu tham số q (từ khoá tìm kiếm)", "reason": "missing_query" }
```

| `reason` | HTTP | Khi nào |
| --- | --- | --- |
| `missing_query` | 400 | Không truyền `q`. |
| `query_too_long` | 400 | Từ khoá > 200 ký tự. |
| `empty_query` | 400 | Từ khoá sau chuẩn hoá không còn gì để tìm. |
| `search_unavailable` | 503 | Máy tìm kiếm (Manticore) tạm thời không phản hồi. |

Lưu ý: endpoint này trả thông điệp lỗi ở khoá **`error`** kèm `reason`, hơi khác các endpoint
còn lại (dùng `message`).
