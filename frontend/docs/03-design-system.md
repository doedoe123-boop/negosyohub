# Design System

## Current State vs. Target

The current theme uses generic `brand-*` CSS variables without a clear identity.
This document defines the design direction we're building toward.

---

## Color System

### Brand Palette Proposal

A **warm coral + deep navy** palette — energetic but trustworthy, suitable for a Philippine local marketplace.

```css
/* Primary — Coral/Orange (action, CTAs, highlights) */
--color-brand-50: #fff4f0;
--color-brand-100: #ffe4d9;
--color-brand-200: #ffc4ad;
--color-brand-300: #ff9f7f;
--color-brand-400: #ff7a52;
--color-brand-500: #f95d2f; /* primary CTA */
--color-brand-600: #e04520;
--color-brand-700: #b83318;
--color-brand-800: #8f2513;
--color-brand-900: #6b1b0f;

/* Neutral — Slate (text, backgrounds, borders) */
--color-slate-50: #f8fafc;
--color-slate-100: #f1f5f9;
--color-slate-200: #e2e8f0;
--color-slate-300: #cbd5e1;
--color-slate-400: #94a3b8;
--color-slate-500: #64748b;
--color-slate-600: #475569;
--color-slate-700: #334155;
--color-slate-800: #1e293b;
--color-slate-900: #0f172a;

/* Accent — Teal (real estate, secondary actions) */
--color-teal-400: #2dd4bf;
--color-teal-500: #14b8a6;
--color-teal-600: #0d9488;

/* Semantic */
--color-success: #16a34a;
--color-warning: #d97706;
--color-error: #dc2626;
--color-info: #2563eb;
```

### Usage Rules

| Token           | Use for                                |
| --------------- | -------------------------------------- |
| `brand-500`     | Primary CTA buttons, active nav, links |
| `brand-50/100`  | Hover backgrounds, selected states     |
| `slate-900`     | Headings, primary text                 |
| `slate-600`     | Body text, descriptions                |
| `slate-400`     | Placeholders, disabled text            |
| `slate-100/200` | Card backgrounds, borders, dividers    |
| `teal-500`      | Real estate CTAs, secondary tags       |

---

## Typography

```css
/* Font stack */
font-family: "Inter", "system-ui", sans-serif;

/* Scale */
--text-xs: 0.75rem / 1rem /* 12px — labels, captions */ --text-sm: 0.875rem /
  1.25rem /* 14px — body small, descriptions */ --text-base: 1rem / 1.5rem
  /* 16px — body text */ --text-lg: 1.125rem / 1.75rem /* 18px — subheadings */
  --text-xl: 1.25rem / 1.75rem /* 20px — card titles */ --text-2xl: 1.5rem /
  2rem /* 24px — section headings */ --text-3xl: 1.875rem / 2.25rem
  /* 30px — page headings */ --text-4xl: 2.25rem / 2.5rem
  /* 36px — hero headings */;
```

### Hierarchy Rules

- Page title: `text-3xl font-bold text-slate-900`
- Section heading: `text-2xl font-semibold text-slate-800`
- Card title: `text-base font-semibold text-slate-800`
- Body: `text-sm text-slate-600`
- Caption / label: `text-xs text-slate-500`

---

## Spacing & Layout

```
Base unit: 4px (Tailwind default)

Content max-width:  1280px (max-w-7xl)
Horizontal padding: 16px mobile, 24px tablet, 32px desktop
Section spacing:    48px (py-12)
Card gap:           16px (gap-4) or 24px (gap-6)
```

---

## Component Patterns

### Cards

**Store card** (food/retail):

```
┌──────────────────────────┐
│  Banner image (3:2 ratio)│
├──────────────────────────┤
│  Logo + Store name       │
│  Category tag | Rating   │
│  Delivery time + min fee │
└──────────────────────────┘
```

**Listing card** (real estate):

```
┌──────────────────────────┐
│  Photo gallery (16:9)    │
├──────────────────────────┤
│  Price (bold, large)     │
│  Beds · Baths · Sqm      │
│  Location                │
│  Agent info              │
└──────────────────────────┘
```

### Buttons

```
Primary:   bg-brand-500 text-white hover:bg-brand-600   rounded-xl px-5 py-2.5
Secondary: border border-slate-300 text-slate-700 hover:bg-slate-50
Danger:    bg-red-500 text-white hover:bg-red-600
Ghost:     text-brand-600 hover:bg-brand-50
```

### Form Inputs

```
Base:  border border-slate-300 rounded-xl px-4 py-3 text-sm
Focus: focus:ring-2 focus:ring-brand-400 focus:border-transparent
Error: border-red-400 bg-red-50
```

---

## Iconography

Using `@heroicons/vue` (already installed).

- Navigation icons: `outline` style only
- Action icons inside buttons: `solid` style
- Status/state icons: solid

---

## Responsive Breakpoints

| Breakpoint | Width  | Layout                |
| ---------- | ------ | --------------------- |
| `sm`       | 640px  | 1-col → 2-col grids   |
| `md`       | 768px  | Show sidebar filters  |
| `lg`       | 1024px | 3-col product grid    |
| `xl`       | 1280px | Max content width hit |

---

## Tailwind Config Tokens (global CSS)

The custom tokens should be defined in `src/assets/main.css` using `@theme`:

```css
@import "tailwindcss";

@theme {
  --color-brand-50: #fff4f0;
  /* ... all brand tokens */
  --color-teal-500: #14b8a6;
}
```
