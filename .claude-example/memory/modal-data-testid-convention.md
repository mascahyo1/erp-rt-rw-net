---
name: modal-data-testid-convention
description: Wajib pakai data-testid di semua field modal Vue — biar Playwright gak salah target ke filter/search di luar modal
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Convention: `data-testid` di semua field modal Vue

## Context
Saat testing E2E flow "karyawan/perusahaan Midtrans checkout" (2026-06-25), test Playwright salah input ke **filter datatable** (yang ada di atas modal), bukan ke **field dalam modal**. Akibatnya test gagal, dan user marah karena membuang waktu debug.

## Why
Generic locator (`input[type="number"]`, `input[placeholder*="Pilih"]`) bisa match element MANA SAJA di page yang memenuhi pattern — bukan spesifik ke modal. Filter datatable di atas modal juga punya `input[type="number"]` (untuk jumlah bayar filter? — no, tapi punya select provider/metode juga) → test salah target.

## How to apply

### 1. SETIAP field di modal (input, select, button, textarea, file) WAJIB punya `data-testid`

Pattern: `data-testid="{field-purpose}"` atau `data-testid="{modal}-{field-purpose}"` kalau banyak modal.

Contoh untuk modal Tambah Pembayaran:
- `data-testid="modal-create"` (form/container untuk scope)
- `data-testid="input-code"` (input Kode Pembayaran)
- `data-testid="btn-select-invoice"` (button SearchableSelectAjax trigger)
- `data-testid="input-amount"` (input Jumlah)
- `data-testid="select-provider"` (select Provider)
- `data-testid="btn-simpan"` (button Simpan di footer)

### 2. Berlaku untuk SEMUA modal di SEMUA Vue page

Wajib:
- Tambah Pembayaran
- Edit (field sama dgn Create, tambah testid `-edit`)
- Delete / Konfirmasi (button konfirmasi)
- Review (single + bulk)
- Detail
- Import
- Export dropdown
- Filter — kalau filter dipakai test, kasih testid juga (atau pakai scope modal)

### 3. Test Playwright pakai `page.getByTestId(...)` BUKAN generic locator

```js
// ❌ JANGAN
await page.locator('input[type="number"]').first().fill('100000');
await page.locator('button:has-text("Simpan")').first().click();

// ✅ BENAR
await page.getByTestId('input-amount').fill('100000');
await page.getByTestId('btn-simpan').click();
```

### 4. Workflow setiap perubahan modal

Sesuai [teliti-workflow.md](teliti-workflow.md):
1. Edit Vue (tambah testid)
2. `npm run build`
3. **Re-read Vue** (verify testid ada di tempat yang benar)
4. Edit test
5. Run test dgn screenshot per step + verify
6. Re-read test

JANGAN skip re-read — testid salah tempat = test salah target.

## Related
- [teliti-workflow.md](teliti-workflow.md) — workflow umum (confirm → todo → 1 file: screenshot+verify+recheck)
- [[testing-prioritas-playwright]] — Playwright prioritized for regression
