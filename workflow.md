# workflow.md — Ringkas (1 halaman) — ERP RT/RW Net

> Versi ringkas dari `STANDARDS.md` v4. Untuk detail lengkap baca `STANDARDS.md` §1,§7,§8.

---

## 1. Kontrak Build-Time (§1) — wajib sebelum testing

- **Lifecycle solid:** mount → fetch → render → interaksi → submit → feedback → cleanup, `await` + `finally`, `npm run build` wajib setelah Vue/model berubah.
- **State solid:** satu sumber kebenaran, update local via `json.data` setelah AJAX, jangan reload.
- **Selector solid:** pakai `data-testid` dulu, jangan `nth-child`/XPath. Tunggu selector visible sebelum klik.

## 2. Hybrid Inertia + AJAX (§2)

- Navigasi (sidebar/breadcrumb/redirect) → **Inertia** (`Inertia::render` + `<Link>`).
- CRUD form (modal/inline, no nav) → **Pure AJAX** (`fetch` + `FormData` → `response()->json()`).
- Dilarang: `useForm().put()` + `forceFormData:true` untuk file upload, `form._method='PUT'` manual, `use JsonResponse` tanpa namespace.

Lihat `dokumentasi/CONVENTIONS.md`.

## 3. Workflow Teliti (§9)

1. **Confirm** requirement ≥95% (tanya opsi konkret).
2. **Todo list → tunggu sign-off user** sebelum coding.
3. **1 file at a time:** edit → screenshot headed → verify → re-read diff → lanjut.
4. **Visual issue:** deskripsi teks row-by-row dulu, jangan silent iteration.
5. **Respons Indonesia + `path:line`.**

## 4. Testing (§7) — Headed E2E per-langkah

**Prioritas:** Playwright `.cjs` adalah **utama** (headed, `slowMo:300-500`). PHPUnit hanya backend murni. Dusk legacy.

**Per langkah wajib:** `assert` + `screenshot` + `network` (waitForResponse) + `video` jika kritis.

```js
await page.goto(BASE + '/operator-saas/konfigurasi');
check('H2 visible', h2 === 'Konfigurasi SaaS');
await shot(page, '02-list-light.png');
const [res] = await Promise.all([
  page.waitForResponse(r => r.url().includes('/api/') && r.request().method()==='POST'),
  page.click('button:has-text("Simpan")')
]);
assert(res.status()===200);
```

**Deep Verify cakupan:** login/permission → list (header/badge/pagination/search/filter/sort) → CRUD all types → validasi 422 → file upload → soft-delete/restore/bulk → import/export → dark/responsive → no JS errors.

**Template:** `DeepVerifyKonfigurasiSaaS.cjs` (15 langkah). Copy untuk fitur baru.

**Commands:**
```bash
node tests/Browser/Playwright/Feature/OperatorPerusahaan/CheckPdfLogoHeaded.cjs
.\parallel-test.ps1 -MaxWorkers 8   # PHPUnit
.\parallel-dusk.ps1 -MaxWorkers 4   # Dusk (butuh npm run build + hot rename)
```

## 5. Definisi Selesai (DoD) — §8

Fitur selesai jika: §1 solid → §2 patuh → `npm run build` sukses → Deep Verify headed 100% + screenshot/video reviewed → PHPUnit pass (jika ada logika baru) → validasi 3 lapis → dark/responsive → data unik+cleanup → no dead code → docs/report updated → commit+push.

---

*Stack: Laravel13/PHP8.3/Vue3.5/Inertia2/Vite8/Tailwind4 | DB: uuidv7, soft delete, blameable | Storage: MinIO via file.proxy*
