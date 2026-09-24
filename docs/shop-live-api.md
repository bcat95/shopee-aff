# Shop Live API — phiên livestream & lịch sử lên sóng của shop

Tra phiên livestream gần nhất và toàn bộ lịch sử lên sóng của shop Shopee, kèm thống kê
khung giờ và thời lượng.

**Endpoint:** `https://data.addlivetag.com/live/shop-live.php`

> **Thay thế một API cũ cùng chức năng.** Hình dạng trả về giữ nguyên của API đó:
> map khoá theo `shop_id`, **mọi giá trị là chuỗi**, `live_logs` là chuỗi JSON hoặc `null`.
> Client cũ chỉ cần đổi URL, không phải sửa code. `stats=1` chỉ **thêm** field mới nên cũng
> không phá client cũ.

> Đọc DB nội bộ, không gọi Shopee ⇒ rate limit rộng: **300 request/phút/IP**
> (còn 40% nếu chưa có [API Key](api-key.md)).

---

## Cách gọi

```http
GET|POST /live/shop-live.php?shop_ids=123,456
GET      /live/shop-live.php?shop_ids=123&stats=1&recent_days=30
```

| Tham số | Bắt buộc | Mặc định | Mô tả |
| --- | --- | --- | --- |
| `shop_ids` | ✔ | — | Một hoặc nhiều `shop_id`, ngăn cách bằng dấu phẩy/khoảng trắng. Nhận cả `shopIds` / `shop_id`. |
| `stats` | | `0` | `1` = thêm `logs` (mảng phiên đã parse) và `stats` (thống kê) vào từng shop. |
| `recent_days` | | `30` | Cửa sổ thống kê "gần đây", 1–365. |

**Shop không có trong kho thì không xuất hiện trong map** (giống API cũ) — không tạo dòng
rỗng, để bạn phân biệt được "không có dữ liệu" với "có mà rỗng".

---

## Response

```json
{
    "38003654": {
        "id": "223",
        "shop_id": "38003654",
        "session_id": "32580181",
        "real_username": "dyaciofficial",
        "nick_name": "DYACI",
        "room_id": "2340",
        "title": "Deal đậm sâu đầu mùa, nhanh nhanh kẻo hết",
        "cover_pic": "vn-11134104-7ras8-m0io9dl6qajx24",
        "member_cnt": "333",
        "like_cnt": "0",
        "start_time": "1772341898747",
        "end_time": "1772349014472",
        "status": "2",
        "ccu": "0",
        "has_draw": "0",
        "has_voucher": "0",
        "num_live": "526",
        "time_created": "1697341283",
        "time_update": "1772382423",
        "live_logs": "[{\"date\":\"2024-11-28\",\"session_id\":18012247,…}]"
    }
}
```

| Trường | Mô tả |
| --- | --- |
| `session_id` / `room_id` | Phiên và phòng live gần nhất ghi nhận được. |
| `title` / `cover_pic` | Tiêu đề và ảnh bìa phiên. |
| `member_cnt` / `like_cnt` / `ccu` | Lượt xem / lượt thích / số người xem đồng thời tại thời điểm ghi nhận. |
| `start_time` / `end_time` | **Mili-giây** (13 chữ số), không phải giây. |
| `time_created` / `time_update` | **Giây** (10 chữ số). Lệch đơn vị với hai trường trên — đây là di sản của API cũ, giữ nguyên để không phá client. |
| `num_live` | Tổng số phiên đã lên sóng. |
| `live_logs` | **Chuỗi JSON** (không phải mảng) chứa lịch sử phiên. Muốn mảng đã parse thì dùng `stats=1` và đọc `logs`. |

---

## `stats=1` — khối thêm

```json
{
    "38003654": {
        "…": "…",
        "logs": [
            { "date": "2024-11-28", "session_id": 18012247, "start_time": 1732752160100, "end_time": 0 }
        ],
        "stats": {
            "total_sessions": 70,
            "recent_days": 30,
            "recent_sessions": 0,
            "recent_active_days": 0,
            "first_live_at": 1732752160,
            "last_live_at": 1769730303,
            "avg_duration_sec": 28376,
            "total_duration_sec": 1333692,
            "longest_duration_sec": 68833,
            "hour_histogram": [0, 0, "…24 phần tử…"],
            "hour_histogram_recent": [0, 0, "…24 phần tử…"]
        }
    }
}
```

| Trường | Mô tả |
| --- | --- |
| `logs[]` | `live_logs` đã parse sẵn. `end_time: 0` = phiên chưa ghi nhận giờ kết thúc (đang live hoặc dữ liệu thiếu). |
| `total_sessions` | Tổng số phiên trong lịch sử đã lưu. |
| `recent_sessions` / `recent_active_days` | Số phiên và số ngày có live trong cửa sổ `recent_days`. `recent_sessions: 0` trong khi `total_sessions` lớn = **shop đã ngừng live**. |
| `first_live_at` / `last_live_at` | Unix (giây) phiên đầu và phiên gần nhất. |
| `avg_duration_sec` / `total_duration_sec` / `longest_duration_sec` | Thời lượng trung bình / tổng / dài nhất (giây). |
| `hour_histogram` | Mảng **24 phần tử**, index = giờ trong ngày (0–23), giá trị = số phiên bắt đầu vào giờ đó. Dùng tìm khung giờ shop hay lên sóng. |
| `hour_histogram_recent` | Như trên nhưng chỉ trong cửa sổ `recent_days`. |

> Phiên có `end_time: 0` bị bỏ khỏi các phép tính thời lượng, nên `total_duration_sec` có thể
> nhỏ hơn thực tế với shop đang live.
