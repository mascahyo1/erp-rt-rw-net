---
name: karyawan-api-search-routes
description: "SearchableSelectAjax di halaman Karyawan butuh route API search sendiri (/karyawan/api/search/...), tidak bisa reuse /operator-perusahaan/api/search/ karena beda session guard"
metadata: 
  node_type: memory
  type: project
  originSessionId: a9fcc23e-1fb0-452e-a6bc-66777865340e
---

Karyawan pages pakai SearchableSelectAjax component yang fetch ke endpoint `/karyawan/api/search/{customers|packages|langganans|invoices|incentives|employees}`. Endpoint TIDAK boleh reuse `/operator-perusahaan/api/search/...` karena beda auth guard (employee vs admin-company) — akan 401/302 redirect ke login.

**Why:** Saat bikin Karyawan parity pages, replicate operator perusahaan view, tapi searchable select-nya fetch ke URL yang salah. Dropdown muncul tapi "Tidak ada hasil" karena API return HTML login page (bukan JSON). SearchController (App\Http\Controllers\Api\SearchController) handle semua ini — sudah generic, hanya perlu route group terpisah.

**How to apply:** Saat tambah Karyawan page baru yang pakai SearchableSelectAjax, pastikan `routes/web/karyawan.php` punya entry di bawah `auth:employee` middleware:
```php
$apiNs = 'App\Http\Controllers\Api';
Route::get('/karyawan/api/search/customers', [$apiNs.'\SearchController', 'customers']);
Route::get('/karyawan/api/search/packages', [$apiNs.'\SearchController', 'packages']);
Route::get('/karyawan/api/search/langganans', [$apiNs.'\SearchController', 'langganans']);
Route::get('/karyawan/api/search/invoices', [$apiNs.'\SearchController', 'invoices']);
Route::get('/karyawan/api/search/incentives', [$apiNs.'\SearchController', 'incentives']);
Route::get('/karyawan/api/search/employees', [$apiNs.'\SearchController', 'employees']);
```
SearchController otomatis scope by `auth()->user()->company_id`, jadi aman dipakai untuk semua guard (admin-company, employee) — yang penting route prefix beda.
