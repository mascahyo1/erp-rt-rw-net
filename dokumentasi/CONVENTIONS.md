# Konvensi Standar Project ERP RT/RW Net

> Dokumen ini jadi acuan utama saat menulis kode baru atau mereview kode existing.
> Jika ada konflik dengan dokumentasi per-halaman, dokumentasi per-halaman yang lebih spesifik menang.

## Prinsip Utama: Hybrid Inertia + AJAX

Project ini pakai **2 protokol berbeda untuk 2 use case berbeda**. Jangan campur.

| Use case | Protokol | Alasan |
|----------|----------|--------|
| **Navigasi** antar halaman (sidebar, breadcrumb, redirect setelah create) | **Inertia** | SPA-like experience, server-driven, partial reload otomatis, props sharing |
| **CRUD form** (create/edit/delete inline atau di modal) | **Pure AJAX** + JSON response | Tidak pindah halaman setelah submit, lebih simpel, tidak ada quirk header/redirect |

---

## 1. Navigasi Pakai Inertia

**Kapan pakai:**
- User klik menu sidebar / breadcrumb → pindah halaman
- Redirect setelah create sukses → ke halaman list/detail
- Link `<Link :href="...">` di Vue
- `router.visit(url)` atau `router.get(url)` di script

**Pattern di controller** (untuk navigasi, BUKAN untuk form submit):
```php
return Inertia::render('OperatorPerusahaan/Tagihan', [
    'tagihan' => $tagihan,
]);
```

**Pattern di Vue:**
```vue
<Link :href="`/operator-perusahaan/tagihan/${id}`">Lihat Detail</Link>
```

**Aturan:** Response navigasi selalu `Inertia::render()` atau `Inertia::location()`.

---

## 2. CRUD Form Pakai AJAX

**Kapan pakai:**
- Submit form di modal (create/edit)
- Submit form inline (tanpa pindah halaman)
- Tombol hapus/restore di list (yang pakai modal konfirmasi)
- Quick action seperti "set status", "bulk action"

**Pattern di Vue (submit form):**
```js
// JANGAN pakai useForm().put()/patch()/delete() — bermasalah dengan multipart
// JANGAN pakai form.put() dengan forceFormData: true — PHP bug multipart PUT
// PAKAI: fetch/axios langsung dengan FormData + method sesuai route

async function submitForm() {
  const data = new FormData();
  data.append('name', form.name);
  data.append('email', form.email);
  if (logoFile.value) data.append('logo', logoFile.value);

  try {
    const res = await fetch(`/api/perusahaan/${id}`, {
      method: 'PUT',  // atau POST/PATCH/DELETE sesuai route
      body: data,
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
    });

    if (!res.ok) {
      const err = await res.json();
      // tampilkan error validasi
      errors.value = err.errors;
      return;
    }

    const json = await res.json();
    toast.success(json.message);
    editMode.value = false;
    // update local state dengan json.data
    Object.assign(props.company, json.data);
  } catch (e) {
    toast.error('Gagal menyimpan.');
  }
}
```

**Pattern di controller (untuk AJAX endpoint):**
```php
public function update(Request $request, Company $company)
{
    $validated = $request->validate([...]);

    // proses file upload
    if ($request->hasFile('logo')) {
        $path = $uploader->processLogo($request->file('logo'), 'companies/logos');
        $validated['logo'] = $path;
    }

    $company->update($validated);

    return response()->json([
        'success' => true,
        'message' => 'Data berhasil diperbarui.',
        'data' => $company->fresh(),
    ], 200);
}
```

**Aturan:**
- Route prefix `/api/...` untuk endpoint AJAX (opsional, bisa juga tanpa prefix tapi pisahkan dari route Inertia)
- Selalu return JSON `{ success, message, data?, errors? }`
- Status code: 200 sukses, 422 validasi gagal, 500 server error
- Validasi error otomatis jadi 422 dengan `{ message, errors: { field: [...] } }` oleh Laravel

---

## 3. Kapan TIDAK Boleh Pakai useForm() Inertia

`useForm()` + `form.put()/patch()/delete()` dengan `forceFormData: true` **dilarang** untuk form dengan file upload. Ini karena:

1. **PHP bug**: PHP tidak parse `multipart/form-data` untuk method PUT/PATCH/DELETE → `$request->all()` kosong di controller
2. **Inertia Laravel v2 quirk**: response redirect tidak set header `X-Inertia: true` → `onSuccess` callback tidak jalan
3. **Form helper quirk**: `form._method = 'PUT'` tidak otomatis masuk body (bukan Inertia convention)

**Pengecualian**: `useForm()` boleh dipakai untuk form **tanpa file upload** (mis. form search, filter, bulk action) karena tidak ada body multipart yang perlu di-parse.

---

## 4. Decision Tree Saat Menulis Form Baru

```
Ada file upload di form?
├── YA → Pakai Pure AJAX (section 2)
│   (form.put() + forceFormData: true AKAN GAGAL)
│
└APA TIDAK →
    Ada navigasi/pindah halaman setelah submit?
    ├── YA → Pakai Inertia::render() / Inertia::location()
    │
    └ TIDAK (tetap di halaman) →
        Pakai useForm() Inertia (lebih simpel)
        tapi return JSON dari controller, bukan Inertia response
```

---

## 5. Testing Pattern

| Tipe test | Pakai | Pattern |
|-----------|-------|---------|
| Navigasi (sidebar, breadcrumb) | Playwright + `page.goto()` atau click `<Link>` | `await page.click('a:has-text("Tagihan")')` |
| Form submit dengan file | Playwright + AJAX interception | `await page.evaluate(() => fetch(...))` atau tunggu response dari `Promise.all([waitForResponse, click])` |
| Form submit tanpa file | Playwright + submit form biasa | `await page.click('button[type="submit"]')` |
| Validasi error | Playwright + expect error toast/text muncul | `await expect(page.locator('text=field is required')).toBeVisible()` |

---

## 6. Contoh di Kode Existing (referensi)

### ✅ Yang Benar (AJAX pattern)
Lihat [Tagihan.vue](operator-perusahaan/Tagihan.vue) — submit form pakai fetch/axios manual.

### ❌ Yang Salah (Inertia form.put() dengan file)
Lihat [PerusahaanSaya.vue](operator-perusahaan/PerusahaanSaya.vue) `submitEdit()` — saat ini pakai `form.put()` + `forceFormData: true` → **bug** (lihat [Known Issues](operator-perusahaan/perusahaan-saya.md#known-pre-existing-issues-belum-fix-bukan-dari-fitur-logo)). Akan di-refactor ke AJAX pattern.

---

## 7. Migrasi Bertahap (Roadmap)

| Prioritas | Task | File yang kena |
|-----------|------|----------------|
| 🔴 Tinggi | Refactor `PerusahaanSaya.vue::submitEdit` ke AJAX | `resources/js/Pages/OperatorPerusahaan/PerusahaanSaya.vue` |
| 🔴 Tinggi | Tambah endpoint AJAX di `PerusahaanSayaController` | `app/Http/Controllers/OperatorPerusahaan/PerusahaanSayaController.php` |
| 🟡 Sedang | Refactor `CompanyController::update` ke endpoint AJAX (untuk SaaS edit) | `app/Http/Controllers/CompanyController.php` |
| 🟡 Sedang | Refactor form lain yang pakai `form.put()` + file upload | `Tagihan.vue`, `RiwayatPembayaran.vue`, dll |
| 🟢 Rendah | Hapus patch `HandleInertiaRequests::handle()` override (sudah tidak dipakai) | `app/Http/Middleware/HandleInertiaRequests.php` |
| 🟢 Rendah | Hapus `ParseMultipartForPutPatchDelete` middleware (sudah tidak relevan) | `app/Http/Middleware/ParseMultipartForPutPatchDelete.php` |

---

## 8. Anti-Pattern yang Dilarang

❌ `form.put(url, { forceFormData: true })` — file upload akan gagal parse
❌ `form._method = 'PUT'` — tidak otomatis masuk body
❌ `back()->with('success', ...)` untuk form submit yang tidak pindah halaman — pakai JSON response
❌ `Inertia::render()` untuk response form submit AJAX — pakai `response()->json()`
❌ Override `X-Inertia: true` di middleware custom — fix di Inertia Laravel package, bukan workaround di project
❌ Bikin middleware parsing multipart manual — gunakan Laravel `_method` override atau pure AJAX

---

## 9. Referensi

- [Inertia.js Docs](https://inertiajs.com/) — untuk navigasi
- [Laravel Validation](https://laravel.com/docs/validation) — untuk response error 422
- [MDN FormData](https://developer.mozilla.org/en-US/docs/Web/API/FormData) — untuk AJAX form upload
- [Dokumentasi per-halaman](operator-perusahaan/) — untuk detail tiap fitur
