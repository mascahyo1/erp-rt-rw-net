<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import CompanySearchInput from '@/Components/CompanySearchInput.vue';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

defineOptions({ layout: LandingLayout });

const selectedCompany = ref(null);

const form = useForm({
    email: '',
    password: '',
    'cf-turnstile-response': '',
    remember: false,
});

const showPassword = ref(false);
const emailTouched = ref(false);
const passwordTouched = ref(false);
const companyTouched = ref(false);
const formCard = ref(null);

const siteKey = computed(() => page.props.turnstile_site_key || '');

function onTurnstileSuccess(token) {
    form['cf-turnstile-response'] = token;
}
function onTurnstileExpired() {
    form['cf-turnstile-response'] = '';
}
// Expose callbacks ke window agar Turnstile widget (loaded async) bisa panggil.
onMounted(() => {
    window.onTurnstileSuccess = onTurnstileSuccess;
    window.onTurnstileExpired = onTurnstileExpired;
});
onBeforeUnmount(() => {
    delete window.onTurnstileSuccess;
    delete window.onTurnstileExpired;
});

const companyError = computed(() => {
    if (!companyTouched.value) return null;
    if (!selectedCompany.value?.id) return 'Pilih perusahaan terlebih dahulu.';
    return null;
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
    if (form.password.length < 6) return 'Password minimal 6 karakter.';
    return null;
});

function submit() {
    companyTouched.value = true;
    emailTouched.value = true;
    passwordTouched.value = true;
    if (companyError.value || emailError.value || passwordError.value) {
        formCard.value?.classList.add('shake');
        setTimeout(() => formCard.value?.classList.remove('shake'), 500);
        return;
    }
    form.transform((data) => ({
        ...data,
        company_id: selectedCompany.value?.id ?? '',
    })).post('/login-karyawan', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Login Karyawan" />
    <section class="min-h-[calc(100vh-200px)] flex">
        <div class="flex-1 grid lg:grid-cols-2">
            <div class="relative overflow-hidden bg-linear-to-br from-amber-500 via-orange-600 to-amber-800 flex items-center justify-center px-6 py-16 lg:py-0">
                <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl animate-blob"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-orange-400/20 rounded-full blur-3xl animate-blob-slow"></div>
                <div class="absolute top-1/3 right-1/3 w-48 h-48 bg-amber-300/20 rounded-full blur-2xl animate-blob"></div>

                <div class="relative text-center text-white max-w-sm">
                    <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl ring-1 ring-white/20 hover:scale-105 transition-transform duration-500">
                        <i class="fas fa-user-tie text-5xl"></i>
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold mb-3">Karyawan</h2>
                    <p class="text-amber-100 leading-relaxed text-sm md:text-base">
                        Akses tagihan, customer, dan insentif Anda sebagai karyawan.
                    </p>
                    <ul class="mt-8 space-y-2.5 text-left text-sm text-amber-100 max-w-xs mx-auto">
                        <li class="flex items-center gap-2.5">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <i class="fas fa-check text-2xs"></i>
                            </span>
                            Kelola tagihan & customer harian
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <i class="fas fa-check text-2xs"></i>
                            </span>
                            Catat pembayaran tunai & transfer
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <i class="fas fa-check text-2xs"></i>
                            </span>
                            Klaim insentif otomatis terhitung
                        </li>
                    </ul>
                </div>
            </div>

            <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
                <div class="w-full max-w-md">
                    <div ref="formCard" class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <div class="text-center mb-6">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Login Karyawan</h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Pilih perusahaan Anda, lalu masuk dengan akun karyawan.</p>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit" novalidate>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan <span class="text-red-500">*</span></label>
                                <CompanySearchInput v-model="selectedCompany" @blur="companyTouched = true" placeholder="Cari perusahaan Anda..." />
                                <p v-if="companyError" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ companyError }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i :class="['fas fa-envelope text-sm transition-colors', emailError ? 'text-red-400' : 'text-gray-400']"></i>
                                    </div>
                                    <input
                                        v-model="form.email"
                                        @blur="emailTouched = true"
                                        type="email"
                                        placeholder="email@perusahaan.id"
                                        class="w-full pl-10 pr-4 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                                        :class="emailError || form.errors.email
                                            ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:ring-amber-500/30 focus:border-amber-500'"
                                    />
                                </div>
                                <p v-if="emailError || form.errors.email" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ emailError || form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
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
                                            : 'border-gray-300 dark:border-gray-700 focus:ring-amber-500/30 focus:border-amber-500'"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors"
                                        :title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                    >
                                        <i :class="['fas text-sm', showPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                                    </button>
                                </div>
                                <p v-if="passwordError" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ passwordError }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between text-sm">
                                <label class="flex items-center gap-2 text-gray-600 dark:text-gray-400 cursor-pointer group">
                                    <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-amber-600 focus:ring-amber-500 cursor-pointer" />
                                    <span class="group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Ingat saya</span>
                                </label>
                                <a href="/lupa-password-karyawan" class="text-amber-600 dark:text-amber-400 hover:underline font-medium">Lupa password?</a>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-2.5 bg-linear-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:shadow-amber-500/30 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                <i v-else class="fas fa-sign-in-alt mr-2"></i>
                                {{ form.processing ? 'Memproses...' : 'Masuk' }}
                            </button>

                            <!-- Cloudflare Turnstile captcha widget -->
                            <div v-if="siteKey" class="cf-turnstile" :data-sitekey="siteKey" data-callback="onTurnstileSuccess" data-expired-callback="onTurnstileExpired"></div>
                            <p v-if="form.errors['cf-turnstile-response']" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ form.errors['cf-turnstile-response'] }}
                            </p>
                        </form>

                        <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-6">
                            Bukan karyawan? <Link href="/login-pelanggan" class="text-amber-600 dark:text-amber-400 hover:underline font-medium">Masuk sebagai pelanggan</Link>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
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

.shake { animation: shake 0.4s ease-in-out; }
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-8px); }
    40% { transform: translateX(8px); }
    60% { transform: translateX(-4px); }
    80% { transform: translateX(4px); }
}
</style>
