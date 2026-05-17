<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const page = usePage();
const toast = useToast();
const user = computed(() => page.props.auth?.user);
const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];

const editMode = ref(false);
const showPassword = ref(false);
const formErrors = ref({});

const profileForm = ref({
  name: user.value?.name || '',
  email: user.value?.email || '',
  phone_country_code: user.value?.phone_country_code || '+62',
  phone_number: user.value?.phone_number || '',
});

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
});

function startEdit() {
  profileForm.value = {
    name: user.value?.name || '',
    email: user.value?.email || '',
    phone_country_code: user.value?.phone_country_code || '+62',
    phone_number: user.value?.phone_number || '',
  };
  formErrors.value = {};
  editMode.value = true;
}

function cancelEdit() {
  editMode.value = false;
  formErrors.value = {};
}

function saveProfile() {
  const e = {};
  if (!profileForm.value.name.trim()) e.name = 'Nama wajib diisi';
  if (!profileForm.value.email.trim()) e.email = 'Email wajib diisi';
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(profileForm.value.email)) e.email = 'Format email tidak valid';
  formErrors.value = e;
  if (Object.keys(e).length > 0) return;

  router.put('/operator-perusahaan/profil-saya', profileForm.value, {
    onSuccess: () => {
      editMode.value = false;
      toast.success('Profil berhasil diperbarui.');
    },
  });
}

function changePassword() {
  const data = { ...passwordForm.value };
  router.put('/operator-perusahaan/profil-saya', { ...profileForm.value, ...data }, {
    onSuccess: () => {
      passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
      showPassword.value = false;
      toast.success('Password berhasil diubah.');
    },
  });
}
</script>

<template>
  <div>
    <Head title="Profil Saya | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Profil Saya</span>
      </nav>
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Profil Saya</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Informasi profil admin perusahaan.</p>
      </div>

      <div v-if="!editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden p-6 sm:p-8">
        <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white text-3xl font-bold shrink-0">{{ (user?.name || 'A')[0] }}</div>
            <div>
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ user?.name }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ user?.email }}</p>
            </div>
          </div>
          <button @click="startEdit" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"><i class="fas fa-edit mr-1.5"></i> Ubah Profil</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Nama Lengkap</label><p class="text-sm text-gray-900 dark:text-white">{{ user?.name }}</p></div>
          <div><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Email</label><p class="text-sm text-gray-900 dark:text-white">{{ user?.email }}</p></div>
          <div><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Perusahaan</label><p class="text-sm text-gray-900 dark:text-white">{{ user?.company?.name || '—' }}</p></div>
          <div><label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">No. Telepon</label><p class="text-sm text-gray-900 dark:text-white">{{ user?.phone_country_code }} {{ user?.phone_number }}</p></div>
        </div>
      </div>

      <form v-if="editMode" @submit.prevent="saveProfile" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label><input v-model="profileForm.name" type="text" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.name ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500']" /><p v-if="formErrors.name" class="text-red-500 text-xs mt-1">{{ formErrors.name }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label><input v-model="profileForm.email" type="email" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.email ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500']" /><p v-if="formErrors.email" class="text-red-500 text-xs mt-1">{{ formErrors.email }}</p></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kode Negara</label><select v-model="profileForm.phone_country_code" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm"><option v-for="k in kodeNegaraList" :key="k" :value="k">{{ k }}</option></select></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">No. HP</label><input v-model="profileForm.phone_number" type="text" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none" /></div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2"><i class="fas fa-lock text-indigo-500"></i> Ganti Password</h4>
          <button type="button" @click="showPassword = !showPassword" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mb-3"><i :class="['fas mr-1', showPassword ? 'fa-chevron-up' : 'fa-chevron-down']"></i>{{ showPassword ? 'Sembunyikan' : 'Tampilkan' }} form ganti password</button>
          <div v-if="showPassword" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password Lama</label><input v-model="passwordForm.current_password" type="password" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none" /></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password Baru</label><input v-model="passwordForm.password" type="password" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none" /></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password</label><input v-model="passwordForm.password_confirmation" type="password" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none" /></div>
            <div class="sm:col-span-3"><button type="button" @click="changePassword" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-sm"><i class="fas fa-key mr-1.5"></i> Ubah Password</button></div>
          </div>
        </div>

        <div class="flex items-center gap-2 pt-2">
          <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm"><i class="fas fa-save mr-1.5"></i> Simpan</button>
          <button type="button" @click="cancelEdit" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
        </div>
      </form>
    </div>
  </div>
</template>
