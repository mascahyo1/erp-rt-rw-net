---
name: testing-prioritas-playwright
description: Prioritas testing adalah Playwright Node.js (headed) — JANGAN jalankan PHPUnit untuk verify perubahan UI/Vue. Frontend benar = semuanya benar.
metadata:
  type: feedback
---

Untuk verify perubahan di repo ini, **SELALU gunakan Playwright Node.js (headed) sebagai test utama** — JANGAN jalankan `php artisan test` (PHPUnit) untuk cek perubahan UI/Vue/Inertia/Inertia.

**Why:**
- Testing utama di repo ini adalah Playwright (lihat `tests/Browser/Playwright/Feature/**/*.cjs`) — `AGENTS.md` sudah tegaskan ini di section "Test runners — use the right one".
- PHPUnit (`php artisan test`) hanya untuk backend unit/feature (auth, RBAC, controller logic). Butuh DB `erp_rt_rw_net` (NON `_tmp`), tidak verify UI sama sekali.
- Kalau output Playwright headed benar (modal muncul, filter jalan, soft-delete/restore jalan, import/export Excel jalan) = **pasti frontend + backend + route + permission + migration sudah benar semua**.
- Lebih reliable: satu test E2E mencakup route → controller → Inertia render → Vue component → permission gate → DB. PHPUnit cuma test 1 layer.

**How to apply:**
- Setelah perubahan **PHP backend (controller/migration/route/permission)**: jalankan `php artisan route:clear && config:clear` lalu run Playwright test untuk halaman tersebut.
- Setelah perubahan **Vue/Inertia/Tailwind**: WAJIB `npm run build` dulu (HMR tidak cukup — Dusk/Playwright baca `public/build/`), baru run Playwright test headed.
- Setelah perubahan **tipe enum/field DB**: cek via `php artisan tinker` schema check, lalu verify di Playwright.
- Untuk CRUD baru: tambahkan file `tests/Browser/Playwright/Feature/{Portal}/{Halaman}*.cjs` (template: `QuickVerifyKonfigurasiSaaS.cjs` baru).
- **JANGAN** jalankan `php artisan test` kecuali user minta eksplisit atau perubahan murni backend logic (helper/trait/service class tanpa UI).
- Set `chromium.launch({ headless: false, slowMo: 300-500 })` — user ingin lihat browser untuk verify.
- Template test: `tests/Browser/Playwright/Feature/OperatorPerusahaan/QuickVerifyKonfigurasiPerusahaan.cjs` (contoh lengkap untuk CRUD + dark + responsive + import/export).
