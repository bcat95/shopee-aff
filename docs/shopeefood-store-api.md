# ShopeeFood Store API — hồ sơ quán ăn

Tra thông tin quán ăn ShopeeFood theo `restaurant_id` từ kho dữ liệu đã thu thập.

**Endpoint:** `https://data.addlivetag.com/shopeefood/store.php`

> Đọc DB nội bộ, **không gọi ShopeeFood** ⇒ nhanh và không đụng hạn mức nguồn.
> Đổi lại: quán chưa được thu thập thì không có dữ liệu.

---

## Cách gọi

**Chỉ nhận GET.** Method khác trả HTTP 405.

```http
GET /shopeefood/store.php?restaurant_id=1245352
GET /shopeefood/store.php?restaurant_id=1245352,1211381
```

| Tham số | Bắt buộc | Mô tả |
| --- | --- | --- |
| `restaurant_id` | ✔ | Một hoặc nhiều id, ngăn cách bằng dấu phẩy. Id trùng được loại bỏ. |

Một `restaurant_id` có thể ứng với **nhiều `delivery_id`** (nhiều điểm giao/chi nhánh), nên
`data[]` có thể dài hơn số id bạn gửi. Kết quả sắp xếp theo `restaurant_id`, rồi `delivery_id`.

---

## Response

```json
{
    "status": "ok",
    "count": 0,
    "data": []
}
```

> ⚠️ Endpoint này trả `status: "ok"` (không phải `"success"` như các endpoint khác) khi
> thành công. Quán không có trong kho → `count: 0`, `data: []` kèm **HTTP 200** — đây là
> "chưa thu thập", không phải lỗi.

### Mỗi phần tử `data[]`

| Nhóm | Trường |
| --- | --- |
| Định danh | `delivery_id`, `restaurant_id`, `brand_id`, `name`, `name_en` |
| Địa chỉ | `address`, `city_id`, `district_id`, `ward_id`, `location_url`, `position{latitude, longitude}` |
| Link | `url`, `restaurant_url`, `url_rewrite_name` |
| Trạng thái | `is_open`, `is_pickup`, `is_foody_delivery`, `is_quality_merchant`, `has_contract`, `contract_type`, `restaurant_status`, `display_order` |
| Giờ mở cửa | `operating{status, open_time, close_time, color}` |
| Đánh giá | `rating{avg, total_review, display_total_review}` |
| Phân loại | `categories[]`, `cuisines[]`, `phones[]` |
| Dịch vụ | `foody_service_id`, `service_type`, `merchant_time`, `limit_distance` |
| Hình ảnh | `banner_mms_img_id`, `logo_mms_img_id`, `image_name` |
| Đơn tối thiểu | `min_order_value.resource_args[0]` |
| Khuyến mãi | `promotion_groups[]` |
| Thời gian | `created_at`, `updated_at` — thời điểm **thu thập**, không phải thời điểm quán cập nhật. |

Cấu trúc lồng (`operating`, `rating`, `position`, `min_order_value.resource_args`) cố ý giữ
theo hình dạng API gốc của ShopeeFood để client cũ dùng lại được.

---

## Lỗi

| HTTP | Khi nào |
| --- | --- |
| 400 | Thiếu `restaurant_id`, hoặc không có id nào hợp lệ (> 0). |
| 405 | Gọi bằng method khác GET. |
| 500 | Không kết nối được database. |

---

## Liên quan

- [ShopeeFood Orders API](shopeefood-orders-api.md) — báo cáo đơn hàng affiliate ShopeeFood.
- [Bảng field đơn ShopeeFood](shopeefood-orders-fields.md)
