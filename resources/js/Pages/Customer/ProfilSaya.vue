<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

const props = defineProps({ customer: Object });
const toast = useToast();

const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];
const editMode = ref(false);
const saved = ref(false);

const form = ref({
  name: props.customer?.name || '',
  email: props.customer?.email || '',
  phone_country_code: props.customer?.phone_country_code || '+62',
  phone_number: props.customer?.phone_number || '',
  address: props.customer?.address || '',
});

const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });

function enterEdit() {
  form.value = {
    name: props.customer?.name || '',
    email: props.customer?.email || '',
    phone_country_code: props.customer?.phone_country_code || '+62',
    phone_number: props.customer?.phone_number || '',
    address: props.customer?.address || '',
  };
  editMode.value = true;
}

function cancelEdit() { editMode.value = false; }

function saveProfile() {
  router.put('/customer/profil-saya', form.value, {
    onSuccess: () => { editMode.value = false; toast.success('Profil berhasil diperbarui.'); },
  });
}

function ubahPassword() {
  router.put('/customer/profil-saya', passwordForm.value, {
    onSuccess: () => {
      passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
      toast.success('Password berhasil diubah.');
    },
    onError: (errors) => {
      if (errors.current_password) toast.error(errors.current_password);
    },
  });
}

function statusBadge(s) { return s === 'Aktif' || s === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
const statusLabel = computed(() => props.customer?.is_active ? 'Aktif' : 'Nonaktif');
</script>

<template>
  <div>
    <Head title="Profil Saya | Pelanggan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Profil Saya</span></nav>
      <div class="flex items-center justify-between"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Profil Saya</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Informasi akun dan data diri Anda.</p></div><button v-if="!editMode" @click="enterEdit" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"><i class="fas fa-edit mr-1.5"></i> Edit Profil</button></div>

      <div v-if="!editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row items-start gap-6 pb-6 border-b border-gray-100 dark:border-gray-700"><div class="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-3xl font-bold shrink-0">{{ (props.customer?.name || 'P')[0] }}</div><div><h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ props.customer?.name }}</h3><span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(statusLabel)]">{{ statusLabel }}</span></div></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6"><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.customer?.email }}</p></div><div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Telepon</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.customer?.phone_country_code }} {{ props.customer?.phone_number }}</p></div></div>
        <div class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alamat</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.customer?.address || '—' }}</p></div>
      </div>

      <form v-if="editMode" @submit.prevent="saveProfile" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8 space-y-4">
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama <span class="text-red-500">*</span></label><input v-model="form.name" type="text" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none" /></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label><input v-model="form.email" type="email" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none" /></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label><div class="flex gap-2"><select v-model="form.phone_country_code" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm"><option v-for="k in kodeNegaraList" :key="k" :value="k">{{ k }}</option></select><input v-model="form.phone_number" type="text" class="flex-1 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none" /></div></div>
        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat</label><textarea v-model="form.address" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none resize-none"></textarea></div>
        <div class="flex justify-end gap-2 pt-2"><button type="button" @click="cancelEdit" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button><button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"><i class="fas fa-save mr-1.5"></i> Simpan</button></div>
      </form>

      <div v-if="!editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1"><i class="fas fa-lock mr-2 text-emerald-500"></i>Ubah Password</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Ganti password akun Anda secara berkala untuk keamanan.</p>
        <form class="max-w-md space-y-4" @submit.prevent="ubahPassword">
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password Lama <span class="text-red-500">*</span></label><input v-model="passwordForm.current_password" type="password" placeholder="Password saat ini" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none" /></div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password Baru <span class="text-red-500">*</span></label><input v-model="passwordForm.password" type="password" placeholder="Min. 6 karakter" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none" /></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi <span class="text-red-500">*</span></label><input v-model="passwordForm.password_confirmation" type="password" placeholder="Ulangi password" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none" /></div>
          </div>
          <div class="flex justify-end"><button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"><i class="fas fa-key mr-1.5"></i> Ubah Password</button></div>
        </form>
      </div>
    </div>
  </div>
</template>
