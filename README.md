# Shopee Affiliate API Documentation

> Tài liệu tổng hợp các API chính thống của Shopee Affiliate để lấy dữ liệu sản phẩm, tạo link affiliate và theo dõi conversion tự động.

**Nguồn chính thức:** [https://affiliate.shopee.vn/open_api/list](https://affiliate.shopee.vn/open_api/list)

---

## 📚 Mục lục

- [🧩 Phạm vi tài liệu trong repo này](#-phạm-vi-tài-liệu-trong-repo-này)
- [🚀 Demo & công cụ liên quan](#-demo--công-cụ-liên-quan)
- [🔑 Bắt đầu - Yêu cầu cần có](#-bắt-đầu---yêu-cầu-cần-có)
- [📋 Danh sách các Endpoint API](#-danh-sách-các-endpoint-api)
    - [1. Sản phẩm (Products)](#1-sản-phẩm-products)
    - [2. Danh sách Offer (Offer Lists)](#2-danh-sách-offer-offer-lists)
    - [3. Link Affiliate (Affiliate Links)](#3-link-affiliate-affiliate-links)
    - [4. Báo cáo Conversion (Conversion Reports)](#4-báo-cáo-conversion-conversion-reports)
- [⚠️ Mã lỗi (Error Codes)](#-mã-lỗi-error-codes)
- [🔄 Cập nhật phiên bản (Version Updates)](#-cập-nhật-phiên-bản-version-updates)
- [📝 Ghi chú & Lưu ý quan trọng](#-ghi-chú--lưu-ý-quan-trọng)

## 🧩 Phạm vi tài liệu trong repo này

`README.md` này **chỉ** tổng hợp các API chính thống của Shopee Affiliate (Open API / GraphQL) từ tài liệu chính thức.

### Phân tách tài liệu

- API chính thống Shopee: trong `README.md` này.
- API không chính thống (Product Data API): xem `product-data-api.md`.

### Nhóm API chính thống được đề cập

- Product APIs (REST)
- Offer APIs (GraphQL)
- Affiliate Link API (GraphQL Mutation)
- Conversion/Validated Report APIs (GraphQL)

## 🚀 Demo & công cụ liên quan

- Demo Shopee Affiliate API: `https://addlivetag.com/shopee-affiliate-api/index.php`
- Danh sách công cụ Addlivetag: `https://addlivetag.com/`
- Một số công cụ nổi bật trên Addlivetag:
    - Tạo link Affiliate Shopee
    - Shopee Affiliate API (DEV)
    - Mở giỏ video Shopee
    - Tính hoa hồng và số đơn hàng Shopee (PC/Extension)
    - Lấy liên kết gốc, Tạo link rút gọn, MCN Manager

## 🔑 Bắt đầu - Yêu cầu cần có

Trước khi sử dụng các API, bạn cần có:

1. **Tài khoản Shopee Affiliate:** Đăng ký tại [Shopee Affiliate Vietnam](https://affiliate.shopee.vn/).
2. **`app_id` và `secret_key`:** Sau khi đăng ký, bạn sẽ nhận được các thông tin này từ Shopee để xác thực các yêu cầu API.
3. **`access_token`:** Token xác thực người dùng, cần thiết cho các API yêu cầu quyền truy cập cá nhân.
4. **`signature`:** Chuỗi mã hóa được tạo từ `secret_key` và các tham số request để đảm bảo bảo mật.

---

## 📋 Danh sách các Endpoint API

Dưới đây là danh sách các API chính được phân loại theo chức năng.

### 1. Sản phẩm (Products)

Các API dùng để lấy thông tin chi tiết về sản phẩm, tìm kiếm sản phẩm và lấy danh sách sản phẩm hot.

#### `product_item_get`

Lấy thông tin chi tiết của một sản phẩm cụ thể dựa trên ID sản phẩm.

- **Phương thức:** `GET`
- **Mô tả:** Trả về toàn bộ thông tin của sản phẩm bao gồm giá, số lượng tồn kho, mô tả, hình ảnh, v.v.

| Tham số          | Kiểu dữ liệu | Bắt buộc | Mô tả                              |
| :--------------- | :----------- | :------- | :--------------------------------- |
| `item_id`        | `string`     | Có       | ID của sản phẩm cần lấy thông tin. |
| `shop_id`        | `integer`    | Không    | ID của gian hàng.                  |
| `affiliate_link` | `boolean`    | Không    | Yêu cầu trả về link affiliate.     |

**Ví dụ Request:**

```bash
curl -X GET "https://open.shopee.vn/openapi/product/v2/product_item_get?item_id=123456789&shop_id=987654" \
-H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

**Ví dụ Response (JSON):**

```json
{
    "code": 0,
    "message": "success",
    "data": {
        "item_id": "123456789",
        "shop_id": 987654,
        "title": "Áo Thun Nam Cổ Tròn Thời Trang",
        "description": "Áo thun chất liệu cotton co giãn...",
        "images": ["https://cf.shopee.vn/file/..._tn.jpg", "https://cf.shopee.vn/file/..._tn.jpg"],
        "price": 150000,
        "stock": 50,
        "affiliate_link": "https://shope.ee/XYZABCD"
    }
}
```

#### `product_search`

Tìm kiếm sản phẩm theo từ khóa.

- **Phương thức:** `GET`
- **Mô tả:** Trả về danh sách các sản phẩm phù hợp với từ khóa tìm kiếm.

| Tham số   | Kiểu dữ liệu | Bắt buộc | Mô tả                                             |
| :-------- | :----------- | :------- | :------------------------------------------------ |
| `keyword` | `string`     | Có       | Từ khóa tìm kiếm.                                 |
| `limit`   | `integer`    | Không    | Số lượng sản phẩm tối đa trả về (mặc định: 10).   |
| `offset`  | `integer`    | Không    | Vị trí bắt đầu lấy sản phẩm (dùng để phân trang). |

#### `product_item_recommend_get`

Lấy danh sách sản phẩm gợi ý liên quan.

- **Phương thức:** `GET`
- **Mô tả:** Dựa trên một sản phẩm, API sẽ trả về các sản phẩm tương tự hoặc liên quan.

---

### 2. Danh sách Offer (Offer Lists)

Các API dùng để lấy danh sách các offer từ Shopee, bao gồm shop offer và product offer.

#### `shopOfferV2` (Get Shop Offer List)

Lấy danh sách các offer từ các shop trên Shopee.

- **Phương thức:** `GraphQL Query`
- **Query:** `shopOfferV2`
- **ResultType:** `ShopOfferConnectionV2`
- **Mô tả:** Trả về danh sách các shop offer với thông tin về tỷ lệ hoa hồng, loại shop, và thời gian offer.

| Tham số                     | Kiểu dữ liệu | Bắt buộc | Mô tả                                                                                                                                |
| :-------------------------- | :----------- | :------- | :----------------------------------------------------------------------------------------------------------------------------------- |
| `shopId` (New)              | `Int64`      | Không    | Tìm kiếm theo shop id.                                                                                                               |
| `keyword`                   | `String`     | Không    | Tìm kiếm theo tên shop.                                                                                                              |
| `shopType` (New)            | `[Int]`      | Không    | Lọc theo loại shop: `OFFICIAL_SHOP = 1` (Shopee Mall), `PREFERRED_SHOP = 2` (Preferred), `PREFERRED_PLUS_SHOP = 4` (Preferred Plus). |
| `isKeySeller` (New)         | `Bool`       | Không    | Lọc offer từ key sellers: `TRUE` = chỉ key sellers, `FALSE` = tất cả.                                                                |
| `sortType`                  | `Int`        | Không    | Loại sắp xếp: `1` = theo thời gian cập nhật, `2` = theo tỷ lệ hoa hồng cao nhất, `3` = theo shop phổ biến.                           |
| `sellerCommCoveRatio` (New) | `String`     | Không    | Tỷ lệ sản phẩm có hoa hồng từ seller (ví dụ: "0.123" = ≥ 1.23%).                                                                     |
| `page`                      | `Int`        | Không    | Số trang.                                                                                                                            |
| `limit`                     | `Int`        | Không    | Số lượng dữ liệu mỗi trang (mặc định: 10).                                                                                           |

**Response Structure:**

- `nodes`: Danh sách `ShopOfferV2`
- `pageInfo`: Thông tin phân trang

**ShopOfferV2 Structure:**

| Field                       | Type         | Mô tả                                                                                                        |
| :-------------------------- | :----------- | :----------------------------------------------------------------------------------------------------------- |
| `commissionRate`            | `String`     | Tỷ lệ hoa hồng (ví dụ: "0.25" = 25%).                                                                        |
| `imageUrl`                  | `String`     | URL hình ảnh shop.                                                                                           |
| `offerLink`                 | `String`     | Link offer affiliate.                                                                                        |
| `originalLink`              | `String`     | Link shop gốc.                                                                                               |
| `shopId`                    | `Int64`      | Shop ID.                                                                                                     |
| `shopName`                  | `String`     | Tên shop.                                                                                                    |
| `ratingStar` (New)          | `String`     | Đánh giá shop.                                                                                               |
| `shopType` (New)            | `[Int]`      | Loại shop: `1` = Official, `2` = Preferred, `4` = Preferred Plus.                                            |
| `remainingBudget` (New)     | `Int`        | Ngân sách còn lại: `0` = Không giới hạn, `3` = Bình thường (>50%), `2` = Thấp (<50%), `1` = Rất thấp (<30%). |
| `periodStartTime`           | `Int`        | Thời gian bắt đầu offer (Unix timestamp).                                                                    |
| `periodEndTime`             | `Int`        | Thời gian kết thúc offer (Unix timestamp).                                                                   |
| `sellerCommCoveRatio` (New) | `String`     | Tỷ lệ sản phẩm có hoa hồng từ seller.                                                                        |
| `bannerInfo`                | `BannerInfo` | Thông tin banner.                                                                                            |

#### `productOfferV2` (Get Product Offer List)

Lấy danh sách các offer từ sản phẩm trên Shopee.

- **Phương thức:** `GraphQL Query`
- **Query:** `productOfferV2`
- **ResultType:** `ProductOfferConnectionV2`
- **Mô tả:** Trả về danh sách các product offer với thông tin chi tiết về sản phẩm và hoa hồng.

| Tham số              | Kiểu dữ liệu | Bắt buộc | Mô tả                                                                                                                 |
| :------------------- | :----------- | :------- | :-------------------------------------------------------------------------------------------------------------------- |
| `shopId` (New)       | `Int64`      | Không    | Tìm kiếm theo shop id.                                                                                                |
| `itemId` (New)       | `Int64`      | Không    | Tìm kiếm theo item id.                                                                                                |
| `productCatId` (New) | `Int32`      | Không    | Lọc theo category id (Level 1/2/3). Xem [category guide](https://banhang.shopee.vn/edu/category-guide) cho VN.        |
| `listType`           | `Int`        | Không    | Loại danh sách: `0` = Tất cả, `2` = Top performing, `3` = Landing category, `4` = Detail category, `5` = Detail shop. |
| `matchId`            | `Int64`      | Không    | ID tương ứng với listType (CategoryId cho listType 3,4; ShopId cho listType 5).                                       |
| `keyword`            | `String`     | Không    | Tìm kiếm theo tên sản phẩm.                                                                                           |
| `sortType`           | `Int`        | Không    | Sắp xếp: `1` = Liên quan, `2` = Số lượng bán, `3` = Giá cao→thấp, `4` = Giá thấp→cao, `5` = Hoa hồng cao→thấp.        |
| `page`               | `Int`        | Không    | Số trang.                                                                                                             |
| `isAMSOffer` (New)   | `Bool`       | Không    | Lọc offer có seller commission: `TRUE` = chỉ AMS offer, `FALSE` = tất cả.                                             |
| `isKeySeller` (New)  | `Bool`       | Không    | Lọc offer từ key sellers.                                                                                             |
| `limit`              | `Int`        | Không    | Số lượng dữ liệu mỗi trang (mặc định: 10).                                                                            |

**Response Structure:**

- `nodes`: Danh sách `ProductOfferV2`
- `pageInfo`: Thông tin phân trang

**ProductOfferV2 Structure:**

| Field                        | Type     | Mô tả                                                                 |
| :--------------------------- | :------- | :-------------------------------------------------------------------- |
| `itemId`                     | `Int64`  | Item ID.                                                              |
| `commissionRate`             | `String` | Tỷ lệ hoa hồng tối đa (ví dụ: "0.25" = 25%).                          |
| `sellerCommissionRate` (New) | `String` | Tỷ lệ hoa hồng từ seller (Commission Xtra).                           |
| `shopeeCommissionRate` (New) | `String` | Tỷ lệ hoa hồng từ Shopee.                                             |
| `commission` (New)           | `String` | Số tiền hoa hồng = giá × commissionRate (đơn vị: tiền tệ địa phương). |
| `sales`                      | `Int32`  | Số lượng đã bán.                                                      |
| `priceMax` (New)             | `String` | Giá tối đa của sản phẩm.                                              |
| `priceMin` (New)             | `String` | Giá tối thiểu của sản phẩm.                                           |
| `productCatIds` (New)        | `[Int]`  | Category id (Level 1, 2, 3).                                          |
| `ratingStar` (New)           | `String` | Đánh giá sản phẩm.                                                    |
| `priceDiscountRate` (New)    | `Int`    | Tỷ lệ giảm giá (ví dụ: 10 = 10%).                                     |
| `imageUrl`                   | `String` | URL hình ảnh sản phẩm.                                                |
| `productName`                | `String` | Tên sản phẩm.                                                         |
| `shopId` (New)               | `Int64`  | Shop ID.                                                              |
| `shopName`                   | `String` | Tên shop.                                                             |
| `shopType` (New)             | `[Int]`  | Loại shop.                                                            |
| `productLink`                | `String` | Link sản phẩm.                                                        |
| `offerLink`                  | `String` | Link offer affiliate.                                                 |
| `periodStartTime`            | `Int`    | Thời gian bắt đầu offer (Unix timestamp).                             |
| `periodEndTime`              | `Int`    | Thời gian kết thúc offer (Unix timestamp).                            |

---

### 3. Link Affiliate (Affiliate Links)

Các API dùng để tạo link affiliate từ một link sản phẩm thông thường.

#### `generateShortLink` (Get Short Link)

Chuyển đổi một link Shopee thông thường thành link affiliate ngắn gọn có gắn mã của bạn.

- **Phương thức:** `GraphQL Mutation`
- **Mutation:** `generateShortLink`
- **ResultType:** `ShortLinkResult!`
- **Mô tả:** Đây là API quan trọng nhất để tạo link kiếm hoa hồng. API này sử dụng GraphQL.

| Tham số     | Kiểu dữ liệu | Bắt buộc | Mô tả                                                               |
| :---------- | :----------- | :------- | :------------------------------------------------------------------ |
| `originUrl` | `String!`    | Có       | Link Shopee gốc (sản phẩm, shop, v.v.).                             |
| `subIds`    | `[String]`   | Không    | Mảng sub id trong utm content của tracking link (tối đa 5 sub ids). |

**Response Structure:**

| Field       | Type      | Mô tả                |
| :---------- | :-------- | :------------------- |
| `shortLink` | `String!` | Link affiliate ngắn. |

**Ví dụ Request (GraphQL):**

```bash
curl -X POST 'https://open-api.affiliate.shopee.vn/graphql' \
-H 'Authorization: SHA256 Credential=123456, Signature=x9bc0bd3ba6c41d98a591976bf95db97a58720a9e6d778845408765c3fafad69d, Timestamp=1577836800' \
-H 'Content-Type: application/json' \
--data-raw '{
  "query": "mutation{\n    generateShortLink(input:{originUrl:\"https://shopee.vn/Apple-Iphone-11-128GB-Local-Set-i.52377417.6309028319\",subIds:[\"s1\",\"s2\",\"s3\",\"s4\",\"s5\"]}){\n        shortLink\n    }\n}"
}'
```

**Ví dụ Response (JSON):**

```json
{
    "data": {
        "generateShortLink": {
            "shortLink": "https://shope.ee/5XyZ7WqR"
        }
    }
}
```

---

### 4. Báo cáo Conversion (Conversion Reports)

Các API dùng để lấy thông tin về các đơn hàng và conversion được tạo qua link affiliate của bạn.

#### `conversionReport` (Get Conversion Report)

Lấy báo cáo chi tiết về các conversion (đơn hàng) trong một khoảng thời gian.

- **Phương thức:** `GraphQL Query`
- **Query:** `conversionReport`
- **ResultType:** `ConversionReportConnection!`
- **Mô tả:** Giúp bạn theo dõi hiệu quả, hoa hồng và chi tiết đơn hàng.

| Tham số                     | Kiểu dữ liệu | Bắt buộc | Mô tả                                                                                                                               |
| :-------------------------- | :----------- | :------- | :---------------------------------------------------------------------------------------------------------------------------------- |
| `purchaseTimeStart`         | `Int`        | Không    | Thời gian bắt đầu đặt hàng (Unix timestamp).                                                                                        |
| `purchaseTimeEnd`           | `Int`        | Không    | Thời gian kết thúc đặt hàng (Unix timestamp).                                                                                       |
| `completeTimeStart`         | `Int`        | Không    | Thời gian bắt đầu hoàn thành đơn hàng (Unix timestamp).                                                                             |
| `completeTimeEnd`           | `Int`        | Không    | Thời gian kết thúc hoàn thành đơn hàng (Unix timestamp).                                                                            |
| `shopName`                  | `String`     | Không    | Tên shop.                                                                                                                           |
| `shopId`                    | `Int64`      | Không    | Shop ID.                                                                                                                            |
| `shopType`                  | `[String]`   | Không    | Loại shop: `ALL`, `SHOPEE_MALL_CB`, `SHOPEE_MALL_NON_CB`, `C2C_CB`, `C2C_NON_CB`, `PREFERRED_CB`, `PREFERRED_NON_CB`.               |
| `conversionId`              | `Int64`      | Không    | Conversion ID (trước đây là Checkout ID).                                                                                           |
| `orderId`                   | `String`     | Không    | Order ID.                                                                                                                           |
| `productName`               | `String`     | Không    | Tên sản phẩm.                                                                                                                       |
| `productId`                 | `Int64`      | Không    | Product ID.                                                                                                                         |
| `categoryLv1Id`             | `Int64`      | Không    | Category Level 1 ID.                                                                                                                |
| `categoryLv2Id`             | `Int64`      | Không    | Category Level 2 ID.                                                                                                                |
| `categoryLv3Id`             | `Int64`      | Không    | Category Level 3 ID.                                                                                                                |
| `categoryType`              | `String`     | Không    | Loại sản phẩm: `ALL`, `DP` (Digital Product), `MP` (Marketplace Product).                                                           |
| `orderStatus`               | `String`     | Không    | Trạng thái đơn hàng: `ALL`, `UNPAID`, `PENDING`, `COMPLETED`, `CANCELLED`.                                                          |
| `buyerType`                 | `String`     | Không    | Loại người mua: `ALL`, `NEW`, `EXISTING`.                                                                                           |
| `attributionType`           | `String`     | Không    | Loại attribution: `Ordered in Same Shop`, `Ordered in Different Shop`.                                                              |
| `device`                    | `String`     | Không    | Loại thiết bị: `ALL`, `APP`, `WEB`.                                                                                                 |
| `productType`               | `String`     | Không    | Loại sản phẩm: `ALL`, `DP`, `MP`.                                                                                                   |
| `fraudStatus`               | `String`     | Không    | Trạng thái fraud: `ALL`, `UNVERIFIED`, `VERIFIED`, `FRAUD`.                                                                         |
| `campaignPartnerName` (New) | `String`     | Không    | Tên đối tác chiến dịch affiliate.                                                                                                   |
| `campaignType` (New)        | `String`     | Không    | Loại chiến dịch: `ALL`, `Seller Open Campaign`, `Seller Target Campaign`, `MCN Campaign`, `Non-Seller Campaign`.                    |
| `limit`                     | `Int`        | Không    | Số lượng dữ liệu tối đa trả về (tối đa 500 mỗi trang).                                                                              |
| `scrollId` (important)      | `String`     | Không    | Con trỏ trang (cursor). **Lưu ý:** Có thời hạn 30 giây. Phải query trang tiếp theo trong vòng 30 giây, nếu không cursor sẽ hết hạn. |

**Response Structure:**

- `nodes`: Danh sách `ConversionReport`
- `pageInfo`: Thông tin phân trang (bao gồm `scrollId`)

**ConversionReport Structure:**

| Field                        | Type                       | Mô tả                                                                                                     |
| :--------------------------- | :------------------------- | :-------------------------------------------------------------------------------------------------------- |
| `purchaseTime`               | `Int`                      | Thời gian mua hàng (Unix timestamp).                                                                      |
| `clickTime`                  | `Int`                      | Thời gian click link (Unix timestamp).                                                                    |
| `conversionId`               | `Int64`                    | Conversion ID.                                                                                            |
| `shopeeCommissionCapped`     | `String`                   | Hoa hồng Shopee sau khi áp dụng commission cap (đơn vị: tiền tệ địa phương).                              |
| `sellerCommission`           | `String`                   | Hoa hồng từ Seller (đơn vị: tiền tệ địa phương).                                                          |
| `totalCommission`            | `String`                   | Tổng hoa hồng từ seller và Shopee, sau khi áp dụng commission cap nhưng trước khi trừ MCN management fee. |
| `buyerType`                  | `String`                   | Trạng thái người mua: `New` hoặc `Existing`.                                                              |
| `utmContent`                 | `String`                   | Sub id (giá trị từ sub-id parameter trong affiliate link).                                                |
| `device`                     | `String`                   | Loại thiết bị.                                                                                            |
| `referrer`                   | `String`                   | Referrer.                                                                                                 |
| `orders`                     | `[ConversionReportOrder]!` | Danh sách đơn hàng trong conversion.                                                                      |
| `linkedMcnName` (New)        | `String`                   | Tên MCN mà affiliate đã liên kết.                                                                         |
| `mcnContractId` (New)        | `Int64`                    | Contract ID giữa affiliate và MCN.                                                                        |
| `mcnManagementFeeRate` (New) | `String`                   | Tỷ lệ phí quản lý MCN dựa trên gross commission.                                                          |
| `mcnManagementFee` (New)     | `String`                   | Phí quản lý MCN dựa trên tổng gross commission.                                                           |
| `netCommission` (New)        | `String`                   | Hoa hồng ròng từ seller và Shopee, sau khi áp dụng commission cap và trừ MCN management fee.              |
| `campaignType` (New)         | `String`                   | Loại chiến dịch.                                                                                          |

**ConversionReportOrder Structure:**

| Field         | Type                           | Mô tả                                                               |
| :------------ | :----------------------------- | :------------------------------------------------------------------ |
| `orderId`     | `String`                       | Order ID.                                                           |
| `orderStatus` | `String`                       | Trạng thái đơn hàng: `UNPAID`, `PENDING`, `COMPLETED`, `CANCELLED`. |
| `shopType`    | `String`                       | Loại shop.                                                          |
| `items`       | `[ConversionReportOrderItem]!` | Danh sách items trong đơn hàng.                                     |

**ConversionReportOrderItem Structure:**

| Field                        | Type     | Mô tả                                                                                     |
| :--------------------------- | :------- | :---------------------------------------------------------------------------------------- |
| `shopId`                     | `Int64`  | Shop ID.                                                                                  |
| `shopName`                   | `String` | Tên shop.                                                                                 |
| `completeTime`               | `Int`    | Thời gian hoàn thành đơn hàng (Unix timestamp).                                           |
| `itemId`                     | `Int64`  | Item ID.                                                                                  |
| `itemName`                   | `String` | Tên item.                                                                                 |
| `itemPrice`                  | `String` | Giá item (đơn vị: tiền tệ địa phương).                                                    |
| `displayItemStatus` (New)    | `String` | Trạng thái kết hợp của order status và fraud status.                                      |
| `actualAmount`               | `String` | Giá trị mua hàng thực tế (đã trừ rebates, vouchers, discounts, cashback, shipping fee).   |
| `qty`                        | `Int`    | Số lượng.                                                                                 |
| `imageUrl`                   | `String` | URL hình ảnh.                                                                             |
| `itemTotalCommission`        | `String` | Tổng hoa hồng từ seller và Shopee trước khi trừ MCN management fee.                       |
| `itemSellerCommission`       | `String` | Hoa hồng từ Seller offer trong một item.                                                  |
| `itemSellerCommissionRate`   | `String` | Tỷ lệ hoa hồng từ seller.                                                                 |
| `itemShopeeCommissionCapped` | `String` | Hoa hồng Shopee trong một item (sau order cap).                                           |
| `itemShopeeCommissionRate`   | `String` | Tỷ lệ hoa hồng từ Shopee.                                                                 |
| `itemNotes`                  | `String` | Giải thích về trạng thái pending, cancel, và fraud.                                       |
| `channelType`                | `String` | Kênh nguồn đơn hàng của người mua.                                                        |
| `attributionType`            | `String` | Loại attribution cụ thể của đơn hàng.                                                     |
| `globalCategoryLv1Name`      | `String` | Tên category Level 1 toàn cầu.                                                            |
| `globalCategoryLv2Name`      | `String` | Tên category Level 2 toàn cầu.                                                            |
| `globalCategoryLv3Name`      | `String` | Tên category Level 3 toàn cầu.                                                            |
| `refundAmount`               | `String` | Số tiền hoàn lại (chỉ cho Digital Product, đơn hàng đã xác nhận nhận với partial refund). |
| `fraudStatus`                | `String` | Trạng thái fraud.                                                                         |
| `modelId`                    | `Int64`  | Model ID là ID duy nhất cho mỗi biến thể item.                                            |
| `promotionId`                | `String` | Promotion ID là ID duy nhất cho bundle deal và add on deal items.                         |
| `campaignPartnerName` (New)  | `String` | Tên đối tác chiến dịch đã khởi tạo MCN campaign.                                          |
| `campaignType` (New)         | `String` | Loại chiến dịch.                                                                          |

#### `validatedReport` (Get Validated Report)

Lấy báo cáo đã được xác thực (validated) dựa trên validation ID từ Billing Information.

- **Phương thức:** `GraphQL Query`
- **Query:** `validatedReport`
- **ResultType:** `ValidatedReportConnection!`
- **Mô tả:** Trả về chi tiết billing đã được xác thực tương ứng với validation ID.

| Tham số                | Kiểu dữ liệu | Bắt buộc | Mô tả                                                                                                                               |
| :--------------------- | :----------- | :------- | :---------------------------------------------------------------------------------------------------------------------------------- |
| `validationId` (New)   | `Int64`      | Có       | Validation ID, có thể tìm thấy trong Billing Information.                                                                           |
| `limit`                | `Int`        | Không    | Số lượng dữ liệu tối đa trả về (tối đa 500 mỗi trang).                                                                              |
| `scrollId` (important) | `String`     | Không    | Con trỏ trang (cursor). **Lưu ý:** Có thời hạn 30 giây. Phải query trang tiếp theo trong vòng 30 giây, nếu không cursor sẽ hết hạn. |

**Response Structure:**

- `nodes`: Danh sách `ValidatedReport`
- `pageInfo`: Thông tin phân trang (bao gồm `scrollId`)

**ValidatedReport Structure:**

Tương tự như `ConversionReport`, nhưng đây là dữ liệu đã được xác thực và sẽ được thanh toán. Các field chính:

| Field                        | Type                      | Mô tả                                                             |
| :--------------------------- | :------------------------ | :---------------------------------------------------------------- |
| `purchaseTime`               | `Int`                     | Thời gian mua hàng.                                               |
| `clickTime`                  | `Int`                     | Thời gian click link.                                             |
| `conversionId`               | `Int64`                   | Conversion ID.                                                    |
| `shopeeCommissionCapped`     | `String`                  | Hoa hồng Shopee sau khi áp dụng commission cap.                   |
| `sellerCommission`           | `String`                  | Hoa hồng từ Seller.                                               |
| `totalCommission`            | `String`                  | Tổng hoa hồng từ seller và Shopee sau khi áp dụng commission cap. |
| `netCommission` (New)        | `String`                  | Hoa hồng ròng sau khi trừ MCN management fee.                     |
| `orders`                     | `[ValidatedReportOrder]!` | Danh sách đơn hàng.                                               |
| `linkedMcnName` (New)        | `String`                  | Tên MCN đã liên kết.                                              |
| `mcnContractId` (New)        | `String`                  | Contract ID với MCN.                                              |
| `mcnManagementFeeRate` (New) | `String`                  | Tỷ lệ phí quản lý MCN.                                            |
| `mcnManagementFee` (New)     | `String`                  | Phí quản lý MCN.                                                  |
| `campaignType` (New)         | `String`                  | Loại chiến dịch.                                                  |

**ValidatedReportOrder và ValidatedReportOrderItem:**

Cấu trúc tương tự như `ConversionReportOrder` và `ConversionReportOrderItem`, nhưng đây là dữ liệu đã được xác thực.

**Lưu ý quan trọng về `scrollId`:**

- `scrollId` có thời hạn 30 giây.
- Khoảng thời gian giữa hai request không được vượt quá 30 giây, nếu không cursor sẽ hết hạn.
- Request đầu tiên (không có scrollId) trả về trang đầu và scrollId.
- Request tiếp theo phải có scrollId để lấy trang tiếp theo.
- scrollId chỉ dùng được một lần và phải query trong vòng 30 giây.
- Request không có scrollId cần có khoảng cách thời gian > 30 giây.

---

## ⚠️ Mã lỗi (Error Codes)

Dưới đây là danh sách các mã lỗi có thể trả về từ API:

| Mã lỗi | Mô tả                                                                                                                                                                                                                 |
| :----- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 11000  | Business Error                                                                                                                                                                                                        |
| 11001  | Params Error: {reason}                                                                                                                                                                                                |
| 11002  | Bind Account Error: {reason}                                                                                                                                                                                          |
| 10020  | Invalid Signature / Your App has been disabled / Request Expired / Invalid Timestamp / Invalid Credential / Invalid Authorization Header / Unsupported Auth Type                                                      |
| 10030  | Rate limit exceeded                                                                                                                                                                                                   |
| 10031  | Access deny                                                                                                                                                                                                           |
| 10032  | Invalid affiliate id                                                                                                                                                                                                  |
| 10033  | Account is frozen                                                                                                                                                                                                     |
| 10034  | Affiliate id in black list                                                                                                                                                                                            |
| 10035  | You currently do not have access to the Shopee Affiliate Open API Platform. Please contact us to request access or learn more. [Contact link](https://help.shopee.vn/portal/webform/c2d6ebc5a2d64dd1b26f8c871730cdbd) |

---

## 🔄 Cập nhật phiên bản (Version Updates)

### Version 2.0

Một số tính năng của Shopee Open API platform đã được tối ưu hóa. Phiên bản API cũ vẫn có sẵn. Các đối tác affiliate có thể chọn cập nhật API tương ứng hoặc không.

### Update 2024-11-15

- Conversion Report API hiện bao gồm các field mới như `netCommission` và `campaignType`, và cập nhật các field hiện có để cung cấp thông tin hoa hồng chi tiết hơn.
- Validated Report API cũng đã được cập nhật với các field mới và sửa đổi các field hiện có.
- Các field mới được đánh dấu là "New", các field đã deprecated được đánh dấu là "To Be Removed".

### Update 2024-11-04

- Thêm các field mới trong ShopOfferV2 API để xem thông tin liên quan đến seller commission.
- Thêm loại sắp xếp mới trong ShopOfferV2 API để hỗ trợ query theo shop phổ biến.

### Update 2023-08-04

- Product Offer V2 API thêm một số field về Item Info và Shop Info.
- Shop Offer V2 API thêm một số field về Item Info, Shop Info và Offer Status.
- Conversion Report API thêm Item Status; và Validated Report API có thể trả về chi tiết billing tương ứng bằng validation id.
- Tất cả field mới được đánh dấu là "New". Tất cả field sẽ bị deprecated được đánh dấu là "To Be Removed".

### Update 2023-05-22

- Checkout response info sẽ thay đổi từ một response cho mỗi checkout ID thành nhiều response cho mỗi checkout ID, điều này xảy ra khi có nhiều order ID dưới cùng một checkout ID.
- Cập nhật/thêm một số field. Các field bị ảnh hưởng được đánh dấu là "New".
- Một số field sẽ bị xóa vào 2023-08-01. Các field bị ảnh hưởng được đánh dấu là "To Be Removed".

### Update 2022-12-20

- Thêm các field mới để phù hợp với tối ưu hóa giao diện front-end trong Conversion Report API.

### Update 2022-05-12

- Sunset các field local category trong Conversion Report API.

### Update 2022-01-30

- Cung cấp Get Brand Offer API v2.0.

### Update 2021-08-10

- Cung cấp các field global category trong Conversion Report API.

### Update 2021-06-15

- Cung cấp thông tin creative banner cho brand offer, bao gồm số lượng banner, fileName, imageUrl, v.v.

### Update 2021-04-19

- Cung cấp offerStatus trong Shopee/Brand/Product offer.
- Hỗ trợ sắp xếp theo ending soon trong Shopee/Brand offer để sắp xếp offer theo thời gian kết thúc từ sớm nhất đến muộn nhất.
- Cung cấp offerUpdates trong Shopee/Brand/Product để theo dõi tất cả thay đổi trạng thái offer.

### Update 2021-03-04

- Cung cấp Product offer API.
- Cung cấp Shop Type trong Conversion Report API.

### Update 2021-01-04

**Cập nhật Getting Offer list API:**

- Cung cấp tỷ lệ hoa hồng chi tiết bao gồm Web (new/existing) và App (new/existing) commission rate.
- Nếu Shop và campaign type của Shopee offer đã được thiết lập bởi các category khác nhau, Open API sẽ cung cấp category commission rate. Nhưng nếu không, API sẽ không cung cấp điều đó.

**Cập nhật Getting Conversion Report:**

- Cung cấp DP orders qua Open API.
- Thêm tùy chọn lọc mới theo product type bao gồm DP (digital products) và MP (marketplace products).
- Thêm trạng thái đơn hàng mới Completed-Partial Refunded, chỉ dành cho Digital Product, đơn hàng đã xác nhận nhận bởi người dùng với partial refund.
- Thêm Fraud Status.
- Thêm tùy chọn lọc theo fraud status bao gồm unverified/verified/fraud.
- Thêm model id là ID duy nhất cho các item thông thường.
- Thêm promotion id là ID duy nhất cho bundle deal và add on deal items.

---

## 📝 Ghi chú & Lưu ý quan trọng

- **Rate Limiting:** Shopee có thể giới hạn số lượng yêu cầu API bạn có thể thực hiện trong một khoảng thời gian nhất định. Hãy kiểm tra tài liệu chính thức để biết giới hạn cụ thể.
- **Tính chính xác của dữ liệu:** Thông tin sản phẩm (giá, tồn kho) có thể thay đổi liên tục. Dữ liệu từ API có thể có độ trễ nhỏ.
- **Điều khoản dịch vụ:** Việc sử dụng API phải tuân thủ nghiêm ngặt các điều khoản dịch vụ của Shopee Affiliate.
- **Luôn kiểm tra tài liệu gốc:** Tài liệu này được tổng hợp và có thể không cập nhật kịp thời. Luôn tham khảo [trang tài liệu chính thức của Shopee](https://affiliate.shopee.vn/open_api/list) để có thông tin mới nhất.
