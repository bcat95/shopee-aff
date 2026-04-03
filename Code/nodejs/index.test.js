import test from "node:test";
import assert from "node:assert/strict";
import crypto from "node:crypto";

import { buildAuthorization, buildPayload } from "./index.js";

test("buildAuthorization creates expected SHA256 header", () => {
    const appId = "123456";
    const secret = "secret_key";
    const payload = JSON.stringify({ query: "{ shopeeOfferV2 { pageInfo { page } } }" });
    const timestamp = 1712000000;
    const expectedSignature = crypto
        .createHash("sha256")
        .update(`${appId}${timestamp}${payload}${secret}`)
        .digest("hex");

    const actual = buildAuthorization(appId, secret, payload, timestamp);

    assert.equal(
        actual,
        `SHA256 Credential=${appId}, Timestamp=${timestamp}, Signature=${expectedSignature}`,
    );
});

test("buildPayload supports generateShortLink and injects input URL", () => {
    const inputUrl = "https://shopee.vn/product/38003654/1589295236";
    const payload = buildPayload("generateShortLink", inputUrl);
    const parsed = JSON.parse(payload);

    assert.match(parsed.query, /generateShortLink/);
    assert.match(parsed.query, new RegExp(inputUrl.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")));
});

test("buildPayload throws for unsupported apiName", () => {
    assert.throws(
        () => buildPayload("unknownApi", "https://shopee.vn"),
        /Unsupported api name/,
    );
});
