# Sàn Cam Product Data API

API lấy thông tin sản phẩm Sàn Cam kèm chi tiết hoa hồng (commission). Dữ liệu được cache trong database **3 giờ** (`CACHE_DURATION = 10800`) trước khi gọi lại API nguồn.

## Tuyên bố pháp lý & phạm vi sử dụng

- Dự án chỉ dành cho **học tập, nghiên cứu kỹ thuật và vận hành nội bộ phi thương mại**.
- Không sử dụng cho mục đích thương mại, kinh doanh trực tiếp, hoặc phân phối lại dữ liệu như dịch vụ trả phí.
- API có thể là **nguồn không chính thống** hoặc phụ thuộc bên thứ ba, nên dữ liệu có thể sai số/chậm cập nhật/thay đổi mà không báo trước.
- Chủ dự án được miễn trừ trách nhiệm với thiệt hại trực tiếp hoặc gián tiếp phát sinh từ việc sử dụng dữ liệu/API.
- Người dùng có trách nhiệm tự kiểm chứng dữ liệu trước khi ra quyết định.

## Quy ước tên gọi

- `Sàn Cam` = Shopee
- `Sàn Đen` = Lazada

**Base URL:** `https://data.addlivetag.com/product-data/product-data.php`

> **Cần nhiều sản phẩm một lúc?** Dùng endpoint batch
> [`product-data-batch.php`](docs/product-data-batch.md) — gửi tới 100 item_id trong 1 request,
> ưu tiên trả từ cache, chỉ sản phẩm chưa có mới tính vào quota API. Đừng gọi endpoint đơn
> trong vòng lặp hàng loạt.

---

## ⚠️ API Key bắt buộc từ 01/10/2026

Từ **01/10/2026**, request không kèm API Key hợp lệ sẽ bị từ chối (**HTTP 401**). Trước mốc đó
request chưa có key vẫn chạy nhưng chỉ còn **40% hạn mức**.

```bash
curl -H "X-API-Key: <key>" \
  "https://data.addlivetag.com/product-data/product-data.php?item_id=1589295236"
```

Lấy key: đăng nhập **addlivetag.com → API Key → Tạo Key**. Chi tiết cách gửi key, khối
`apiKeyNotice` trong response và hạn mức từng nhóm: **[docs/api-key.md](docs/api-key.md)**.

---

## Cập nhật mới nhất

**23/09/2026 — API Key**: xem mục trên.

**22/06/2026 — Cap hoa hồng Sàn Cam cơ bản: 50.000 → 40.000 VNĐ**

- Chỉ áp dụng cho **hoa hồng Sàn Cam** (`shopeeComFinal`, `capRaw`, `cap`, `capAfterRate`).
- **Hoa hồng seller Xtra** (`sellerComFinal`) **không đổi** — vẫn không cap, tính theo tỷ lệ seller.
- Giới hạn **8% giá sản phẩm** cho commission từ sàn vẫn giữ nguyên.
- Các trường response liên quan: `capRaw` = `40000`, `isCapped` / `isLimitCap` phản ánh khi HH Shopee vượt cap mới.
- **Cache DB:** request đầu tiên sau deploy tự phát hiện `commission_cap_raw` cũ (50k), tính lại hoa hồng từ rate đã lưu và cập nhật DB — không cần gọi lại API nguồn.

---
## Thông báo bảo trì – Link rút gọn & Product Data API

Hiện tại mình tạm ngưng xử lý các link rút gọn (như s.shopee.vn, shp.ee…) do lưu lượng tăng mạnh, gây ảnh hưởng trực tiếp đến hiệu năng của Product Data API.

Đã có bổ sung máy chủ xử lý link rút gọn, tuy nhiên để đảm bảo ổn định và tốc độ, vẫn khuyến nghị dùng link gốc hoặc tự xử lý trước phía server của bạn.

## Hai cách sử dụng ổn định nhất

- **item_id (khuyến nghị):**  
  Gửi trực tiếp mã sản phẩm → nhanh, chính xác, ít lỗi  

- **Link gốc:**  
  Mở sản phẩm trên Shopee → copy link đầy đủ trên thanh địa chỉ  

## Vì sao tạm dừng link rút gọn?

Link rút gọn không chứa thông tin sản phẩm ngay từ đầu. Hệ thống phải đi qua nhiều bước chuyển hướng để tìm ra link gốc:

- Tốn tài nguyên  
- Tăng độ trễ  
- Dễ phát sinh lỗi khi traffic cao  

Để đảm bảo API hoạt động ổn định cho số đông, mình tạm thời hạn chế xử lý link rút gọn trong giai đoạn này.

## Bạn nên làm gì lúc này?

- Ưu tiên dùng **item_id**  
- Hoặc dùng **link đầy đủ** copy trực tiếp từ trình duyệt  
- Tránh dùng link rút gọn từ tin nhắn, bài đăng  

👉 Nếu bắt buộc phải dùng link rút gọn, nên **tự convert sang link gốc trước ở server của bạn**

## Code mẫu xử lý link rút gọn

### PHP (cURL – follow redirect)
```php
<?php
function expandShortUrl(string $url, int $timeout = 15, int $connectTimeout = 5): ?string
{
    $url = preg_match('/^https?:\/\//i', $url) ? $url : 'https://' . $url;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 15,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
        CURLOPT_NOBODY         => false,
    ]);
    curl_exec($ch);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $err   = curl_error($ch);
    curl_close($ch);

    if ($err !== '') {
        return null;
    }
    return filter_var($final, FILTER_VALIDATE_URL) ? $final : null;
}

$long = expandShortUrl('https://s.shopee.vn/4VU2IjQjPF');
var_dump($long);
```
Bash (curl)
```curl -Ls -o /dev/null -w '%{url_effective}\n' 'https://s.shopee.vn/4VU2IjQjPF'```

```Node.js
import { request } from 'undici';

async function expandShortUrl(shortUrl) {
  const { headers } = await request(shortUrl, {
    method: 'GET',
    maxRedirections: 15,
    headers: {
      'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
    },
  });

  return headers.location ? new URL(headers.location, shortUrl).href : shortUrl;
}

expandShortUrl('https://s.shopee.vn/4VU2IjQjPF')
  .then(console.log)
  .catch(console.error);
Node.js (fetch – Node 18+)
const res = await fetch('https://s.shopee.vn/4VU2IjQjPF', {
  redirect: 'follow',
  headers: {
    'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
  },
});

console.log(res.url);
```

### Lưu ý
Shopee có thể trả HTML/chặn bot tùy IP, rate limit, cookie
Việc expand link không ổn định bằng mở trực tiếp trên trình duyệt
Không nên phụ thuộc hoàn toàn vào link rút gọn nếu build hệ thống lớn
Kế hoạch sắp tới

Sẽ tách riêng một dịch vụ chuyên xử lý chuyển đổi link rút gọn → link gốc, độc lập với API chính.

Khi hoàn tất, hệ thống sẽ mở lại hỗ trợ link rút gọn với giới hạn hợp lý để đảm bảo hiệu năng.

---


> Nếu đang làm affiliate hoặc build tool, nên chuyển luôn sang item_id để tối ưu tốc độ và tránh lỗi về lâu dài.

## Cách gọi API

### Phương thức

- **GET** hoặc **POST**
- Hỗ trợ CORS: `Access-Control-Allow-Origin: *`

### Tham số

| Tham số     | Bắt buộc      | Mô tả                                                                 |
| ----------- | ------------- | --------------------------------------------------------------------- |
| `item_id`   | Một trong hai | ID sản phẩm Shopee (số).                                              |
| `url`       | Một trong hai | URL sản phẩm Shopee (đầy đủ hoặc short link).                         |
| `base_rate` | Không         | % HH Sàn (cơ bản) của account bên gọi. `0.08` hoặc `8` đều được.      |
| `cap`       | Không         | Cap HH Sàn theo VNĐ của account bên gọi, vd `20000`. Mặc định 40000.   |
| `affid`     | Không         | Affiliate ID để dựng link affiliate (`affLink`). Alias: `aff_id`, `affiliate_id`. |
| `sub_id`    | Không         | sub_id đã ghép sẵn, vd `kolA-tet26-fb-post9-note` (tối đa 5 vị trí).  |
| `sub1`…`sub5` | Không       | sub_id theo từng vị trí; có `sub1..sub5` thì bỏ qua `sub_id`.         |
| `key`       | **Từ 01/10/2026** | API Key. Gửi bằng header `X-API-Key` thì hơn. Alias: `api_key`. Xem [docs/api-key.md](docs/api-key.md). |
| `clear_cache` | Không       | `1` = bỏ qua cache DB, gọi thẳng API nguồn rồi cập nhật lại DB.       |

**Lưu ý:** Cần truyền **ít nhất một** trong hai: `item_id` hoặc `url`. Nếu truyền `url`, API sẽ tự trích `item_id` từ URL.

### Link affiliate (`affLink`)

Truyền `affid` → response kèm long-link affiliate chuẩn 2026 (cơ chế `an_redir`,
xem [hướng dẫn](https://data.addlivetag.com/shopee/aff-link.html)). Không truyền `affid` thì
`affLink = null` — server không giữ affiliate_id mặc định.

```
?item_id=1589295236&affid=12345678901&sub1=kolA&sub2=tet26&sub4=post9
```

```
affLink = https://s.shopee.vn/an_redir
            ?origin_link=https%3A%2F%2Fshopee.vn%2Fproduct%2F38003654%2F1589295236
            &affiliate_id=12345678901
            &sub_id=kolA-tet26--post9
```

Cách API dựng link:

- **Landing (`originLink`)**: lấy từ `productLink`, **bỏ toàn bộ query** (`utm_*`, `gads_*`, `sp_atk`… vi phạm
  policy tracking mới). Thiếu `productLink` thì dựng lại từ `shopId`/`itemId`.
  Link rút gọn (`s.shopee.vn`, `shp.ee`) và host không phải Shopee → `originLink = null`, `affLink = null`.
- **Domain**: theo quốc gia của landing (`shopee.co.id` → `s.shopee.co.id/an_redir`).
- **sub_id**: tối đa 5 vị trí nối bằng `-`. Ký tự `-` bên trong 1 sub bị đổi thành `_` (nếu không Shopee
  hiểu nhầm thành vị trí mới), ký tự lạ đổi thành `_`, mỗi sub tối đa 50 ký tự. Vị trí trống ở giữa được
  giữ nguyên để không lệch thứ tự (`sub1` + `sub2` + `sub4` → `kolA-tet26--post9`), vị trí trống ở cuối bị cắt.

| Field         | Ý nghĩa                                                              |
| ------------- | -------------------------------------------------------------------- |
| `affLink`     | Long-link affiliate `an_redir` (null nếu không truyền `affid`)       |
| `originLink`  | Landing page sạch đã dùng để dựng `affLink`                          |
| `affiliateId` | `affid` đã nhận (null nếu không truyền / sai định dạng)              |
| `subId`       | sub_id sau khi chuẩn hoá (null nếu không truyền)                     |
| `shopId`      | ID shop — dùng dựng landing khi thiếu `productLink`                  |

### Hoa hồng theo tier của account bên gọi

Endpoint chạy bằng cấu hình nguồn phía server, nhưng mỗi tool gọi vào có thể ở tier Shopee khác nhau
(HH Sàn 3,5% / 5% / 8%; cap 40k hoặc 20k). Dùng `base_rate` và `cap` để tính đúng theo account của bạn:

```
?item_id=45703342049&base_rate=8&cap=20000
```

Thứ tự ưu tiên khi xác định **% HH Sàn**:

1. `base_rate` truyền vào request
2. Rate mà server nhận được từ Shopee API
3. Mức cơ bản cấu hình ở server (`SHOPEE_BASE_COMMISSION_RATE`, mặc định 3,5%) — chỉ dùng khi API thiếu field / lỗi

**Ngoại lệ quan trọng:** nếu Shopee trả HH Sàn = **0** (ngành hàng không có HH Sàn) thì luôn giữ 0,
`base_rate` không ghi đè — vì tier nào cũng không được HH Sàn cho ngành hàng đó.

**HH Xtra (người bán) không bị ảnh hưởng** bởi `base_rate` lẫn `cap` — Xtra không có cap.

Response trả kèm rate đã dùng để bạn đối chiếu:

| Field               | Ý nghĩa                                                            |
| ------------------- | ------------------------------------------------------------------ |
| `sellerRate`        | % HH Xtra dạng thập phân (`0.02`)                                  |
| `shopeeRate`        | % HH Sàn dạng thập phân (`0.035`)                                  |
| `sellerRatePercent` | % HH Xtra dạng phần trăm (`2`)                                     |
| `shopeeRatePercent` | % HH Sàn dạng phần trăm (`3.5`)                                    |
| `totalRatePercent`  | Tổng % (`5.5`)                                                     |
| `shopeeRateSource`  | `api_or_db` \| `request_base_rate` \| `config_base_rate` \| `none` |
| `requestedBaseRate` | Giá trị `base_rate` đã nhận (null nếu không truyền)                |
| `requestedCapRaw`   | Giá trị `cap` đã nhận (null nếu không truyền)                      |

### Định dạng URL được hỗ trợ

- URL đầy đủ: `https://shopee.vn/product/<shop_id>/<item_id>`
- Dạng path: `-i.<shop_id>.<item_id>`, `/product/<shop_id>/<item_id>`, `/opaanlp/<shop_id>/<item_id>`
- Query: `?item_id=...` hoặc `?itemId=...`
- **Short link:** `s.shopee.vn`, `vn.shp.ee` — API sẽ resolve sang URL gốc (timeout 3s) rồi lấy `item_id`.

---

## Ví dụ request

### Theo item_id

```
GET https://data.addlivetag.com/product-data/product-data.php?item_id=1589295236
```

### Theo URL sản phẩm

```
GET https://data.addlivetag.com/product-data/product-data.php?url=https://shopee.vn/product/38003654/1589295236
```

### POST (tùy chọn)

```
POST https://data.addlivetag.com/product-data/product-data.php
Content-Type: application/x-www-form-urlencoded

item_id=1589295236
```

hoặc

```
url=https://shopee.vn/product/38003654/1589295236
```

---

## Response

### Content-Type

`application/json; charset=utf-8`

### Thành công (có dữ liệu sản phẩm)

**Ví dụ:** `?item_id=1589295236`

```json
{
  "status": "success",
  "productInfo": {
    "itemId": 1589295236,
    "catId": 100892,
    "catIds": [100630, 100664, 100892],
    "catName": "Nước cân bằng da",
    "catPath": ["Sắc Đẹp", "Nước cân bằng da"],
    "productName": "Áo Len Nam Nữ Cổ Lọ Quảng Châu Form Basic Dài Tay Dày Dặn Mềm Mịn Cực Ấm Hàn Quốc Nhiều Màu DYACI AL83",
    "shopName": "DYACI",
    "price": 122200,
    "sales": 990,
    "imageUrl": "https://cf.shopee.vn/file/vn-11134207-7r98o-lpg62kjcq15n6b",
    "productLink": "https://shopee.vn/product/38003654/1589295236",
    "rating": "4.80",
    "commission": 21996,
    "sellerComFinal": 16497,
    "shopeeComFinal": 5499,
    "isXtra": true,
    "hasSellerCommission": true,
    "hasShopeeCommission": true,
    "isCapped": false,
    "isLimitCap": false,
    "cap": 40000,
    "capRaw": 40000,
    "capAfterRate": 40000,
    "lastUpdate": "2026-03-12 07:39:03",
    "dataSource": "db",
    "shopId": 38003654,
    "originLink": "https://shopee.vn/product/38003654/1589295236",
    "affiliateId": null,
    "subId": null,
    "affLink": null
  }
}
```

### Giải thích trường `productInfo`

| Trường                | Kiểu          | Mô tả                                                                             |
| --------------------- | ------------- | --------------------------------------------------------------------------------- |
| `itemId`              | number        | ID sản phẩm Shopee.                                                               |
| `catId`               | number/null   | Danh mục lá (cụ thể nhất) của sản phẩm — phần tử cuối của `catIds`.               |
| `catIds`              | number[]      | Đường dẫn danh mục Shopee từ gốc → lá, ví dụ `[100011, 100049]`.                  |
| `catName`             | string        | Tên danh mục lá. **Vắng mặt hoàn toàn** khi chưa tra được tên (không trả `null`).  |
| `catPath`             | string[]      | Tên các cấp đã biết, theo thứ tự gốc → lá. Cấp chưa biết tên bị bỏ qua, nên **có thể ngắn hơn `catIds`**. Vắng mặt khi không biết tên cấp nào. |
| `productName`         | string        | Tên sản phẩm.                                                                     |
| `shopName`            | string        | Tên shop.                                                                         |
| `price`               | number        | Giá hiện tại (VNĐ).                                                               |
| `sales`               | number        | Đã bán (historical sold).                                                         |
| `imageUrl`            | string        | URL ảnh chính.                                                                    |
| `productLink`         | string        | Link sản phẩm Shopee.                                                             |
| `rating`              | string/number | Đánh giá sao.                                                                     |
| `commission`          | number        | Tổng hoa hồng sau thuế/user rate (VNĐ).                                           |
| `sellerComFinal`      | number        | Hoa hồng seller (sau user rate & tax).                                            |
| `shopeeComFinal`      | number        | Hoa hồng Shopee (sau cap 40k & giới hạn 8%).                                      |
| `isXtra`              | boolean       | Có tham gia Xtra (seller commission).                                             |
| `hasSellerCommission` | boolean       | Có hoa hồng từ seller.                                                            |
| `hasShopeeCommission` | boolean       | Có hoa hồng từ Shopee.                                                            |
| `isCapped`            | boolean       | Hoa hồng Shopee bị giới hạn cap.                                                  |
| `isLimitCap`          | boolean       | Trùng logic với `isCapped`.                                                       |
| `cap`                 | number        | Cap hoa hồng áp dụng (sau rate).                                                  |
| `capRaw`              | number        | Cap gốc hoa hồng Shopee (40,000 VNĐ).                                             |
| `capAfterRate`        | number        | Cap sau khi áp dụng user rate.                                                    |
| `lastUpdate`          | string        | Thời điểm cập nhật dữ liệu (datetime).                                            |
| `dataSource`          | string        | Nguồn: `"api"` (mới từ Shopee), `"db"` (cache), `"fallback"` (không có chi tiết). |
| `shopId`              | number/null   | ID shop.                                                                          |
| `originLink`          | string/null   | Landing page sạch (bỏ query tracking) dùng dựng link affiliate.                   |
| `affiliateId`         | string/null   | `affid` đã nhận từ request.                                                       |
| `subId`               | string/null   | sub_id sau chuẩn hoá.                                                             |
| `affLink`             | string/null   | Long-link affiliate `an_redir` — chỉ có khi truyền `affid`.                       |

### Lưu ý về `catId` / `catIds`

- Là **id danh mục của Shopee** (lấy từ `productCatIds` của Shopee Affiliate API), không phải `category_id` nội bộ trong DB. API không trả tên danh mục, chỉ trả id.
- Shopee luôn trả đủ 3 phần tử và **độn số `0`** cho cấp không có (`[100011, 100049, 0]`); API này đã loại bỏ các số `0` đó, nên độ dài `catIds` là số cấp thật. Đừng lấy `catIds[2]` làm danh mục lá — dùng `catId`.
- Chỉ được điền khi dữ liệu đi qua Shopee API. Sản phẩm đang nằm sẵn trong cache DB từ trước ngày 07/09/2026 sẽ trả `catIds: []` cho tới lượt refresh kế tiếp — cần ngay thì gọi `?clear_cache=1`.
- Shopee thỉnh thoảng trả rỗng cho sản phẩm còn sống; khi đó giá trị đã lưu trước đó được giữ nguyên, không bị xoá.

### Lưu ý về `catName` / `catPath` — QUAN TRỌNG

Tên danh mục **không phải Shopee cung cấp**. Affiliate API chỉ trả id, và hệ id đó
(`100011`) khác hệ id của web shopee.vn (`11035567`) nên cây danh mục công khai không map
được. Tên ở đây là **suy luận thống kê**, không phải dữ liệu chính thống.

Hệ quả khi dùng:

- **Độ phủ chưa đầy đủ** và sẽ tăng dần theo thời gian. Chỗ nào không đủ tin cậy thì **bỏ
  hẳn khỏi response**, không trả `null` và không đoán bừa. Nên `catName` / `catPath` có thể
  không xuất hiện — code bên gọi phải kiểm tra key tồn tại trước khi đọc.
- **`catPath` không khớp vị trí với `catIds`.** Cấp nào chưa biết tên thì bị bỏ qua, nên
  `catPath` có thể ngắn hơn. Ví dụ `catIds: [100011, 100049]` mà chỉ biết tên cấp 1 thì
  `catPath: ["Thời Trang Nam"]`. Muốn biết chắc sản phẩm thuộc danh mục nào thì dùng
  `catIds`, đừng suy từ độ dài `catPath`.
- **Tên có thể trùng nhau giữa hai danh mục khác nhau cùng cấp.** Tên vẫn đúng ngành hàng
  đại thể nhưng hai `catId` khác nhau có thể ra cùng một `catName`. **Đừng dùng `catName`
  làm khoá**; khoá là `catId`.
- Cần chính xác tuyệt đối thì dùng `catId`, hoặc tự map sang cây danh mục của tài khoản
  Shopee Open Platform của bạn.

### Các khối ở cấp cao nhất

Ngoài `status` và `productInfo`, response còn kèm:

| Khoá | Khi nào xuất hiện | Nội dung |
| --- | --- | --- |
| `legalNotice` | Luôn luôn | Phạm vi sử dụng (học tập/nghiên cứu/nội bộ phi thương mại), cảnh báo nguồn không chính thống, `brandAliases` (`san_cam` = Shopee, `san_den` = Lazada). |
| `apiKeyNotice` | Chỉ khi **chưa có key hợp lệ** | `status` (`missing`/`invalid`), `daysLeft`, `requiredFrom`, hướng dẫn lấy và gửi key. Xem [docs/api-key.md](docs/api-key.md). |
| `warning` | Khi trả dữ liệu cũ / không đủ | Xem mục dưới. |

Cả hai khối đều là **khoá mới thêm vào**, không đụng tới field cũ — tool cũ bỏ qua khoá lạ
nên không cần sửa gì.

### Khi dùng cache hoặc API lỗi

- Nếu có dữ liệu cache: vẫn trả `status: "success"` và có thể kèm `"warning": "Using cached data - API fetch failed"`.
- Nếu không tìm thấy sản phẩm và API lỗi: vẫn `status: "success"`, `productInfo` với các field chính = `null` hoặc 0, và `"warning": "Product not found in database and API fetch failed"`.

### Lỗi (HTTP 4xx/5xx)

**Thiếu tham số (400):**

```json
{
  "status": "error",
  "message": "item_id or valid Shopee URL is required"
}
```

**Rate limit (429):**

```json
{
  "status": "error",
  "message": "Rate limit exceeded. Please try again later."
}
```

**Lỗi server (500):**

```json
{
  "status": "error",
  "message": "Internal server error",
  "error": "..."
}
```

---

## Rate limit

Giới hạn theo **IP** (qua Cloudflare / X-Forwarded-For / REMOTE_ADDR), đếm riêng từng luồng:

| Luồng | Có API Key | Chưa có API Key |
| --- | --- | --- |
| Gọi API nguồn (sản phẩm chưa có/hết hạn cache) | 150 request/phút | 60 request/phút (40%) |
| Đọc từ database (cache còn hạn) | 2.000 request/phút | 800 request/phút (40%) |
| Resolve link rút gọn (`s.shopee.vn`, `vn.shp.ee`) | 30 request/phút | 12 request/phút (40%) |

- Vượt giới hạn: HTTP **429** và JSON `status: "error"` như trên.
- Tỉ lệ 40% cho bên chưa có key có thể bị siết thêm trước 01/10/2026 — xem
  [docs/api-key.md](docs/api-key.md). Đừng thiết kế hệ thống dựa vào con số này.
- Hạn mức phía nguồn là **tài nguyên dùng chung** cho tất cả người gọi, và thấp hơn nhiều so
  với hạn mức đọc cache. Cần quét số lượng lớn thì dùng
  [endpoint batch](docs/product-data-batch.md), đừng vòng lặp qua endpoint đơn.

---

## Cache & nguồn dữ liệu

- Dữ liệu sản phẩm (giá, hoa hồng, v.v.) được lưu DB và **cache 3 giờ** (`CACHE_DURATION = 10800` trong `config.php`).
- Luồng xử lý:
  1. Có bản ghi trong DB và chưa hết hạn cache → trả từ **db** (`dataSource: "db"`), không gọi Shopee API.
  2. Hết hạn hoặc chưa có trong DB → gọi Shopee API, lưu DB, trả từ **api** (`dataSource: "api"`).
  3. Gọi API Shopee lỗi nhưng có bản ghi cũ → trả cache kèm `warning`.
  4. Không có DB và API lỗi → trả `productInfo` tối thiểu và `dataSource: "fallback"` với `warning`.

---

## Hoa hồng (commission)

- **Seller commission (Xtra):** không cap, tính theo tỷ lệ seller và áp dụng user rate & tax.
- **Shopee commission:** cap 40,000 VNĐ (raw), và giới hạn tối đa 8% giá sản phẩm; giá trị cuối là mức thấp hơn trong các giới hạn đó (và sau user rate).
- `commission` = `sellerComFinal` + `shopeeComFinal` (đơn vị VNĐ).

---

## OPTIONS (CORS)

- Method: `OPTIONS` được hỗ trợ; server trả HTTP 200 không body để preflight.

---

## Tóm tắt endpoint

| Mục        | Giá trị                                                     |
| ---------- | ----------------------------------------------------------- |
| URL        | `https://data.addlivetag.com/product-data/product-data.php` |
| Method     | GET, POST, OPTIONS                                          |
| Query/body | `item_id` (số) **hoặc** `url` (URL Shopee)                  |
| Xác thực   | `X-API-Key: <key>` hoặc `&key=<key>` — **bắt buộc từ 01/10/2026** |
| Response   | JSON, `status` + `productInfo` hoặc `message` (khi lỗi)     |
| Rate limit | 150/phút (api), 2.000/phút (db), 30/phút (short link) — theo IP; còn 40% nếu chưa có key |
| Cache      | 3 giờ (`CACHE_DURATION = 10800`)                            |
| Timezone   | Asia/Ho_Chi_Minh (cho `lastUpdate`)                         |
