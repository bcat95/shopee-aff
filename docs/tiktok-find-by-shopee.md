# Tìm sản phẩm TikTok Shop từ link Shopee (đối chiếu chéo sàn)

Đưa vào link Shopee → trả về danh sách sản phẩm TikTok Shop **có khả năng là cùng sản phẩm**, kèm điểm khớp và so sánh giá / hoa hồng hai sàn.

**Base URL:** `https://data.addlivetag.com/tiktok/find-by-shopee.php`

> ⚠️ **API Key bắt buộc từ 01/10/2026** — gửi header `X-API-Key: <key>` hoặc `&key=<key>`.
> Trước mốc đó, request chưa có key chỉ còn 40% hạn mức. Xem [api-key.md](api-key.md).

## Phải đọc trước khi dùng

Kết quả là **suy luận tự động theo tên + giá**, không phải xác nhận hai sàn bán cùng một món hàng. Luôn xem `matchConfidence` trước khi tin con số so sánh:

| `matchConfidence` | Nghĩa | Cách dùng |
| --- | --- | --- |
| `high` | Trùng mã model, hoặc điểm ≥ 0.80 | Gần như chắc chắn cùng sản phẩm |
| `medium` | Điểm 0.60–0.79 | Rất giống, nên nhìn lại bằng mắt |
| `low` | Dưới 0.60 | Nhiều khả năng **chỉ cùng loại hàng** |

Ca thật minh hoạ khác biệt: "Nồi Chiên Không Dầu Lock&Lock EJF357" trả về nồi chiên BlueStone / SmartChoice, tên gần giống và giá lệch dưới 15% — nhưng là sản phẩm khác hẳn, và được đánh `low`.

**Phạm vi theo creator.** TikTok không có tìm kiếm toàn sàn ẩn danh: kết quả là tập sản phẩm creator đó được phép quảng bá. Hai creator khác nhau tra cùng một link Shopee có thể ra kết quả khác nhau — vì vậy cache khoá theo `(shopee_item_id, creator_username)`.

---

## Nguồn dữ liệu

1. **Shopee** — `product-data/product-data.php` (tên, giá, ảnh, hoa hồng).
2. **TikTok** — RioHub `GET /partner/tiktok/affiliate/products/search` (API partner chính thống, xác thực API key).

Cấu hình trong `.env`:

```env
RIOHUB_API_KEY='rhk_...'
RIOHUB_CREATOR_USERNAME='ten_creator_da_ket_noi'
# RIOHUB_BASE_URL='https://riohub.vn/api/v1'
```

---

## Cách gọi

GET hoặc POST, có CORS.

| Tham số | Bắt buộc | Mô tả |
| --- | --- | --- |
| `url` | một trong ba | Link sản phẩm Shopee (kể cả short link `s.shopee.vn`). |
| `item_id` | một trong ba | `item_id` Shopee. |
| `name` + `price` | một trong ba | Bỏ qua bước Shopee, tìm thẳng theo tên + giá (tra tay / test). Chế độ này **không cache**. |
| `creator_username` | không | Mặc định lấy `RIOHUB_CREATOR_USERNAME`. |
| `limit` | không | Số kết quả, mặc định 5, tối đa 20. |
| `minScore` | không | Ngưỡng điểm khớp, mặc định `0.45`. |
| `priceBand` | không | Band giá ±%, mặc định `0.2` (±20%). `0` = không lọc giá. |
| `clear_cache` | không | `=1` → bỏ cache DB, luôn gọi RioHub. |
| `cacheTtlHours` | không | Thời hạn cache, mặc định `24`. |
| `debug` | không | `=1` → kèm khối `debug.queries` (từ khoá đã chạy, `filters_applied` dội về từ TikTok). |

```
GET /tiktok/find-by-shopee.php?url=https://shopee.vn/product/38003654/1589295236
GET /tiktok/find-by-shopee.php?item_id=1589295236&limit=3&minScore=0.6
GET /tiktok/find-by-shopee.php?name=Kem%20Ch%E1%BB%91ng%20N%E1%BA%AFng%20Anessa&price=450000
```

---

## Response

```json
{
  "status": "success",
  "sourceProduct": {
    "platform": "shopee",
    "itemId": 1589295236,
    "productName": "Áo Len Nam Nữ Cổ Lọ Quảng Châu ... DYACI AL83",
    "price": 175000,
    "commission": 35875,
    "commissionRatePercent": 20.5
  },
  "creatorUsername": "nnguynanh0",
  "queriesUsed": ["dyaci ao len", "ao len co", "ao len"],
  "candidateCount": 41,
  "count": 1,
  "matches": [{
    "productId": "1733991918024558051",
    "productName": "Áo Len Cổ Lọ Nam Nữ Quảng Châu Form Basic ... AL83",
    "shopName": "...",
    "price": 170000,
    "commissionRatePercent": 10,
    "commissionRateSource": "standard",
    "commission": 17000,
    "productLink": "https://shop.tiktok.com/view/product/1733991918024558051",
    "matchScore": 0.653,
    "matchConfidence": "high",
    "matchDetail": { "nameSim": 0.5882, "priceSim": 0.9429, "brandHit": false, "modelHit": true, "headHit": 1 }
  }],
  "bestMatch": { "...": "..." },
  "comparison": {
    "shopeePrice": 175000, "tiktokPrice": 170000, "priceDiff": -5000,
    "cheaperPlatform": "tiktok",
    "shopeeCommission": 35875, "tiktokCommission": 17000,
    "higherCommissionPlatform": "shopee",
    "matchConfidence": "high"
  },
  "dataSource": "api"
}
```

`comparison` có thêm `warning` khi `matchConfidence` không phải `high`.

### `commissionRateSource` — rate hoa hồng TikTok lấy từ đâu

| Giá trị | Nghĩa |
| --- | --- |
| `observed_creator` | `observed_commission` với `scope: creator` — đơn của chính creator này, đã gồm HH thưởng. Tin cậy nhất. |
| `observed_global` | `observed_commission` với `scope: global` — đơn của creator khác, chỉ dùng khi **cao hơn** rate chuẩn. |
| `standard` | `commission.rate` do shop công bố. |
| `none` | Sản phẩm không mở hoa hồng cho creator này (`commission` vắng hẳn khỏi response TikTok). |

TikTok không trả hoa hồng thưởng ở bất kỳ endpoint sản phẩm nào, nên `observed_commission` là cách duy nhất thấy rate thực. Nhưng `scope: global` **có thể thấp hơn** rate shop đang mở, nên code lấy `max(observed, standard)` chứ không thay thế mù. Thực tế đo được: `observed_commission` rất hiếm — 0/10 sản phẩm trong mẫu test đầu tiên.

---

## Thuật toán khớp

1. **Sinh từ khoá** từ tên Shopee: bỏ dấu, bỏ token marketing (`cao cap`, `chinh hang`, `form`, `basic`…), lấy token đầu (tên sản phẩm sàn VN đặt danh từ chính ở đầu rồi nhồi keyword phía sau). Sinh 1–3 truy vấn từ hẹp (có brand) tới rộng, **chạy hết** — truy vấn hẹp và rộng trả tập khác nhau.
2. **Lọc giá ngay trên server TikTok** qua `price_min`/`price_max` (±20% mặc định). Đã xác minh filter thật sự đi lên TikTok bằng `filters_applied`.
3. **Chấm điểm**: tên 0.45 · giá 0.20 · brand 0.15 · mã model 0.20. Thiếu brand/model trong tên nguồn → trọng số đó dồn về tên.
4. **Cổng `headHit`**: 2 token nội dung đầu ("áo len") phải có bên ứng viên, thiếu thì phạt tới 50%. Không có cổng này, khăn giấy "dày dai mềm mịn cao cấp" vẫn ăn điểm tên đáng kể khi so với áo len.
5. **Phạt khác mã**: nguồn có mã model mà ứng viên không mang mã đó → nhân 0.85.

**Mã model là tín hiệu mạnh nhất.** Hàng Quảng Châu bán chéo sàn giữ nguyên mã nhà cung cấp: Shopee "DYACI **AL83**" khớp TikTok "...Viscose Ấm Áp **AL83**", lệch giá 3%.

Điểm tên dùng *containment* (bao nhiêu token nguồn xuất hiện bên ứng viên) chứ không phải Jaccard — tên TikTok thường dài ngắn khác Shopee, phạt độ dài là sai.

**Không có tìm theo ảnh:** TikTok Shop Affiliate Open API không nhận ảnh đầu vào. Muốn chính xác hơn nữa thì phải tự làm pHash ảnh phía mình — hiện chưa làm.

---

## Cache & rate limit

- Cache DB bảng `cross_platform_match`, khoá `(shopee_item_id, creator_username)`, TTL mặc định 24h. Chỉ phần khớp được cache; `sourceProduct` (giá/HH Shopee) luôn lấy mới vì đổi nhanh hơn.
- Cột `verified` để xác nhận tay: `1` = đúng cùng sản phẩm, `-1` = khớp sai, `0` = chưa ai xác nhận. Dùng lâu dài thành từ điển mapping tin cậy hơn thuật toán.
- Rate limit theo IP: **30 request/phút** khi phải gọi API (1 lần match = 1 call Shopee + tối đa 3 call RioHub), **300 request/phút** khi trả từ cache. Vượt → HTTP 429.
- Hạn mức RioHub: 300 req/phút và 100.000 req/ngày mỗi key.

Import bảng: `docs/database/cross_platform_match.sql`.

---

## Lỗi

| Tình huống | HTTP | Response |
| --- | --- | --- |
| Thiếu `url`/`item_id`/`name` | 400 | `status: error` |
| Thiếu `creator_username` và `.env` cũng trống | 400 | `status: error` |
| Thiếu `RIOHUB_API_KEY` | 500 | `status: error` |
| Shopee API lỗi / không có sản phẩm | 502 | `status: error` |
| Creator chưa kết nối trên RioHub | 502 | message kèm `RioHub HTTP 404 (not_found: ...)` |
| Vượt rate limit | 429 | `status: error` |

Lưu ý mã lỗi RioHub cần phân biệt: `424 creator_token_invalid` là **token TikTok của creator hỏng** (creator phải kết nối lại), không phải lỗi API key — đừng retry, đừng xoay key.
