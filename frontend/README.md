# NegosyoHub Storefront

Standalone Vue 3 SPA consuming the NegosyoHub Laravel API.

## Stack

- **Vue 3** (Composition API + `<script setup>`)
- **Vite 6** (dev server with proxy to Laravel at `localhost:8080`)
- **Vue Router 4** (SPA routing)
- **Pinia** (state management — auth, cart)
- **Axios** (HTTP client with CSRF + JWT interceptors)
- **Tailwind CSS v4** (utility styling)

## Setup

```bash
cp .env.example .env
npm install
npm run dev        # http://localhost:5173
```

## Environment Variables

| Variable            | Description                                             |
| ------------------- | ------------------------------------------------------- |
| `VITE_API_BASE_URL` | Laravel API base URL (default: `http://localhost:8080`) |
| `VITE_APP_NAME`     | App display name                                        |

## Architecture

```
src/
├── api/           # Axios modules per resource (auth, stores, products, cart…)
├── composables/   # Reusable Vue composition functions
├── layouts/       # DefaultLayout (navbar/footer), AuthLayout (login/register)
├── pages/         # Route-level page components
│   ├── auth/
│   ├── store/
│   ├── product/
│   ├── checkout/
│   ├── account/
│   └── realty/
├── components/    # Shared UI components
├── router/        # Vue Router configuration
└── stores/        # Pinia stores (auth, cart)
```

## Proxy

In development, Vite proxies `/api/*` and `/sanctum/*` to `http://localhost:8080`
so there are no CORS issues locally. In production, serve both from the same domain
or configure `CORS_ALLOWED_ORIGINS` in Laravel's `.env`.
