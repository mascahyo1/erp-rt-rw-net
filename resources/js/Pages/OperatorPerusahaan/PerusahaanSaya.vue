<script setup>
import { ref } from 'vue';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: OperatorPerusahaanLayout });

const perusahaan = ref({
  nama: 'PT Net Sejahtera',
  email: 'info@netsejahtera.id',
  alamat: 'Jl. Merdeka No. 10, Jakarta Selatan, DKI Jakarta 12950',
  kode_negara: '+62',
  no_telp: '81234567890',
  website: 'https://netsejahtera.id',
  npwp: '12.345.678.9-012.000',
  status: 'Aktif',
  created_at: '2025-01-10',
  logo: null,
});

const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];
const editMode = ref(false);
const form = ref({ ...perusahaan.value });
const formErrors = ref({});
const saved = ref(false);

function enterEdit() { form.value = { ...perusahaan.value }; formErrors.value = {}; saved.value = false; editMode.value = true; }
function cancelEdit() { editMode.value = false; }
function validateForm() { const e = {}; if (!form.value.nama.trim()) e.nama = 'Nama wajib diisi'; if (!form.value.email.trim()) e.email = 'Email wajib diisi'; if (!form.value.alamat.trim()) e.alamat = 'Alamat wajib diisi'; formErrors.value = e; return Object.keys(e).length === 0; }
function saveEdit() { if (!validateForm()) return; perusahaan.value = { ...form.value }; editMode.value = false; saved.value = true; setTimeout(() => saved.value = false, 3000); }

function formatTelepon(p) { return p.kode_negara + ' ' + p.no_telp; }
function statusBadge(s) { return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
</script>

<template>
  <div>
    <Head title="Perusahaan Saya | Perusahaan" />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Perusahaan Saya</span>
      </nav>

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Perusahaan Saya</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola profil dan informasi perusahaan Anda.</p></div>
        <button v-if="!editMode" @click="enterEdit" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-edit mr-1.5"></i> Edit Perusahaan</button>
      </div>

      <div v-if="saved" class="px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-700 dark:text-emerald-400"><i class="fas fa-check-circle mr-1.5"></i> Data perusahaan berhasil disimpan.</div>

      <!-- View Mode -->
      <div v-if="!editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 sm:p-8">
          <div class="flex flex-col sm:flex-row items-start gap-6 pb-6 border-b border-gray-100 dark:border-gray-700">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-3xl font-bold shrink-0">{{ perusahaan.nama.charAt(0) }}</div>
            <div>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ perusahaan.nama }}</h3>
              <span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(perusahaan.status)]">{{ perusahaan.status }}</span>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ perusahaan.email }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Telepon</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ formatTelepon(perusahaan) }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Website</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ perusahaan.website || '—' }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">NPWP</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ perusahaan.npwp || '—' }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Daftar</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ perusahaan.created_at }}</p></div>
          </div>
          <div class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alamat</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ perusahaan.alamat }}</p></div>
        </div>
      </div>

      <!-- Edit Mode -->
      <div v-if="editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8">
        <form class="space-y-4" @submit.prevent="saveEdit">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Perusahaan <span class="text-red-500">*</span></label><input v-model="form.nama" type="text" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.nama ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><p v-if="formErrors.nama" class="text-red-500 text-xs mt-1">{{ formErrors.nama }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label><input v-model="form.email" type="email" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.email ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" /><p v-if="formErrors.email" class="text-red-500 text-xs mt-1">{{ formErrors.email }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label><div class="flex gap-2"><select v-model="form.kode_negara" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option v-for="k in kodeNegaraList" :key="k" :value="k">{{ k }}</option></select><input v-model="form.no_telp" type="text" placeholder="81234567890" class="flex-1 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" /></div></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Website</label><input v-model="form.website" type="text" placeholder="https://" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" /></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">NPWP</label><input v-model="form.npwp" type="text" placeholder="12.345.678.9-012.000" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" /></div>
          </div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat <span class="text-red-500">*</span></label><textarea v-model="form.alamat" rows="2" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none', formErrors.alamat ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']"></textarea><p v-if="formErrors.alamat" class="text-red-500 text-xs mt-1">{{ formErrors.alamat }}</p></div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="cancelEdit" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
            <button type="submit" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-save mr-1.5"></i> Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
