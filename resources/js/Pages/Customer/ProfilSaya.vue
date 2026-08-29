<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

const page = usePage();
const props = defineProps({ customer: Object });
const toast = useToast();

const extra = computed(() => page.props.customer_extra || {});
const user = computed(() => props.customer || {});

const editMode = ref(false);
const submitting = ref(false);
const formErrors = ref({});

const profileForm = ref({
  no_nik: '',
  no_kk: '',
  photo_ktp: null,
  photo_kk: null,
  photo_profile: null,
});

function startEdit() {
  profileForm.value = {
    no_nik: extra.value?.no_nik || '',
    no_kk: extra.value?.no_kk || '',
    photo_ktp: null,
    photo_kk: null,
    photo_profile: null,
  };
  formErrors.value = {};
  editMode.value = true;
}

function cancelEdit() {
  editMode.value = false;
  formErrors.value = {};
  profileForm.value.photo_ktp = null;
  profileForm.value.photo_kk = null;
  profileForm.value.photo_profile = null;
}

function onFileChange(field, event) {
  profileForm.value[field] = event.target.files[0] || null;
  formErrors.value[field] = null;
}

async function saveProfile() {
  submitting.value = true;

  const formData = new FormData();
  formData.append('no_nik', profileForm.value.no_nik || '');
  formData.append('no_kk', profileForm.value.no_kk || '');
  if (profileForm.value.photo_ktp) formData.append('photo_ktp', profileForm.value.photo_ktp);
  if (profileForm.value.photo_kk) formData.append('photo_kk', profileForm.value.photo_kk);
  if (profileForm.value.photo_profile) formData.append('photo_profile', profileForm.value.photo_profile);
  formData.append('_method', 'PUT');

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const response = await fetch('/customer/profil-saya', {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
    },
    credentials: 'same-origin',
  });

  submitting.value = false;

  if (response.ok) {
    editMode.value = false;
    toast.success('Profil berhasil diperbarui.');
    router.reload({ only: ['customer', 'customer_extra', 'auth'] });
  } else {
    let errs = {};
    try { errs = (await response.json()).errors || {}; } catch (e) { /* ignore */ }
    formErrors.value = errs;
    toast.error('Gagal memperbarui profil. Periksa input Anda.');
  }
}

function statusLabel() { return user.value?.is_active ? 'Aktif' : 'Nonaktif'; }
function statusBadge(s) { return s === 'Aktif' || s === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'; }
</script>

<template>
  <div>
    <Head title="Profil Saya | Pelanggan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Profil Saya</span>
      </nav>

      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Profil Saya</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola informasi akun dan dokumen identitas Anda.</p>
        </div>
        <button v-if="!editMode" @click="startEdit" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
          <i class="fas fa-edit mr-1.5"></i> Edit Profil
        </button>
      </div>

      <!-- View Mode -->
      <div v-if="!editMode" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">
        <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
          <div v-if="extra?.photo_profile_url" class="w-16 h-16 rounded-full overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 shrink-0">
            <img :src="extra.photo_profile_url" alt="Foto Profil" class="w-full h-full object-cover" />
          </div>
          <div v-else class="w-16 h-16 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-2xl font-bold shrink-0">
            {{ (user?.name || 'P')[0] }}
          </div>
          <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ user?.name }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ user?.company?.name || '—' }}</p>
            <span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(statusLabel())]">{{ statusLabel() }}</span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kode</label>
            <p class="text-sm text-gray-900 dark:text-white mt-1 font-mono">{{ extra?.code || '—' }}</p>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</label>
            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ user?.email }}</p>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Telepon</label>
            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ user?.phone_country_code }} {{ user?.phone_number }}</p>
          </div>
          <div>
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alamat</label>
            <p class="text-sm text-gray-900 dark:text-white mt-1">{{ user?.address || '—' }}</p>
          </div>
        </div>

        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3"><i class="fas fa-id-card text-emerald-500 mr-1.5"></i>Dokumen Identitas</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">NIK</label>
              <p class="text-sm text-gray-900 dark:text-white mt-1 font-mono">{{ extra?.no_nik || '—' }}</p>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. KK</label>
              <p class="text-sm text-gray-900 dark:text-white mt-1 font-mono">{{ extra?.no_kk || '—' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div v-if="extra?.photo_ktp_url" class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
              <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Foto KTP</label>
              <a :href="extra.photo_ktp_url" target="_blank" rel="noopener noreferrer" class="block mt-2">
                <img :src="extra.photo_ktp_url" alt="Foto KTP" class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700 hover:opacity-90 transition-opacity" />
              </a>
            </div>
            <div v-else class="bg-gray-50 dark:bg-gray-900/50 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-3">
              <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Foto KTP</label>
              <p class="text-xs text-gray-400 mt-2">Belum diunggah</p>
            </div>

            <div v-if="extra?.photo_kk_url" class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
              <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Foto KK</label>
              <a :href="extra.photo_kk_url" target="_blank" rel="noopener noreferrer" class="block mt-2">
                <img :src="extra.photo_kk_url" alt="Foto KK" class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700 hover:opacity-90 transition-opacity" />
              </a>
            </div>
            <div v-else class="bg-gray-50 dark:bg-gray-900/50 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-3">
              <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Foto KK</label>
              <p class="text-xs text-gray-400 mt-2">Belum diunggah</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Mode -->
      <form v-if="editMode" @submit.prevent="saveProfile" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4" data-testid="form-main">
        <!-- Kode read-only banner -->
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg px-4 py-3">
          <div class="flex items-center gap-2">
            <i class="fas fa-info-circle text-emerald-600 dark:text-emerald-400"></i>
            <span class="text-xs text-emerald-700 dark:text-emerald-300">Kode pelanggan tidak dapat diubah. Hubungi admin jika perlu perubahan.</span>
          </div>
          <p class="text-sm font-mono font-semibold text-emerald-900 dark:text-emerald-200 mt-1.5">{{ extra?.code || '—' }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">No. NIK <span class="text-red-500">*</span></label>
            <input v-model="profileForm.no_nik" type="text" placeholder="Nomor KTP" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.no_nik ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-emerald-500']"  data-testid="input-nomor-ktp" />
            <p v-if="formErrors.no_nik" class="text-red-500 text-xs mt-1">{{ formErrors.no_nik }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">No. KK <span class="text-red-500">*</span></label>
            <input v-model="profileForm.no_kk" type="text" placeholder="Nomor Kartu Keluarga" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.no_kk ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-emerald-500']"  data-testid="input-nomor-kartu-keluarga" />
            <p v-if="formErrors.no_kk" class="text-red-500 text-xs mt-1">{{ formErrors.no_kk }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Foto KTP</label>
            <div v-if="extra?.photo_ktp_url" class="mb-2">
              <a :href="extra.photo_ktp_url" target="_blank" rel="noopener noreferrer" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">
                <i class="fas fa-image mr-1"></i>Lihat foto saat ini
              </a>
            </div>
            <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" @change="onFileChange('photo_ktp', $event)" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50 transition-colors" />
            <p class="mt-1 text-xs text-gray-400">Format: JPG, JPEG, PNG, WebP, PDF. Maksimal 2MB.</p>
            <p v-if="formErrors.photo_ktp" class="text-red-500 text-xs mt-1">{{ formErrors.photo_ktp }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Foto KK</label>
            <div v-if="extra?.photo_kk_url" class="mb-2">
              <a :href="extra.photo_kk_url" target="_blank" rel="noopener noreferrer" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">
                <i class="fas fa-image mr-1"></i>Lihat foto saat ini
              </a>
            </div>
            <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" @change="onFileChange('photo_kk', $event)" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50 transition-colors" />
            <p class="mt-1 text-xs text-gray-400">Format: JPG, JPEG, PNG, WebP, PDF. Maksimal 2MB.</p>
            <p v-if="formErrors.photo_kk" class="text-red-500 text-xs mt-1">{{ formErrors.photo_kk }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Foto Profil</label>
          <div v-if="extra?.photo_profile_url" class="mb-2 flex items-center gap-3">
            <img :src="extra.photo_profile_url" alt="Foto Profil" class="w-12 h-12 rounded-full object-cover border border-gray-200 dark:border-gray-700" />
            <a :href="extra.photo_profile_url" target="_blank" rel="noopener noreferrer" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">
              <i class="fas fa-external-link-alt mr-1"></i>Lihat ukuran penuh
            </a>
          </div>
          <input type="file" accept="image/jpeg,image/png,image/webp" @change="onFileChange('photo_profile', $event)" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50 transition-colors" />
          <p class="mt-1 text-xs text-gray-400">Format: JPG, JPEG, PNG, WebP. Maksimal 2MB. Foto akan dikompres otomatis.</p>
          <p v-if="formErrors.photo_profile" class="text-red-500 text-xs mt-1">{{ formErrors.photo_profile }}</p>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
          <button type="button" @click="cancelEdit" :disabled="submitting" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors disabled:opacity-50">Batal</button>
          <button type="submit" :disabled="submitting" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50" data-testid="btn-simpan">
            <i class="fas fa-save mr-1.5"></i>{{ submitting ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
