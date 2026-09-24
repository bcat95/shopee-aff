# Lazada API — thông tin sản phẩm & bung link

Hai endpoint cho Lazada (*"Sàn Đen"*): lấy dữ liệu sản phẩm kèm hoa hồng, và bung link rút
gọn/affiliate về link gốc.

| Endpoint | Việc |
| --- | --- |
| [`lazada/product.php`](#product-data) | Thông tin sản phẩm + hoa hồng, nhiều id/1 request |
| [`lazada/resolve.php`](#link-resolver) | Link rút gọn / affiliate → link gốc + `itemId` |

> Phạm vi sử dụng: học tập, nghiên cứu kỹ thuật, vận hành nội bộ phi thương mại.
> Xem thêm [api-key.md](api-key.md).

---

## Product Data

**Endpoint:** `https://data.addlivetag.com/lazada/product.php`

Format response cố ý làm **giống [Shopee Product Data API](../product-data-api.md)**
(`status` + `productInfo` / `products[]`, `dataSource`, `lastUpdate`) để tool dùng chung một
bộ code xử lý cho cả hai sàn.

### Tham số

| Tham số | Bắt buộc | Mặc định | Mô tả |
| --- | --- | --- | --- |
| `ids` | một trong ba | — | Một hoặc nhiều product id, ngăn cách bằng dấu phẩy. |
| `item_id` | một trong ba | — | Một product id. |
| `url` | một trong ba | — | Link sản phẩm / link rút gọn / link affiliate — tự bung rồi lấy id. Alias: `link`. |
| `cacheTtlHours` | | `24` | Thời hạn cache DB (giờ). `0` = luôn coi cache là hết hạn. |
| `clear_cache` | | `0` | `1` = bỏ qua cache, gọi thẳng Lazada API rồi cập nhật DB. Alias: `clearcache`, `clearCache`. |
| `locale` | | `en-US` | Ngôn ngữ dữ liệu trả về. Alias: `lang`. |

### Ví dụ

```bash
curl "https://data.addlivetag.com/lazada/product.php?ids=3235542573"
curl "https://data.addlivetag.com/lazada/product.php?ids=3235542573,579832468&locale=vi-VN"
curl "https://data.addlivetag.com/lazada/product.php?url=https://s.lazada.vn/s.nx6wD"
```

**Một id** → trả `productInfo` (object). **Nhiều id** → trả `products` (mảng, đúng thứ tự id
đã yêu cầu).

### Trường `productInfo`

| Trường | Kiểu | Mô tả |
| --- | --- | --- |
| `itemId` | number | Product id Lazada. |
| `productName` | string | Tên sản phẩm. |
| `shopName` / `shopId` | string / number | Người bán. |
| `brandName` / `brandId` | string / number | Thương hiệu. |
| `categoryL1` | number | Danh mục cấp 1. |
| `price` | number | Giá sau giảm (`discountPrice`). |
| `currency` | string | Đơn vị tiền, mặc định `₫`. |
| `stock` / `outOfStock` | number / boolean | Tồn kho và cờ hết hàng. |
| `sales` / `sales7d` | number | **Lượt bán 7 ngày** — Lazada không trả lượt bán luỹ kế, nên `sales` ở đây *không* cùng nghĩa với `sales` của Shopee (luỹ kế). |
| `imageUrl` / `pictures` | string / string[] | Ảnh chính và toàn bộ ảnh. |
| `productLink` | string | Link sản phẩm dựng từ id. |
| `lastUpdate` | string | Thời điểm dữ liệu được cập nhật. |
| `dataSource` | string | `api` (vừa gọi Lazada) hoặc `db` (cache). |

#### Khối hoa hồng

| Trường | Mô tả |
| --- | --- |
| `commission` | Tổng hoa hồng quy ra tiền (`totalCommissionAmount`). |
| `commissionRate` | Tổng tỷ lệ hoa hồng. |
| `cpsCommission` / `cpsCommissionRate` | Phần hoa hồng CPS. |
| `crossStoreCommissionRate` | Tỷ lệ khi người mua đến từ **cửa hàng khác**. |
| `sameStoreCommissionRate` | Tỷ lệ khi cùng cửa hàng. |
| `sameProductCommissionRate` | Tỷ lệ khi cùng sản phẩm. |
| `bonusOfferFlag` | Có chương trình thưởng thêm hay không. |

> Lazada trả **nhiều mức tỷ lệ tuỳ ngữ cảnh mua hàng** (cùng shop / khác shop / cùng sản
> phẩm), khác hẳn mô hình seller + sàn của Shopee. Đừng so `commissionRate` của hai sàn như
> hai con số cùng loại.

### Luồng cache

1. Còn hạn trong DB → trả từ `db`, không gọi Lazada.
2. Hết hạn / chưa có → gọi Lazada API, lưu DB, trả `api`.
3. API lỗi (hoặc `clear_cache` mà gọi hỏng) → **fallback về cache DB kể cả đã quá hạn**,
   thay vì trả rỗng.

---

## Link Resolver

**Endpoint:** `https://data.addlivetag.com/lazada/resolve.php?url=<link>`

Bung link rút gọn / link affiliate Lazada về link sản phẩm gốc và trích `itemId` (+ `skuId`).

```json
{
    "status": "success",
    "itemId": "579832468",
    "skuId": "1286570835",
    "originLink": "https://www.lazada.vn/products/pdp-i579832468-s1286570835.html?…",
    "resolvedBy": "link-rel-origin",
    "inputUrl": "…"
}
```

Các dạng link hỗ trợ:

- `https://www.lazada.vn/products/pdp-i579832468-s1286570835.html`
- `https://www.lazada.vn/products/i3235542573.html`
- `https://s.lazada.vn/s.nx6wD?c=d&t=p-idEuua-s1P4JqV` (trả HTML kèm `<link rel="origin">`)
- `https://c.lazada.vn/t/...?url=<link gốc đã encode>`
- Link chứa tham số `origin_link=` / `redir=`

**Chỉ nhận host Lazada** (`lazada.vn`, `lazada.com`, `lazada.co.id`, `lazada.com.my`,
`lazada.com.ph`, `lazada.sg`, `lazada.co.th`, `lzd.co`) — chặn việc lợi dụng làm proxy/SSRF.
Host khác trả lỗi 400.

Có CORS, dùng được trực tiếp từ trình duyệt.
