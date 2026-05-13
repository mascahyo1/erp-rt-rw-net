<script setup>
import { ref, computed } from 'vue';
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineOptions({ layout: KaryawanLayout });

const page = usePage();
const user = computed(() => page.props.auth?.user);

const karyawan = computed(() => ({
  nama: user.value?.name ?? 'Karyawan',
  email: user.value?.email ?? '',
  alamat: user.value?.address ?? '',
  kode_negara: user.value?.phone_country_code ?? '+62',
  no_telp: user.value?.phone_number ?? '',
  jabatan: user.value?.position ?? 'Karyawan',
  perusahaan: user.value?.company?.name ?? 'Perusahaan',
}));

const editMode = ref(false);
const form = ref({ ...karyawan.value });
const formErrors = ref({});
const saved = ref(false);
const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];

function enterEdit() { form.value = { ...karyawan.value }; formErrors.value = {}; saved.value = false; editMode.value = true; }
function cancelEdit() { editMode.value = false; }
function validateForm() { const e = {}; if (!form.value.nama.trim()) e.nama = 'Nama wajib diisi'; formErrors.value = e; return Object.keys(e).length === 0; }
function saveEdit() { if (!validateForm()) return; editMode.value = false; saved.value = true; setTimeout(() => saved.value = false, 3000); }
</script>
<template>
  <div><Head title="Profil Saya | Karyawan" /><slot name="header">Profil Saya</slot>
    <div class="space-y-6">
      <div class="flex items-center justify-between"><p class="text-sm text-gray-500 dark:text-gray-400">Informasi akun karyawan.</p><button v-if="!editMode" @click="enterEdit" class="inline-flex items-center px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm"><i class="fas fa-edit mr-1.5"></i> Edit Profil</button></div>
      <div v-if="saved" class="px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-700 dark:text-emerald-400"><i class="fas fa-check-circle mr-1.5"></i> Profil berhasil diperbarui.</div>
      <div v-if="!editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700"><div class="w-16 h-16 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-2xl font-bold shrink-0">{{ karyawan.nama.charAt(0) }}</div><div><h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ karyawan.nama }}</h3><p class="text-sm text-gray-500 dark:text-gray-400">{{ karyawan.jabatan }} — {{ karyawan.perusahaan }}</p></div></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ karyawan.email }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Telepon</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ karyawan.kode_negara }} {{ karyawan.no_telp }}</p></div></div>
        <div class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alamat</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ karyawan.alamat }}</p></div>
      </div>
      <div v-if="editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6"><form class="space-y-4" @submit.prevent="saveEdit">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama <span class="text-red-500">*</span></label><input v-model="form.nama" type="text" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.nama ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500 focus:border-amber-500']" /><p v-if="formErrors.nama" class="text-red-500 text-xs mt-1">{{ formErrors.nama }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label><input v-model="form.email" type="email" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors" /></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label><div class="flex gap-2"><select v-model="form.kode_negara" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors"><option v-for="k in kodeNegaraList" :key="k" :value="k">{{ k }}</option></select><input v-model="form.no_telp" type="text" class="flex-1 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors" /></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat</label><textarea v-model="form.alamat" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors resize-none"></textarea></div>
        <div class="flex justify-end gap-2 pt-2"><button type="button" @click="cancelEdit" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" class="px-6 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm"><i class="fas fa-save mr-1.5"></i> Simpan</button></div>
      </form></div>
    </div>
  </div>
</template>
