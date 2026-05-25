<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ company: Object });
const toast = useToast();

const editMode = ref(false);
const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];

const form = useForm({
  name: props.company?.name || '',
  email: props.company?.email || '',
  phone_country_code: props.company?.phone_country_code || '+62',
  phone_number: props.company?.phone_number || '',
  address: props.company?.address || '',
  description: props.company?.description || '',
});

function enterEdit() {
  form.defaults({
    name: props.company?.name || '',
    email: props.company?.email || '',
    phone_country_code: props.company?.phone_country_code || '+62',
    phone_number: props.company?.phone_number || '',
    address: props.company?.address || '',
    description: props.company?.description || '',
  });
  form.reset();
  editMode.value = true;
}

function cancelEdit() { editMode.value = false; }

function submitEdit() {
  form.put('/operator-perusahaan/perusahaan-saya/' + props.company.id, {
    onSuccess: () => {
      editMode.value = false;
      toast.success('Data perusahaan berhasil diperbarui.');
    },
  });
}

function statusBadge(s) {
  return s === 'Aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
}
</script>

<template>
  <div>
    <Head title="Perusahaan Saya | Perusahaan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/operator-perusahaan/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Perusahaan Saya</span>
      </nav>

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Perusahaan Saya</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola profil dan informasi perusahaan Anda.</p></div>
        <button v-if="!editMode && $page.props.permissions?.includes('perusahaan-saya.edit')" @click="enterEdit" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-edit mr-1.5"></i> Edit Perusahaan</button>
      </div>

      <div v-if="!editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 sm:p-8">
          <div class="flex flex-col sm:flex-row items-start gap-6 pb-6 border-b border-gray-100 dark:border-gray-700">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-3xl font-bold shrink-0">{{ (props.company?.name || 'P')[0] }}</div>
            <div>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ props.company?.name }}</h3>
              <span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(props.company?.is_active ? 'Aktif' : 'Nonaktif')]">{{ props.company?.is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.email || '—' }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Telepon</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.phone_country_code }} {{ props.company?.phone_number }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Website</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.website || '—' }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">NPWP</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.npwp || '—' }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Daftar</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.created_at }}</p></div>
          </div>
          <div class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alamat</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.address || '—' }}</p></div>
          <div v-if="props.company?.description" class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deskripsi</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.description }}</p></div>
        </div>
      </div>

      <div v-if="editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8">
        <form class="space-y-4" @submit.prevent="submitEdit">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Perusahaan <span class="text-red-500">*</span></label><input v-model="form.name" type="text" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', form.errors.name ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label><input v-model="form.email" type="email" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', form.errors.email ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label><div class="flex gap-2"><select v-model="form.phone_country_code" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-colors"><option v-for="k in kodeNegaraList" :key="k" :value="k">{{ k }}</option></select><input v-model="form.phone_number" type="text" placeholder="81234567890" class="flex-1 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-colors" /></div><p v-if="form.errors.phone_number" class="text-red-500 text-xs mt-1">{{ form.errors.phone_number }}</p></div>
          </div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat</label><textarea v-model="form.address" rows="2" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none', form.errors.address ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"></textarea><p v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label><textarea v-model="form.description" rows="3" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none', form.errors.description ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"></textarea><p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p></div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="cancelEdit" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i> Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
