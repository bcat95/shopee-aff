# Hướng dẫn dùng API đơn ShopeeFood

API lấy danh sách đơn affiliate ShopeeFood của **chính tài khoản bạn**. Server chỉ đứng
giữa chuyển tiếp — không lưu cookie, không lưu đơn.

```
GET https://data.addlivetag.com/shopeefood/orders.php
```

Tài liệu field chi tiết: [`shopeefood-orders-fields.md`](shopeefood-orders-fields.md).
Data mẫu để thử: `demo_shopeefood_orders_pending.json` (data mẫu nội bộ, không kèm trong repo vì chứa đơn hàng thật).

## 1. Lấy cookie

1. Đăng nhập https://affiliate.shopee.vn trên Chrome.
2. Cài extension **J2TEAM Cookies** → bấm **Export** ở tab đang mở.
3. Dán nguyên khối JSON vừa export vào tham số `cookie`.

API nhận cả 3 dạng, không cần xử lý trước:

- JSON J2TEAM: `{"url":"...","cookies":[{"name":"SPC_ST","value":"..."}, ...]}`
- Mảng JSON: `[{"name":"SPC_ST","value":"..."}, ...]`
- Chuỗi thô: `SPC_ST=abc; SPC_F=xyz; ...`

> Cookie = chìa khoá tài khoản. Gửi qua header `X-SPF-Cookie` thay vì `?cookie=` để nó
> không nằm trong URL (URL hay bị ghi lại ở log proxy, lịch sử trình duyệt). Cookie hết
> hạn khi bạn đăng xuất Shopee hoặc sau vài tuần — lúc đó export lại.

## 2. Gọi thử

```bash
curl -s "https://data.addlivetag.com/shopeefood/orders.php?from=2026-08-01&to=2026-08-24" \
  -H "X-SPF-Cookie: <dán cookie vào đây>" \
  -o don-shopeefood.json
```

Mở `don-shopeefood.json` — nếu thấy `"code": 0` là chạy được.

## 3. Tham số

| Tham số | Mặc định | Ghi chú |
|---|---|---|
| `cookie` | *(bắt buộc)* | Hoặc header `X-SPF-Cookie`. Tối đa 32 KB |
| `from`, `to` | 30 ngày gần nhất | `YYYY-MM-DD`, `YYYY-MM-DD HH:MM:SS`, hoặc unix timestamp. Lọc theo **thời điểm đặt đơn**. Nhập ngược `from > to` thì server tự đảo lại |
| `page` | `1` | Trang |
| `page_size` | `100` | **Tối đa 100** — nhập hơn sẽ bị hạ về 100, không báo lỗi |
| `tenant` | `2` | `2` = ShopeeFood. `0` = đơn Shopee sàn thường |
| `key` | — | Chỉ cần nếu admin đã bật `SHOPEEFOOD_PROXY_KEY`. Hoặc header `X-SPF-Key` |

### Lấy hết đơn khi có nhiều hơn 100

`data.total_count` cho biết tổng số đơn. Lặp `page` cho tới khi gom đủ:

```bash
PAGE=1
while : ; do
  curl -s "https://data.addlivetag.com/shopeefood/orders.php?from=2026-08-01&to=2026-08-24&page=$PAGE&page_size=100" \
    -H "X-SPF-Cookie: $COOKIE" -o "trang-$PAGE.json"
  N=$(python3 -c "import json,sys;print(len(json.load(open('trang-$PAGE.json'))['data']['list']))")
  [ "$N" -lt 100 ] && break
  PAGE=$((PAGE+1))
done
```

Khoảng thời gian càng dài thì càng nhiều trang. Chia nhỏ theo tuần sẽ nhẹ hơn cho cả hai bên.

## 4. Đọc kết quả — 3 điều dễ sai nhất

### 4.1 Mọi số tiền phải chia 100 000

Shopee trả số nguyên đã nhân sẵn 100 000 để tránh số thập phân.

| Trong JSON | Tiền thật |
|---|---|
| `item_price: 6500000000` | 65.000đ |
| `actual_amount: 4500000000` | 45.000đ |
| `item_commission: 405000000` | 4.050đ |
| `checkout_cap: 2500000000` | 25.000đ |

`platform_commission_rate` cũng vậy: `9000` → **9%**, `5000` → **5%**, `25000` → **25%**.

### 4.2 Hoa hồng tính trên `actual_amount`, không phải `item_price × qty`

`item_price` là giá niêm yết **1 phần**, chưa trừ khuyến mãi.
`actual_amount` là tiền khách thực trả cho **cả dòng đó** (đã gồm số lượng, đã trừ giảm giá).

```
hoa hồng dòng = actual_amount × platform_commission_rate ÷ 100000
```

Ví dụ có `item_price` 58.800đ, `qty` 2, nhưng `actual_amount` chỉ 76.500đ — lấy 76.500đ.

### 4.3 Mỗi đơn bị chặn trần 25.000đ hoa hồng

Khi `is_shopee_capped: true`:

- `gross_commission` = hoa hồng **trước** khi cắt (số bạn tính tay sẽ ra số này)
- `capped_commission` = số **thực nhận**, tối đa bằng `checkout_cap`
- `item_commission` từng món **đã bị chia lại theo tỉ lệ** cho khớp trần

Muốn biết thực nhận thì lấy `capped_commission` (hoặc `affiliate_net_commission` — cùng
giá trị, nhưng kiểu chuỗi nên phải ép về số trước khi cộng).

### 4.4 Mã đơn ở đây khác mã đơn trong app

`checkout_id` / `order_id` là mã nội bộ của báo cáo chuyển đổi — **không** phải mã đơn mà
khách nhìn thấy trong app đặt món. Trường `order_sn`, vốn là chỗ mang mã đơn hiển thị, thì
luôn rỗng ở dịch vụ giao đồ ăn. Nghĩa là response **không có trường nào** chứa mã đơn của app.

Muốn đối chiếu một đơn giữa app và báo cáo thì **dựa vào subid**, nằm trong `utm_content`.

Nhưng `utm_content` có **2 dạng**, tách nhầm là ra rác:

```
Dạng A — link do bạn tự gắn tag (Shopee nối sub_id1..5 bằng dấu '-')
  "225----"     → sub1 = "225", sub2..5 rỗng
  "----"        → cả 5 phần rỗng

Dạng B — link chia sẻ từ trong app Shopee
  "37712193991759004-AppS-android-11010"
   └── mã chia sẻ ──┘ └B┘ └ nền tảng ┘ └build┘
```

Với **dạng A**: tách 5 phần bằng dấu `-`, subid của bạn nằm ở phần đầu.

Với **dạng B**: đoạn đầu là mã chia sẻ do Shopee sinh (số 16–17 chữ số hoặc hex 32 ký tự),
**không phải** subid bạn đặt — nên không map ngược về link của bạn được. Tuyệt đối đừng tách
5 phần với dạng này, vì phần 2 sẽ ra `"AppS"`, phần 3 ra nền tảng, phần 4 ra số build.

Nhận biết: `utm_content` chứa `-AppS-` hoặc `-AccS-` thì là dạng B. Dù dạng nào cũng luôn xử
lý subid như **chuỗi**, đừng ép kiểu số.

## 5. Trạng thái đơn

| `conversion_status` | Nghĩa | Hoa hồng |
|---|---|---|
| `1` | Đang chờ — khách đã trả tiền, chưa đối soát xong | Tạm tính, còn đổi |
| `2` | Hoàn tất | Chốt |
| `3` | Bị từ chối (nghi gian lận) | **Bằng 0** |

Đơn `1` sẽ chuyển thành `2` hoặc `3` ở lần đồng bộ sau → nếu lưu vào DB thì **ghi đè theo
`checkout_id`**, đừng cộng dồn.

> Đừng đọc `checkout_status` (chuỗi) để phân loại — đơn đã hoàn tất vẫn ghi
> `"Waiting for payment"`. Chỉ tin `conversion_status`.

Hoa hồng bằng 0 còn có 2 trường hợp bình thường, không phải lỗi: món giá 0 (combo tặng
kèm) và món được voucher phủ hết (`actual_amount: 0`).

## 6. Gặp lỗi

| Kết quả | Nguyên nhân | Cách xử lý |
|---|---|---|
| `"Thiếu cookie tài khoản"` | Không truyền `cookie` / `X-SPF-Cookie` | Truyền cookie |
| `"Cookie quá dài"` | Cookie > 32 KB | Export lại, đừng gộp nhiều tài khoản |
| `"from/to không hợp lệ"` | Sai định dạng ngày | Dùng `YYYY-MM-DD` |
| `"Sai hoặc thiếu key"` (401) | Site đang bật khoá | Xin `key` từ admin |
| `"Không kết nối được tới máy chủ dữ liệu"` (502) | Mạng hoặc Shopee treo | Thử lại sau vài phút |
| Trả về `{"status":"ok","raw":"<!DOCTYPE html..."}` | Cookie hết hạn → Shopee đá về trang đăng nhập | Đăng nhập lại, export cookie mới |
| `data.list` rỗng | Khoảng thời gian không có đơn | Nới `from`/`to` |

## 7. Copy for LLM

Dán nguyên khối dưới đây vào ChatGPT / Claude / Gemini trước khi nhờ nó viết code xử lý
dữ liệu. Nó gói đủ luật để LLM không tính sai tiền — sai lầm hay gặp nhất là quên chia
100 000 và nhân `item_price × qty`.

````text
Tôi có JSON từ API báo cáo đơn affiliate ShopeeFood. Hãy tuân thủ CHÍNH XÁC các quy tắc sau,
đây là điều đã kiểm chứng trên dữ liệu thật, không phải phỏng đoán:

CẤU TRÚC
- data.list[] = danh sách checkout. Mỗi checkout có orders[] (thực tế luôn đúng 1 phần tử,
  nhưng vẫn phải lặp). Mỗi order có items[] = các dòng món.
- data.total_count = tổng số đơn khớp bộ lọc; page_size tối đa 100 → phải phân trang.

ĐƠN VỊ (quan trọng nhất)
- MỌI số tiền đã nhân sẵn 100000. Chia 100000 mới ra VNĐ.
  item_price, actual_amount, refunded_amount, mọi *_commission, checkout_cap.
  VD: 6500000000 = 65.000đ; 2500000000 = 25.000đ.
- platform_commission_rate cũng nhân 100000: 9000 = 9%, 5000 = 5%, 25000 = 25%.
- Mọi mốc thời gian là unix timestamp (giây). Giá trị 0 nghĩa là CHƯA xảy ra, không phải 1970.

CÔNG THỨC HOA HỒNG
- hoa hồng 1 dòng = actual_amount * platform_commission_rate / 100000
- TUYỆT ĐỐI KHÔNG dùng item_price * qty. item_price là giá niêm yết 1 phần chưa giảm giá;
  actual_amount là tiền thực trả của CẢ DÒNG (đã gồm qty, đã trừ khuyến mãi).
- gross_commission = tổng hoa hồng trước khi cắt trần.
- checkout_cap = trần hoa hồng mỗi đơn (25.000đ).
- capped_commission = min(gross_commission, checkout_cap) = SỐ THỰC NHẬN.
- Khi is_shopee_capped = true, item_commission của từng món ĐÃ bị chia lại theo tỉ lệ cho
  khớp trần, nên nó KHÔNG còn bằng actual_amount * rate. Luôn đúng:
  tổng item_commission == capped_commission == affiliate_net_commission.
- affiliate_net_commission là KIỂU CHUỖI dù mang giá trị số → ép về int trước khi cộng.
  Các field mcn_*, linked_mcn_*, eligible_seller_commission cũng vậy.

TRẠNG THÁI
- conversion_status: 1 = đang chờ, 2 = hoàn tất, 3 = bị từ chối vì gian lận (hoa hồng = 0).
- ĐỪNG dùng checkout_status (chuỗi) để phân nhánh: đơn đã hoàn tất vẫn ghi
  "Waiting for payment". Chỉ dùng conversion_status hoặc display_order_status (số).
- items[].affiliate_item_status: 1 = chờ, 0 = xong, 3 = bị từ chối.
- Đơn conversion_status=1 sẽ đổi sang 2 hoặc 3 ở lần đồng bộ sau → upsert theo checkout_id,
  không insert-only, không cộng dồn.

KHOÁ DỮ LIỆU
- Khoá đơn trong báo cáo: checkout_id (trùng với orders[0].order_id).
- CỰC KỲ QUAN TRỌNG: checkout_id/order_id KHÁC mã đơn mà khách nhìn thấy trong app đặt món.
  Đây là mã nội bộ của báo cáo chuyển đổi. order_sn — trường vốn mang mã đơn hiển thị —
  LUÔN RỖNG, nên response KHÔNG có trường nào chứa mã đơn của app.
  ⇒ Muốn đối chiếu một đơn giữa app và báo cáo thì PHẢI dùng subid, tức đoạn đầu của
  utm_content (phần trước "-AppS-"). Đối soát bằng mã đơn sẽ không bao giờ khớp.
- Subid: utm_content có dạng <subid>-AppS-<nền tảng>-<phiên bản>. Subid có 2 dạng: số
  16-17 chữ số HOẶC hex 32 ký tự → luôn xử lý như chuỗi, đừng ép kiểu số.
- order_sn LUÔN RỖNG với ShopeeFood → không dùng làm khoá.
- Khoá dòng món: (checkout_id, promotion_id). KHÔNG dùng item_id — cùng một item_id có thể
  xuất hiện nhiều dòng trong 1 đơn với item_price khác nhau (khác size/topping).
- shop_id chạy từ 4 tới 10 chữ số → cột BIGINT, không dùng INT.
- Tên món/quán có dấu tiếng Việt → utf8mb4.

FIELD ĐỪNG DÙNG
- global_category_lv1..3_id/_name: luôn 0 / rỗng với ShopeeFood, không phân loại được.
- brand_commission_rate, total_brand_commission, capped_brand_commission: luôn 0.
- referrer: chuỗi chứa JSON, nội dung lặp lại đúng 5 field internal_source/direct_source/
  indirect_source/first_external_source/last_external_source đã có sẵn ở cấp checkout →
  dùng field phẳng.
- app_type (1 hoặc 2) KHÔNG map sang iOS/Android — cả 4 tổ hợp đều tồn tại. Muốn biết nền
  tảng phải parse utm_content, định dạng: <contentId>-AppS-<platform>-<buildVersion>.
  contentId có 2 dạng: số 16-17 chữ số HOẶC hex 32 ký tự → đừng ép kiểu số.
- display_item_status: field Shopee mới thêm, snapshot cũ không có → đọc bằng ?? '' / .get().

Bây giờ hãy [MÔ TẢ VIỆC BẠN CẦN LÀM, vd: viết script Python gom hoa hồng theo từng quán].
````

## 8. Cần đọc thêm

- [`shopeefood-orders-fields.md`](shopeefood-orders-fields.md) — bảng field đầy đủ, gồm cả
  các field hằng số và ghi chú lưu DB.
- `demo_shopeefood_orders_pending.json` (data mẫu nội bộ, không kèm trong repo vì chứa đơn hàng thật)
  — 1 trang response thật (27 đơn) để test code mà không cần cookie.
