---
description: Export data ke Excel dengan format rapi dan profesional. Use when the user needs export Excel, download spreadsheet, generate laporan, atau "buat export".
---

## Aturan Export Excel (PhpSpreadsheet)

### 1. Cegah Notasi Ilmiah
Kolom NIK, No KK, No Telp, dan data numerik panjang WAJIB diformat sebagai string/text.

```php
// Jangan begini — NIK jadi 3.20504E+16
$sheet->setCellValueExplicit('C2', $data->nik, DataType::TYPE_STRING);

// Atau set format kolom dulu
$sheet->getStyle('C:C')
    ->getNumberFormat()
    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
```

**Kolom yang wajib TYPE_STRING:**
- NIK, No KK, No Telepon, NPWP, No Rekening
- Kode pos, kode unik apa pun
- Angka dengan leading zero

### 2. Merge & Center
```php
// Judul utama merge full width
$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', 'Laporan Data Penduduk');
$sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

// Sub-judul merge full width
$sheet->mergeCells('A2:H2');
$sheet->setCellValue('A2', 'Periode: Januari 2025');
$sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
```

### 3. Bold untuk Header
```php
$sheet->getStyle('A3:H3')->getFont()->setBold(true);
```

### 4. Border untuk Data
```php
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
];
$sheet->getStyle('A3:H' . ($lastRow))->applyFromArray($styleArray);
```

### 5. Informasi Wajib di Sheet

Setiap sheet HARUS punya header informasi:
```
Baris 1: Judul Laporan (merge, center, bold, font 14)
Baris 2: Tanggal Generate + Periode + Filter yang digunakan
Baris 3: Kosong (spasi)
Baris 4: Header kolom data (bold, background light gray)
Baris 5+: Data
```

### 6. Auto-size Kolom
```php
foreach (range('A', $sheet->getHighestColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
```

### 7. Orientasi Landscape untuk Banyak Kolom
```php
$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
```

### 8. Nama File & Download
```php
$filename = 'Laporan_' . $jenis . '_' . now()->format('Ymd_His') . '.xlsx';
return response()->download($path, $filename, [
    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
])->deleteFileAfterSend(true);
```
