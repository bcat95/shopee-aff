# Price & Commission History API

Đọc **lịch sử giá** và **lịch sử hoa hồng** của nhiều sản phẩm Sàn Cam trong một request,
tuỳ chọn theo từng loại.

**Base URL:** `https://data.addlivetag.com/price-tracking/history.php`

> ⚠️ **API Key bắt buộc từ 01/10/2026** — gửi header `X-API-Key: <key>` hoặc `&key=<key>`.
> Trước mốc đó, request chưa có key chỉ còn 40% hạn mức. Xem [api-key.md](api-key.md).

Endpoint này **không gọi API nguồn lần nào** — thuần đọc kho dữ liệu đã tích luỹ trong
database, nên không tiêu quota Sàn Cam và chịu được tần suất cao hơn hẳn các endpoint có
fetch.

> Tuyên bố pháp lý & phạm vi sử dụng giống các endpoint khác: học tập, nghiên cứu kỹ thuật,
> vận hành nội bộ phi thương mại. Dữ liệu có thể sai số, tự kiểm chứng trước khi ra quyết định.

---

## Dữ liệu đến từ đâu

| Nguồn | Vai trò |
| ----- | ------- |
| `price_history` | Giá theo ngày |
| `commission_history` | Hoa hồng theo ngày |
| `price_statistics` | Thống kê toàn thời gian đã tính sẵn (khối `allTime`) |
| `products` | Tên, ảnh, link sản phẩm (khối `product`) |

**Độ phân giải là NGÀY, không phải giờ.** Cả hai bảng lịch sử đều `UNIQUE (product_id,
recorded_date)` — mỗi sản phẩm một dòng mỗi ngày. Giá đổi nhiều lần trong cùng một ngày thì
chỉ còn lại lần ghi cuối của ngày đó. Cần giá hiện tại thì dùng
[Product Data Batch API](product-data-batch.md).

---

## Tham số

| Tham số | Bắt buộc | Mô tả |
| ------- | -------- | ----- |
| `item_ids` | ✅ | Danh sách item_id: mảng JSON, `item_ids[]=…`, hoặc chuỗi ngăn bằng phẩy / xuống dòng / khoảng trắng. Nhận cả URL sản phẩm. Alias: `itemIds`, `ids`, `item_id`, `urls`, `url`. **Tối đa 50 sản phẩm.** |
| `type` | | `price` (mặc định) · `commission` · `both`. Alias: `gia`, `hoahong`, `hh`, `all`, `prices`, `commissions`. |
| `days` | | Số ngày gần nhất. Mặc định `90`, tối đa `730`. |
| `from` · `to` | | Khoảng ngày cụ thể (`YYYY-MM-DD`). Có `from`/`to` thì bỏ qua `days`; gửi ngược thứ tự thì tự đảo lại; khoảng rộng quá `730` ngày thì cắt phần xa nhất, giữ phần gần hiện tại. |
| `changes_only` | | `1` = chỉ trả những ngày **có thay đổi**. Xem mục dưới. |
| `base_rate` · `cap` | | Tier tài khoản affiliate của bạn. **Bắt buộc nếu muốn đọc chuỗi hoa hồng cho đúng** — xem mục dưới. |
| `format` | | `chart` = trả mảng song song `labels[]`/`data[]` thay cho mảng object. Cắm thẳng vào Chart.js. |
| `no_product` | | `1` = bỏ khối `product` cho response gọn hơn. Alias: `skip_product`. |

Hỗ trợ **GET** và **POST** (form hoặc JSON), bật CORS.

---

## ⚠️ Chuỗi hoa hồng: phải khai `base_rate` mới đọc đúng

**% HH Sàn ghi trong lịch sử là của TÀI KHOẢN đã fetch ngày hôm đó, không phải thuộc tính
cố định của sản phẩm.** Hệ thống xoay vòng nhiều tài khoản affiliate để chia quota, mỗi tài
khoản một tier khác nhau (3,5% / 5% / 8%…). Hệ quả: chuỗi nhảy loạn dù sàn chẳng đổi gì.

Đo trên sản phẩm `45703342049`, **giá đứng yên suốt 56 ngày**:

| | Không khai tier | Khai `base_rate=8&cap=20000` |
| --- | --- | --- |
| Số lần "đổi" hoa hồng | **15** | **4** |
| % HH Sàn | 3,5 → 5,5 → 3 → 0 → 2,5 → 8 → 4 → 2,5 | 8 suốt (trừ ngày dữ liệu hỏng) |

11 trong 15 lần đổi đó là **giả**.

**Khai `base_rate` (và `cap`)** thì toàn chuỗi được tính lại theo đúng một tier: `shopeeRate`
của mọi ngày có rate > 0 được thay bằng tier bạn khai, số tiền tính lại từ `priceSnapshot`,
và mỗi điểm vẫn giữ khối `recorded` chứa số gốc để đối chiếu. Response có `normalized: true`.

Quy tắc giữ nguyên như Product Data API: rate gốc = **0** thì giữ 0 kể cả khi khai
`base_rate` — ngành hàng không có HH Sàn thì tier nào cũng không được.

**Không khai** thì endpoint trả số thô nhưng **tự tố cáo**:

- `notice` ở cấp response giải thích vì sao chuỗi có thể nhiễu.
- `commission.warning` ở từng sản phẩm khi % HH Sàn đổi nhiều hơn hẳn % HH seller.
- `stats.sellerRateChangeCount` — số lần đổi của **% HH seller**, thứ **không phụ thuộc
  tier** nên đáng tin. So với `stats.shopeeRateRecordedChangeCount` là biết chuỗi nhiễu cỡ nào.

### Cờ `suspect`

Ngày nào có rate = 0 lẻ loi giữa chuỗi toàn rate > 0 thì điểm đó được đánh
`suspect: true` và đếm vào `stats.suspectCount`. Gần như chắc chắn là dữ liệu hỏng chứ không
phải sàn bỏ hoa hồng một ngày rồi trả lại. **Endpoint không tự sửa số liệu** — chỉ đánh dấu
để bạn tự quyết bỏ hay giữ.

---

## `format=chart` — cắm thẳng vào thư viện biểu đồ

Chart.js và hầu hết thư viện khác nhận `labels[]` + `data[]` riêng, nên client đang phải tự
map lại từ mảng object. `format=chart` trả sẵn dạng đó, thay cho `points`:

```json
"price": {
  "count": 5,
  "chart": {
    "labels":        ["2026-06-25", "2026-06-30", "2026-07-07", "2026-08-04", "2026-08-06"],
    "price":         [175000, 115100, 250000, 180000, 175000],
    "originalPrice": [175000, 115100, 250000, 180000, 175000],
    "discountPercent": [0, 0, 0, 0, 0]
  },
  "stats": { ... }, "allTime": { ... }
}
```

Khối `commission.chart` có `labels`, `totalRatePercent`, `sellerRatePercent`,
`shopeeRatePercent`, `commission`, `sellerComFinal`, `shopeeComFinal`.

Nhẹ hơn đáng kể — cùng một yêu cầu 90 ngày, cả hai loại: **47,5 KB → 8,7 KB** (giảm 82%).

```js
const r = await fetch(url + '&format=chart&base_rate=8').then(r => r.json());
const c = r.items[0].price.chart;
new Chart(ctx, {
  type: 'line',
  data: { labels: c.labels, datasets: [{ label: 'Giá', data: c.price, stepped: true }] }
});
```

Dùng kèm `changes_only=1` thì chuỗi thưa — đặt `stepped: true` để đường biểu diễn đúng ý
nghĩa "giá giữ nguyên tới mốc sau".

---

## `changes_only` — nên bật

Đa số sản phẩm đứng giá hàng tuần. Đo trên dữ liệu thật: một sản phẩm 90 ngày chỉ có **4 lần
đổi giá**, response từ **18 KB xuống 2,1 KB** (giảm 88%) mà không mất thông tin nào — giá
giữa hai mốc chính là giá của mốc trước.

```
changes_only=0 → 90 điểm, 18 KB
changes_only=1 →  5 điểm, 2,1 KB   (mốc đầu + 4 lần đổi)
```

Mỗi điểm có cờ `changed`: `false` ở mốc đầu tiên, `true` ở mỗi lần đổi.

Với hoa hồng, "thay đổi" tính theo **tổng % hoa hồng**, không theo số tiền — số tiền phụ
thuộc giá nên giá nhúc nhích là tiền đổi theo, lọc theo tiền sẽ ra gần như mọi ngày.

**`stats` luôn tính trên toàn bộ ngày trong khoảng hỏi**, kể cả khi bật cờ này. Bật cờ chỉ
làm gọn mảng `points`, không làm sai thống kê.

---

## Ví dụ request

```bash
# Lịch sử giá 90 ngày
curl -s "https://data.addlivetag.com/price-tracking/history.php?item_ids=1589295236&days=90"

# Chỉ các mốc đổi giá
curl -s "https://data.addlivetag.com/price-tracking/history.php?item_ids=1589295236&days=90&changes_only=1"

# Lịch sử hoa hồng
curl -s "https://data.addlivetag.com/price-tracking/history.php?item_ids=45703342049&days=60&type=commission"

# Cả hai loại, nhiều sản phẩm, khoảng ngày cụ thể
curl -s https://data.addlivetag.com/price-tracking/history.php \
  -H 'Content-Type: application/json' \
  -d '{"item_ids":[1589295236,45703342049],"type":"both",
       "from":"2026-07-01","to":"2026-09-22","changes_only":1}'
```

---

## Response

```json
{
  "status": "success",
  "type": "both",
  "requested": 2,
  "returned": 2,
  "range": { "from": "2026-06-25", "to": "2026-09-22", "days": 90 },
  "changesOnly": true,
  "summary": { "withData": 2, "noData": 0, "invalid": 0 },
  "limits": {
    "maxItems": 50, "maxDays": 730, "maxPoints": 20000,
    "pointsReturned": 27, "truncated": false, "remaining": 598
  },
  "items": [ ... ]
}
```

Ba ô của `summary` cộng lại đúng bằng `requested`. `items` giữ **đúng thứ tự client gửi**.

### Mỗi phần tử `items[]`

| Trường | Mô tả |
| ------ | ----- |
| `input` | Giá trị gốc client gửi (id hay url) — để map ngược. |
| `itemId` | item_id đã trích được; `null` nếu đầu vào hỏng. |
| `status` | `success` · `no_data` (không có lịch sử trong khoảng) · `error` (đầu vào hỏng). |
| `reason` | Chỉ khi không phải `success`: `no_history_in_range` hoặc `invalid_item_id_or_url`. |
| `product` | Tên, link, ảnh, rating, đã bán. Vắng khi bật `no_product`. |
| `price` | Khối lịch sử giá — có khi `type` là `price` hoặc `both`. |
| `commission` | Khối lịch sử hoa hồng — có khi `type` là `commission` hoặc `both`. |

### Khối `price`

```json
{
  "count": 5,
  "points": [
    { "date": "2026-06-25", "price": 175000, "originalPrice": 175000,
      "discountPercent": 0, "currency": "VND", "flashSale": false,
      "stockAvailable": 0, "changed": false, "recordedTime": "2026-08-31 20:15:02" }
  ],
  "stats": {
    "min": 115100, "max": 250000, "avg": 193785.56,
    "first": 175000, "last": 175000,
    "minDate": "2026-06-30", "maxDate": "2026-07-07",
    "firstDate": "2026-06-25", "lastDate": "2026-09-22",
    "changeCount": 4, "change": 0, "changePercent": 0,
    "isLowest": false, "isHighest": false, "dayCount": 90
  },
  "allTime": {
    "currentPrice": 175000, "minPrice": 114000, "maxPrice": 250000,
    "avgPrice": 179279.19, "priceChange7d": 0, "priceChange30d": 0,
    "lowestPriceDate": null, "highestPriceDate": null,
    "lastPriceUpdate": "2026-09-22"
  }
}
```

`stats` tính trong **khoảng ngày đang hỏi**; `allTime` là **toàn thời gian**, lấy từ bảng
thống kê đã tính sẵn nên rộng hơn. Giá trả về đơn vị **VNĐ**.

### Khối `commission`

```json
{
  "count": 15,
  "points": [
    { "date": "2026-09-20",
      "sellerRate": 0.02, "shopeeRate": 0.04,
      "sellerRatePercent": 2, "shopeeRatePercent": 4, "totalRatePercent": 6,
      "sellerComFinal": 12234, "shopeeComFinal": 24468, "commission": 36702,
      "isXtra": true, "isCapped": false, "priceSnapshot": 611712,
      "changed": true, "suspect": false, "recordedTime": "2026-09-20 17:50:55",
      "recorded": { "shopeeRatePercent": 2.5, "sellerComFinal": 12234,
                    "shopeeComFinal": 15293, "commission": 27527 } }
  ],
  "stats": {
    "minTotalRatePercent": 2, "maxTotalRatePercent": 10,
    "firstTotalRatePercent": 5.5, "lastTotalRatePercent": 6,
    "minCommission": 12234, "maxCommission": 61171, "lastCommission": 36702,
    "changeCount": 14,
    "sellerRateChangeCount": 2, "shopeeRateRecordedChangeCount": 14,
    "suspectCount": 4, "normalized": false,
    "firstDate": "2026-07-25", "lastDate": "2026-09-22", "dayCount": 56
  }
}
```

Khối `recorded` chỉ xuất hiện khi bạn khai `base_rate` — nó giữ số **gốc đã ghi** để đối
chiếu, còn các trường ở cấp trên đã được tính lại theo tier bạn khai.

`sellerComFinal` / `shopeeComFinal` / `commission` là **số tiền VNĐ** tại thời điểm ghi nhận,
tính theo tier của tài khoản proxy — không áp `base_rate`/`cap` của bên gọi như Product Data
API. Muốn quy đổi sang tier của mình thì lấy `totalRatePercent` × `priceSnapshot`.
`priceSnapshot` là giá sản phẩm lúc ghi nhận, đơn vị VNĐ.

---

## Giới hạn

| Mục | Giá trị |
| --- | ------- |
| Sản phẩm / request | 50 |
| Khoảng ngày tối đa | 730 |
| Điểm dữ liệu / response | 20.000 |
| Rate limit | 600 sản phẩm/phút theo IP (đếm theo **số sản phẩm**, không theo số request) |

Chạm trần 20.000 điểm thì response bị cắt bớt, `limits.truncated = true` và có thêm
`warning` — **không bao giờ cắt âm thầm**. Gặp trường hợp này thì giảm số sản phẩm, thu hẹp
khoảng ngày, hoặc bật `changes_only=1`.

Hiệu năng đo trên dữ liệu thật:

| Request | Thời gian | Dung lượng |
| ------- | --------- | ---------- |
| 1 sản phẩm × 90 ngày, giá | 0,29 s | 18 KB |
| 1 sản phẩm × 90 ngày, giá, `changes_only` | 0,23 s | 2,1 KB |
| 50 sản phẩm × 90 ngày, cả hai loại | 0,79 s | 1,7 MB |
| 50 sản phẩm × 90 ngày, cả hai loại, `changes_only` | 0,67 s | 650 KB |

---

## Khác gì `check.php`?

| | `check.php` | `history.php` |
| --- | --- | --- |
| Số sản phẩm | 1 | tới 50 |
| Loại dữ liệu | chỉ giá | giá · hoa hồng · cả hai |
| Khoảng ngày | cố định 90 | `days` hoặc `from`/`to`, tới 730 |
| Lọc mốc đổi giá | không | `changes_only=1` |
| Từ chối khi dữ liệu cũ | có (quá 3 ngày là trả `Data not fresh`) | không, trả những gì đang có |

`check.php` giữ nguyên, không đổi gì — tool đang tích hợp chạy bình thường.

---

## Tóm tắt endpoint

| Mục | Giá trị |
| --- | ------- |
| URL | `https://data.addlivetag.com/price-tracking/history.php` |
| Method | GET, POST, OPTIONS |
| Đầu vào | `item_ids` (tối đa 50) + `type` + `days` hoặc `from`/`to` |
| Response | JSON: `status` + `range` + `summary` + `limits` + `items[]` |
| Gọi API nguồn | **Không** — thuần đọc DB |
| Timezone | Asia/Ho_Chi_Minh |
