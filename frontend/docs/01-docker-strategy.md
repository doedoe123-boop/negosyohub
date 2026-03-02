# Docker Strategy: Frontend + Backend

## Development vs. Production — Key Distinction

| Concern         | Development                         | Production                                 |
| --------------- | ----------------------------------- | ------------------------------------------ |
| Frontend server | Vite dev server (HMR, fast refresh) | Static files served by Nginx               |
| Node.js needed? | Yes (to run Vite)                   | **No** — build output is plain HTML/JS/CSS |
| Docker role     | Optional consistency tool           | Required for reproducible deployment       |
| API proxy       | Vite proxy (`/api` → Laravel)       | Nginx `location /api` → PHP-FPM            |

---

## Development Setup (Current)

```
┌─────────────────────┐     ┌──────────────────────┐
│  frontend_node      │     │  php / nginx          │
│  (Node 20, Docker)  │     │  (Laravel API)        │
│  Vite dev :5173     │────▶│  :8080                │
│  HMR + proxy /api   │     │                       │
└─────────────────────┘     └──────────────────────┘
```

- `frontend_node` runs `npm install && npm run dev -- --host`
- Vite proxies `/api/*` and `/sanctum/*` to Laravel at `:8080`
- Developer hits `http://localhost:5173`

### Running locally (without Docker node container)

```bash
cd frontend
npm install
npm run dev
```

This is faster to start but less consistent across machines.

---

## Production Setup (Target)

```
┌──────────────────────────────────────────────────────┐
│  Nginx                                               │
│                                                      │
│  location /           → serve /dist (SPA fallback)  │
│  location /api/       → proxy to PHP-FPM             │
│  location /sanctum/   → proxy to PHP-FPM             │
└──────────────────────────────────────────────────────┘
         │
         ├── /dist/          (built from `npm run build`)
         └── PHP-FPM         (Laravel)
```

### Build step (CI/CD or manual)

```bash
cd frontend
npm ci
npm run build
# dist/ folder is now ready
```

### Nginx config additions for production

```nginx
root /var/www/html/frontend/dist;

# SPA fallback — all non-file/API routes serve index.html
location / {
    try_files $uri $uri/ /index.html;
}

# API passthrough to PHP-FPM
location /api/ {
    proxy_pass http://php:9000;
    # ... existing PHP-FPM config
}
```

---

## Production Docker Strategy (Recommended for this project)

Use a **multi-stage Dockerfile** for the frontend:

```dockerfile
# Stage 1: Build
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Only copy dist/ into the Nginx image
FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html/frontend
```

Or, simpler for a hobby project: in CI/CD, run `npm run build` and mount `dist/` into your existing Nginx container volume.

---

## Summary Rule

> **In development**, Node.js serves the frontend.  
> **In production**, Nginx serves the frontend (static files).  
> Node.js is a build tool only — it never runs in production.
