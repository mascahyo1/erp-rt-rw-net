<script setup>
import { computed } from 'vue';

const props = defineProps({
  errors: { type: Object, default: () => ({}) },
  title: { type: String, default: '' },
  testId: { type: String, default: 'form-error-summary' },
  labels: { type: Object, default: () => ({}) },
});

const FALLBACK_LABELS = {
  kode: 'Kode',
  nama: 'Nama',
  name: 'Nama',
  email: 'Email',
  password: 'Password',
  kode_negara: 'Kode Negara',
  no_telp: 'Telepon',
  phone_number: 'Telepon',
  no_nik: 'NIK',
  no_kk: 'No. KK',
  photo_ktp: 'Foto KTP',
  photo_kk: 'Foto KK',
  photo_profile: 'Foto Profil',
  alamat: 'Alamat',
  status: 'Status',
  file: 'File',
  role_id: 'Role',
  role: 'Role',
  admin_id: 'Admin',
  karyawan_id: 'Karyawan',
  customer_id: 'Pelanggan',
  company_id: 'Perusahaan',
  paket_id: 'Paket',
  paket: 'Paket',
  tanggal: 'Tanggal',
  tanggal_mulai: 'Tanggal Mulai',
  tanggal_akhir: 'Tanggal Akhir',
  tanggal_bayar: 'Tanggal Bayar',
  tanggal_verifikasi: 'Tanggal Verifikasi',
  amount_paid: 'Nominal Bayar',
  nominal: 'Nominal',
  jumlah: 'Jumlah',
  harga: 'Harga',
  deskripsi: 'Deskripsi',
  description: 'Deskripsi',
  title: 'Judul',
  judul: 'Judul',
  pesan: 'Pesan',
  isi: 'Isi',
  is_active: 'Status',
  display_order: 'Urutan',
  photo: 'Foto',
  image: 'Foto',
  periode: 'Periode',
  bulan: 'Bulan',
  tahun: 'Tahun',
  keterangan: 'Keterangan',
  catatan: 'Catatan',
  lokasi: 'Lokasi',
  latitude: 'Latitude',
  longitude: 'Longitude',
  koordinat: 'Koordinat',
  tarif: 'Tarif',
  tipe: 'Tipe',
  jenis: 'Jenis',
  gender: 'Jenis Kelamin',
  kode_pos: 'Kode Pos',
  rt: 'RT',
  rw: 'RW',
  kelurahan: 'Kelurahan',
  kecamatan: 'Kecamatan',
  kota: 'Kota',
  provinsi: 'Provinsi',
  search: 'Pencarian',
  start_date: 'Tanggal Mulai',
  end_date: 'Tanggal Akhir',
  cf_turnstile_response: 'Verifikasi Captcha',
};

function humanize(key) {
  if (props.labels[key]) return props.labels[key];
  if (FALLBACK_LABELS[key]) return FALLBACK_LABELS[key];
  return key
    .replace(/[_\-.]/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

const errorList = computed(() =>
  Object.entries(props.errors || {})
    .filter(([, v]) => v)
    .map(([field, message]) => ({
      field,
      label: humanize(field),
      message: Array.isArray(message) ? message[0] : message,
    }))
);

const summaryTitle = computed(
  () => props.title || `Perbaiki ${errorList.value.length} isian berikut:`
);
</script>

<template>
  <div
    v-if="errorList.length > 0"
    :data-testid="testId"
    class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3"
  >
    <div class="flex items-start gap-2">
      <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-red-700 dark:text-red-300">{{ summaryTitle }}</p>
        <ul class="mt-1.5 space-y-1 text-xs text-red-600 dark:text-red-400 list-disc list-inside">
          <li v-for="err in errorList" :key="err.field">
            <strong>{{ err.label }}</strong>: {{ err.message }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
