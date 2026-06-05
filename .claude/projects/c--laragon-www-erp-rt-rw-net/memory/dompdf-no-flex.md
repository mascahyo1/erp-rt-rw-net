---
name: dompdf-no-flex
description: DomPDF tidak support CSS Flexbox — pakai table-based layout untuk PDF/Word
metadata:
  type: project
---

**DomPDF** (PHP library untuk generate PDF dari HTML) **TIDAK support CSS Flexbox** dengan benar. CSS Flexbox (`display: flex`, `align-items`, `justify-content`) di-render dengan default alignment, sehingga layout jadi kacau dan unpredictable.

**Why:** Ini penting untuk SEMUA PDF/Word generation di project ini yang pakai DomPDF atau PhpWord:
- `app/Http/Controllers/OperatorPerusahaan/TagihanController.php` — `buildInvoiceHtml()`
- `app/Http/Controllers/OperatorPerusahaan/PembayaranController.php` — `downloadPdf()` (via `resources/views/pdf/payment-receipt.blade.php`)
- Export Word methods juga pakai HTML→Word

**Solusi layout untuk DomPDF:**
- ✅ Pakai HTML `<table>`, `<tr>`, `<td>` asli — paling reliable
- ✅ Pakai CSS `display: table` / `table-cell` — works
- ✅ Pakai `float: left/right` + `clear: both`
- ✅ Pakai `display: inline-block` + `vertical-align: middle`
- ❌ JANGAN pakai `display: flex` (atau `align-items`, `justify-content`)
- ❌ JANGAN pakai CSS Grid

**How to apply:**
- Setiap kali bikin HTML untuk PDF/Word di project ini, **pakai table-based layout**
- Untuk header 2-kolom (info kiri + logo kanan): `<table>` dengan 2 `<td>` + `vertical-align: middle`
- Untuk baris dengan 2 elemen kiri-kanan: `<table>` 100% dengan `text-align` di tiap `<td>`
- Cek existing pattern di `.info-grid` (line 641-644 TagihanController) — itu contoh yang works
- Test PDF di headed browser (`headless: false`) setelah setiap perubahan layout
- Kalau layout masih ngawur, suspect flexbox dulu sebelum nyalahin hal lain
