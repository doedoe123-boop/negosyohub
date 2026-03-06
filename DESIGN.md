# NegosyoHub — Design System & UI Guide

> **Last updated:** 2026-03-05  
> **Stitch Project:** `projects/3964431837404061634` — "NegosyoHub — Premium UI Redesign"  
> **Screens:** Property Listing · Shop Dashboard

---

## 1. Design DNA

NegosyoHub's visual identity is built on **trust, clarity, and Filipino pride**. Every design decision should make buyers feel confident and sellers feel empowered. We draw from the aesthetic of high-trust platforms like Lamudi, Shopify, and Lazada — but with a distinctly local character.

### Core Principles

| Principle                       | What it means                                                                                          |
| ------------------------------- | ------------------------------------------------------------------------------------------------------ |
| **Trust-first**                 | Show verification badges, secure icons, and social proof at every touchpoint                           |
| **Clarity over decoration**     | Data-dense screens should breathe — whitespace is not wasted space                                     |
| **Local voice**                 | Use ₱ peso symbol, Filipino place names, familiar payment methods (PayMongo, GCash, Pag-IBIG mentions) |
| **Elevation through restraint** | Subtle shadows and soft color fills, not heavy borders or loud gradients                               |

---

## 2. Color Palette

### Primary — Deep Navy

The primary brand anchor used for sidebars, headers, and CTAs. Conveys trust, stability, and professionalism.

| Token              | Hex       | Usage                                    |
| ------------------ | --------- | ---------------------------------------- |
| `--color-navy-950` | `#060E1F` | Deepest backgrounds                      |
| `--color-navy-900` | `#0F2044` | **Primary** — Sidebar, navbar, dark CTAs |
| `--color-navy-800` | `#1A2F5A` | Hover states on navy                     |
| `--color-navy-700` | `#1E3A6E` | Secondary navy tones                     |
| `--color-navy-100` | `#D6E0F5` | Light navy tints                         |
| `--color-navy-50`  | `#EEF2FB` | Navy wash backgrounds                    |

### Accent — Emerald Green

Used for primary CTAs, verified badges, positive KPIs, and progress indicators.

| Token                 | Hex       | Usage                                       |
| --------------------- | --------- | ------------------------------------------- |
| `--color-emerald-700` | `#047857` | Hover state on CTAs                         |
| `--color-emerald-600` | `#059669` | **Primary accent** — Buttons, badges, links |
| `--color-emerald-100` | `#D1FAE5` | Badge backgrounds, success tints            |
| `--color-emerald-50`  | `#ECFDF5` | Subtle section tints                        |

### Brand — Warm Coral

Used for highlighted prices, promotional tags, and attention-drawing elements.

| Token               | Hex       | Usage                                       |
| ------------------- | --------- | ------------------------------------------- |
| `--color-brand-600` | `#E04520` | Hover                                       |
| `--color-brand-500` | `#F95D2F` | **Price highlights**, nav accent indicators |
| `--color-brand-100` | `#FFE4D9` | Badge tints                                 |
| `--color-brand-50`  | `#FFF4F0` | Section backgrounds                         |

### Neutral — Slate

Used for all body text, UI structure, borders, and backgrounds.

| Token               | Hex       | Usage                        |
| ------------------- | --------- | ---------------------------- |
| `--color-slate-950` | `#020617` | Strongest text               |
| `--color-slate-900` | `#0F172A` | Footer background text       |
| `--color-slate-800` | `#1E293B` | **Body text primary**        |
| `--color-slate-500` | `#64748B` | Subtitles, labels            |
| `--color-slate-300` | `#CBD5E1` | Borders, dividers            |
| `--color-slate-100` | `#F1F5F9` | Image placeholders, skeleton |
| `--color-slate-50`  | `#F8FAFC` | **Page background**          |

### Semantic

| Token                       | Hex       | Usage                            |
| --------------------------- | --------- | -------------------------------- |
| Amber `--color-amber-500`   | `#F59E0B` | Pending / warning states         |
| Sky `--color-sky-600`       | `#0284C7` | "For Rent" badges, informational |
| Purple `--color-purple-600` | `#9333EA` | Pre-selling / exclusive tags     |
| Red `--color-error`         | `#DC2626` | Cancelled, errors, out of stock  |
| Green `--color-success`     | `#16A34A` | Confirmed, success states        |

---

## 3. Typography

**Font Family:** [Inter](https://fonts.google.com/specimen/Inter) (Primary) — loaded via Google Fonts.

```css
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap");
```

### Type Scale

| Role               | Size      | Weight    | Color           |
| ------------------ | --------- | --------- | --------------- |
| Hero Headline (H1) | `36–48px` | `800`     | Navy `#0F2044`  |
| Page Title (H2)    | `28–32px` | `700`     | Slate `#1E293B` |
| Section Title (H3) | `20–24px` | `700`     | Slate `#1E293B` |
| Card Title         | `16px`    | `600`     | Slate `#1E293B` |
| Body / Description | `14–16px` | `400`     | Slate `#475569` |
| Label / Caption    | `12px`    | `500–600` | Slate `#64748B` |
| Micro / Badge text | `10–11px` | `600`     | varies          |

### Typography Rules

- Line height: `1.6` for body, `1.2` for headings
- Letter spacing: `0.05em` (tight) for ALL-CAPS labels; normal for body
- Price text: Always **bold 700+**, coral `#F95D2F`, Peso symbol `₱` no space

---

## 4. Spacing & Layout

### Grid System

- Max container width: `1280px` (`max-w-7xl`)
- Horizontal padding: `px-4 sm:px-6`
- Gutter between cards: `16px` (mobile) → `20px` (desktop)
- Section vertical rhythm: `py-12` between major sections

### Component Sizes

| Element                   | Size                     |
| ------------------------- | ------------------------ |
| Navbar height             | `64px`                   |
| Sidebar width (dashboard) | `240px`                  |
| Card border radius        | `12px` (`rounded-2xl`)   |
| Button border radius      | `8px` (`rounded-xl`)     |
| Badge border radius       | `999px` (`rounded-full`) |
| Input border radius       | `10px`                   |

### Shadows

```css
/* Card shadow */
box-shadow:
  0 1px 3px rgba(0, 0, 0, 0.08),
  0 1px 2px rgba(0, 0, 0, 0.04);

/* Elevated card / modal */
box-shadow:
  0 4px 16px rgba(0, 0, 0, 0.1),
  0 2px 4px rgba(0, 0, 0, 0.06);

/* Sticky inquiry form */
box-shadow: 0 8px 24px rgba(15, 32, 68, 0.12);
```

---

## 5. Component Patterns

### Badges / Status Pills

```html
<!-- Listing type -->
<span
  class="rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-0.5 text-xs font-semibold"
  >For Sale</span
>
<span
  class="rounded-full bg-sky-100 text-sky-700 px-2.5 py-0.5 text-xs font-semibold"
  >For Rent</span
>
<span
  class="rounded-full bg-amber-100 text-amber-700 px-2.5 py-0.5 text-xs font-semibold"
  >For Lease</span
>
<span
  class="rounded-full bg-purple-100 text-purple-700 px-2.5 py-0.5 text-xs font-semibold"
  >Pre-Selling</span
>

<!-- Trust -->
<span
  class="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-0.5 text-xs font-semibold"
>
  <svg class="size-3"><!-- checkmark --></svg> Verified Agency
</span>

<!-- Order status (dashboard) -->
<span class="rounded-full bg-emerald-100 text-emerald-700 ...">Paid</span>
<span class="rounded-full bg-amber-100 text-amber-700 ...">Pending</span>
<span class="rounded-full bg-sky-100 text-sky-700 ...">Shipped</span>
<span class="rounded-full bg-red-100 text-red-700 ...">Cancelled</span>
```

### Primary CTA Button (Emerald)

```html
<button
  class="rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white
               shadow-sm hover:bg-emerald-700 hover:shadow-emerald-600/25 hover:shadow-md
               active:scale-[0.98] transition-all"
>
  Request Viewing
</button>
```

### Secondary CTA Button (Navy Outline)

```html
<button
  class="rounded-xl border-2 border-navy-900 px-6 py-3 text-sm font-semibold
               text-navy-900 hover:bg-navy-50 transition-colors"
>
  Create Promotion
</button>
```

### Verified Badge (Trust Signal)

```html
<span
  class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200
             bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"
>
  <svg class="size-3.5 text-emerald-600"><!-- check-shield icon --></svg>
  Verified Agency
</span>
```

---

## 6. Screen Inventory

### Screen 1: Property Listing Detail Page

**Stitch ID:** `d4144332ef124da89badb661bb85048e`  
**Preview:** `docs/design/property-listing-screen.png`  
**Dimensions:** 2560 × 5614 px (desktop, full scroll)

**Key sections:**

1. Sticky navbar — Navy, Emerald CTA, blur backdrop
2. Breadcrumb trail
3. Hero image gallery — primary (60%) + 2×2 thumbnails (40%)
4. Main content — 7:3 column split
   - Left: Title, price (coral), spec chips, description, amenities, map
   - Right: Sticky "Schedule a Viewing" inquiry card with agency profile
5. Trust signals: Pag-IBIG estimate, view count, secure-data notice
6. Related properties horizontal scroll
7. Dark Navy footer

**Trust pattern to replicate:**

- Always show `Verified Agency` badge next to the agent card
- Price includes monthly estimate: `"Est. ₱38,000/mo via Pag-IBIG"`
- `🔒 Your data is protected` note below form submit button

---

### Screen 2: Shop / Seller Dashboard

**Stitch ID:** `ecc899e627324e479bf479c5d9c0c4c6`  
**Preview:** `docs/design/shop-dashboard-screen.png`  
**Dimensions:** 2560 × 2048 px (desktop viewport)

**Key sections:**

1. Fixed 240px left sidebar — Deep Navy, white text, Emerald active indicator
2. Top bar — White, greeting + avatar + notifications
3. KPI row — 4 equal white cards (Revenue, Orders, Products, Rating)
4. Mid row — Recent orders table (7) + Quick Actions + Store Health (3)
5. Bottom row — Revenue area chart (6) + Top Products list (6)

**Dashboard conventions:**

- Active sidebar nav item: `border-l-4 border-emerald-500 bg-emerald-900/10 text-white`
- KPI upward trend: Emerald `↑ +18%`; downward trend: Coral `↓ -3%`
- Unread message badge: Coral pill on nav item
- ⚠️ Restock alerts use amber `#F59E0B` panel with soft amber background

---

## 7. Interaction & Motion Guidelines

| Interaction    | Animation                                                 |
| -------------- | --------------------------------------------------------- |
| Card hover     | `translateY(-2px)` + shadow intensify, `150ms ease-out`   |
| Image hover    | `scale(1.05)` inside `overflow-hidden` container, `300ms` |
| Button press   | `scale(0.98)`, `100ms`                                    |
| Navbar flyout  | `translateY(-4px) → 0 + opacity 0 → 1`, `150ms ease-out`  |
| Mobile menu    | slide down + fade, `200ms ease-out`                       |
| Skeleton pulse | `animate-pulse` (Tailwind default)                        |
| KPI number     | Count-up animation on mount (JS)                          |
| Chart          | Fade + draw in from left, `800ms`                         |

---

## 8. Trust & Security Signals Checklist

Every page should have at least **3 of the following** trust signals visible:

- [ ] **Verified seller/agency badge** (Emerald, checkmark icon)
- [ ] **Secure checkout / inquiry note** (`🔒 Your data is protected`)
- [ ] **Payment provider logos** (PayMongo, GCash, Stripe)
- [ ] **Social proof stat** ("1,243 viewed this week")
- [ ] **Rating + review count** (⭐ 4.8 · 312 reviews)
- [ ] **Response rate** ("Replies within 1 hour")
- [ ] **Philippine flag badge** (🇵🇭 Made in the Philippines)
- [ ] **Buyer guarantee strip** (near checkout/inquiry CTA)

---

## 9. Design-to-Code Mapping

The existing frontend uses **Tailwind CSS v4** (`@import "tailwindcss"` + `@theme {}` tokens). New screens should extend the existing `style.css` design tokens.

### New tokens to add for Navy palette:

```css
/* Add to @theme in style.css */

/* Deep Navy */
--color-navy-950: #060e1f;
--color-navy-900: #0f2044;
--color-navy-800: #1a2f5a;
--color-navy-700: #1e3a6e;
--color-navy-100: #d6e0f5;
--color-navy-50: #eef2fb;
```

The existing `--color-teal-*` tokens cover the real estate accent. The `--color-brand-*` tokens cover coral pricing. No other additions needed — the Emerald colors are Tailwind defaults.

---

## 10. Stitch Project Reference

| Item                     | Value                                                    |
| ------------------------ | -------------------------------------------------------- |
| Project Name             | NegosyoHub — Premium UI Redesign                         |
| Project ID               | `3964431837404061634`                                    |
| Stitch URL               | `https://stitch.google.com/projects/3964431837404061634` |
| Screen: Property Listing | `screens/d4144332ef124da89badb661bb85048e`               |
| Screen: Shop Dashboard   | `screens/ecc899e627324e479bf479c5d9c0c4c6`               |
| Screen: Luxury Homepage  | `screens/55a278bdf02f48d3917a24dc1cf8c48d`               |
| Device Type              | Desktop (2560px canvas)                                  |
| Font                     | Inter                                                    |
| Color Mode               | Light                                                    |
| Corner Rounding          | 8–12px                                                   |

### Suggested Next Screens to Generate

| Priority  | Screen                        | Notes                                        |
| --------- | ----------------------------- | -------------------------------------------- |
| 🔴 High   | **Properties Search Results** | Grid/list toggle, filter sidebar, map view   |
| 🔴 High   | **Product Detail Page**       | Shop sector equivalent of Property Listing   |
| 🟡 Medium | **Add New Product Wizard**    | Multi-step form from dashboard               |
| 🟡 Medium | **Messages / Chat**           | Buyer-seller inbox                           |
| 🟢 Low    | **Store Settings Page**       | Profile, payout, notification preferences    |
| 🟢 Low    | **Mobile Homepage**           | Mobile-first redesign of the hero + sections |
