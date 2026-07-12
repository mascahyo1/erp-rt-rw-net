#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generate content for all remaining Notion pages + Plane task IDs.
Output: notion_pages_with_content.json
"""
import json
import re

# Load page list from fetch step
with open("c:\\laragon\\www\\erp-rt-rw-net\\.tmp\\notion_pages.json", "r", encoding="utf-8") as f:
    pages = json.load(f)

# Plane task IDs (hardcoded mapping from earlier work)
# Format: (clean_title, section) -> plane_task_uuid
PLANE_IDS = {
    # Pendahuluan (1)
    ("Pendahuluan & Topik Lintas Portal", "Pendahuluan & Topik Lintas Portal"): "b897c6b2-b2ae-496a-98ad-814cf435f24a",
    # Topik Umum (6)
    ("Multi-Tenant", "Topik Umum"): "6051034d-c4ed-4112-839f-5179a7e5ee63",  # DONE
    ("Kontak Support", "Topik Umum"): "2498ff30-ca30-45f7-bc11-6b014c1d09f3",
    ("Import/Export Excel", "Topik Umum"): "5d9c031e-9a50-4c00-aff0-07b567141f71",
    ("Workflow Persetujuan", "Topik Umum"): "d947d2f9-8f3c-4fc3-bedd-4f319cf31f1d",
    ("Kredensial Demo", "Topik Umum"): "23858a42-e416-4dec-aa5e-5071071b691f",
    ("RBAC & Permission", "Topik Umum"): "51ffa6d1-d323-4ad7-9062-53be6c043231",
    # Landing (9) — note 2 beranda entries
    ("beranda", "Halaman Publik / Landing"): "6216dc4e-67bb-463f-aab8-b3b89f7c1385",
    ("tentang-kami", "Halaman Publik / Landing"): "6ade348a-edde-4e2f-b443-bf0ce4b8f641",
    ("hubungi-kami", "Halaman Publik / Landing"): "407fcae5-d379-4915-aee2-df4bf50d5df9",
    ("kebijakan-privasi", "Halaman Publik / Landing"): "38ec0c01-4263-443a-9232-a59f5e2936e1",
    ("syarat-dan-ketentuan", "Halaman Publik / Landing"): "eded4c21-382c-428d-bf80-631814e0679a",
    ("login-operator-saas", "Halaman Publik / Landing"): "2db03fb5-f5dd-4153-8c21-4c37681ef1d7",
    ("login-perusahaan", "Halaman Publik / Landing"): "18cf2925-75dd-4e40-9ca3-d68b4922b6cd",
    ("login-karyawan", "Halaman Publik / Landing"): "37630240-2387-4995-807d-a7bdfe48d4c9",
    ("login-pelanggan", "Halaman Publik / Landing"): "9043da5b-3b5c-42ad-85c2-82c0cce1d73e",
    # Operator SaaS (10)
    ("dashboard", "Operator SaaS"): "82474de0-e5c0-4ff1-817a-2ea43454e77f",
    ("halaman-perusahaan", "Operator SaaS"): "a61c2b23-2ee7-49bd-b570-3105953c69c3",
    ("halaman-admin-perusahaan", "Operator SaaS"): "0e841a56-3e07-42c8-9a24-6af7794b5a78",
    ("halaman-admin-saas", "Operator SaaS"): "79c5885d-56d5-4ffd-b6ff-cade96b0efc9",
    ("halaman-konfigurasi", "Operator SaaS"): "7f689d6c-720d-4f90-b115-09f83fa4fb0d",
    ("halaman-role-saas", "Operator SaaS"): "5b4f3180-560f-41ce-80d9-c675285d5410",
    ("halaman-admin-role-saas", "Operator SaaS"): "7d754817-0a44-4537-9199-4ad6aabb975c",
    ("halaman-role-admin-perusahaan", "Operator SaaS"): "9b7d9535-627c-415c-8607-f991a4109faa",
    ("halaman-role-perusahaan", "Operator SaaS"): "7db8b276-c1c8-415d-b17a-000db339fad6",
    ("profil-saya", "Operator SaaS"): "d2db2f61-fc2d-4514-9363-ff327ef8835f",
    # Admin Perusahaan (17)
    ("dashboard", "Admin Perusahaan"): "60b57146-84a6-4413-8eb2-770e9a9d3c9c",
    ("daftar-paket", "Admin Perusahaan"): "64d54f58-ca40-4e1d-a726-5e8a3d555c18",
    ("halaman-langganan", "Admin Perusahaan"): "c469d066-8d05-42f2-ab5d-e7836225b94d",
    ("halaman-karyawan", "Admin Perusahaan"): "107336db-8f08-4314-b57c-1afb4754439c",
    ("halaman-customer", "Admin Perusahaan"): "46d2d7b9-74d3-412a-b5cc-1e98a99466c0",
    ("halaman-admin-perusahaan", "Admin Perusahaan"): "0e1ed0d1-e7e6-4f3e-a055-6b7892550f40",
    ("halaman-tagihan", "Admin Perusahaan"): "745fd395-a19b-47b1-b87c-50349f853625",  # DONE
    ("halaman-riwayat-pembayaran", "Admin Perusahaan"): "cd456df2-7c90-4ff1-81c6-5971ed9f3f9d",
    ("halaman-insentif", "Admin Perusahaan"): "82672a5b-a810-4d15-b5d4-21e2db8a020b",
    ("halaman-riwayat-insentif", "Admin Perusahaan"): "af31c374-7ba4-4399-95c4-de242fd79785",
    ("halaman-role-perusahaan", "Admin Perusahaan"): "8c1d5be6-61d5-4c73-91ce-2f0ea5304a90",
    ("halaman-admin-role-perusahaan", "Admin Perusahaan"): "7a9ed1bc-11d8-4324-8583-ee9418284cd6",
    ("halaman-admin-role-web-karyawan", "Admin Perusahaan"): "8f621b4d-61e0-453b-9e45-1566affd6fad",
    ("halaman-role-web-karyawan", "Admin Perusahaan"): "0c1e0818-67d2-4b1c-bc69-a6f5586e491b",
    ("halaman-konfigurasi-perusahaan", "Admin Perusahaan"): "c326fd83-f643-4b57-a5bf-815c5548318a",
    ("perusahaan-saya", "Admin Perusahaan"): "ad6f07b4-f9fb-4796-bc3d-bcccc8ee2686",
    ("profil-saya", "Admin Perusahaan"): "b1432bd4-9e9d-41c1-bb25-3a08defb635f",
    # Karyawan (7)
    ("dashboard", "Karyawan"): "5c5a295a-de2c-4d42-84cf-4d6f41f0f0f0",
    ("halaman-customer", "Karyawan"): "f047bdb4-5651-4417-84a9-0adb661885ed",
    ("halaman-langganan", "Karyawan"): "721ba554-f107-4cb7-a86a-dce94131b313",
    ("halaman-tagihan", "Karyawan"): "151f5507-c854-43bb-9054-115ec447cdcd",
    ("halaman-riwayat-pembayaran", "Karyawan"): "5bc4372f-9e4b-4408-b0f4-3c5bb6f336ff",
    ("halaman-insentif", "Karyawan"): "4f708c57-ff9e-4fb4-9a31-5f477c39d1bd",
    ("profil-saya", "Karyawan"): "ac6849b4-9b77-4f4a-adfa-0bd4aa50c0e5",
    # Pelanggan (10)
    ("dashboard", "Pelanggan"): "397d7398-15d8-43f2-8775-8724ebba49f3",
    ("paket-saya", "Pelanggan"): "143c94f8-1458-4571-938c-d72fc349fbea",
    ("paket-tambah", "Pelanggan"): "b2dd6a6b-e7d5-453b-ac5b-25fdeedd6a45",
    ("paket-detail", "Pelanggan"): "cc96987d-0521-473b-894b-7797acd48f76",
    ("tagihan-saya", "Pelanggan"): "ed7cd054-1fd7-4383-ae29-00698fd745a9",
    ("tagihan-detail", "Pelanggan"): "2cbea49e-886c-4680-b201-375e3c89d0af",
    ("pembayaran-tambah", "Pelanggan"): "7aaff052-8725-43a3-9439-65e345f1451d",
    ("pembayaran-detail", "Pelanggan"): "9cc2a391-d795-4be6-b75c-0508f60d67e4",
    ("riwayat-pembayaran", "Pelanggan"): "7ee4c842-fcd2-491d-b27f-ff31355705c3",
    ("profil-saya", "Pelanggan"): "ca26230b-1d4e-4dcd-afcf-9935cb783d3e",
    # Developer Guide (1)
    ("Developer Guide (Master)", "Developer Guide"): "5ea74804-1258-40d4-b877-d30dd5e13cfb",
}


def clean_title(title):
    """Strip [UG-XYZ] or [DG-XYZ] prefix."""
    return re.sub(r"^\[(UG|DG)-[A-Za-z]+\]\s*", "", title).strip()


def gen_dashboard(section):
    """Generate dashboard content per section."""
    if section == "Pendahuluan & Topik Lintas Portal":
        return """# [UG-Pendahuluan] Pendahuluan & Topik Lintas Portal

> Halaman pengantar untuk User Guide ERP RT/RW Net. Berisi ringkasan aplikasi, konsep multi-tenant, 4 portal, dan topik yang berlaku lintas portal.

---

## 🎯 Tentang Aplikasi

**ERP RT/RW Net** adalah aplikasi web-based untuk mengelola operasional ISP (Internet Service Provider) skala RT/RW Net — mulai dari pendaftaran pelanggan, pengelolaan paket, pembuatan tagihan, penerimaan pembayaran, hingga pelaporan insentif karyawan.

### Highlight Fitur
- 🏢 **Multi-tenant** — banyak perusahaan ISP dalam satu aplikasi
- 👥 **4 portal** terpisah sesuai persona (Operator SaaS, Admin Perusahaan, Karyawan, Pelanggan)
- 💳 **Tagihan & Pembayaran** — invoice otomatis, upload bukti bayar, approval workflow
- 📊 **Excel Import/Export** — bulk input/export via spreadsheet
- 🔐 **RBAC** — role-based access control per tenant
- 📱 **Responsive** — optimal di mobile (320px) sampai desktop

---

## 🏗️ 4 Portal Overview

| Portal | URL Pattern | Akses | Tugas Utama |
|--------|------------|-------|-----------|
| 🟣 **Operator SaaS** | `operator-saas.{domain}` | Multi-tenant | Kelola perusahaan, role SaaS, konfigurasi global |
| 🔵 **Admin Perusahaan** | `operator-perusahaan.{domain}` | Per-tenant | Kelola karyawan, customer, paket, tagihan |
| 🟢 **Karyawan** | `karyawan.{domain}` | Per-tenant | Input tagihan, lihat insentif, riwayat pembayaran |
| 🟡 **Pelanggan** | `pelanggan.{domain}` | Self-service | Cek paket, lihat tagihan, upload bukti pembayaran |

Setiap portal punya login terpisah, dashboard sendiri, dan halaman yang disesuaikan dengan tugas persona tersebut.

---

## 📚 Topik Lintas Portal

Beberapa konsep berlaku di semua portal dan perlu dipahami sebelum menggunakan aplikasi:

- 🔐 **[RBAC & Permission]** — sistem permission dan role-based access control
- 🏢 **[Multi-Tenant]** — isolasi data per perusahaan ISP
- 🔑 **[Multi-Login (4 guard)]** — autentikasi terpisah per portal
- 📊 **[Import/Export Excel]** — cara download template, isi data, upload
- ✅ **[Workflow Persetujuan]** — review/bulk-review untuk Pembayaran + Insentif
- 🌗 **[Tema (light/dark/system)]** — setting tampilan
- 🔔 **[Notifikasi (toast)]** — feedback sistem real-time

Lihat sub-halaman di bawah ini untuk detail masing-masing topik.

---

## 🆘 Butuh Bantuan?

- 📧 **Email:** support@jmpgroup.id
- 💬 **WhatsApp:** +62 xxx-xxx-xxxx (jam operasional 09:00-17:00 WIB)
- 📖 **Kredensial Demo:** lihat halaman [Kredensial Demo](.)

---

<sub>📝 Versi: 2026-07-12 · Sumber: Plane ERPRT-7</sub>
"""
    # Per-persona dashboard
    personas = {
        "Operator SaaS": {
            "emoji": "🟣",
            "portal": "operator-saas",
            "kpis": [
                ("Total Tenant Aktif", "12 perusahaan"),
                ("Total Admin SaaS", "5 user"),
                ("Pengguna Bulanan", "1,247 login/minggu"),
                ("Revenue MRR", "Rp 45.000.000"),
            ],
            "actions": [
                ("Tambah Perusahaan Baru", "/saas/companies/create"),
                ("Buat Admin SaaS", "/saas/admins/create"),
                ("Lihat Log Aktivitas", "/saas/activity-log"),
                ("Konfigurasi Global", "/saas/settings"),
            ],
        },
        "Admin Perusahaan": {
            "emoji": "🔵",
            "portal": "operator-perusahaan",
            "kpis": [
                ("Pelanggan Aktif", "145 customer"),
                ("Tagihan Belum Dibayar", "23 invoice"),
                ("Karyawan Aktif", "8 staff"),
                ("Paket Tersedia", "5 paket"),
            ],
            "actions": [
                ("Generate Tagihan Bulanan", "/invoices/bulk-create"),
                ("Tambah Pelanggan", "/customers/create"),
                ("Approve Pembayaran Pending", "/payments/pending"),
                ("Export Laporan Bulanan", "/reports/monthly"),
            ],
        },
        "Karyawan": {
            "emoji": "🟢",
            "portal": "karyawan",
            "kpis": [
                ("Tagihan Bulan Ini", "Saya buat 42 tagihan"),
                ("Insentif Bulan Ini", "Rp 1.250.000"),
                ("Pelanggan Ditangani", "23 customer"),
                ("Approval Pending", "5 pembayaran"),
            ],
            "actions": [
                ("Buat Tagihan Baru", "/invoices/create"),
                ("Lihat Insentif Saya", "/incentives/my"),
                ("Cek Riwayat Pembayaran", "/payments/history"),
            ],
        },
        "Pelanggan": {
            "emoji": "🟡",
            "portal": "pelanggan",
            "kpis": [
                ("Paket Aktif", "Paket 10Mbps"),
                ("Tagihan Belum Dibayar", "1 invoice"),
                ("Riwayat Pembayaran", "12 transaksi"),
                ("Status Layanan", "Aktif"),
            ],
            "actions": [
                ("Bayar Tagihan", "/invoices/pay"),
                ("Lihat Detail Paket", "/packages/my"),
                ("Upload Bukti Bayar", "/payments/upload"),
                ("Hubungi Support", "/support"),
            ],
        },
    }
    p = personas.get(section)
    if not p:
        return "# " + section + "\n\n(Konten akan ditambah segera)"
    kpi_rows = "\n".join(f"| **{name}** | {val} |" for name, val in p["kpis"])
    action_rows = "\n".join(f"- **[{name}]({url})**" for name, url in p["actions"])
    return f"""# {p['emoji']} [Dashboard {section}]

> Dashboard utama untuk **{section}** — ringkasan KPI dan quick actions.

---

## 🎯 Untuk Siapa?

Halaman ini hanya untuk **{p['emoji']} {section}** yang login di portal `{p['portal']}.jmpgroup.id`.

---

## 📊 KPI Ringkasan

| KPI | Nilai |
|-----|-------|
{kpi_rows}

> KPI di-refresh setiap 5 menit. Klik untuk lihat detail breakdown.

---

## ⚡ Quick Actions

{action_rows}

---

## 📈 Aktivitas Terkini

- 15 menit lalu: 3 tagihan baru dibuat oleh **Ahmad S.**
- 1 jam lalu: Pembayaran INV-2025-001234 di-approve oleh **Siti R.**
- 3 jam lalu: 2 pelanggan baru ditambahkan oleh **Budi P.**
- Hari ini: Total 47 transaksi (target harian: 50)

---

## 📊 Shortcut Navigasi

- 📋 [Halaman {section}]() — daftar lengkap
- 📥 Download Laporan
- ⚙️ [Pengaturan]()
- ❓ [Bantuan]()

---

<sub>📝 Versi: 2026-07-12 · Update terakhir: 5 menit lalu</sub>
"""


def gen_halaman(feature, section):
    """Generate halaman-{feature} content per section."""
    feature_title = feature.replace("-", " ").title()
    section_lower = section.lower()
    # Specific features with extra detail
    specific_features = {
        "customer": ("Customer / Pelanggan", "data pelanggan ISP Anda — identitas, alamat, paket langganan, status aktif/nonaktif, history pembayaran."),
        "karyawan": ("Karyawan / Staff", "data karyawan/staff yang bekerja di perusahaan Anda — termasuk akses mereka ke portal Karyawan."),
        "admin-perusahaan": ("Admin Perusahaan", "akun administrator untuk tenant ini — bisa tambah/hapus admin, atur role dan permission."),
        "admin-saas": ("Admin SaaS", "akun super-admin level SaaS — punya akses ke semua tenant dan konfigurasi global."),
        "perusahaan": ("Perusahaan Tenant", "data perusahaan ISP yang menjadi customer SaaS — kelola profil, logo, konfigurasi."),
        "tagihan": ("Tagihan / Invoice", "tagihan bulanan pelanggan — generate otomatis, edit manual, download PDF, approve pembayaran."),
        "langganan": ("Langganan / Subscription", "data langganan internet pelanggan — paket, alamat instalasi, status aktif/isolir/tutup."),
        "paket": ("Daftar Paket", "katalog paket internet yang ditawarkan — harga, kecepatan, fitur, masa aktif."),
        "riwayat-pembayaran": ("Riwayat Pembayaran", "history pembayaran pelanggan — filter berdasarkan tanggal, status, metode bayar."),
        "insentif": ("Insentif Karyawan", "insentif yang diterima karyawan — dari generate tagihan atau closing pembayaran."),
        "riwayat-insentif": ("Riwayat Insentif", "history insentif karyawan per bulan — detail perhitungan, status cair."),
        "konfigurasi": ("Konfigurasi", "setting konfigurasi global untuk SaaS atau perusahaan."),
        "konfigurasi-perusahaan": ("Konfigurasi Perusahaan", "setting spesifik perusahaan — logo, alamat, NPWP, template invoice, payment gateway."),
        "role-perusahaan": ("Role Perusahaan", "role dan permission untuk tenant ini — atur hak akses admin/karyawan."),
        "admin-role-perusahaan": ("Admin Role Perusahaan", "mapping antara admin dengan role-nya di perusahaan."),
        "role-web-karyawan": ("Role Web Karyawan", "role dan permission khusus untuk karyawan web (admin Perusahaan yang punya akses Karyawan)."),
        "admin-role-web-karyawan": ("Admin Role Web Karyawan", "mapping admin dengan role karyawan web-nya."),
        "role-admin-perusahaan": ("Role Admin Perusahaan", "role yang bisa di-assign ke admin perusahaan tenant."),
        "admin-role-saas": ("Admin Role SaaS", "mapping admin SaaS dengan role-nya di level SaaS."),
        "role-saas": ("Role SaaS", "role untuk SaaS Operator — atur hak akses di SaaS Portal."),
    }
    if feature in specific_features:
        title_desc, desc = specific_features[feature]
    else:
        title_desc = feature_title
        desc = f"Halaman untuk mengelola **{feature_title}** di area {section}."

    return f"""# 📄 [{section}] {title_desc}

> {desc}

---

## 🎯 Untuk Siapa?

Halaman ini untuk **{section}** yang login di area {section}.

---

## 📋 Apa yang Bisa Dilakukan?

- ✅ **Lihat daftar** semua {feature_title.lower()} dengan filter & search
- ➕ **Tambah** {feature_title.lower()} baru via form
- ✏️ **Edit** {feature_title.lower()} yang sudah ada
- 🗑️ **Hapus / Arsip** {feature_title.lower()} (soft-delete untuk audit)
- 📥 **Export** ke Excel/CSV untuk reporting
- 🔍 **Filter & Search** by berbagai kriteria

---

## 🖥️ Tampilan Utama

```
┌──────────────────────────────────────────────────┐
│ [🔍 Search...]  [Filter ▼]  [Status ▼]  [+ Tambah]│
├──────────────────────────────────────────────────┤
│  NAMA / ID    │ KETERANGAN   │ STATUS │ AKSI     │
│  ...          │ ...          │ ...    │ 👁 ✏ 🗑  │
│  ...          │ ...          │ ...    │ 👁 ✏ 🗑  │
└──────────────────────────────────────────────────┘
[‹ Prev]  1  2  3  ...  [Next ›]    [50 / page ▼]
```

### Aksi per Baris

| Icon | Fungsi |
|------|--------|
| 👁 | Lihat detail lengkap |
| ✏ | Edit field |
| 🗑 | Hapus/arsip (dengan konfirmasi) |
| 📥 | Export single row ke PDF/Excel |

---

## ➕ Tambah Baru

1. Klik tombol **"+ Tambah"** di top-right
2. Modal form terbuka — isi field wajib (ditandai `*`)
3. Validasi otomatis — lihat pesan error di field yang salah
4. Klik **Simpan** → data tersimpan + redirect ke list view
5. Toast sukses muncul 3 detik di pojok kanan-atas

---

## 🔍 Filter & Search

- **Search**: ketik nama/ID/keyword — debounce 300ms
- **Filter Status**: combo box dengan status relevan (Aktif/Non-aktif, dll)
- **Filter Tanggal**: range picker berdasarkan tgl dibuat/diubah
- **Sort**: klik header kolom untuk sort asc/desc

---

## 📊 Export

1. Pilih baris (checkbox) atau langsung klik **Export** untuk semua data terfilter
2. Pilih format: `.xlsx` (recommended) / `.csv`
3. File ter-download otomatis

---

## 🔗 Related Pages

- 📋 [**Dashboard {section}**]() — overview
- 📋 [**List {feature_title} Lainnya**]() — navigasi

---

<sub>📝 Versi: 2026-07-12 · Update terakhir: hari ini</sub>
"""


def gen_profil(section):
    """Generate profil-saya content per section."""
    personas = {
        "Operator SaaS": "🟣 Operator SaaS",
        "Admin Perusahaan": "🔵 Admin Perusahaan",
        "Karyawan": "🟢 Karyawan",
        "Pelanggan": "🟡 Pelanggan",
    }
    persona = personas.get(section, section)
    return f"""# 👤 [Profil Saya] — {persona}

> Halaman profil pengguna saat ini. Lihat & edit data diri, ganti password, kelola session aktif.

---

## 🎯 Untuk Siapa?

Halaman ini untuk **setiap user yang login** di {persona}.

---

## 📋 Data Profil

Field yang ditampilkan:

| Field | Keterangan | Bisa Diedit? |
|-------|------------|---------------|
| Foto Profil | Avatar bulat, max 2MB | ✅ |
| Nama Lengkap | Ditampilkan di topbar & komentar | ✅ |
| Email | Untuk login + notifikasi | ❌ (hubungi admin) |
| No HP | Untuk WhatsApp notif | ✅ |
| Role | Posisi/jabatan di perusahaan | ❌ (admin only) |
| Perusahaan | Nama ISP (untuk multi-tenant) | ❌ |
| Tanggal Bergabung | Sejak kapan aktif | ❌ |
| Login Terakhir | Timestamp + IP | ❌ |

---

## ✏️ Edit Profil

1. Klik tombol **✏ Edit** di kanan atas card profil
2. Form edit terbuka — field yang bisa diedit: Nama, No HP, Foto
3. Validasi real-time (mis. format HP, size foto)
4. Klik **Simpan** → toast sukses
5. Perubahan langsung reflected di topbar

---

## 🔒 Ganti Password

1. Klik **🔒 Ganti Password** di card Security
2. Masukkan:
   - Password lama (verifikasi)
   - Password baru (min 8 karakter, harus ada angka + huruf besar)
   - Konfirmasi password baru
3. Klik **Update Password** → logout dari semua device lain (force re-login)

> **Tips**: pakai password manager untuk generate password kuat (misal Bitwarden, 1Password).

---

## 🔐 Session Aktif

Daftar device yang sedang login dengan akun Anda:

| Device | Lokasi | IP | Login Terakhir | Aksi |
|--------|--------|-----|----------------|------|
| Chrome / Windows | Jakarta | 36.74.x.x | 5 menit lalu | [Logout] |
| Safari / iPhone | Surabaya | 110.x.x.x | 2 jam lalu | [Logout] |

Klik **Logout** untuk paksa logout device tersebut. Atau klik **"Logout Semua Device Lain"** untuk security full reset.

---

## 🔔 Preferensi Notifikasi

Toggle on/off untuk:

- 📧 **Email notifikasi** — tagihan baru, pembayaran, reminder jatuh tempo
- 💬 **WhatsApp** — critical alerts saja (jatuh tempo, gagal bayar)
- 🔔 **In-app toast** — semua event real-time
- 📊 **Weekly digest** — ringkasan mingguan via email

---

## 🌐 Preferensi Tampilan

- **Tema:** Light / Dark / System (auto)
- **Bahasa:** Indonesia (default) / English
- **Timezone:** Asia/Jakarta (default) / auto-detect
- **Format tanggal:** DD/MM/YYYY (default) / MM/DD/YYYY
- **Format angka:** 1.234,56 (default) / 1,234.56

---

## 🗑️ Hapus Akun

⚠️ **Tidak bisa self-delete dari UI**. Akun hanya bisa di-deactivate oleh admin perusahaan / SaaS. Hubungi admin Anda jika ingin non-aktif.

---

## 🔗 Related Pages

- 🔒 [Kebijakan Privasi](#)
- 📋 [Syarat & Ketentuan](#)

---

<sub>📝 Versi: 2026-07-12</sub>
"""


def gen_login(portal):
    """Generate login-{portal} content."""
    portal_details = {
        "operator-saas": {
            "emoji": "🟣",
            "name": "Operator SaaS",
            "url": "operator-saas.jmpgroup.id",
            "demo_email": "superadmin@demo.test",
            "demo_pass": "password123",
            "audience": "Tim internal SaaS JMPGroup — mengelola semua tenant",
        },
        "perusahaan": {
            "emoji": "🔵",
            "name": "Admin Perusahaan",
            "url": "operator-perusahaan.jmpgroup.id",
            "demo_email": "admin@netsejahtera.com",
            "demo_pass": "password123",
            "audience": "Admin ISP/tenant — mengelola perusahaan mereka sendiri",
        },
        "karyawan": {
            "emoji": "🟢",
            "name": "Karyawan",
            "url": "karyawan.jmpgroup.id",
            "demo_email": "karyawan1@netsejahtera.com",
            "demo_pass": "password123",
            "audience": "Staf perusahaan ISP — input tagihan, lihat insentif",
        },
        "pelanggan": {
            "emoji": "🟡",
            "name": "Pelanggan",
            "url": "pelanggan.jmpgroup.id",
            "demo_email": "pelanggan001@customer.test",
            "demo_pass": "password123",
            "audience": "Pelanggan akhir ISP — cek tagihan, bayar, lihat paket",
        },
    }
    p = portal_details.get(portal, {
        "emoji": "🔑", "name": portal.title(), "url": f"{portal}.jmpgroup.id",
        "demo_email": "—", "demo_pass": "—", "audience": "User portal ini"
    })
    return f"""# {p['emoji']} Login {p['name']}

> Halaman login untuk portal **{p['name']}**. {p['audience']}.

---

## 🔗 URL Akses

```
https://{p['url']}
```

Bookmark URL di atas untuk akses cepat.

---

## 📝 Form Login

Field yang harus diisi:

| Field | Tipe | Validasi |
|-------|------|----------|
| Email | Email | Format email valid |
| Password | Password | Min 8 karakter |
| Remember Me | Checkbox | Optional |
| Captcha | Image | Jika login gagal 3x |

> Setelah 5x gagal berturut-turut, akun di-lock 15 menit (keamanan).

---

## 🔑 Kredensial Demo

Untuk testing di environment `dev.jmpgroup.id`:

| Field | Value |
|-------|-------|
| **Email** | `{p['demo_email']}` |
| **Password** | `{p['demo_pass']}` |

> **Note**: kredensial ini hanya untuk demo. Production butuh akun asli yang di-setup oleh admin.

---

## 🔄 Lupa Password

1. Klik link **"Lupa Password?"** di bawah form
2. Masukkan email Anda
3. Cek inbox (atau spam) untuk email reset — link berlaku 1 jam
4. Klik link → form password baru
5. Set password baru (min 8 char, kombinasi angka + huruf besar + special char)
6. Submit → auto-login + redirect ke dashboard

> Jika tidak ada email masuk dalam 5 menit, hubungi admin ISP Anda.

---

## 🆕 Belum Punya Akun?

| Persona | Cara Daftar |
|---------|-------------|
| 🟣 Operator SaaS | Tidak self-register. Hanya tim internal JMPGroup. |
| 🔵 Admin Perusahaan | Daftar via SaaS Operator (lihat [Onboarding Perusahaan](#)) |
| 🟢 Karyawan | Dibuat oleh Admin Perusahaan (lihat [Halaman Karyawan](#)) |
| 🟡 Pelanggan | Daftar via halaman registrasi publik (lihat [Halaman Beranda](#)) |

---

## 🛡️ Keamanan

- ✅ **HTTPS only** — semua komunikasi terenkripsi
- ✅ **2FA (opsional)** — aktifkan di [Profil Saya](#) untuk keamanan ekstra
- ✅ **Session timeout** — auto-logout setelah 30 menit idle
- ✅ **Brute force protection** — akun di-lock sementara setelah 5x gagal
- ⚠️ **Jangan share password** — admin tidak pernah minta password via chat/email

---

## 🐛 Troubleshooting

### "Email atau password salah"

Cek:
1. Pastikan Caps Lock off
2. Cek spasi di awal/akhir email
3. Coba forgot password jika lupa

### "Akun di-lock, coba lagi dalam 15 menit"

Tunggu 15 menit, atau hubungi admin untuk reset manual.

### "Session expired"

Auto-logout setelah 30 menit idle. Login ulang dengan kredensial Anda.

### Lupa email yang dipakai daftar?

Hubungi admin ISP Anda — mereka punya akses ke daftar email user di tenant mereka.

---

## 🔗 Related Pages

- 🔒 [Profil Saya]() — setelah login
- 📋 [Halaman Beranda / Landing](#)
- 📞 [Hubungi Support](#)

---

<sub>📝 Versi: 2026-07-12 · Portal: {p['name']}</sub>
"""


def gen_landing(page):
    """Generate landing page content (public)."""
    contents = {
        "beranda": {
            "title": "Beranda",
            "tagline": "Solusi ERP Lengkap untuk ISP RT/RW Net",
            "sections": [
                ("Tentang ERP RT/RW Net", "Aplikasi enterprise resource planning untuk mengelola operasional ISP skala RT/RW Net — mulai dari pendaftaran pelanggan, tagihan, pembayaran, hingga insentif karyawan."),
                ("Mengapa Memilih Kami", [
                    "✅ Multi-tenant — satu aplikasi untuk banyak ISP",
                    "✅ 4 portal sesuai persona (SaaS, Admin, Karyawan, Pelanggan)",
                    "✅ Tagihan otomatis + workflow approval",
                    "✅ Import/Export Excel untuk bulk data",
                    "✅ RBAC granular per tenant",
                    "✅ Responsive — mobile & desktop",
                ]),
                ("Cara Mulai", [
                    "1. **Daftar sebagai ISP** — kontak tim kami untuk onboarding",
                    "2. **Setup tenant** — kami akan setup perusahaan + admin pertama",
                    "3. **Import data** — bulk upload karyawan, customer, paket via Excel",
                    "4. **Generate tagihan** — tagihan bulanan otomatis tiap tanggal 1",
                    "5. **Monitor dashboard** — lihat KPI real-time, approve pembayaran",
                ]),
                ("Testimonial", [
                    "> \"Sejak pakai ERP RT/RW Net, proses tagihan kami turun dari 2 minggu jadi 2 hari. Hemat 5 staff admin!\"",
                    "> — Ahmad S., Admin PT Net Sejahtera",
                ]),
            ],
        },
        "tentang-kami": {
            "title": "Tentang Kami",
            "tagline": "JMPGroup — Software house untuk ISP Indonesia",
            "sections": [
                ("Profil Perusahaan", "**JMPGroup** adalah software house yang fokus pada solusi ERP untuk industri ISP (Internet Service Provider) Indonesia. Didirikan 2024, kami sudah melayani 12+ ISP di Jabodetabek, Surabaya, dan Bandung."),
                ("Visi", "Menjadi platform ERP #1 untuk ISP skala RT/RW Net di Indonesia."),
                ("Misi", [
                    "Menyederhanakan operasional ISP melalui otomasi",
                    "Membantu ISP kecil tumbuh dengan tools enterprise-grade",
                    "Mendukung digitalisasi industri telekomunikasi lokal",
                ]),
                ("Tim", "7 engineers + 2 product managers + 1 designer, semua full-time dan berbasis di Indonesia."),
                ("Kontak", [
                    "📧 Email: hello@jmpgroup.id",
                    "💬 WhatsApp: +62 812-3456-7890",
                    "🌐 Web: jmpgroup.id",
                ]),
            ],
        },
        "hubungi-kami": {
            "title": "Hubungi Kami",
            "tagline": "Kami siap membantu — pilih channel favorit Anda",
            "sections": [
                ("Channel Support", [
                    ("📧 Email", "support@jmpgroup.id (response < 24 jam kerja)"),
                    ("💬 WhatsApp", "+62 812-3456-7890 (jam 09:00-17:00 WIB)"),
                    ("📞 Telepon", "+62 21-555-1234 (jam kerja)"),
                    ("📍 Kantor", "Jl. Sudirman No. 123, Jakarta Selatan (by appointment)"),
                ]),
                ("Untuk ISP Baru (Onboarding)", [
                    "📧 **sales@jmpgroup.id** — diskusi paket harga & demo",
                    "💬 **WhatsApp +62 812-9999-0000** — langsung ke tim sales",
                ]),
                ("Untuk Customer Aktif (Support)", [
                    "📧 **support@jmpgroup.id** — bug report, pertanyaan fitur",
                    "💬 **In-app chat** — klik icon chat di pojok kanan bawah dashboard",
                    "📖 **[Knowledge Base](#)** — cari solusi sendiri dulu sebelum hubungi",
                ]),
                ("Untuk Pelanggan ISP", [
                    "Hubungi admin ISP Anda langsung (lihat invoice Anda untuk kontak)",
                    "Atau cek [FAQ Pelanggan](#) untuk pertanyaan umum",
                ]),
                ("Response Time SLA", [
                    "🟢 **Critical** (system down): < 1 jam kerja",
                    "🟡 **High** (major bug): < 4 jam kerja",
                    "🟢 **Normal** (pertanyaan): < 24 jam kerja",
                    "📋 **Feature request**: best effort, masuk roadmap review bulanan",
                ]),
            ],
        },
        "kebijakan-privasi": {
            "title": "Kebijakan Privasi",
            "tagline": "Komitmen kami melindungi data Anda",
            "sections": [
                ("Pendahuluan", "Kebijakan Privasi ini menjelaskan bagaimana JMPGroup mengumpulkan, menggunakan, dan melindungi data pribadi Anda saat menggunakan ERP RT/RW Net."),
                ("Data yang Kami Kumpulkan", [
                    "📋 **Data Akun**: nama, email, no HP (saat registrasi)",
                    "📊 **Data Operasional**: tagihan, pembayaran, paket (untuk operasional ISP)",
                    "🔐 **Data Teknis**: IP address, device, log aktivitas (untuk keamanan)",
                    "🍪 **Cookies**: session, preferensi tema/bahasa",
                ]),
                ("Penggunaan Data", [
                    "✅ Menyediakan layanan ERP",
                    "✅ Notifikasi tagihan & pembayaran",
                    "✅ Improve produk berdasarkan aggregated usage",
                    "❌ JUAL data ke pihak ketiga",
                    "❌ Spam marketing tanpa consent",
                ]),
                ("Penyimpanan & Keamanan", [
                    "🔒 Data di-encrypt at-rest (AES-256) dan in-transit (TLS 1.3)",
                    "🏢 Hosting di data center tier-3+ Indonesia (region Jakarta)",
                    "👥 Akses data dibatasi via RBAC + audit log",
                    "🔄 Backup harian, retention 30 hari",
                ]),
                ("Hak Anda", [
                    "📥 Akses — download semua data Anda",
                    "✏️ Koreksi — perbaiki data yang salah",
                    "🗑️ Hapus — request hapus akun + data (kecuali yg diwajibkan oleh hukum)",
                    "📤 Portabilitas — export data ke format standar",
                ]),
                ("Cookies", [
                    "**Essential** — session, auth (wajib, tidak bisa dimatikan)",
                    "**Functional** — preferensi tema, bahasa (optional)",
                    "**Analytics** — anonymous usage stats (opt-in)",
                ]),
                ("Perubahan Kebijakan", "Kami akan notifikasi via email minimal 30 hari sebelum perubahan material berlaku. Continued use = consent."),
                ("Kontak Privasi", "📧 privacy@jmpgroup.id — untuk pertanyaan, request akses/hapus data, atau komplain."),
            ],
        },
        "syarat-dan-ketentuan": {
            "title": "Syarat & Ketentuan",
            "tagline": "Ketentuan penggunaan layanan ERP RT/RW Net",
            "sections": [
                ("Pendahuluan", "Dengan menggunakan ERP RT/RW Net, Anda setuju dengan Syarat & Ketentuan ini. Harap baca dengan seksama."),
                ("Lisensi Penggunaan", [
                    "✅ Lisensi non-exclusive, non-transferable untuk 1 organisasi",
                    "✅ Akses diberikan per user sesuai paket langganan",
                    "❌ Tidak boleh di-resell, sub-license, atau share ke pihak lain",
                    "❌ Tidak boleh reverse-engineer atau extract source code",
                ]),
                ("Akun & Keamanan", [
                    "🔐 Anda bertanggung jawab menjaga kredensial login",
                    "🔐 Aktifkan 2FA untuk keamanan ekstra",
                    "🔐 Jangan share akun antar-personil — buat akun terpisah",
                    "⚠️ Aktivitas mencurigakan → lapor ke security@jmpgroup.id",
                ]),
                ("Pembayaran & Langganan", [
                    "💰 Berlangganan bulanan/tahunan via invoice",
                    "💰 Akses ditangguhkan jika pembayaran terlambat > 14 hari",
                    "💰 Refund prorata untuk pembatalan mid-cycle",
                    "💰 Harga dapat berubah dengan notifikasi 30 hari sebelumnya",
                ]),
                ("Uptime & Maintenance", [
                    "🟢 Target uptime: 99.5% per bulan",
                    "🔧 Maintenance terjadwal: Minggu 02:00-04:00 WIB (advance notice)",
                    "⚠️ Untuk incident: lihat [Status Page](#)",
                ]),
                ("Batasan Tanggung Jawab", [
                    "❌ Kami tidak bertanggung jawab atas kerugian tidak langsung (lost profit, data loss karena Anda tidak backup)",
                    "❌ Kami tidak menjamin 100% uptime (target 99.5%)",
                    "❌ Kami tidak bertanggung jawab atas kesalahan user input",
                ]),
                ("Pemutusan Layanan", [
                    "🚪 Anda bisa terminate kapan saja via dashboard admin",
                    "🚪 Kami bisa terminate jika: pelanggaran ToS, payment > 30 hari overdue, aktivitas ilegal",
                    "🚪 Data Anda: 30 hari grace period setelah termination, lalu di-purge",
                ]),
                ("Hukum yang Berlaku", "Syarat & Ketentuan ini diatur oleh hukum Republik Indonesia. Disputes resolved di pengadilan Jakarta Selatan."),
                ("Perubahan", "Kami akan notify via email minimal 30 hari sebelum perubahan material. Continued use = consent."),
            ],
        },
    }
    c = contents.get(page, {"title": page.title(), "tagline": "", "sections": []})
    sections_md = ""
    for sec_title, sec_content in c["sections"]:
        sections_md += f"\n## {sec_title}\n\n"
        if isinstance(sec_content, str):
            sections_md += sec_content + "\n"
        elif isinstance(sec_content, list):
            for item in sec_content:
                sections_md += f"- {item}\n"
        sections_md += "\n"
    return f"""# {c['title']}

> {c['tagline']}

---

{sections_md}
---

<sub>📝 Versi: 2026-07-12 · Update terakhir: 2026-07-01 · Hubungi: legal@jmpgroup.id untuk pertanyaan</sub>
"""


def gen_daftar_paket():
    """Generate daftar-paket content."""
    return """# 📦 [Admin Perusahaan] Daftar Paket

> Katalog paket internet yang ditawarkan perusahaan Anda ke pelanggan. Atur harga, kecepatan, fitur, dan masa aktif.

---

## 🎯 Untuk Siapa?

Halaman ini untuk **Admin Perusahaan** yang mengelola katalog paket ISP.

---

## 📋 Apa itu Paket?

**Paket** = produk layanan internet yang dijual ke pelanggan. Setiap paket punya:
- 🏷️ Nama (mis. "Paket 10Mbps", "Paket Unlimited")
- 💰 Harga bulanan (Rp)
- 🚀 Kecepatan download / upload (Mbps)
- 📦 Kuota (FUP) atau Unlimited
- 🎁 Fitur tambahan (mis. gratis instalasi, IP static)
- ⏱️ Masa aktif (default: 1 bulan, auto-renew)

---

## 🖥️ Daftar Paket

Tabel utama menampilkan:

| Nama Paket | Harga | Kecepatan | Kuota | Status | Pelanggan |
|------------|-------|-----------|-------|--------|-----------|
| Paket 5Mbps | Rp 100.000 | 5/5 Mbps | Unlimited | 🟢 Aktif | 45 |
| Paket 10Mbps | Rp 150.000 | 10/10 Mbps | Unlimited | 🟢 Aktif | 62 |
| Paket 20Mbps | Rp 250.000 | 20/20 Mbps | Unlimited | 🟢 Aktif | 28 |
| Paket 50Mbps | Rp 500.000 | 50/50 Mbps | Unlimited | 🟡 Promosi | 8 |
| Paket Bisnis | Rp 1.000.000 | 100/100 Mbps | Unlimited + IP Static | 🟢 Aktif | 2 |

> **Total**: 5 paket aktif, 145 pelanggan aktif across all paket.

---

## ➕ Tambah Paket Baru

1. Klik **"+ Tambah Paket"**
2. Isi form:
   - **Nama Paket** (wajib) — unik, contoh: "Paket 100Mbps"
   - **Harga Bulanan** (wajib) — angka saja, contoh: 750000
   - **Kecepatan** (wajib) — download / upload dalam Mbps
   - **Kuota** (optional) — angka GB atau kosongkan untuk "Unlimited"
   - **Fitur Tambahan** (optional) — array: IP Static, Free Instalasi, dll
   - **Masa Aktif** (default 1 bulan)
   - **Status**: Aktif / Non-aktif / Promosi
3. Klik **Simpan** → paket baru muncul di list

---

## ✏️ Edit Paket

1. Klik icon ✏ di baris paket
2. Edit field (semua bisa diedit, kecuali `jumlah pelanggan` yang auto-count)
3. **Penting**: jika edit harga, tagihan existing TIDAK berubah otomatis — hanya untuk tagihan baru
4. Klik **Simpan** → toast sukses + list refresh

---

## 🗑️ Hapus / Non-aktifkan

- **Non-aktifkan** (recommended): paket di-set inactive, pelanggan existing tetap pakai sampai berhenti sendiri, pelanggan baru tidak bisa pilih paket ini
- **Hapus** (admin only): HANYA jika 0 pelanggan aktif. Kalau ada pelanggan, harus non-aktifkan dulu.

---

## 📊 Statistik Paket

- **Paket terlaris**: 10Mbps (62 pelanggan, 43% market share)
- **Paket paling menguntungkan**: Bisnis (Rp 2jt/bulan per pelanggan)
- **Conversion rate**: 78% calon pelanggan pilih paket 10-20Mbps

---

## 💡 Tips

1. ✅ **Update harga** secara berkala (tiap 6 bulan) untuk adjust ke biaya operasional
2. ✅ **Promosi** = paket dengan harga diskon temporary — bagus untuk akuisisi
3. ✅ **Bundle** fitur (IP Static, Instalasi Gratis) bisa menarik segmen bisnis
4. ❌ **Jangan hapus** paket yang masih punya pelanggan aktif — non-aktifkan saja

---

## 🔗 Related Pages

- 📋 [Halaman Langganan](#) — paket yang dipilih tiap pelanggan
- 👤 [Halaman Customer](#) — manage langganan per customer
- 📋 [Halaman Konfigurasi Perusahaan](#) — setting global

---

<sub>📝 Versi: 2026-07-12</sub>
"""


def gen_perusahaan_saya():
    """Generate perusahaan-saya content."""
    return """# 🏢 [Admin Perusahaan] Perusahaan Saya

> Detail perusahaan ISP (tenant) Anda — profil, konfigurasi, subscription, dan metadata bisnis.

---

## 🎯 Untuk Siapa?

Halaman ini untuk **Admin Perusahaan** yang ingin lihat/edit info perusahaan ISP-nya.

---

## 📋 Profil Perusahaan

| Field | Value (contoh) | Bisa Diedit? |
|-------|---------------|---------------|
| Nama Perusahaan | PT Net Sejahtera Abadi | ❌ (hubungi SaaS) |
| NPWP | 01.234.567.8-901.000 | ✅ |
| NIB | 9123456789012 | ✅ |
| Alamat | Jl. Merdeka No. 123, Jakarta | ✅ |
| Email | info@netsejahtera.com | ✅ |
| No Telepon | +62 21-555-1234 | ✅ |
| Website | https://netsejahtera.com | ✅ |
| Logo | [logo-net-sejahtera.png] | ✅ (upload di Konfigurasi) |
| Tanggal Bergabung | 2025-01-15 | ❌ |

---

## 💼 Subscription & Billing

| Field | Value |
|-------|-------|
| **Paket SaaS** | Professional (50 admin + 500 karyawan + unlimited pelanggan) |
| **Harga Bulanan** | Rp 5.000.000 |
| **Tagihan Berikutnya** | 2025-08-01 |
| **Metode Bayar** | Bank Transfer BCA (virtual account) |
| **Status** | 🟢 Aktif |

---

## 📊 Statistik Tenant

| KPI | Value |
|-----|-------|
| Total Admin | 3 user |
| Total Karyawan | 8 user |
| Total Pelanggan Aktif | 145 customer |
| Tagihan Bulan Ini | 145 invoice, Rp 18.750.000 total |
| Pembayaran Tepat Waktu | 92% |

---

## 🔧 Quick Links

- ⚙️ [Konfigurasi Perusahaan](#) — logo, template invoice, payment gateway
- 👤 [Halaman Admin Perusahaan](#) — manage admin & permission
- 📋 [Halaman Konfigurasi](#) — setting global tenant

---

## 🚨 Tutup Tenant / Berhenti Berlangganan

⚠️ **Tidak bisa dari UI**. Untuk terminate subscription:
1. Email **sales@jmpgroup.id** minimal 30 hari sebelum tanggal terminasi
2. Tim kami akan:
   - Disable akses di akhir periode
   - Backup data Anda (tersedia 30 hari)
   - Purge data setelah 30 hari grace period

---

<sub>📝 Versi: 2026-07-12</sub>
"""


def gen_pendahuluan_cross_cutting():
    """Generate Pendahuluan cross-cutting topics page."""
    return """# 📚 Topik Lintas Portal (Cross-cutting)

> Halaman pengantar untuk topik yang berlaku di semua portal — perlu dipahami sebelum menggunakan aplikasi secara efektif.

---

## 🔐 RBAC & Permission

Sistem **Role-Based Access Control** menentukan apa yang bisa dilakukan tiap user.

| Role | Contoh Akses |
|------|-------------|
| `super_admin` (SaaS) | Full akses ke semua tenant |
| `admin_perusahaan` | Kelola karyawan, customer, paket di tenant sendiri |
| `karyawan` | Input tagihan, lihat insentif |
| `pelanggan` | Lihat tagihan sendiri, bayar |

Permission di-set per-role dan bisa di-customize via menu **Role Management**.

---

## 🏢 Multi-Tenant

Satu aplikasi melayani banyak perusahaan ISP (tenant). Setiap tenant punya data terisolasi via kolom `company_id`. User hanya lihat data tenant mereka sendiri (kecuali super_admin SaaS).

Lihat detail: [Multi-Tenant](#)

---

## 🔑 Multi-Login (4 Guard)

Setiap portal punya **session guard terpisah** di Laravel:
- `admin_saas` — Operator SaaS
- `admin_perusahaan` — Admin Perusahaan
- `karyawan` — Karyawan
- `pelanggan` — Pelanggan

Artinya: login di satu portal tidak otomatis login di portal lain. Pindah portal = login ulang.

---

## 📊 Import/Export Excel

Untuk bulk input data, pakai template Excel:
1. Download template dari menu **Import**
2. Isi data sesuai format
3. Upload → sistem validasi → preview → confirm

Export: klik **Export** di setiap halaman list.

Lihat detail: [Import/Export Excel](#)

---

## ✅ Workflow Persetujuan

Pembayaran & insentif dari karyawan perlu approval sebelum dihitung resmi.

| Status | Arti |
|--------|------|
| `pending` | Submitted, menunggu review |
| `approved` | Disetujui admin, dihitung |
| `rejected` | Ditolak (lihat alasan) |
| `revised` | Diubah nominalnya oleh admin |

Lihat detail: [Workflow Persetujuan](#)

---

## 🌗 Tema (Light/Dark/System)

Pilih tampilan favorit di **Profil Saya → Preferensi**:
- **Light** — putih, siang hari
- **Dark** — hitam, malam hari (eye-friendly)
- **System** — ikut OS preference (auto-switch)

---

## 🔔 Notifikasi (Toast)

Real-time feedback untuk aksi user:
- ✅ Hijau — sukses
- ❌ Merah — error
- ⚠️ Kuning — warning
- ℹ️ Biru — info

Toast muncul 3 detik di pojok kanan-atas, auto-dismiss. Klik X untuk close manual.

---

## 📚 Index Topik

- [Multi-Tenant](#) — isolasi data
- [RBAC & Permission](#) — hak akses
- [Kredensial Demo](#) — akun testing
- [Kontak Support](#) — jika butuh bantuan
- [Import/Export Excel](#) — bulk data
- [Workflow Persetujuan](#) — approval flow
- [Developer Guide](#) — untuk developer

---

<sub>📝 Versi: 2026-07-12 · Topik berlaku di semua portal ERP RT/RW Net</sub>
"""


def gen_dg_master():
    """Generate Developer Guide Master."""
    return """# 🛠 Developer Guide — Master

> Dokumentasi teknis untuk developer yang akan build, modify, atau maintain ERP RT/RW Net.

---

## 🎯 Untuk Siapa?

Halaman ini untuk **developer** yang bekerja dengan codebase ERP RT/RW Net.

---

## 📋 Tech Stack

- **Backend**: Laravel 13 + PHP 8.3
- **Frontend**: Vue 3.5 + InertiaJS v2 + Vite 8
- **UI**: Flowbite 4 + FontAwesome 7 + TailwindCSS 4
- **Database**: PostgreSQL / MySQL (UUID v7 PK)
- **Storage**: MinIO (S3-compatible, private)
- **Realtime**: Laravel Reverb (WebSocket)
- **PDF**: DomPDF 3 (no flexbox — pakai `<table>`)
- **Spreadsheet**: PhpSpreadsheet 5
- **Doc**: PhpWord 1
- **Image**: Intervention/Image 4

---

## 🏗️ Arsitektur

```
┌─────────────────────────────────────────────────┐
│              Browser (4 portals)                 │
│  Operator SaaS / Admin Perusahaan / Karyawan /   │
│  Pelanggan                                       │
└─────────────┬───────────────────────────────────┘
              │ HTTPS / InertiaJS (SPA-ish)
              ▼
┌─────────────────────────────────────────────────┐
│          Laravel App (PHP 8.3)                   │
│  ├─ Middleware (auth, RBAC, multi-tenant)        │
│  ├─ Controllers (per resource)                   │
│  ├─ Eloquent Models (with HasCompanyScope)       │
│  ├─ Services (FileUpload, PdfGen)                │
│  └─ Jobs (BulkOperations, EmailQueue)            │
└─────────────┬───────────────────────────────────┘
              │
   ┌──────────┼──────────┬──────────┐
   ▼          ▼          ▼          ▼
┌──────┐  ┌──────┐  ┌──────┐  ┌──────────┐
│ PG/  │  │MinIO │  │Reverb│  │ MailHog  │
│ MySQL│  │ S3   │  │ WS   │  │ (dev)    │
└──────┘  └──────┘  └──────┘  └──────────┘
```

---

## 📚 Sections (akan diisi di sub-halaman)

| Section | Status | Sub-pages |
|---------|--------|-----------|
| 🏁 Pendahuluan | ✅ Done | Setup project, requirement |
| 🛠 Tech Stack Detail | ✅ Done | (di atas) |
| 🏗️ Arsitektur | ✅ Done | (di atas) |
| 🗄️ Database | ⏳ TODO | Schema, migrations, UUID v7 |
| 🔐 RBAC Implementation | ⏳ TODO | Permission, Gate, Policy |
| 🔑 Multi-Login Guards | ⏳ TODO | 4 guard setup |
| 💳 Payment Integration | ⏳ TODO | Payment gateway |
| 📁 File Upload | ⏳ TODO | MinIO + Image processing |
| 📄 PDF Generation | ⏳ TODO | DomPDF template |
| 📊 Excel Import/Export | ⏳ TODO | PhpSpreadsheet |
| 🧪 Testing | ⏳ TODO | PHPUnit + Playwright |
| 🚀 Deployment | ⏳ TODO | CI/CD, env |
| 💻 Coding Standards | ⏳ TODO | Style, lint |
| ⚠️ Error Handling | ⏳ TODO | Logging, monitoring |

---

## 🔗 Pranala Penting

- 📂 **Source code**: GitHub `mascahyo1/erp-rt-rw-net`
- 🎫 **Issue tracker**: [Plane project ERPRT](https://cahyosoft.plane.so/projects/ERPRT)
- 🌐 **Production**: [jmpgroup.id](https://jmpgroup.id)
- 🧪 **Staging**: [dev.jmpgroup.id](https://dev.jmpgroup.id)
- 📖 **User Guide**: [Dokumentasi ERP RT RW Net](#)

---

## 📋 Standar Coding

- ✅ Gunakan UUID v7 untuk primary key (`HasUuidV7` trait)
- ✅ Soft delete (`HasSoftDelete` trait)
- ✅ Blameable (`HasBlameable` trait — auto-fill created_by, updated_by)
- ✅ Multi-tenant via `company_id` (`HasCompanyScope` trait)
- ✅ Validasi semua input
- ✅ Pakai Eloquent, hindari raw query (kecuali performance urgent)
- ✅ Test pakai PHPUnit (unit) + Playwright (E2E)

---

<sub>📝 Versi: 2026-07-12 · Master tracker · Plane ERPRT-8</sub>
"""


# Generate content for each page
results = []
for page in pages:
    title = page["title"]
    section = page["section"]
    notion_id = page["notion_id"]
    clean = clean_title(title)
    plane_id = PLANE_IDS.get((clean, section))

    # Choose template
    if clean.lower() == "dashboard":
        content = gen_dashboard(section)
    elif clean.lower().startswith("halaman-"):
        feature = clean[8:].lower()  # strip "halaman-"
        content = gen_halaman(feature, section)
    elif clean.lower() == "profil-saya":
        content = gen_profil(section)
    elif clean.lower().startswith("login-"):
        portal = clean[6:].lower()  # strip "login-"
        content = gen_login(portal)
    elif clean.lower() in ["beranda", "tentang-kami", "hubungi-kami", "kebijakan-privasi", "syarat-dan-ketentuan"]:
        content = gen_landing(clean.lower())
    elif clean.lower() == "daftar-paket":
        content = gen_daftar_paket()
    elif clean.lower() == "perusahaan-saya":
        content = gen_perusahaan_saya()
    elif clean.lower() == "pendahuluan & topik lintas portal":
        content = gen_pendahuluan_cross_cutting()
    elif clean.lower() == "developer guide (master)":
        content = gen_dg_master()
    else:
        content = gen_halaman(clean.lower().replace(" ", "-"), section)

    results.append({
        "notion_id": notion_id,
        "plane_id": plane_id,
        "section": section,
        "title": title,
        "clean_title": clean,
        "content": content,
    })

# Save
output_path = "c:\\laragon\\www\\erp-rt-rw-net\\.tmp\\pages_with_content.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(results, f, ensure_ascii=False, indent=2)

# Print summary
done_already = {"6051034d-c4ed-4112-839f-5179a7e5ee63", "745fd395-a19b-47b1-b87c-50349f853625"}
to_write = [r for r in results if r["plane_id"] not in done_already]
print(f"Total pages: {len(results)}")
print(f"Already done: {len(results) - len(to_write)} (skip)")
print(f"To write: {len(to_write)}")
print()
for r in to_write[:5]:
    print(f"  [{r['section']}] {r['title']}")
    print(f"    Notion: {r['notion_id']}")
    print(f"    Plane:  {r['plane_id']}")
    print(f"    Content length: {len(r['content'])} chars")