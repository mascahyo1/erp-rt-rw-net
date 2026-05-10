<script setup>
import { ref } from 'vue';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: OperatorPerusahaanLayout });

const editMode = ref(false);
const showPassword = ref(false);
const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];

const form = ref({
  nama: 'Admin Perusahaan',
  email: 'admin@netsejahtera.id',
  kode_negara: '+62',
  no_telp: '081234567890',
  password_lama: '',
  password_baru: '',
  password_konfirmasi: '',
});
const errors = ref({});

const initialForm = () => ({
  nama: 'Admin Perusahaan',
  email: 'admin@netsejahtera.id',
  kode_negara: '+62',
  no_telp: '081234567890',
  password_lama: '',
  password_baru: '',
  password_konfirmasi: '',
});

function startEdit() {
  form.value = initialForm();
  errors.value = {};
  editMode.value = true;
}

function cancelEdit() {
  editMode.value = false;
  form.value = initialForm();
  errors.value = {};
}

function saveProfile() {
  const e = {};
  if (!form.value.nama.trim()) e.nama = 'Nama wajib diisi';
  if (!form.value.email.trim()) e.email = 'Email wajib diisi';
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) e.email = 'Format email tidak valid';
  if (!form.value.no_telp.trim()) e.no_telp = 'No. HP wajib diisi';
  if (form.value.password_baru || form.value.password_konfirmasi) {
    if (!form.value.password_lama) e.password_lama = 'Password lama wajib diisi';
    if (form.value.password_baru.length < 6) e.password_baru = 'Minimal 6 karakter';
    if (form.value.password_baru !== form.value.password_konfirmasi) e.password_konfirmasi = 'Password tidak cocok';
  }
  errors.value = e;
  if (Object.keys(e).length > 0) return;
  editMode.value = false;
}
</script>

<template>
  <div>
    <Head title="Profil Saya | Perusahaan" />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Dashboard</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Profil Saya</span>
      </nav>
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Profil Saya</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Informasi profil admin perusahaan.</p>
      </div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-3xl font-bold shrink-0">
              {{ editMode ? form.nama.charAt(0) : 'A' }}
            </div>
            <div>
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ editMode ? form.nama : 'Admin Perusahaan' }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ editMode ? form.email : 'admin@netsejahtera.id' }}</p>
            </div>
          </div>
          <button v-if="!editMode" @click="startEdit" class="px-4 py-2 text-sm font-medium rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition-colors shadow-sm">
            <i class="fas fa-edit mr-1.5"></i> Ubah Profil
          </button>
        </div>

        <div v-if="!editMode" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Nama Lengkap</label>
            <p class="text-sm text-gray-900 dark:text-white">Admin Perusahaan</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Email</label>
            <p class="text-sm text-gray-900 dark:text-white">admin@netsejahtera.id</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Perusahaan</label>
            <p class="text-sm text-gray-900 dark:text-white">PT Net Sejahtera</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Role</label>
            <p class="text-sm text-gray-900 dark:text-white">Super Admin</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">No. Telepon</label>
            <p class="text-sm text-gray-900 dark:text-white">+62 81234567890</p>
          </div>
        </div>

        <form v-else @submit.prevent="saveProfile" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
              <input v-model="form.nama" type="text" placeholder="Nama Anda" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', errors.nama ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
              <p v-if="errors.nama" class="text-red-500 text-xs mt-1">{{ errors.nama }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label>
              <input v-model="form.email" type="email" placeholder="email@contoh.com" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', errors.email ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
              <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email }}</p>
            </div>
          </div>
          <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Negara</label>
                <select v-model="form.kode_negara" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors">
                  <option v-for="k in kodeNegaraList" :key="k" :value="k">{{ k }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">No. HP <span class="text-red-500">*</span></label>
                <input v-model="form.no_telp" type="text" placeholder="08xxxxxxxxxx" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', errors.no_telp ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                <p v-if="errors.no_telp" class="text-red-500 text-xs mt-1">{{ errors.no_telp }}</p>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
              <i class="fas fa-lock text-sky-500"></i> Ganti Password
            </h4>
            <div class="space-y-1 mb-3">
              <button type="button" @click="showPassword = !showPassword" class="text-xs text-sky-600 dark:text-sky-400 hover:underline">
                <i :class="['fas mr-1', showPassword ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                {{ showPassword ? 'Sembunyikan' : 'Tampilkan' }} form ganti password
              </button>
            </div>
            <div v-if="showPassword" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password Lama</label>
                <input v-model="form.password_lama" type="password" placeholder="Password saat ini" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', errors.password_lama ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                <p v-if="errors.password_lama" class="text-red-500 text-xs mt-1">{{ errors.password_lama }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password Baru</label>
                <input v-model="form.password_baru" type="password" placeholder="Min. 6 karakter" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', errors.password_baru ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                <p v-if="errors.password_baru" class="text-red-500 text-xs mt-1">{{ errors.password_baru }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password</label>
                <input v-model="form.password_konfirmasi" type="password" placeholder="Ulangi password baru" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', errors.password_konfirmasi ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500']" />
                <p v-if="errors.password_konfirmasi" class="text-red-500 text-xs mt-1">{{ errors.password_konfirmasi }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2 pt-2">
            <button type="submit" class="px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm">
              <i class="fas fa-save mr-1.5"></i> Simpan
            </button>
            <button type="button" @click="cancelEdit" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
              Batal
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
