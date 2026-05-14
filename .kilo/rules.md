# Environment Rules

## Frontend dev server
- `npm run dev` is ALWAYS running. Do NOT run `npm run build` under any circumstances.
- Frontend changes are served via Vite HMR at `http://[::1]:5173` (or `localhost:5173`).
- The user refreshes their browser manually to see changes.

## Dusk browser tests
- Dusk requires production build artifacts because Chrome sandbox cannot reach Vite dev server.
- When running Dusk: temporarily rename `public/hot` → `public/hot.bak`, run dusk, then restore.
- After Dusk completes, `public/hot` MUST be restored for `npm run dev` to resume.
- The build artifacts in `public/build/` are used as-is; they may be stale if form templates changed recently.
- Screenshots are saved per-guard under `tests/Browser/screenshots/operator-saas/login/`, etc. (gitignored).
- PHPUnit feature tests (`php artisan test`) can run anytime without special setup.

## Test structure
```
tests/
├── Browser/
│   ├── Feature/
│   │   ├── OperatorSaas/LoginTest.php
│   │   ├── OperatorPerusahaan/LoginTest.php
│   │   ├── Karyawan/LoginTest.php
│   │   └── Pelanggan/LoginTest.php
│   └── screenshots/         ← gitignored
│       ├── operator-saas/login/
│       ├── operator-perusahaan/login/
│       ├── karyawan/login/
│       └── pelanggan/login/
├── Feature/
│   ├── Auth/                ← login tests per guard
│   ├── OperatorSaas/        ← CRUD tests (Perusahaan, AdminSaas)
│   └── OperatorPerusahaan/  ← CRUD tests (AdminPerusahaan, Karyawan, Customer)
└── Unit/
```
