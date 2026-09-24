# ShopeeFood — Báo cáo đơn affiliate (`/shopeefood/orders.php`)

Mô tả cấu trúc response thật của `shopeefood/orders.php` — proxy tới báo cáo đơn affiliate
ShopeeFood, gọi bằng cookie tài khoản của chính bạn.

Data mẫu:

| File | Ảnh chụp | Phủ case |
|---|---|---|
| `demo_shopeefood_orders_pending.json` | mới hơn, đơn **đang chờ** | `conversion_status=1`, `order_status=PAID`, có field mới `display_item_status` |
| `demo_shopeefood_orders_completed.json` | cũ hơn, đơn **đã hoàn tất** | `conversion_status=2`, `order_status=COMPLETED`, chưa có `display_item_status` |

**`_pending.json`** là **trọn 1 trang response thật**: 27 record / 36 dòng món, khớp đúng
`total_count = 27`. Đã vá 3 chỗ bị đứt ký tự lúc sao chép — vá bằng dữ liệu suy ra được
chắc chắn, không bịa:

| Record | Field vá | Căn cứ |
|---|---|---|
| `1872226310` | `actual_amount` (mất 8 ký tự tên key) | vị trí cố định sau `item_price`, giá trị `0` còn nguyên |
| `1872211334` món 1 | `attribution_type: 1` | = `1` ở toàn bộ 36 dòng món của cả 2 file |
| `1871768978` | 5 field `*_source` + `is_shopee_capped` | 5 field lấy từ chuỗi `referrer` của **chính record đó**; `is_shopee_capped=false` vì `gross == capped` |

**`_completed.json`** là **tập con đã lọc** (8/nhiều record), giữ lại các record nguyên vẹn
phủ đủ biến thể: 1 món / nhiều món / bị cap / bị fraud / giá 0 / rate 5% – 9% – 25% /
trùng `item_id` trong cùng đơn.

## 1. Ba con số PHẢI nhớ trước khi đọc bất cứ field nào

### 1.1 Mọi số tiền nhân sẵn 100 000

`item_price`, `actual_amount`, `refunded_amount`, `*_commission`, `checkout_cap` đều là
**VNĐ × 100 000**. Chia 100 000 mới ra tiền thật.

```
item_price      6500000000  →     65.000đ
actual_amount   4500000000  →     45.000đ
item_commission  405000000  →      4.050đ
checkout_cap    2500000000  →     25.000đ   (trần hoa hồng/đơn)
```

### 1.2 `platform_commission_rate` cũng nhân 100 000

`9000` = 9%, `5000` = 5%, `25000` = 25%. Công thức: `rate / 100000`.

### 1.3 Mọi mốc thời gian là unix timestamp (giây)

`purchase_time`, `click_time`, `complete_time`, `checkout_complete_time`,
`fraud_complete_time`. Giá trị `0` = **chưa xảy ra**, không phải 1970.

## 2. Công thức hoa hồng (đã đối chiếu khớp 100% trên data mẫu)

```
item_commission(trước cap) = actual_amount × platform_commission_rate / 100000
gross_commission           = Σ item_commission(trước cap)     ← TỔNG CHƯA CẮT
capped_commission          = min(gross_commission, checkout_cap)
Σ item_commission(trả về)  = capped_commission                ← ĐÃ CẮT
```

Hai bẫy lớn:

1. **Tính trên `actual_amount`, KHÔNG phải `item_price × qty`.** `item_price` là giá niêm
   yết 1 đơn vị *trước* khuyến mãi; `actual_amount` là tiền thực trả của **cả dòng** (đã
   gồm `qty`, đã trừ giảm giá). Ví dụ `item_price=58.800đ, qty=2` nhưng
   `actual_amount=76.500đ` chứ không phải 117.600đ.
2. **Khi đơn bị cap, `item_commission` trong `items[]` đã bị chia lại theo tỉ lệ.** Nó
   KHÔNG còn bằng `actual_amount × rate`. Muốn biết số gốc chưa cắt phải xem
   `gross_commission`.

Ví dụ cap (`checkout_id=1833448817`): 2 item ra 29.187đ (`gross_commission`), trần 25.000đ
→ `capped_commission=25.000đ`, và `item_commission` 2 dòng bị hạ xuống 17.682đ + 7.318đ
= đúng 25.000đ.

Cờ `is_shopee_capped=true` bật kèm.

### Khi nào hoa hồng = 0

| Trường hợp | Dấu hiệu |
|---|---|
| Đơn bị chặn gian lận | `conversion_status=3`, `is_fraud=1`, `fraud_status=3`, `affiliate_item_status=3`, `fraud_reason="Rejected due to fraudulent activity detected"` |
| Món giá 0 (combo deal / hàng tặng) | `item_price=0` và `actual_amount=0` |
| Món có giá nhưng phần thực trả = 0 | `item_price>0` nhưng `actual_amount=0` (voucher phủ hết) |

Lưu ý: đơn fraud vẫn giữ nguyên `item_price`/`actual_amount` — chỉ `item_commission` bị ép
về 0. Đừng dùng `actual_amount` để suy ra hoa hồng cho nhóm này.

## 3. Vòng đời đơn — 2 nhánh trạng thái

| Field | Đang chờ | Hoàn tất | Bị từ chối |
|---|---|---|---|
| `conversion_status` | `1` | `2` | `3` |
| `checkout_status` | `"Pending"` | `"Waiting for payment"` | (theo trạng thái gốc) |
| `checkout_status_app` | `0` | `1` | (theo trạng thái gốc) |
| `checkout_complete_time` | `0` | unix ts | = `fraud_complete_time` |
| `orders[].order_status` | `"PAID"` | `"COMPLETED"` | giữ nguyên PAID/COMPLETED |
| `orders[].shopee_order_status` | `1` | `2` | giữ nguyên |
| `orders[].display_order_status` | `1` | `2` | `3` |
| `orders[].complete_time` | `0` | unix ts | `0` nếu chưa hoàn tất |
| `items[].affiliate_item_status` | `1` | `0` | `3` |

⚠️ `checkout_status` là chuỗi hiển thị, **không đáng tin để phân nhánh logic** — snapshot
đơn đã hoàn tất vẫn ghi `"Waiting for payment"`. Luôn dùng `conversion_status` /
`display_order_status` (số).

`fraud_complete_time` là mốc chốt kiểm gian lận theo **lô**, nhiều đơn khác nhau dùng
chung một giá trị — không phải mốc riêng của đơn.

## 4. Bảng field

### 4.1 Bọc ngoài

| Field | Kiểu | Ghi chú |
|---|---|---|
| `code` | int | `0` = thành công |
| `msg` | string | `"success"` |
| `data.page_num` / `page_size` | int | `page_size` tối đa 100 (Shopee chặn nếu vượt) |
| `data.total_count` | int | Tổng đơn khớp bộ lọc → dùng để phân trang |
| `data.list[]` | array | Mỗi phần tử = 1 **checkout** |

### 4.2 Cấp checkout (`data.list[]`)

| Field | Kiểu | Ý nghĩa |
|---|---|---|
| `checkout_id` | string | **Khoá chính**. Trùng `orders[0].order_id` ở toàn bộ mẫu |
| `purchase_time` | int ts | Lúc đặt đơn |
| `click_time` / `click_id` | int ts / hex32 | Lúc bấm link + id phiên click. Click có thể cách đặt hàng nhiều ngày (attribution window) |
| `conversion_status` | int | 1 chờ / 2 hoàn tất / 3 từ chối — xem §3 |
| `checkout_cap` | int×1e5 | Trần hoa hồng mỗi đơn. Mẫu: 25.000đ |
| `is_shopee_capped` | bool | Đơn có bị chạm trần không |
| `gross_commission` | int×1e5 | Hoa hồng **trước** cap |
| `capped_commission` | int×1e5 | Sau cap. = `Σ items[].item_commission` |
| `estimated_total_commission` | int×1e5 | = `capped_commission` khi không MCN |
| `estimated_total_commission_with_mcn` | int×1e5 | = trên khi không MCN |
| `affiliate_net_commission` | **string** | Số thực nhận. Kiểu chuỗi — ép về int trước khi cộng |
| `total_brand_commission` | int×1e5 | Luôn 0 với ShopeeFood (không có hoa hồng nhãn hàng) |
| `affiliate_id` / `affiliate_name` | int / string | Tài khoản affiliate |
| `user_status` | string | `"New"` = khách mới của Shopee, `"Existing"` = khách cũ |
| `utm_content` | string | Mang subid. **Có ít nhất 2 dạng** — xem §6 |
| `device` | string | `"App"` |
| `app_type` | int | `1` hoặc `2`, cùng lúc tồn tại cả ios lẫn android ở cả hai giá trị → **không** map được sang nền tảng; muốn biết ios/android phải parse `utm_content` |
| `tenant` | int | `2` = ShopeeFood (đây chính là bộ lọc của API) |
| `product_type` | string | `"mp"` |
| `referrer` | **string chứa JSON** | Phải `json_decode` 2 lần. Nội dung lặp lại đúng 5 field `internal_source` / `direct_source` / `indirect_source` / `first_external_source` / `last_external_source` đã có sẵn ở cấp checkout → **dùng field phẳng, bỏ qua `referrer`** |
| `attribution_type` | int | `2` ở cấp checkout (cấp item là `1`) |
| `content_type`, `internal_source`, `indirect_source`, `first_external_source`, `estimated_validation_month` | string | Rỗng ở toàn bộ mẫu |
| `direct_source` | string | `"untracked"` |
| `last_external_source` | string | `"Others"` |
| `traffic_type` | int | `0` |
| `report_payment_validation_info` | object | Toàn bộ field = 0 / rỗng ở mẫu (chưa vào chu kỳ đối soát) |
| `mcn_*`, `linked_mcn_*`, `campaign_mcn_*`, `eligible_seller_commission` | string `"0"` / rỗng | Không dùng MCN. Kiểu **string** dù mang giá trị số |

### 4.3 Cấp đơn (`list[].orders[]`)

Ở toàn bộ mẫu mảng này **luôn có đúng 1 phần tử**, nhưng vẫn phải lặp — cấu trúc là mảng.

| Field | Kiểu | Ý nghĩa |
|---|---|---|
| `order_id` | string | Trùng `checkout_id` |
| `order_sn` | string | **Luôn rỗng** với ShopeeFood (khác Shopee thường) → không dùng làm khoá |
| `order_status` | string | `PAID` / `COMPLETED` |
| `shopee_order_status`, `display_order_status` | int | Xem §3 |
| `complete_time`, `fraud_complete_time` | int ts | |
| `affiliate_transaction_id` | string | ID giao dịch phía Shopee, 18 chữ số |
| `shop_type` | int | `0` |
| `cancel_reason` | string | Rỗng ở mẫu |
| `is_fixed_fee` | bool | `false` |
| `ams_order_billing_order_cap`, `is_ams_order_billing_order_capped` | int / bool | `0` / `false` |
| `items[]` | array | Chi tiết món |

### 4.4 Cấp món (`orders[].items[]`)

| Field | Kiểu | Ý nghĩa |
|---|---|---|
| `item_id` | int | ID món. **Không unique trong 1 đơn** — cùng món đặt 2 dòng riêng (size/topping khác) sẽ lặp `item_id` với `item_price` khác nhau |
| `promotion_id` | string | `0_0_<id>`, **unique mỗi dòng** → dùng cái này làm khoá dòng, không dùng `item_id` |
| `item_name` | string | Tên món. Chứa dấu tiếng Việt, emoji-free nhưng có ký tự lạ → luôn `utf8mb4` |
| `shop_id` / `shop_name` | int / string | Quán. `shop_id` trải rộng từ 4 chữ số (`635`, `1690`) tới 10 chữ số (`1000070949`) → cột **BIGINT** |
| `item_price` | int×1e5 | Giá niêm yết **1 đơn vị**, trước giảm |
| `actual_amount` | int×1e5 | Tiền thực trả của **cả dòng** (đã gồm qty). Có thể lẻ tới đơn vị nhỏ do chia tỉ lệ voucher: `3197368421` = 31.973,68421đ |
| `qty` | int | Số lượng |
| `refunded_amount` | int×1e5 | `0` ở toàn bộ mẫu |
| `item_commission` | int×1e5 | Hoa hồng dòng, **đã trừ cap** — xem §2 |
| `platform_commission_rate` | int×1e5 | 9000 / 5000 / 25000 = 9% / 5% / 25% |
| `img_code` | string | Ghép URL ảnh Shopee CDN |
| `item_status` | string | `"UNRATED"` (khách chưa đánh giá) |
| `display_item_status` | string | **Field mới**, chỉ có ở snapshot mới; giá trị `"Pending"`. Snapshot cũ không có key này → đọc bằng `?? ''` |
| `affiliate_item_status` | int | 1 chờ / 0 hoàn tất / 3 từ chối |
| `is_fraud`, `fraud_status`, `fraud_reason` | int / int / string | `0`+`2`+`""` bình thường; `1`+`3`+lý do khi bị chặn |
| `model_id` | string | `"0"` (món ăn không có biến thể như hàng sàn) |
| `global_category_lv1..3_id` / `_name` | int / string | **Luôn 0 / rỗng** với ShopeeFood → đừng phân loại theo đây |
| `brand_commission_rate`, `capped_brand_commission`, `brand_origin_commission_rate` | int | Luôn 0 |
| `campaign_type` | int | `3` |
| `platform_calculation_type` | int | `1` |
| `platform_commission_campaign_source` | int | `7` |
| `attribution_type` | int | `1` ở cấp item |
| `ams_order_billing_rate`, `campaign_mcn_origin_commission_rate` | int | `0` |
| `campaign_mcn_brand_gross_commission` | string `"0"` | |

## 5. Khi lưu vào DB

> ⚠️ **`checkout_id` KHÔNG phải mã đơn khách thấy trong app đặt món.** Nó là mã nội bộ của
> báo cáo chuyển đổi. `order_sn` — trường vốn mang mã đơn hiển thị — luôn rỗng ở ShopeeFood,
> nên trong response **không có trường nào** chứa mã đơn của app.
>
> Muốn đối chiếu một đơn giữa app và báo cáo thì **phải dựa vào subid**: đoạn đầu của
> `utm_content`, phần trước `-AppS-`. Đối soát bằng mã đơn sẽ không bao giờ khớp — đây là
> câu hỏi đã phát sinh thật khi vận hành (24/08/2026).

- Khoá đơn: `checkout_id`. Khoá dòng món: `(checkout_id, promotion_id)` — **không** dùng
  `item_id` (lặp) hay `order_sn` (rỗng).
- Nếu cần map ngược về đơn trong app: lưu thêm cột subid tách từ `utm_content` — nhưng đọc §6
  trước, vì `utm_content` không chỉ có một dạng.

## 6. `utm_content` có ít nhất 2 dạng — đừng tách mù

Đây là chỗ dễ viết code sai nhất, vì hai dạng cùng dùng dấu `-` nhưng ý nghĩa khác hẳn.

### Dạng A — link do mình tự gắn tag

Shopee nối `sub_id1..sub_id5` bằng dấu `-`, phần nào trống thì để rỗng:

```
"225----"      → sub1="225",  sub2..5 rỗng
"----"         → cả 5 phần đều rỗng
"ShopeeFood"   → sub1="ShopeeFood", các phần sau không có
```

Tách bằng `explode('-', $utm)` lấy 5 phần là **đúng** với dạng này.

### Dạng B — link chia sẻ từ trong app Shopee

```
"37712193991759004-AppS-android-11010"
"d70ac374476db3dee64a8deb7f0497b7-AppS-ios-1480"
"6938992619562034-AccS-webapp"
```

Cấu trúc: `<id chia sẻ>-<AppS|AccS>-<nền tảng>-<phiên bản build>`. Đoạn đầu là mã chia sẻ do
Shopee sinh (số 16–17 chữ số **hoặc** hex 32 ký tự), **không phải** subid do affiliate đặt.

⚠️ Nếu tách dạng B bằng cùng logic 5-phần thì `sub2` = `"AppS"`, `sub3` = nền tảng,
`sub4` = số build — toàn rác. Hệ thống nào lọc/gom nhóm theo `sub_id2` sẽ hỏng với nhóm đơn này.

### Phân biệt

Kiểm tra `strpos($utm, '-AppS-') !== false || strpos($utm, '-AccS-') !== false` → dạng B,
chỉ lấy đoạn đầu làm mã tham chiếu và **bỏ qua** các đoạn sau. Còn lại là dạng A, tách 5 phần
như bình thường.

Dạng A là trường hợp thường gặp với link tự dựng. Dạng B xuất hiện khi tài khoản có lưu
lượng đến từ chia sẻ trong app, nên code phải xử lý được cả hai dù hiện chưa gặp dạng B.
- Tiền: giữ nguyên số nguyên ×1e5 trong DB (`BIGINT`), chỉ chia khi hiển thị. Chia sớm
  bằng float là mất số lẻ kiểu `3197368421`.
- `shop_id`, `item_id`, `affiliate_id`, `checkout_id` → `BIGINT` / `VARCHAR`, đừng `INT`.
- Chuỗi tiếng Việt → `utf8mb4_unicode_ci`. Nếu DB của bạn bật strict mode, lỗi
  *"Data truncated"* nghĩa là **mất dữ liệu thật**, không phải cảnh báo suông.
- Đơn `conversion_status=1` sẽ đổi sang `2` hoặc `3` ở lần đồng bộ sau → upsert theo
  `checkout_id`, đừng insert-only.
- API chỉ được **thêm** field, không đổi tên key sẵn có (nhiều tool ngoài đang gọi vào).
  `display_item_status` là ví dụ Shopee thêm field mới giữa 2 snapshot — code đọc phải
  chịu được cả hai dạng.
