# Marketplace Positioning

## What We Are Building

NegosyoHub is a **multi-sector marketplace platform**, not a single-store e-commerce shop.

The core distinction:

| Single-store E-commerce        | Multi-sector Marketplace (us)             |
| ------------------------------ | ----------------------------------------- |
| One brand, one product catalog | Multiple stores across multiple sectors   |
| Customer buys from _the site_  | Customer buys from _a store on the site_  |
| Fixed product types            | Different sectors have different UX needs |
| Checkout is always the same    | Checkout flow may differ per sector       |

---

## Sectors

### Active / Planned

| Sector                  | Key Entities                | Unique UX Needs                          |
| ----------------------- | --------------------------- | ---------------------------------------- |
| **Food & Retail**       | Stores, Products, Cart      | Product grid, add-to-cart, delivery time |
| **Real Estate**         | Listings, Agents            | Map view, inquiry form, no cart          |
| **Services** _(future)_ | Service providers, Bookings | Calendar/booking flow                    |
| **Jobs** _(future)_     | Employers, Listings         | Posting + application flow               |

Each sector is a **sub-experience** within the platform. The homepage unifies them.

---

## Platform Identity (What Customers Experience)

### 1. Discovery First

The homepage is a marketplace directory, not a product catalog.

```
Homepage
├── Hero: "Your Local Marketplace" CTA
├── Sector picker (Food, Real Estate, Services...)
├── Featured stores (across all sectors)
└── Location-based browsing (future)
```

### 2. Store as the Primary Unit

Customers browse **stores**, not products. Products are inside stores.

```
/stores          → store directory (filterable by sector)
/stores/:slug    → store page (products, about, reviews)
/products/:id    → product detail (breadcrumb back to store)
```

Real estate breaks this pattern intentionally:

```
/properties      → listing directory (map + list toggle)
/properties/:slug → listing detail (photos, agent, inquiry)
```

### 3. Platform Branding Over Store Branding

NegosyoHub owns the visual identity. Stores customize within that framework (logo, banner, description) — they don't override the platform shell (navbar, footer, checkout).

---

## Customer Journey by Sector

### Food & Retail

```
Homepage → Browse Stores → Store page → Add to cart → Checkout → Order tracking
```

### Real Estate

```
Homepage → Browse Properties → Property detail → Contact agent / Inquire
```

_(No cart, no checkout — inquiry-based)_

---

## Design Implications

- Navbar must feel like a **platform** (NegosyoHub branding), not a store header
- Sector filtering is a first-class UI element — not buried in search
- "Add to cart" and "Inquire" are different CTAs that coexist on the platform
- Product cards and listing cards have **different layouts** — no one-size-fits-all card component
- Cart is **food/retail only** — it should not appear on the real estate experience

---

## What We Are NOT

- Not a single-vendor store (like a Shopify storefront)
- Not a clone of Lazada/Shopee (we're local-first, multi-sector)
- Not just a food delivery app (Grab/Foodpanda are vertical-specific; we're horizontal)
