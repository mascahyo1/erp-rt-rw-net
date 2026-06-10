# Flow Generate Tagihan — Multi-Cycle Design

> **Status:** 7/7 design decisions settled (2026-06-08)
> **Implementation:** Pending (commit design decisions dulu, code di session berikutnya)

---

## 🎯 Konsep

Halaman `/operator-perusahaan/tagihan` punya tombol "Generate". Ketika diklik, muncul modal dengan **dynamic form per billing cycle** (Harian / Mingguan / Bulanan / Tahunan). Pilihan cycle menentukan field input yang muncul.

## 📋 Input Form Per Cycle (Final)

| Cycle | Input Fields | Validation |
|-------|--------------|------------|
| **Harian (D)** | `usage_date` (1 date) + `due_date` | usage_date >= today |
| **Mingguan (W)** | `usage_start_date` (Senin) + `due_date` | usage_start_date = Senin (auto-derive end_date = +6 days) |
| **Bulanan (M)** | `tahun` + `bulan` + `due_date` (optional) | tahun >= 2020, bulan 1-12 |
| **Tahunan (Y)** | `tahun` + `due_date` (optional) | tahun >= 2020 |

Catatan: due_date di semua cycle **optional** — kalau kosong, pakai CompanyConfig `invoice.default_due_days` (per-company), fallback hardcoded 30.

## 🎨 Invoice Number Format (Final)

```
INV-{CYCLE}-{PERIOD}-{TIMESTAMP_MS}-{6_LETTERS}-{6_DIGITS}
```

| Cycle | PERIOD format | Source | Contoh |
|-------|---------------|--------|--------|
| Harian (D) | `YYYYMMDD` | `usage_date` | `INV-D-20260608-1717838401456-AFJKLQ-789012` |
| Mingguan (W) | `YYYYMMDD` | `usage_start_date` (Senin) | `INV-W-20260608-1717838401789-BCDEFG-345678` |
| Bulanan (M) | `YYYYMM` | input tahun + bulan | `INV-M-202610-1717838400123-XKHQNZ-123456` |
| Tahunan (Y) | `YYYY` | input tahun | `INV-Y-2026-1717838402123-GHIJKL-901234` |

**Properties:**
- Seunik UUIDv7 (timestamp_ms + 6 letters + 6 digits = practically unique)
- Mudah dibaca manusia (parts pisah, jelas cycle + period)
- Bermakna (cycle + period visible)
- Sortable by time (timestamp_ms natural ordering)
- Per-company unique (controller filter by company_id, idempotency by cust_internet_id + usage_start_date)

## 💰 Amount Source (Final)

**`internet_packages.price`** adalah source of truth.

- 1 paket = 1 cycle = 1 price
- Contoh: Paket "30 Mbps Monthly" → price 300rb → customer langganan paket ini = invoice 300rb/bulan
- Customer langganan paket "30 Mbps Daily" → invoice 10rb/hari (price = 10rb untuk paket daily)
- No proration, no `cust_internets.billing_amount` (dropped in Day 1.5)

## ⏰ Scheduler (Final)

**DISABLED** (commented in `routes/console.php`).

Manual trigger only via:
- UI: tombol "Generate" di Tagihan page (per cycle)
- CLI: `php artisan app:invoice-generate --cycle=<D|W|M|Y> --usage-date=...` (atau args sesuai cycle)

Uncomment scheduler jika di-re-enable nanti.

## 🔁 Idempotency (Final)

Per-cycle specific check (composite: `cust_internet_id` + cycle-specific period check):

| Cycle | Idempotency Check |
|-------|-------------------|
| Harian (D) | `usage_start_date = $usage_date` (exact) |
| Mingguan (W) | `usage_start_date BETWEEN $start_date AND $start_date + 6 days` |
| Bulanan (M) | `YEAR(usage_start_date) = $year AND MONTH(usage_start_date) = $month` |
| Tahunan (Y) | `YEAR(usage_start_date) = $year` |

Kalau ada invoice match, **skip** (gak duplicate).

## 📊 Schema Changes (Final)

### Migration: add `cycle` column ke `cust_internet_invcs`

```php
Schema::table('cust_internet_invcs', function (Blueprint $table) {
    $table->enum('cycle', ['daily', 'weekly', 'monthly', 'yearly'])
        ->default('monthly')
        ->after('cust_internet_id');
});
```

### Existing data backfill

- `migrate:fresh --seed` OK (data masih dummy, gak ada data real customer)
- Default 'monthly' untuk existing invoice
- Future invoice: explicit cycle value

## 🏗️ Implementation Plan (Pending)

### Day 2 — Multi-Cycle Generate + Piutang Page

1. **Migration**: add `cycle` column ke `cust_internet_invcs`
2. **`InvoiceGeneratorService`** refactor: 4 methods (generateDaily/Weekly/Monthly/Yearly) + per-cycle idempotency
3. **`GenerateInvoicesCommand`** update: `--cycle` flag + cycle-specific args
4. **`TagihanController::generate()`** update: parse input per cycle
5. **`Tagihan.vue`** modal: dynamic form (radio cycle dulu, lalu form berubah)
6. **Piutang page**: route + controller + Vue

### Day 3 — Dashboard Widget + E2E

1. **Dashboard widget** "Piutang Outstanding" (total Rp + count)
2. **E2E test**: full flow 4 cycles + piutang + dashboard
3. **Commit + push**

## 📐 Database Schema (Final)

```
internet_packages
  - id, code, name, price, billing_cycle (D/W/M/Y), ...

cust_internets
  - id, customer_id, internet_package_id, account_number, internet_status, ...
  - (NO billing_amount, NO billing_cycle_* — dropped Day 1.5)

cust_internet_invcs
  - id, cust_internet_id, cycle (D/W/M/Y), invoice_number, usage_start_date,
    usage_end_date, invoice_due_date, amount, total_amount, discount_amount,
    tax_amount, grand_total, payment_status, status, description, ...
  - (NO due_date — dropped Day 1.5, kept invoice_due_date only)
```

## 📝 Conversation Log (decisions)

- **#1 Harian input**: User declined `start_date + end_date`, chose `usage_date` (1 date) + `due_date`
- **#2 Invoice format**: User iterated from "with company code" → "without company code" → final hybrid
- **#3 Amount source**: User identified `internet_packages` as source of truth
- **#4 Scheduler**: User chose "no scheduler, skip" — commented in code
- **#5 Idempotency**: User chose per-cycle specific check (more robust)
- **#6 Cycle column**: User chose add `cycle` column (clarity, query, reporting)
- **#7 Backwards compat**: User OK with `migrate:fresh --seed` (data masih dummy)

## 🚀 Quick Reference

### CLI Commands (after implementation)

```bash
# Harian
php artisan app:invoice-generate --cycle=daily --usage-date=2026-06-08

# Mingguan
php artisan app:invoice-generate --cycle=weekly --usage-date=2026-06-08

# Bulanan
php artisan app:invoice-generate --cycle=monthly --month=2026-06

# Tahunan
php artisan app:invoice-generate --cycle=yearly --year=2026
```

### Per-Cycle Args

| Cycle | Required Args | Optional Args |
|-------|--------------|---------------|
| daily | `--cycle=daily --usage-date=YYYY-MM-DD` | `--due-days=N`, `--company=<uuid>` |
| weekly | `--cycle=weekly --usage-date=YYYY-MM-DD` (Senin) | `--due-days=N`, `--company=<uuid>` |
| monthly | `--cycle=monthly --month=YYYY-MM` | `--due-days=N`, `--company=<uuid>` |
| yearly | `--cycle=yearly --year=YYYY` | `--due-days=N`, `--company=<uuid>` |
