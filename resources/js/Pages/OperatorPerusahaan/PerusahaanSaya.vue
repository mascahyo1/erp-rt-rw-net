<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import OperatorPerusahaanLayout from '@/Layouts/OperatorPerusahaanLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useAjaxForm } from '@/Composables/useAjaxForm';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: OperatorPerusahaanLayout });

const props = defineProps({ company: Object });
const toast = useToast();
const page = usePage();
const can = (perm) => page.props.permissions?.includes(perm);

const editMode = ref(false);
const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];
const logoLightPreview = ref(null);
const logoDarkPreview = ref(null);

// Form data — pakai reactive object (bukan useForm) karena submit pakai AJAX.
const form = reactive({
  name: props.company?.name || '',
  email: props.company?.email || '',
  phone_country_code: props.company?.phone_country_code || '+62',
  phone_number: props.company?.phone_number || '',
  address: props.company?.address || '',
  description: props.company?.description || '',
  logo: null,
  logo_dark: null,
});

const { submit, processing, errors } = useAjaxForm();

function resetForm() {
  form.name = props.company?.name || '';
  form.email = props.company?.email || '';
  form.phone_country_code = props.company?.phone_country_code || '+62';
  form.phone_number = props.company?.phone_number || '';
  form.address = props.company?.address || '';
  form.description = props.company?.description || '';
  form.logo = null;
  form.logo_dark = null;
}

function enterEdit() {
  resetForm();
  logoLightPreview.value = null;
  logoDarkPreview.value = null;
  editMode.value = true;
}

function cancelEdit() { editMode.value = false; }

function onLogoLightChange(e) {
  const file = e.target.files[0];
  if (file) {
    form.logo = file;
    logoLightPreview.value = URL.createObjectURL(file);
  }
}

function onLogoDarkChange(e) {
  const file = e.target.files[0];
  if (file) {
    form.logo_dark = file;
    logoDarkPreview.value = URL.createObjectURL(file);
  }
}

function clearLogoLight() {
  form.logo = null;
  logoLightPreview.value = null;
}

function clearLogoDark() {
  form.logo_dark = null;
  logoDarkPreview.value = null;
}

async function submitEdit() {
  // Backend expects these exact field names (controller's validate rules):
  // name, email, phone_country_code, phone_number, address, description, logo, logo_dark
  const result = await submit(
    `/operator-perusahaan/api/perusahaan-saya/${props.company.id}`,
    {
      name: form.name,
      email: form.email,
      phone_country_code: form.phone_country_code,
      phone_number: form.phone_number,
      address: form.address,
      description: form.description,
      logo: form.logo,
      logo_dark: form.logo_dark,
    },
    {
      onSuccess: (json) => {
        // Update local company prop with fresh data from server
        Object.assign(props.company, json.data);
        editMode.value = false;
        logoLightPreview.value = null;
        logoDarkPreview.value = null;
        toast.success(json.message || 'Data perusahaan berhasil diperbarui.');
      },
      onError: (json) => {
        toast.error(json.message || 'Validasi gagal. Periksa kembali isian form.');
      },
    }
  );

  // result.ok tells us if it succeeded
  if (result.ok) {
    // already handled in onSuccess
  } else {
    // already handled in onError
  }
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
        <button v-if="!editMode && can('perusahaan-saya.edit')" @click="enterEdit" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm"><i class="fas fa-edit mr-1.5"></i> Edit Perusahaan</button>
      </div>

      <div v-if="!editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 sm:p-8">
          <div class="flex flex-col sm:flex-row items-start gap-6 pb-6 border-b border-gray-100 dark:border-gray-700">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-3xl font-bold shrink-0 overflow-hidden">
              <img v-if="props.company?.logo_url" :src="props.company.logo_url" alt="Logo" class="w-full h-full object-contain bg-white p-1" />
              <span v-else>{{ (props.company?.name || 'P')[0] }}</span>
            </div>
            <div>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ props.company?.name }}</h3>
              <span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(props.company?.is_active ? 'Aktif' : 'Nonaktif')]">{{ props.company?.is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.email || '—' }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Telepon</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.phone_country_code }} {{ props.company?.phone_number }}</p></div>
            <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Daftar</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.created_at }}</p></div>
          </div>
          <div class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alamat</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.address || '—' }}</p></div>
          <div v-if="props.company?.description" class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deskripsi</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ props.company?.description }}</p></div>

          <div v-if="props.company?.logo_url || props.company?.logo_dark_url" class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Logo Perusahaan</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Mode Terang</label>
                <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex items-center justify-center min-h-[120px]">
                  <img v-if="props.company?.logo_url" :src="props.company.logo_url" alt="Logo Light" class="max-h-24 max-w-full object-contain" />
                  <span v-else class="text-xs text-gray-400">—</span>
                </div>
              </div>
              <div>
                <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Mode Gelap</label>
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-4 flex items-center justify-center min-h-[120px]">
                  <img v-if="props.company?.logo_dark_url" :src="props.company.logo_dark_url" alt="Logo Dark" class="max-h-24 max-w-full object-contain" />
                  <span v-else class="text-xs text-gray-500">—</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8">
        <form class="space-y-4" @submit.prevent="submitEdit" enctype="multipart/form-data">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Perusahaan <span class="text-red-500">*</span></label><input v-model="form.name" type="text" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', errors.name ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label><input v-model="form.email" type="email" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', errors.email ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']" /><p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email[0] }}</p></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label><div class="flex gap-2"><select v-model="form.phone_country_code" class="w-24 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-colors"><option v-for="k in kodeNegaraList" :key="k" :value="k">{{ k }}</option></select><input v-model="form.phone_number" type="text" placeholder="81234567890" class="flex-1 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-colors" /></div><p v-if="errors.phone_number" class="text-red-500 text-xs mt-1">{{ errors.phone_number[0] }}</p></div>
          </div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat</label><textarea v-model="form.address" rows="2" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none', errors.address ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"></textarea><p v-if="errors.address" class="text-red-500 text-xs mt-1">{{ errors.address[0] }}</p></div>
          <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label><textarea v-model="form.description" rows="3" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none', errors.description ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-sky-500']"></textarea><p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description[0] }}</p></div>

          <!-- Logo (Light) -->
          <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Logo Perusahaan</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Logo (Mode Terang)</label>
                <div class="bg-gray-50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 flex items-center justify-center min-h-[120px] mb-2">
                  <img v-if="logoLightPreview || props.company?.logo_url" :src="logoLightPreview || props.company.logo_url" alt="Logo Light" class="max-h-20 max-w-full object-contain" />
                  <span v-else class="text-xs text-gray-400">Pilih file untuk preview</span>
                </div>
                <input type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" @change="onLogoLightChange" class="w-full text-xs text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-300 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50 cursor-pointer" />
                <div class="flex items-center justify-between mt-1">
                  <p class="text-[10px] text-gray-500 dark:text-gray-400">JPG/PNG/WebP/SVG, maks 2MB. <span class="text-emerald-600 dark:text-emerald-400">Otomatis dikompres</span> ke WebP kecuali SVG.</p>
                  <button v-if="logoLightPreview || props.company?.logo_url" type="button" @click="clearLogoLight" class="text-[10px] text-red-500 hover:text-red-700 dark:hover:text-red-400">Hapus</button>
                </div>
                <p v-if="errors.logo" class="text-red-500 text-xs mt-1">{{ errors.logo[0] }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Logo (Mode Gelap)</label>
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-3 flex items-center justify-center min-h-[120px] mb-2">
                  <img v-if="logoDarkPreview || props.company?.logo_dark_url" :src="logoDarkPreview || props.company.logo_dark_url" alt="Logo Dark" class="max-h-20 max-w-full object-contain" />
                  <span v-else class="text-xs text-gray-500">Pilih file untuk preview</span>
                </div>
                <input type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" @change="onLogoDarkChange" class="w-full text-xs text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-50 file:text-sky-700 dark:file:bg-sky-900/30 dark:file:text-sky-300 hover:file:bg-sky-100 dark:hover:file:bg-sky-900/50 cursor-pointer" />
                <div class="flex items-center justify-between mt-1">
                  <p class="text-[10px] text-gray-500 dark:text-gray-400">Versi untuk dark mode. JPG/PNG/WebP/SVG, maks 2MB.</p>
                  <button v-if="logoDarkPreview || props.company?.logo_dark_url" type="button" @click="clearLogoDark" class="text-[10px] text-red-500 hover:text-red-700 dark:hover:text-red-400">Hapus</button>
                </div>
                <p v-if="errors.logo_dark" class="text-red-500 text-xs mt-1">{{ errors.logo_dark[0] }}</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button type="button" @click="cancelEdit" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</button>
            <button type="submit" :disabled="processing" class="px-6 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50"><i class="fas fa-save mr-1.5"></i> Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
