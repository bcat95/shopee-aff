# Sàn Cam Product Data API — Batch (nhiều sản phẩm / 1 request)

Lấy thông tin + hoa hồng của **nhiều sản phẩm Shopee** trong một lần gọi. Dành cho tool
cần làm tươi số lượng lớn sản phẩm mỗi chu kỳ (noti giá, quét kho sản phẩm affiliate…).

**Base URL:** `https://data.addlivetag.com/product-data/product-data-batch.php`

Cùng bộ quy tắc cache / hoa hồng / danh mục với endpoint 1 sản phẩm
([product-data-api.md](../product-data-api.md)) — hai endpoint dùng chung `product-data-lib.php`,
không có bản logic thứ hai. Đọc tài liệu endpoint đơn trước để hiểu ý nghĩa từng trường
trong `productInfo`; ở đây chỉ nói phần khác biệt.

> Tuyên bố pháp lý & phạm vi sử dụng: giống endpoint đơn — học tập, nghiên cứu kỹ thuật,
> vận hành nội bộ phi thương mại. Dữ liệu từ nguồn không chính thống, tự kiểm chứng trước
> khi ra quyết định.

> ⚠️ **API Key bắt buộc từ 01/10/2026.** Gửi header `X-API-Key: <key>` (hoặc `&key=<key>`).
> Batch là nơi thấy rõ nhất khác biệt: chưa có key thì quota gọi nguồn chỉ còn 40% —
> 60 thay vì 150 sản phẩm/phút. Xem [api-key.md](api-key.md).

---

## Vì sao có endpoint này

Gọi endpoint đơn 100 lần tốn 100 request HTTP và 100 lượt tra dữ liệu riêng lẻ. Batch gom
lại còn **1 request HTTP và một lượt tra cho cả lô** — rẻ hơn nhiều bậc ở cả hai phía.

Nguyên tắc: **cache trước, nguồn sau.**

1. Sản phẩm đã có trong cache DB và còn hạn → trả ngay, rẻ, tính vào quota `db` (2.000/phút).
2. Sản phẩm chưa có / hết hạn cache → gọi Shopee API, tính vào quota `api` (150/phút, còn 60/phút nếu chưa có API Key).
3. Vượt trần gọi nguồn trong một request → trả **cache cũ** kèm cờ `stale`, không trả rỗng.

---

## Cách gọi

### Phương thức

- **POST** (khuyến nghị — danh sách dài không nhét vừa URL) hoặc **GET**
- Body: JSON (`application/json`) hoặc form (`application/x-www-form-urlencoded`)
- CORS: `Access-Control-Allow-Origin: *`, có hỗ trợ `OPTIONS`

### Tham số

| Tham số      | Bắt buộc | Mô tả                                                                                      |
| ------------ | -------- | ------------------------------------------------------------------------------------------ |
| `item_ids`   | Một trong| Danh sách item_id. Nhận mảng JSON, `item_ids[]=…`, hoặc chuỗi ngăn bằng `,` / xuống dòng / khoảng trắng. Alias: `itemIds`, `ids`, `item_id`. |
| `urls`       | Một trong| Danh sách URL sản phẩm Shopee (link gốc). Alias: `url`.                                     |
| `items`      | Một trong| Mảng hỗn hợp; phần tử là id/url, hoặc object để khai `sub_id` riêng từng sản phẩm.          |
| `base_rate`  | Không    | % HH Sàn của account bên gọi (`0.08` hoặc `8`). Áp cho **cả lô**.                          |
| `cap`        | Không    | Cap HH Sàn (VNĐ) của account bên gọi. Mặc định 40000. Áp cho cả lô.                        |
| `affid`      | Không    | Affiliate ID để dựng `affLink`. Alias: `aff_id`, `affiliate_id`.                            |
| `sub_id` / `sub1`…`sub5` | Không | sub_id chung cho cả lô; từng sản phẩm khai riêng thì phần khai riêng thắng.     |
| `cache_only` | Không    | `1` = tuyệt đối không gọi Shopee API, chỉ trả những gì cache có. Alias: `db_only`.          |
| `max_api`    | Không    | Trần số sản phẩm được gọi nguồn trong request này. Mặc định 20, tối đa 50, `0` = như `cache_only`. |
| `clear_cache`| Không    | `1` = bỏ qua cache, buộc gọi nguồn (vẫn bị chặn bởi `max_api`).                             |

**Trần mỗi request: 100 sản phẩm** (`limits.maxItems`). Gửi dư thì phần vượt bị cắt lặng —
đếm `requested` trong response để biết.

Link rút gọn (`s.shopee.vn`, `shp.ee`) **không hỗ trợ** ở endpoint batch: mỗi link là một
lượt bung link riêng ở phía server, gửi cả trăm link là tự làm chậm mình. Tự convert sang link gốc, hoặc
tốt hơn là gửi thẳng `item_id`.

### Ví dụ — POST JSON

```bash
curl -s https://data.addlivetag.com/product-data/product-data-batch.php \
  -H 'Content-Type: application/json' \
  -d '{
        "item_ids": [1589295236, 45703342049, 22222222222],
        "base_rate": 8,
        "cap": 20000,
        "affid": "12345678901",
        "sub1": "appX"
      }'
```

### Ví dụ — sub_id riêng từng sản phẩm

Cùng một sản phẩm gửi cho nhiều user thì sub_id phải khác nhau, nếu không báo cáo affiliate
gộp hết vào một mối:

```json
{
  "affid": "12345678901",
  "items": [
    { "item_id": 1589295236, "sub1": "userA", "sub2": "noti_giam_gia" },
    { "item_id": 1589295236, "sub1": "userB", "sub2": "noti_giam_gia" },
    { "item_id": 45703342049, "sub_id": "userC-noti_hh" }
  ]
}
```

### Ví dụ — form / GET

```
POST item_ids=1589295236,45703342049&cache_only=1
GET  ?item_ids=1589295236,45703342049&affid=12345678901
```

---

## Response

```json
{
  "status": "success",
  "requested": 3,
  "returned": 3,
  "summary": {
    "fromCache": 1,
    "fromApi": 1,
    "stale": 0,
    "skipped": 0,
    "notFound": 1,
    "invalid": 0
  },
  "limits": {
    "maxItems": 100,
    "maxApiPerRequest": 20,
    "apiFetched": 1,
    "apiRemaining": 297,
    "dbRemaining": 1995,
    "sourceRateLimited": false,
    "sourceCooldownSeconds": 0
  },
  "products": [
    {
      "input": "1589295236",
      "itemId": 1589295236,
      "status": "success",
      "dataSource": "db",
      "productInfo": { "...": "giống hệt productInfo của endpoint đơn" }
    }
  ],
  "shopeeAccountNote": "...",
  "legalNotice": { "...": "..." }
}
```

Sáu ô của `summary` cộng lại **đúng bằng `requested`** — mỗi sản phẩm rơi vào đúng một ô,
tiện đối soát mà không phải duyệt cả mảng `products`.

### `products[]` — mỗi mục một sản phẩm, **đúng thứ tự client gửi**

| Trường        | Mô tả                                                                              |
| ------------- | ----------------------------------------------------------------------------------- |
| `input`       | Giá trị gốc client gửi (id hay url) — để map ngược khi gửi lẫn id và url.            |
| `itemId`      | item_id đã trích được; `null` nếu đầu vào hỏng.                                       |
| `status`      | `success` \| `stale` \| `not_found` \| `skipped` \| `error` (xem bảng dưới).          |
| `dataSource`  | `db` \| `api` \| `db_stale` \| `fallback`.                                            |
| `reason`      | Chỉ có khi không phải `success` — lý do máy đọc được (xem bảng dưới).                 |
| `message`     | Chỉ có với `status = error` — mô tả cho người đọc.                                    |
| `productInfo` | Y hệt `productInfo` của endpoint đơn (kể cả `affLink`, `catIds`, `priceStats`…). `null` khi `status = error`. |

Gửi trùng id trong cùng một request thì vẫn nhận đủ số dòng (tiện map theo index), nhưng
chỉ tốn **một** lượt tra cache/nguồn.

### Ý nghĩa `status`

| status      | Nghĩa                                                                             | Nên làm gì                                  |
| ----------- | --------------------------------------------------------------------------------- | ------------------------------------------- |
| `success`   | Dữ liệu dùng được: `dataSource=db` (cache còn hạn) hoặc `api` (vừa lấy mới).       | Dùng bình thường.                           |
| `stale`     | Có dữ liệu nhưng **là bản cũ**, lượt này chưa làm tươi được.                       | Dùng tạm, gửi lại id đó ở lượt sau.         |
| `skipped`   | Chưa có cache và cũng chưa kịp gọi nguồn trong lượt này.                            | Gửi lại ở lượt sau.                         |
| `not_found` | Không có trong cache, nguồn cũng không trả về (sản phẩm hết hàng / không có HH).   | Ngừng hỏi lại liên tục.                     |
| `error`     | Đầu vào hỏng (không phải id, link không rút được id, link rút gọn).                | Sửa đầu vào phía bạn.                       |

### Ý nghĩa `reason`

| reason                  | Nghĩa                                                                     |
| ----------------------- | -------------------------------------------------------------------------- |
| `cache_only`            | Bạn bật `cache_only` / `max_api=0` nên không gọi nguồn.                    |
| `api_budget_exhausted`  | Vượt `max_api` của request, hoặc hết quota `api` trong phút này.           |
| `source_cooldown`       | Shopee vừa chặn ở lượt trước, endpoint đang nghỉ (xem `sourceCooldownSeconds`). |
| `source_rate_limited`   | Shopee chặn ngay trong lượt này.                                            |
| `api_fetch_failed`      | Gọi nguồn nhưng lỗi/không có dữ liệu.                                       |
| `db_write_failed`       | Lấy được dữ liệu mới nhưng ghi cache hỏng — `productInfo` vẫn là bản mới.   |
| `not_cached`            | Sản phẩm chưa từng có trong cache.                                          |
| `cache_expired`         | Cache quá 3 giờ.                                                            |
| `commission_unverified` | Rate hoa hồng trong cache chưa được API xác minh.                           |
| `invalid_item_id_or_url`| Không đọc được item_id.                                                     |
| `short_link_unsupported`| Link rút gọn — không hỗ trợ ở batch.                                        |

---

## Rate limit

Tính theo **IP** như endpoint đơn, nhưng **đếm theo số sản phẩm**, không phải theo số
request HTTP:

| Nguồn | Trần         | Cách tính                                                        |
| ----- | ------------ | ---------------------------------------------------------------- |
| `db`  | 2.000/phút   | Mỗi sản phẩm trong request = 1 lượt (kể cả sản phẩm sau đó phải gọi nguồn). |
| `api` | 150/phút     | Chỉ những sản phẩm **thực sự phải gọi Shopee** mới bị tính.       |

Nghĩa là: **đã có cache thì thoải mái** (2000 sản phẩm/phút ≈ 20 request × 100 sản phẩm),
sản phẩm mới mới ăn vào quota API.

Hết quota `api` giữa chừng thì **không trả 429 cho cả lô** — phần lấy được từ cache vẫn trả
bình thường, phần còn lại đánh dấu `stale`/`skipped`. Vượt quota `db` mới trả HTTP 429.

### Trần thật nằm ở phía Shopee

Hạn mức **phía nguồn** (không phải của endpoint này) mới là nút thắt: gọi dồn dập sẽ
nhận lỗi `10030 Rate limit exceeded`, và khi đã chạm trần thì nghỉ hơn một phút vẫn chưa hồi.

Endpoint tự bảo vệ:

- Thấy lỗi 10030 → **dừng ngay** phần còn lại của lô, không gọi tiếp cho hỏng nốt.
- Bật cooldown **120 giây** cho toàn hệ thống; trong thời gian đó batch chỉ phục vụ cache.
- Response báo `limits.sourceRateLimited` và `limits.sourceCooldownSeconds` để bạn biết mà giãn nhịp.

Đã thử gộp nhiều item vào một query GraphQL bằng alias và **bỏ**: Shopee tính mỗi alias là
một lượt gọi (không tiết kiệm quota), mà khi chạm trần thì hỏng **cả query** — mất luôn
những sản phẩm lẽ ra lấy được. Giờ mỗi sản phẩm là một request, chạy song song 4 luồng.

---

## Cập nhật 4–5 nghìn sản phẩm thì làm thế nào

1. **Nạp lần đầu (kho sản phẩm còn trống):** phần lớn sản phẩm chưa có cache nên bị chặn bởi
   quota nguồn, không phải bởi endpoint này. Chạy rải: mỗi phút vài request, mỗi request
   `max_api` 10–20. Đừng cố nhồi một lượt.
2. **Chu kỳ thường xuyên (kho đã đầy):** gửi thẳng lô 100 id/request. Cache 3 giờ nên đa số
   trả từ `db`; chỉ phần hết hạn mới chạm nguồn. 5000 sản phẩm ≈ 50 request, chạy trong
   ~3 phút là xong (giới hạn `db` 2000 sản phẩm/phút).
3. **Chỉ cần đọc, không cần tươi:** thêm `cache_only=1` — không bao giờ chạm quota API,
   không bao giờ bị cooldown.
4. **Xử lý kết quả:** gom lại những id có `status` là `stale` / `skipped` rồi gửi lại ở vòng
   sau, thay vì gọi lại cả 5000.
5. **Tôn trọng tín hiệu lùi:** `limits.sourceRateLimited = true` hoặc
   `sourceCooldownSeconds > 0` thì dừng vòng quét, đợi hết cooldown.

Ví dụ vòng lặp (giả mã):

```
retry = []
for lo in chunks(all_ids, 100):
    r = POST(batch, {item_ids: lo, max_api: 20})
    if r.limits.sourceCooldownSeconds > 0: sleep(r.limits.sourceCooldownSeconds)
    for p in r.products:
        if p.status in ('stale', 'skipped'): retry.append(p.itemId)
        else: save(p.productInfo)
# vòng sau xử lý retry
```

---

## Tóm tắt endpoint

| Mục        | Giá trị                                                             |
| ---------- | ------------------------------------------------------------------- |
| URL        | `https://data.addlivetag.com/product-data/product-data-batch.php`   |
| Method     | GET, POST, OPTIONS                                                  |
| Đầu vào    | `item_ids` / `urls` / `items` (tối đa 100 sản phẩm)                 |
| Response   | JSON: `status` + `summary` + `limits` + `products[]`                |
| Rate limit | 2000 sản phẩm/phút (cache), 300 sản phẩm/phút (gọi nguồn), theo IP  |
| Cache      | 3 giờ (`CACHE_DURATION`), dùng chung kho với endpoint đơn           |
| Timezone   | Asia/Ho_Chi_Minh                                                    |
