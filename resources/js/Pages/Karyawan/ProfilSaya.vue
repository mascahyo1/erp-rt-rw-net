<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import KaryawanLayout from '@/Layouts/KaryawanLayout.vue';
import CountryCodeSelect from '@/Components/CountryCodeSelect.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: KaryawanLayout });

const page = usePage();
const toast = useToast();
const user = computed(() => page.props.auth?.user);

const editMode = ref(false);
const formErrors = ref({});

const profileForm = ref({
  name: user.value?.name || '',
  email: user.value?.email || '',
  phone_country_code: user.value?.phone_country_code || '+62',
  phone_number: user.value?.phone_number || '',
  address: user.value?.address || '',
});

function startEdit() {
  profileForm.value = {
    name: user.value?.name || '',
    email: user.value?.email || '',
    phone_country_code: user.value?.phone_country_code || '+62',
    phone_number: user.value?.phone_number || '',
    address: user.value?.address || '',
  };
  formErrors.value = {};
  editMode.value = true;
}

function cancelEdit() { editMode.value = false; formErrors.value = {}; }

function saveProfile() {
  const e = {};
  if (!profileForm.value.name.trim()) e.name = 'Nama wajib diisi';
  formErrors.value = e;
  if (Object.keys(e).length > 0) return;

  router.put('/karyawan/profil-saya', profileForm.value, {
    onSuccess: () => {
      editMode.value = false;
      toast.success('Profil berhasil diperbarui.');
    },
  });
}
</script>

<template>
  <div>
    <Head title="Profil Saya | Karyawan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/karyawan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Profil Saya</span></nav>
      <div class="flex items-center justify-between"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Profil Saya</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Informasi akun karyawan.</p></div><button v-if="!editMode" @click="startEdit" class="inline-flex items-center px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm"><i class="fas fa-edit mr-1.5"></i> Edit Profil</button></div>

      <div v-if="!editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700"><div class="w-16 h-16 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-2xl font-bold shrink-0">{{ (user?.name || 'K')[0] }}</div><div><h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ user?.name }}</h3><p class="text-sm text-gray-500 dark:text-gray-400">{{ user?.position || 'Karyawan' }} — {{ user?.company?.name || 'Perusahaan' }}</p></div></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ user?.email }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Telepon</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ user?.phone_country_code }} {{ user?.phone_number }}</p></div></div>
        <div class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alamat</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ user?.address || '—' }}</p></div>
      </div>

      <form v-if="editMode" @submit.prevent="saveProfile" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama <span class="text-red-500">*</span></label><input v-model="profileForm.name" type="text" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.name ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-amber-500']" /><p v-if="formErrors.name" class="text-red-500 text-xs mt-1">{{ formErrors.name }}</p></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label><input v-model="profileForm.email" type="email" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 outline-none" /></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label><div class="flex gap-2"><div class="w-36 shrink-0"><CountryCodeSelect v-model="profileForm.phone_country_code" accent="amber" /></div><input v-model="profileForm.phone_number" type="text" class="flex-1 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 outline-none" /></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat</label><textarea v-model="profileForm.address" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 outline-none resize-none"></textarea></div>
        <div class="flex justify-end gap-2 pt-2"><button type="button" @click="cancelEdit" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" class="px-6 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors shadow-sm"><i class="fas fa-save mr-1.5"></i> Simpan</button></div>
      </form>
    </div>
  </div>
</template>
