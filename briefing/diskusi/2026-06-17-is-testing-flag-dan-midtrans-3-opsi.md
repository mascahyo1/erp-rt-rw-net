# Diskusi: `companies.is_testing` flag + Midtrans 3-Opsi Config

> **Tanggal:** 2026-06-17 (Selasa)
> **Sesi:** Diskusi pre-implementation (BELUM ngoding)
> **Status:** 🟡 Open — masih ada pertanyaan final
> **Author:** Claude (untuk user review)

---

## Context & Problem

User concern:
1. **`companies` table ga ada flag `is_testing`** → susah distinguish company dummy vs real di production
2. **Payment gateway Midtrans config perlu fleksibel** — beda company beda kebutuhan (nebeng akun SaaS, BYOK, atau disable)
3. **Laporan keuangan** harus bisa filter exclude testing data, supaya admin bisa lihat real revenue walaupun ada dummy di production

**Tujuan:**
- Admin SaaS bisa marking company mana yang "testing only" (sandbox payment, dummy data)
- Laporan keuangan real-time exclude `is_testing = true`
- Midtrans config fleksibel per company (3 mode: SaaS-shared, BYOK, disabled)
- Aman di production (no accidental real charge ke customer dummy)

---

## Yang Sudah Diputuskan (Confirmed)

### 1. Schema decision: `is_testing` di level company

**Tabel `companies` — tambah 2 kolom baru:**
- `is_testing` boolean DEFAULT false → flag untuk filter laporan keuangan
- `enable_midtrans` boolean DEFAULT true → kill switch payment gateway per company

**Purpose `is_testing`:**
- Filter laporan keuangan (aggregate exclude testing data)
- Auto-force Midtrans ke sandbox (lihat policy decision di bawah)
- Tampilan badge "TESTING" di UI admin

### 2. Midtrans 3-Opsi

**Tabel `companies` — tambah 1 kolom:**
- `midtrans_byok` boolean DEFAULT false

**3 Opsi Midtrans:**

| Opsi | `enable_midtrans` | `midtrans_byok` | Behavior |
|---|---|---|---|
| **Disabled** | `false` | (any) | Midtrans OFF, sembunyikan button bayar |
| **Nebeng SaaS** | `true` | `false` | Pakai `saas_configs.master_midtrans_*` |
| **BYOK** | `true` | `true` | Pakai `company_configs.midtrans_*` sendiri |

### 3. 2 Tabel Config Terpisah

| Tabel | Purpose | Pattern |
|---|---|---|
| **`saas_configs`** | Setting level SaaS owner (1 record) | Key-value, **NO company_id** |
| **`company_configs`** | Setting level perusahaan (N records, 1 per company) | Key-value, dengan `company_id` |

Keduanya pakai pattern key-value existing (`key`, `type`, `value`, `description`) — bukan kolom langsung. Model sudah ada: `App\Models\SaasConfig` dan `App\Models\CompanyConfig`.

**Field tambahan yang dibutuhkan:**

**`saas_configs` (3 row baru):**
- `master_midtrans_client_key` (type: text, encrypted)
- `master_midtrans_server_key` (type: text, encrypted)
- `master_midtrans_is_production` (type: boolean, default false)

**`company_configs` (3 row baru per company, hanya kalau `midtrans_byok = true`):**
- `midtrans_client_key` (type: text, encrypted)
- `midtrans_server_key` (type: text, encrypted)
- `midtrans_is_production` (type: boolean, default false)

### 4. Config Resolution Algorithm

```
1. companies.enable_midtrans = false → return null (Midtrans disabled)
2. companies.midtrans_byok = true → pakai company_configs.midtrans_*
3. companies.midtrans_byok = false → pakai saas_configs.master_midtrans_*
4. companies.is_testing = true → FORCE is_production = false (sandbox) ← TAPI INI BLM KONFIRM
5. Return null kalau config tidak lengkap → throw "Midtrans not configured"
```

---

## Yang Masih Open Question (Belum Diputuskan)

### ❓ Q1: Policy `is_testing` → Midtrans sandbox enforcement

| Opsi | Behavior | Pro | Con |
|---|---|---|---|
| **X — Strict** | `is_testing = true` → paksa sandbox regardless of config | ✅ Tidak mungkin salah charge customer real | ❌ Admin tidak bisa test real flow production di company testing |
| **Y — Suggestion** | Default sandbox, admin bisa override per-company | ✅ Fleksibel | ❌ Risk: admin lupa set ulang → real charge ke customer dummy |
| **Z — Independent** | `is_testing` cuma filter laporan, tidak affect Midtrans | ✅ Clean separation | ❌ Admin harus manual ensure testing pakai sandbox → human error |

**Rekomendasi: X (Strict)** untuk safety.

### ❓ Q2: `is_testing` lifecycle

Apakah `is_testing` ini **permanen** (satu company selalu testing) atau **temporary** (akan dipromote ke production)?

**Implikasi UI/UX:**
- **Permanen** → admin SaaS pilih saat create company, ada badge "TESTING" permanen di UI
- **Temporary** → ada button "Promote to Production" + audit log perubahan + perlu approval?

**Default asumsi: permanen** (butuh konfirmasi user).

### ❓ Q3: Behavior `enable_midtrans = false`

Apakah artinya:
- **(a) Sembunyikan button bayar Midtrans** saja, masih bisa input pembayaran manual (tunai/transfer), atau
- **(b) Disable semua flow pembayaran**, customer cuma bisa lihat tagihan tanpa bayar?

**Rekomendasi: (a)** — biar customer masih bisa bayar manual kalau perlu.

### ❓ Q4: UI Konfigurasi Master Midtrans di SaaS

Apakah field `master_midtrans_is_production` di `saas_configs` perlu:
- **UI khusus di Operator SaaS** (admin SaaS input via form di halaman Konfigurasi SaaS) — lebih user-friendly
- **Atau cukup set via seeder/env** — simpler, tapi kurang flexible

**Rekomendasi: UI khusus** di halaman Konfigurasi SaaS yang sudah ada (mirip dengan field SaaS yang lain).

---

## Trade-off yang Perlu Dipertimbangkan

### Laporan Keuangan Granularity

`is_testing` di level company — tapi **payment & invoice data** di level child records (`customers`, `cust_internets`, `cust_internet_invcs`, `cust_internet_payments`).

**Opsi:**
- **A. Query-time filter** — manual `WHERE c.company_id IN (SELECT id FROM companies WHERE is_testing = false)`
- **B. Global scope di Model** — auto-filter, tapi magic
- **C. Cascade flag ke child records** — banyak table perlu kolom `is_testing`

**Rekomendasi: A (Query-time filter)** untuk simplicity + explicit.

### Encryption Strategy

- **Midtrans API keys** (`server_key`, `client_key`) perlu di-encrypt di DB (Laravel `encrypted` cast)?
- Best practice: YES (kalau DB bocor, secrets tidak langsung compromised)
- Tapi: encrypted value tidak bisa di-search / index → trade-off acceptable karena jarang query by key

**Rekomendasi: encrypted cast.**

### Existing Config Pattern Compatibility

Saat ini `config/midtrans.php` baca dari `.env` (hardcoded env-based). Saat config di-move ke `saas_configs` table, perlu:
- **A. Backfill** — copy value dari `.env` ke `saas_configs` table saat pertama migrate
- **B. Fallback** — `saas_configs.master_midtrans_*`优先, `.env` sebagai fallback kalau null
- **C. Migrate total** — hapus dari `.env`, paksa pakai table (butuh seeder untuk existing data)

**Rekomendasi: B (Fallback)** untuk backward compatibility.

---

## Open Questions Summary (untuk User)

1. **Q1:** Pilih X / Y / Z untuk policy `is_testing` → sandbox enforcement?
2. **Q2:** `is_testing` permanen atau temporary (ada "Promote to Production" flow)?
3. **Q3:** `enable_midtrans = false` → behavior (a) sembunyikan Midtrans saja, atau (b) disable semua pembayaran?
4. **Q4:** Master Midtrans di `saas_configs` perlu UI khusus, atau cukup seeder/env?

---

## Next Step

Setelah 4 pertanyaan di atas dijawab, baru lanjut:
1. **Design trade-off analysis** (per Opsi A/B/C per concern)
2. **Planning** (file apa yang diubah, migration, controller, Vue, test)
3. **Implementation** (mulai ngoding)

**Saat ini: BELUM NGODING, masih diskusi.**

---

## Catatan Tambahan

- Folder ini `briefing/diskusi/` khusus untuk catatan diskusi pre-implementation, biar terpisah dari `report/` (yang isinya daily/weekly/progress final).
- File ini akan di-update setiap ada keputusan baru / klarifikasi baru.
- Setelah keputusan final, baru buat planning file terpisah (misal `briefing/diskusi/2026-06-17-final-plan.md`) yang isinya step-by-step implementation.
