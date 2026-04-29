# Production Subdomains, SEO, and Sitemap Setup

This document covers the production setup for NegosyoHub when the application is split into:

- a public customer/storefront host
- a public marketplace/business landing host
- store-owner portal subdomains under the marketplace host

It also explains why approved stores can fail to open even when the approval flow succeeds.

## 1. Why Approved Store Links Can Fail

If a seller is approved and the generated store URL looks correct but the browser shows:

`This site can't be reached`

then the problem is usually **not** Laravel. It usually means the subdomain never resolved to the server, so the request never reached nginx or PHP.

In this repo, store URLs are generated from:

- `APP_URL`
- `APP_DOMAIN`
- the store slug

The store login URL pattern is:

`https://{store-slug}.{APP_DOMAIN}/portal/{login_token}/login`

Example:

`https://horizon-peak-realty.marketplace.negosyohub.org/portal/stk_xxx/login`

Laravel is already routing these hosts through:

- [store.php](/var/www/negosyo-hub/src/routes/store.php)
- [ResolveStoreFromSubdomain.php](/var/www/negosyo-hub/src/app/Http/Middleware/ResolveStoreFromSubdomain.php)
- [Store.php](/var/www/negosyo-hub/src/app/Models/Store.php)

So if the browser cannot even resolve `horizon-peak-realty.marketplace.negosyohub.org`, the missing piece is infrastructure.

## 2. Required Production Host Strategy

For the current setup, use:

- Marketplace/business host:
  - `marketplace.negosyohub.org`
- Store-owner portal subdomains:
  - `*.marketplace.negosyohub.org`
- Customer storefront host:
  - whatever public customer host you choose, for example `negosyohub.org` or `www.negosyohub.org`

Important:

- seller portals should live under `*.marketplace.negosyohub.org`
- customer pages should not share the same wildcard host unless that is an intentional product decision

## 3. Required DNS Setup

If store-owner subdomains are under `marketplace.negosyohub.org`, then you need a wildcard DNS record for:

- `*.marketplace.negosyohub.org`

Recommended Cloudflare setup:

- `A marketplace -> your_server_ip`
- `A *.marketplace -> your_server_ip`
- both can be `Proxied` if you are using Cloudflare SSL/CDN

Important:

- `*.negosyohub.org` is **not** the same as `*.marketplace.negosyohub.org`
- if your store URLs are `slug.marketplace.negosyohub.org`, then the wildcard must match that exact level

If `horizon-peak-realty.marketplace.negosyohub.org` returns `DNS_PROBE_FINISHED_NXDOMAIN`, check Cloudflare DNS first.

## 4. Required SSL Setup

If you use Cloudflare with `Full (strict)`, the origin server also needs a certificate that covers:

- `marketplace.negosyohub.org`
- `*.marketplace.negosyohub.org`

Recommended:

- use a Cloudflare Origin Certificate for:
  - `marketplace.negosyohub.org`
  - `*.marketplace.negosyohub.org`

Important:

- a cert only for `marketplace.negosyohub.org` does **not** cover store subdomains
- each approved store should **not** generate a new certificate
- one wildcard certificate should cover all first-level store subdomains

## 5. Required Nginx Setup

Your marketplace server block must explicitly accept both the main marketplace host and all store subdomains:

```nginx
server {
    listen 80;
    server_name marketplace.negosyohub.org *.marketplace.negosyohub.org;

    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name marketplace.negosyohub.org *.marketplace.negosyohub.org;

    root /var/www/project/negosyohub/src/public;
    index index.php index.html;

    ssl_certificate /etc/ssl/cf_origin.pem;
    ssl_certificate_key /etc/ssl/cf_origin.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

If `marketplace.negosyohub.org` works but `slug.marketplace.negosyohub.org` does not, double-check:

- `server_name marketplace.negosyohub.org *.marketplace.negosyohub.org;`
- wildcard DNS exists
- wildcard SSL exists

## 6. Required Laravel Environment Values

For a marketplace host using store-owner subdomains, production should use values like:

```env
APP_URL=https://marketplace.negosyohub.org
APP_DOMAIN=marketplace.negosyohub.org
FRONTEND_URL=https://your-customer-frontend-host
```

Examples:

If the public customer SPA also lives on `marketplace.negosyohub.org`, then:

```env
FRONTEND_URL=https://marketplace.negosyohub.org
```

If the customer SPA lives on `https://negosyohub.org`, then:

```env
FRONTEND_URL=https://negosyohub.org
```

Important:

- `APP_DOMAIN` controls subdomain routing
- `APP_URL` controls URL generation defaults
- `FRONTEND_URL` is used by notifications that point customers back to the SPA

After changing env values, always refresh Laravel caches:

```bash
cd /var/www/project/negosyohub/src
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

## 7. How To Verify the Store Subdomain Flow

After approving a store:

1. Confirm the store has a `login_token`.
2. Confirm the generated URL matches:
   - `https://{slug}.marketplace.negosyohub.org/portal/{token}/login`
3. Check DNS:
   - `nslookup horizon-peak-realty.marketplace.negosyohub.org`
4. Check SSL:
   - open the subdomain directly in the browser
5. Check nginx:
   - confirm wildcard `server_name`
6. Check Laravel env:
   - `APP_DOMAIN=marketplace.negosyohub.org`

If step 3 fails, the problem is DNS.  
If step 3 works but step 4 fails, the problem is SSL or nginx.  
If step 4 works but the route returns 404/403, then the request is reaching Laravel and the issue is app-side.

## 8. Sitemap and Robots Strategy

Robots and sitemaps should follow the **hostname**, not just the codebase split.

### Recommended setup

Use separate public SEO surfaces:

#### Customer host

Example:

- `https://negosyohub.org/robots.txt`
- `https://negosyohub.org/sitemap.xml`

This host should include:

- homepage
- stores
- products
- properties
- movers
- deals
- FAQ
- public insights

This host should exclude:

- checkout
- cart
- account
- authenticated pages

#### Marketplace/business host

Example:

- `https://marketplace.negosyohub.org/robots.txt`
- `https://marketplace.negosyohub.org/sitemap.xml`

This host should include:

- business landing page
- seller onboarding info pages
- public legal pages
- public FAQ pages for sellers/businesses

This host should exclude:

- admin panel
- seller dashboard
- store-owner subdomain login pages
- setup pages
- password reset pages
- tokenized portal paths

### Answer: do we need two different sitemaps?

Yes, if the customer and marketplace experiences are served from different public hosts.

You should also use:

- separate `robots.txt` per host
- separate sitemap indexes per host if both hosts are public

## 9. Suggested Robots Rules

### Customer host example

```txt
User-agent: *
Allow: /
Disallow: /account
Disallow: /checkout
Disallow: /cart
Disallow: /login
Disallow: /register

Sitemap: https://negosyohub.org/sitemap.xml
```

### Marketplace host example

```txt
User-agent: *
Allow: /
Disallow: /moon/
Disallow: /store/
Disallow: /realty/
Disallow: /lipat-bahay/
Disallow: /portal/
Disallow: /login
Disallow: /register/store-owner/success

Sitemap: https://marketplace.negosyohub.org/sitemap.xml
```

Note:

- `robots.txt` is host-specific
- blocking `/portal/` on the marketplace host is good because store-owner access is private and tokenized

## 10. Recommended Sitemap Structure

### Customer host

Use a sitemap index:

- `sitemap.xml`
  - `sitemap-pages.xml`
  - `sitemap-products.xml`
  - `sitemap-properties.xml`
  - `sitemap-stores.xml`
  - `sitemap-movers.xml`

### Marketplace host

Use a smaller sitemap:

- `sitemap.xml`
  - `sitemap-pages.xml`
  - `sitemap-legal.xml`
  - `sitemap-business-faq.xml`

Do not include:

- store-owner subdomain login URLs
- admin URLs
- authenticated dashboard pages

## 11. Deployment Checklist

Before relying on approved store links in production, verify all of these:

- wildcard DNS exists for `*.marketplace.negosyohub.org`
- wildcard SSL exists for `*.marketplace.negosyohub.org`
- nginx `server_name` includes `*.marketplace.negosyohub.org`
- `APP_DOMAIN=marketplace.negosyohub.org`
- `APP_URL=https://marketplace.negosyohub.org`
- Laravel caches are rebuilt after env changes
- an approved store has a `login_token`

If all of the above are correct, approved store subdomains should resolve and redirect to their tokenized portal login pages.
