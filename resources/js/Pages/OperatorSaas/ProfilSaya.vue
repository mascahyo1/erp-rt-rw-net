<script setup>
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import CountryCodeSelect from '@/Components/CountryCodeSelect.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { errorSummary } from '@/Composables/useFormErrorToast.js';
import { useToast } from '@/Composables/useToast';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: OperatorSaasLayout });

const page = usePage();
const toast = useToast();
const user = page.props.auth.user;

const showPw = ref(false);
const form = useForm({
  name: user.name,
  email: user.email,
  phone_country_code: user.phone_country_code || '+62',
  phone_number: user.phone_number || '',
  current_password: '',
  password: '',
  password_confirmation: '',
});

const showPasswordFields = ref(false);

function submit() {
  form.put('/operator-saas/profil-saya', {
    onSuccess: () => form.reset('current_password', 'password', 'password_confirmation'),
    onError: () => toast.error('Validasi gagal: ' + errorSummary(form.errors), 6000),
  });
}
</script>

<template>
  <Head title="Profil Saya" />
  <ToastContainer />

  <div>
    <div class="mb-6">
      <nav class="flex items-center gap-1.5 text-sm mb-4">
        <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Profil Saya</span>
      </nav>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profil Saya</h1>
    </div>

    <div class="max-w-2xl">
      <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
        <form @submit.prevent="submit" class="space-y-5" data-testid="form-main">
          <FormErrorSummary :errors="form.errors" testId="form-error-summary-profil" />
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama <span class="text-red-500">*</span></label>
            <input v-model="form.name" type="text" :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 outline-none', form.errors.name ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500']" />
            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label>
            <input v-model="form.email" type="email" :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 outline-none', form.errors.email ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500']" />
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Email harus valid dan unik.</p>
<p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
              <div class="w-36 shrink-0">
                <CountryCodeSelect v-model="form.phone_country_code" accent="indigo" :error="!!form.errors.phone_country_code" />
              </div>
              <input v-model="form.phone_number" type="text" inputmode="numeric" placeholder="812xxxxxxxx" :class="['flex-1 px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 outline-none', form.errors.phone_number ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500']"  data-testid="input-812xxxxxxxx" />
            </div>
            <p v-if="form.errors.phone_country_code" class="text-red-500 text-xs mt-1">{{ form.errors.phone_country_code }}</p>
            <p v-if="form.errors.phone_number" class="text-red-500 text-xs mt-1">{{ form.errors.phone_number }}</p>
          </div>

          <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
            <button type="button" @click="showPasswordFields = !showPasswordFields" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
              <i :class="['fas mr-1', showPasswordFields ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
              Ubah Password
            </button>

            <div v-if="showPasswordFields" class="space-y-4 mt-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password Saat Ini</label>
                <div class="relative"><input v-model="form.current_password" type="password" :class="['w-full px-4 py-2.5 pr-10 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 outline-none', form.errors.current_password ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500']" /><button type="button" @click="showPw = !showPw" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-indigo-500 transition-colors" :title="showPw ? 'Sembunyikan' : 'Tampilkan'"><i :class="['fas text-sm', showPw ? 'fa-eye-slash' : 'fa-eye']"></i></button></div>
                <p v-if="form.errors.current_password" class="text-red-500 text-xs mt-1">{{ form.errors.current_password }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password Baru</label>
                <div class="relative"><input v-model="form.password" type="password" :class="['w-full px-4 py-2.5 pr-10 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 outline-none', form.errors.password ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500']" placeholder="Minimal 8 karakter"  data-testid="input-minimal-8-karakter" />
                <button type="button" @click="showPw = !showPw" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-indigo-500 transition-colors" :title="showPw ? 'Sembunyikan' : 'Tampilkan'"><i :class="['fas text-sm', showPw ? 'fa-eye-slash' : 'fa-eye']"></i></button></div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Opsional — isi hanya jika ingin ganti password. Minimal 8 karakter.</p>
<p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password Baru</label>
                <div class="relative"><input v-model="form.password_confirmation" type="password" :class="['w-full px-4 py-2.5 pr-10 rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 outline-none', form.errors.password_confirmation ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500']" /><button type="button" @click="showPw = !showPw" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-indigo-500 transition-colors" :title="showPw ? 'Sembunyikan' : 'Tampilkan'"><i :class="['fas text-sm', showPw ? 'fa-eye-slash' : 'fa-eye']"></i></button></div>
                <p v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ form.errors.password_confirmation }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2" data-testid="btn-simpan">
              <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-save"></i>
              {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
