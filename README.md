## ```bc-custom-link``` Tạo Link Rút Gọn
Rút gọn link Shopee cho một trang cụ thể trên Shopee

### Install
1. ```link.php``` Line 50 & 51 add AppID and API key, see at https://affiliate.shopee.vn/open_api

![|](https://i.imgur.com/Bc6X9ub.png)

SQL Table:

```
CREATE TABLE `shopee_affiliate_link` (
  `id` int(11) NOT NULL,
  `us_id` varchar(128) DEFAULT NULL,
  `appid` varchar(64) DEFAULT NULL,
  `link` varchar(512) DEFAULT NULL,
  `tracking_link` varchar(256) DEFAULT NULL,
  `sub_id` varchar(512) DEFAULT NULL,
  `time_create` int(11) DEFAULT NULL,
  `ip` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `shopee_affiliate_link`
  ADD PRIMARY KEY (`id`);
```

**By Bcat95 vui lòng ghi nguồn khi chia sẻ**
