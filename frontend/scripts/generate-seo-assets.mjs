import { mkdir, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const frontendRoot = path.resolve(__dirname, "..");
const publicDir = path.join(frontendRoot, "public");

const rawSiteUrl =
  process.env.VITE_SITE_URL ||
  process.env.VERCEL_PROJECT_PRODUCTION_URL ||
  "http://localhost:5173";

const siteUrl = rawSiteUrl.startsWith("http")
  ? rawSiteUrl.replace(/\/+$/, "")
  : `https://${rawSiteUrl.replace(/\/+$/, "")}`;

const routes = [
  { path: "/", priority: "1.0", changefreq: "daily" },
  { path: "/stores", priority: "0.9", changefreq: "daily" },
  { path: "/properties", priority: "0.9", changefreq: "daily" },
  { path: "/movers", priority: "0.9", changefreq: "weekly" },
  { path: "/deals", priority: "0.8", changefreq: "daily" },
  { path: "/insights", priority: "0.7", changefreq: "weekly" },
  { path: "/faq", priority: "0.6", changefreq: "monthly" },
  { path: "/about", priority: "0.5", changefreq: "monthly" },
];

const lastModified = new Date().toISOString();

const sitemap = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${routes
  .map(
    ({ path: routePath, priority, changefreq }) => `  <url>
    <loc>${siteUrl}${routePath}</loc>
    <lastmod>${lastModified}</lastmod>
    <changefreq>${changefreq}</changefreq>
    <priority>${priority}</priority>
  </url>`
  )
  .join("\n")}
</urlset>
`;

const robots = `User-agent: *
Allow: /
Disallow: /account
Disallow: /cart
Disallow: /checkout
Disallow: /login
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password

Sitemap: ${siteUrl}/sitemap.xml
`;

await mkdir(publicDir, { recursive: true });
await writeFile(path.join(publicDir, "sitemap.xml"), sitemap, "utf8");
await writeFile(path.join(publicDir, "robots.txt"), robots, "utf8");
