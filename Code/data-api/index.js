/**
 * Ví dụ gọi Data API (data.addlivetag.com) bằng Node.js 18+.
 *
 * API Key bắt buộc từ 01/10/2026 — đặt ADDLIVETAG_API_KEY trong .env hoặc biến môi trường.
 *
 *   node index.js product 1589295236
 *   node index.js batch 1589295236,41013426581
 *   node index.js market "ao len"
 *   node index.js shop-check 38003654
 */

import fs from "node:fs";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const BASE_URL = "https://data.addlivetag.com";
const __dirname = path.dirname(fileURLToPath(import.meta.url));

export function loadEnv(filePath) {
    if (!fs.existsSync(filePath)) return {};
    const env = {};
    for (const rawLine of fs.readFileSync(filePath, "utf8").split("\n")) {
        const line = rawLine.trim();
        if (!line || line.startsWith("#")) continue;
        const [key, ...rest] = line.split("=");
        if (!key || rest.length === 0) continue;
        env[key.trim()] = rest.join("=").trim().replace(/^['"]|['"]$/g, "");
    }
    return env;
}

function apiKey() {
    const env = loadEnv(path.join(__dirname, ".env"));
    return process.env.ADDLIVETAG_API_KEY || env.ADDLIVETAG_API_KEY || "";
}

/**
 * Gọi endpoint và trả JSON đã parse.
 *
 * Key gửi qua header X-API-Key. Không nhét key vào URL ở phía server — URL hay bị ghi vào
 * access log, còn header thì không.
 */
export async function callDataApi(endpoint, { query = {}, body = null, method = "GET" } = {}) {
    const url = new URL(endpoint, BASE_URL);
    for (const [k, v] of Object.entries(query)) {
        if (v !== undefined && v !== null && v !== "") url.searchParams.set(k, String(v));
    }

    const headers = {};
    const key = apiKey();
    if (key) headers["X-API-Key"] = key;
    if (body) headers["Content-Type"] = "application/json";

    const res = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : undefined });
    const json = await res.json();

    // Chưa có key thì response kèm khối nhắc — in ra để không bị bất ngờ vào ngày bị chặn.
    if (json.apiKeyNotice) {
        console.warn(`[api-key] ${json.apiKeyNotice.status}: ${json.apiKeyNotice.message}`);
    }
    if (res.status === 429) {
        throw new Error("429 — vượt rate limit. Có API Key thì hạn mức cao hơn 2,5 lần.");
    }
    if (res.status === 401) {
        throw new Error("401 — thiếu API Key. Lấy key tại addlivetag.com → API Key → Tạo Key.");
    }

    return json;
}

export const api = {
    /** Một sản phẩm. base_rate/cap để tính hoa hồng theo tier account của bạn. */
    product: (itemId, opts = {}) =>
        callDataApi("/product-data/product-data.php", { query: { item_id: itemId, ...opts } }),

    /** Nhiều sản phẩm (tối đa 100/request). Luôn dùng cái này thay cho vòng lặp product(). */
    batch: (itemIds, opts = {}) =>
        callDataApi("/product-data/product-data-batch.php", {
            method: "POST",
            body: { item_ids: itemIds, ...opts },
        }),

    /** Toàn cảnh thị trường theo từ khoá — không tiêu quota Shopee. */
    market: (keyword, opts = {}) =>
        callDataApi("/search/market.php", { query: { q: keyword, limit: 20, ...opts } }),

    /** Tra hoa hồng shop theo shopId. Nhớ đọc pending[] trước khi kết luận. */
    shopCheck: (shopIds, opts = {}) =>
        callDataApi("/offers/shop-check.php", { query: { shopIds, ...opts } }),

    /** Lịch sử giá & hoa hồng theo ngày. */
    history: (itemIds, days = 90) =>
        callDataApi("/price-tracking/history.php", { query: { item_ids: itemIds, days } }),
};

const isDirectRun = process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href;

if (isDirectRun) {
    const [, , command = "product", arg = "1589295236"] = process.argv;

    const run = {
        product: () => api.product(arg),
        batch: () => api.batch(arg.split(",").map((s) => s.trim())),
        market: () => api.market(arg),
        "shop-check": () => api.shopCheck(arg),
        history: () => api.history(arg),
    }[command];

    if (!run) {
        console.error(`Lệnh không hỗ trợ: ${command}. Dùng: product | batch | market | shop-check | history`);
        process.exitCode = 1;
    } else {
        run()
            .then((data) => console.log(JSON.stringify(data, null, 2)))
            .catch((error) => {
                console.error(error.message);
                process.exitCode = 1;
            });
    }
}
