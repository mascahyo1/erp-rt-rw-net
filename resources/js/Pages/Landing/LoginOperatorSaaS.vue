<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import { useToast } from '@/Composables/useToast';
import { errorSummary } from '@/Composables/useFormErrorToast';

defineOptions({ layout: LandingLayout });

const page = usePage();
const toast = useToast();
const form = useForm({
  email: '',
  password: '',
  'cf-turnstile-response': '',
  remember: false,
});

const showPassword = ref(false);
const emailTouched = ref(false);
const passwordTouched = ref(false);
const formCard = ref(null);

const siteKey = computed(() => page.props.turnstile_site_key || '');

// Turnstile callback: dipanggil widget saat user solve captcha.
// Set nilai token ke form state supaya ikut terkirim saat submit.
function onTurnstileSuccess(token) {
  form['cf-turnstile-response'] = token;
}
// Callback saat token expired (otomatis setelah ~2 menit).
// Kita reset state agar submit ditolak sampai user solve ulang.
function onTurnstileExpired() {
  form['cf-turnstile-response'] = '';
}
/**
 * Submit button disabled sampai Turnstile solved.
 * - siteKey empty (dev/testing tanpa Turnstile) → always enabled
 * - siteKey set → enabled hanya kalau form sudah punya token
 */
const turnstileSolved = computed(() => !siteKey.value || !!form['cf-turnstile-response']);

// PENTING: Set window callbacks SYNCHRONOUSLY (top-level script), BUKAN onMounted.
// LandingLayout's Turnstile MutationObserver bisa render widget SEBELUM component
// ini onMounted (test keys auto-solve instan). Kalau window callback di-set di
// onMounted, callback mungkin terlewat → form['cf-turnstile-response'] tetap
// kosong → tombol STUCK di "Tunggu verifikasi captcha..." walau widget "Success!".
window.onTurnstileSuccess = onTurnstileSuccess;
window.onTurnstileExpired = onTurnstileExpired;
onBeforeUnmount(() => {
  delete window.onTurnstileSuccess;
  delete window.onTurnstileExpired;
});

const emailError = computed(() => {
  if (!emailTouched.value) return null;
  if (!form.email) return 'Email wajib diisi.';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) return 'Format email tidak valid.';
  return null;
});
const passwordError = computed(() => {
  if (!passwordTouched.value) return null;
  if (!form.password) return 'Password wajib diisi.';
  return null;
});

function submit() {
  emailTouched.value = true;
  passwordTouched.value = true;
  if (emailError.value || passwordError.value) {
    formCard.value?.classList.add('shake');
    setTimeout(() => formCard.value?.classList.remove('shake'), 500);
    return;
  }
  form.post('/login-operator-saas', {
    // JANGAN panggil turnstile.reset() — lihat comment di submitLogin LoginPelanggan.vue.
    onError: (errors) => {
      toast.error('Login gagal: ' + errorSummary(errors), 6000);
      if (errors['cf-turnstile-response']) {
        document.querySelectorAll('.cf-turnstile').forEach(w => {
          w.innerHTML = '';
          w.removeAttribute('data-ts-rendered');
        });
      }
    },
    onFinish: () => form.reset('password'),
  });
}
</script>

<template>
  <div>
    <ToastContainer />
    <section class="min-h-[calc(100vh-200px)] flex overflow-x-hidden">
      <div class="flex-1 grid grid-cols-1 lg:grid-cols-2 min-w-0">
        <!-- Left: branded panel (animated orbs) -->
        <div class="relative overflow-hidden bg-linear-to-br from-indigo-600 via-indigo-700 to-purple-700 flex items-center justify-center px-4 sm:px-6 py-16 lg:py-0 min-w-0">
          <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl animate-blob"></div>
          <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-purple-400/20 rounded-full blur-3xl animate-blob-slow"></div>
          <div class="absolute top-1/2 right-1/4 w-40 h-40 bg-indigo-300/20 rounded-full blur-2xl animate-blob"></div>
          <div class="relative text-center text-white max-w-sm">
            <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl ring-1 ring-white/20 hover:scale-105 transition-transform duration-500">
              <i class="fas fa-cloud text-5xl"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold mb-3">Operator SaaS</h2>
            <p class="text-indigo-100 leading-relaxed text-sm md:text-base">
              Kelola semua tenant RT/RW Net dari satu dashboard. Pantau performa, atur langganan, dan bantu perusahaan tumbuh.
            </p>
            <ul class="mt-8 space-y-2.5 text-left text-sm text-indigo-100 max-w-xs mx-auto">
              <li class="flex items-center gap-2.5">
                <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                  <i class="fas fa-check text-2xs"></i>
                </span>
                Multi-tenant terpusat
              </li>
              <li class="flex items-center gap-2.5">
                <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                  <i class="fas fa-check text-2xs"></i>
                </span>
                Monitoring semua perusahaan
              </li>
              <li class="flex items-center gap-2.5">
                <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                  <i class="fas fa-check text-2xs"></i>
                </span>
                Role & permission management
              </li>
            </ul>
          </div>
        </div>

        <!-- Right: form -->
        <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
          <div ref="formCard" class="w-full max-w-md rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div class="text-center mb-6">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Masuk Operator SaaS</h1>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Masukkan kredensial operator Anda.
              </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit" novalidate>
            <FormErrorSummary :errors="form.errors" title="Gagal masuk — periksa isian berikut:" />
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i :class="['fas fa-envelope text-sm transition-colors', emailError ? 'text-red-400' : 'text-gray-400']"></i>
                  </div>
                  <input
                    v-model="form.email"
                    @blur="emailTouched = true"
                    type="email"
                    placeholder="admin@rtrwnet.id"
                    class="w-full pl-10 pr-4 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                    :class="emailError || page.props.errors?.email
                      ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                      : 'border-gray-300 dark:border-gray-700 focus:ring-indigo-500/30 focus:border-indigo-500'"
                  />
                </div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Format: nama@domain.com</p>
<p v-if="emailError || page.props.errors?.email" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                  <i class="fas fa-exclamation-circle"></i>
                  {{ emailError || page.props.errors.email[0] }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password <span class="text-red-500">*</span></label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i :class="['fas fa-lock text-sm transition-colors', passwordError ? 'text-red-400' : 'text-gray-400']"></i>
                  </div>
                  <input
                    v-model="form.password"
                    @blur="passwordTouched = true"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="••••••••"
                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                    :class="passwordError
                      ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                      : 'border-gray-300 dark:border-gray-700 focus:ring-indigo-500/30 focus:border-indigo-500'"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors"
                    :title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                  >
                    <i :class="['fas text-sm', showPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                  </button>
                </div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Gunakan password akun Anda.</p>
<p v-if="passwordError || page.props.errors?.password || page.props.errors?.email" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                  <i class="fas fa-exclamation-circle"></i>
                  {{ passwordError || page.props.errors?.password?.[0] || page.props.errors?.email?.[0] }}
                </p>
              </div>

              <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-gray-600 dark:text-gray-400 cursor-pointer group">
                  <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                  <span class="group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Ingat saya</span>
                </label>
                <a href="/lupa-password-operator-saas" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Lupa password?</a>
              </div>

              <button
                type="submit" data-testid="btn-login-submit"
                :disabled="form.processing || !turnstileSolved"
                class="w-full py-2.5 bg-linear-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:shadow-indigo-500/30 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else-if="!turnstileSolved" class="fas fa-shield-halved mr-2"></i>
                <i v-else class="fas fa-sign-in-alt mr-2"></i>
                {{ form.processing ? 'Memproses...' : (!turnstileSolved ? 'Tunggu verifikasi captcha...' : 'Masuk') }}
              </button>

              <!-- Cloudflare Turnstile captcha widget -->
              <div v-if="siteKey" data-testid="cf-turnstile-widget" class="cf-turnstile" :data-sitekey="siteKey" data-callback="onTurnstileSuccess" data-expired-callback="onTurnstileExpired"></div>
              <p v-if="form.errors['cf-turnstile-response']" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <i class="fas fa-exclamation-circle"></i>
                {{ form.errors['cf-turnstile-response'] }}
              </p>
            </form>

            <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-6">
              Belum punya akun? <Link href="/hubungi-kami" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Hubungi tim sales</Link>
            </p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* Floating orbs */
@keyframes blob {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -20px) scale(1.05); }
  66% { transform: translate(-20px, 15px) scale(0.95); }
}
@keyframes blob-slow {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-25px, 20px) scale(1.08); }
}
.animate-blob { animation: blob 14s ease-in-out infinite; }
.animate-blob-slow { animation: blob-slow 18s ease-in-out infinite; }

/* Shake on validation error */
.shake { animation: shake 0.4s ease-in-out; }
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-8px); }
  40% { transform: translateX(8px); }
  60% { transform: translateX(-4px); }
  80% { transform: translateX(4px); }
}
</style>
