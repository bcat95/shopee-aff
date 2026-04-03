# Node.js example

## 1) Tạo file env local

```bash
cp .env.example .env
```

Cập nhật `.env` với thông tin thật:

```env
SHOPEE_API_APP_ID=your_real_app_id
SHOPEE_API_SECRET=your_real_api_secret
```

## 2) Chạy script

```bash
npm run start
```

Script sẽ:

- Tạo GraphQL payload.
- Tạo chữ ký SHA256 theo format Authorization của Shopee.
- Gọi endpoint `https://open-api.affiliate.shopee.vn/graphql`.

## API có sẵn (tham số `apiName`)

- `shopeeOfferV2`
- `brandOfferV2`
- `productOfferV2`
- `generateShortLink`
- `conversionReportV2`
- `validationReportV2`

## Ví dụ

```bash
node index.js shopeeOfferV2
node index.js productOfferV2
node index.js generateShortLink "https://shopee.vn/product/38003654/1589295236"
node index.js conversionReportV2
node index.js validationReportV2
```

## Kiểm tra nhanh trước khi push

```bash
npm run lint
npm run test
```
