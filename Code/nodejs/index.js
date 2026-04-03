import fs from "node:fs";
import path from "node:path";
import crypto from "node:crypto";
import { fileURLToPath, pathToFileURL } from "node:url";

const API_URL = "https://open-api.affiliate.shopee.vn/graphql";
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export function loadEnv(filePath) {
    if (!fs.existsSync(filePath)) {
        throw new Error(".env not found. Copy .env.example to .env first.");
    }

    const content = fs.readFileSync(filePath, "utf8");
    const env = {};

    for (const rawLine of content.split("\n")) {
        const line = rawLine.trim();
        if (!line || line.startsWith("#")) continue;

        const [key, ...rest] = line.split("=");
        if (!key || rest.length === 0) continue;

        env[key.trim()] = rest
            .join("=")
            .trim()
            .replace(/^['"]|['"]$/g, "");
    }

    return env;
}

export function buildPayload(apiName, inputUrl) {
    const queries = {
        shopeeOfferV2: `
{
  shopeeOfferV2(keyword: "phone", sortType: 1, page: 1, limit: 5) {
    nodes { offerName offerLink commissionRate }
    pageInfo { page limit hasNextPage }
  }
}
`,
        brandOfferV2: `
{
  brandOffer(keyword: "phone", sortType: 1, page: 1, limit: 5) {
    nodes { offerName offerLink commissionRate }
    pageInfo { page limit hasNextPage }
  }
}
`,
        productOfferV2: `
{
  productOfferV2(keyword: "phone", sortType: 1, page: 1, limit: 5) {
    nodes { productName offerLink commissionRate sales }
    pageInfo { page limit hasNextPage }
  }
}
`,
        generateShortLink: `
mutation {
  generateShortLink(input: { originUrl: "${inputUrl}", subIds: ["s1"] }) {
    shortLink
  }
}
`,
        conversionReportV2: `
{
  conversionReport(limit: 5) {
    nodes { conversionId purchaseTime totalCommission }
    pageInfo { scrollId }
  }
}
`,
        validationReportV2: `
{
  validatedReport(validationId: 1, limit: 5) {
    nodes { conversionId purchaseTime totalCommission }
    pageInfo { scrollId }
  }
}
`,
    };

    if (!queries[apiName]) {
        const supported = Object.keys(queries).join(", ");
        throw new Error(`Unsupported api name: ${apiName}. Supported: ${supported}`);
    }

    return JSON.stringify({ query: queries[apiName] });
}

export function buildAuthorization(appId, secret, payload, timestamp) {
    const signatureBase = `${appId}${timestamp}${payload}${secret}`;
    const signature = crypto.createHash("sha256").update(signatureBase).digest("hex");
    return `SHA256 Credential=${appId}, Timestamp=${timestamp}, Signature=${signature}`;
}

export async function callShopeeApi() {
    const env = loadEnv(path.join(__dirname, ".env"));
    const appId = env.SHOPEE_API_APP_ID || "";
    const secret = env.SHOPEE_API_SECRET || "";

    if (!appId || !secret) {
        throw new Error("Missing SHOPEE_API_APP_ID or SHOPEE_API_SECRET in .env");
    }

    const apiName = process.argv[2] || "shopeeOfferV2";
    const inputUrl = process.argv[3] || "https://shopee.vn";
    const payload = buildPayload(apiName, inputUrl);
    const timestamp = Math.floor(Date.now() / 1000);
    const authorization = buildAuthorization(appId, secret, payload, timestamp);

    const response = await fetch(API_URL, {
        method: "POST",
        headers: {
            Authorization: authorization,
            "Content-Type": "application/json",
        },
        body: payload,
    });

    const json = await response.json();
    return {
        api: apiName,
        httpCode: response.status,
        response: json,
    };
}

const isDirectRun = process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href;

if (isDirectRun) {
    callShopeeApi()
        .then((data) => {
            console.log(JSON.stringify(data, null, 2));
        })
        .catch((error) => {
            console.error(
                JSON.stringify(
                    {
                        success: false,
                        error: error.message,
                        usage: "node index.js [apiName] [originUrl-for-generateShortLink]",
                    },
                    null,
                    2,
                ),
            );
            process.exitCode = 1;
        });
}
