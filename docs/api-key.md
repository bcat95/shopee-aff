# API Key — bắt buộc từ 01/10/2026

> Áp dụng cho toàn bộ endpoint trên `data.addlivetag.com`.
> Hiện đang trong **giai đoạn nhắc**: request chưa có key vẫn được phục vụ, nhưng chỉ còn
> **40% hạn mức**. Từ **01/10/2026** request không có key hợp lệ sẽ bị từ chối.

---

## Vì sao có bước này

Trần của cả hệ thống là **~533 call/phút** (4 tài khoản Shopee × 8.000 call/giờ). Đo trong
một giờ ngày 23/09/2026: **40.561 request**, trong đó **98,4%** đến từ tool gọi thẳng server
(không có `Origin`/`Referer`), và **một IP đơn lẻ đạt 412 call/phút** — tức gần như nuốt trọn
trần của mọi người còn lại. Hệ quả: cả 4 account chạy 118% trần, dội về 445 lỗi `10030`
trong 70 phút.

Không định danh được bên gọi thì không siết đúng người. API Key là cách rẻ nhất để phần
hạn mức khan hiếm được chia cho người dùng thật thay vì một script chạy loạn.

---

## Lấy key ở đâu

Đăng nhập [addlivetag.com](https://addlivetag.com/) → mục **API Key** → **Tạo Key**.

Key có dạng chuỗi **48 ký tự hex**, ví dụ:

```
0a1b2c3d4e5f60718293a4b5c6d7e8f9a0b1c2d3e4f50617
```

(chuỗi trên chỉ là ví dụ minh hoạ định dạng, không phải key dùng được)

Key là của riêng bạn — đừng đưa lên client-side (JS trong trình duyệt, extension công khai,
repo public). Nếu lộ thì vào portal tạo key mới.

---

## Gửi key thế nào

Bốn cách đều được chấp nhận, chọn cách tiện nhất với môi trường của bạn:

| Cách | Ví dụ | Dùng khi |
| --- | --- | --- |
| Header `X-API-Key` | `X-API-Key: <key>` | **Khuyến nghị.** Tool Node/PHP/Python gọi từ server. |
| Header `Authorization` | `Authorization: Bearer <key>` | Client đã quen chuẩn Bearer. |
| Query `?key=` | `...?item_id=123&key=<key>` | Google Apps Script, extension, gọi tay. |
| Query `?api_key=` | `...?item_id=123&api_key=<key>` | Nhận kèm cho đỡ nhầm. |

### curl

```bash
curl -H "X-API-Key: <key>" \
  "https://data.addlivetag.com/product-data/product-data.php?item_id=1589295236"
```

### PHP

```php
$ch = curl_init('https://data.addlivetag.com/product-data/product-data.php?item_id=1589295236');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['X-API-Key: ' . getenv('ADDLIVETAG_API_KEY')],
]);
$res = json_decode(curl_exec($ch), true);
curl_close($ch);
```

### Node.js

```js
const res = await fetch(
    "https://data.addlivetag.com/product-data/product-data.php?item_id=1589295236",
    { headers: { "X-API-Key": process.env.ADDLIVETAG_API_KEY } }
);
const data = await res.json();
```

### Google Apps Script

```js
const url = "https://data.addlivetag.com/product-data/product-data.php"
          + "?item_id=1589295236&key=" + KEY;
const data = JSON.parse(UrlFetchApp.fetch(url).getContentText());
```

---

## Cách biết request của mình đã có key hay chưa

### Khối `apiKeyNotice` trong response

Request **chưa có key** (hoặc key sai định dạng) nhận thêm khối này ở cấp cao nhất của JSON.
Đã có key hợp lệ thì khối này **không xuất hiện**.

```json
{
    "status": "success",
    "productInfo": { "…": "…" },
    "apiKeyNotice": {
        "status": "missing",
        "message": "Từ 01/10/2026, request không có API Key sẽ bị từ chối. Còn 7 ngày để bổ sung Key.",
        "requiredFrom": "2026-10-01",
        "daysLeft": 7,
        "howToGetKey": "Đăng nhập addlivetag.com → API Key → Tạo Key",
        "howToSend": "Gửi header \"X-API-Key: <key>\" hoặc thêm \"&key=<key>\" vào URL",
        "docs": "https://data.addlivetag.com/"
    }
}
```

| `status` | Nghĩa | Việc cần làm |
| --- | --- | --- |
| *(không có khối)* | Key hợp lệ | Không phải làm gì. |
| `missing` | Không truyền key | Tạo key và gắn vào request. |
| `invalid` | Có truyền nhưng **sai định dạng** (không phải 48 ký tự hex) hoặc key không tồn tại | Kiểm tra lại chuỗi key — thường là dán thiếu, hoặc để nguyên chuỗi mẫu `<key>`. |

Đừng parse `message` (tiếng Việt, có thể đổi câu chữ) — parse `status` và `daysLeft`.

### HTTP header

Những response không chèn được khối JSON vẫn kèm header:

```
X-API-Key-Required-From: 2026-10-01
Warning: 299 - "API Key required from 2026-10-01. Get yours at addlivetag.com, then send header X-API-Key or param &key="
```

---

## Hạn mức: có key và không key

Hạn mức tính theo **request/phút/IP**, đếm riêng cho luồng gọi API nguồn và luồng đọc cache.

| | Có key hợp lệ | Chưa có key |
| --- | --- | --- |
| Đến **30/09/2026** | Nguyên hạn mức | **40% hạn mức** (ví dụ: 150 → 60 request/phút ở luồng gọi nguồn) |
| Từ **01/10/2026** | Nguyên hạn mức | **HTTP 401**, không phục vụ |

Tỉ lệ 40% có thể được siết dần trước hạn (cấu hình `API_KEY_NOKEY_RATE_RATIO` phía server),
nên đừng thiết kế hệ thống dựa trên con số này. Vượt hạn mức thì nhận **HTTP 429**:

```json
{
    "status": "error",
    "message": "Rate limit exceeded. Please try again later."
}
```

Lý do siết trước khi chặn: tool tự động không đọc `apiKeyNotice`, cũng không đọc header
`Warning` — nhưng chắc chắn "đọc" được `429`.

---

## Sau 01/10/2026 — response khi thiếu key

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json; charset=utf-8
```

```json
{
    "status": "error",
    "message": "Thiếu API Key. Đăng nhập addlivetag.com → API Key → Tạo Key, rồi gửi header \"X-API-Key: <key>\" hoặc thêm \"&key=<key>\" vào URL."
}
```

---

## Ghi chú kỹ thuật

- **Xác thực theo hash:** server chỉ giữ `sha256` của key, đồng bộ từ addlivetag.com mỗi 10
  phút. Key vừa tạo có thể cần tới ~10 phút mới được nhận.
- **Fail-open có chủ ý:** nếu DB tra key gặp sự cố, key đúng định dạng vẫn được phục vụ.
  Một sự cố hạ tầng không nên khoá sạch người dùng thật.
- **Không ảnh hưởng field cũ:** `apiKeyNotice` là khoá **mới hoàn toàn** ở cấp cao nhất.
  Tool cũ bỏ qua khoá lạ nên không cần sửa gì để tiếp tục chạy.
