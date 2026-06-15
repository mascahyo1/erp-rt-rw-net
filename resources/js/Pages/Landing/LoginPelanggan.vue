<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import CompanySearchInput from '@/Components/CompanySearchInput.vue';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: LandingLayout });

const page = usePage();

const activeTab = ref('login');
const selectedCompany = ref(null);

const siteKey = computed(() => page.props.turnstile_site_key || '');

function onLoginTurnstileSuccess(token) { loginForm['cf-turnstile-response'] = token; }
function onLoginTurnstileExpired() { loginForm['cf-turnstile-response'] = ''; }
function onRegisterTurnstileSuccess(token) { registerForm['cf-turnstile-response'] = token; }
function onRegisterTurnstileExpired() { registerForm['cf-turnstile-response'] = ''; }
// Expose callbacks ke window agar Turnstile widget (loaded async) bisa panggil.
onMounted(() => {
    window.onLoginTurnstileSuccess = onLoginTurnstileSuccess;
    window.onLoginTurnstileExpired = onLoginTurnstileExpired;
    window.onRegisterTurnstileSuccess = onRegisterTurnstileSuccess;
    window.onRegisterTurnstileExpired = onRegisterTurnstileExpired;
});
onBeforeUnmount(() => {
    delete window.onLoginTurnstileSuccess;
    delete window.onLoginTurnstileExpired;
    delete window.onRegisterTurnstileSuccess;
    delete window.onRegisterTurnstileExpired;
});

const loginForm = useForm({
    email: '',
    password: '',
    'cf-turnstile-response': '',
    remember: false,
});

const registerForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    'cf-turnstile-response': '',
});

// Touched states
const lt = { company: ref(false), email: ref(false), password: ref(false) };
const rt = { company: ref(false), name: ref(false), email: ref(false), phone: ref(false), password: ref(false), password_confirmation: ref(false) };

// Login errors
const loginCompanyError = computed(() => lt.company.value && !selectedCompany.value?.id ? 'Pilih perusahaan terlebih dahulu.' : null);
const loginEmailError = computed(() => {
    if (!lt.email.value) return null;
    if (!loginForm.email) return 'Email wajib diisi.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(loginForm.email)) return 'Format email tidak valid.';
    return null;
});
const loginPasswordError = computed(() => {
    if (!lt.password.value) return null;
    if (!loginForm.password) return 'Password wajib diisi.';
    if (loginForm.password.length < 6) return 'Password minimal 6 karakter.';
    return null;
});

// Register errors
const regCompanyError = computed(() => rt.company.value && !selectedCompany.value?.id ? 'Pilih perusahaan terlebih dahulu.' : null);
const regNameError = computed(() => {
    if (!rt.name.value) return null;
    if (!registerForm.name) return 'Nama wajib diisi.';
    if (registerForm.name.length < 3) return 'Nama minimal 3 karakter.';
    return null;
});
const regEmailError = computed(() => {
    if (!rt.email.value) return null;
    if (!registerForm.email) return 'Email wajib diisi.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(registerForm.email)) return 'Format email tidak valid.';
    return null;
});
const regPhoneError = computed(() => {
    if (!rt.phone.value) return null;
    if (!registerForm.phone) return 'Nomor HP wajib diisi.';
    if (!/^[0-9+\-\s()]{8,20}$/.test(registerForm.phone)) return 'Nomor HP tidak valid.';
    return null;
});
const regPasswordError = computed(() => {
    if (!rt.password.value) return null;
    if (!registerForm.password) return 'Password wajib diisi.';
    if (registerForm.password.length < 8) return 'Password minimal 8 karakter.';
    return null;
});
const regPasswordConfirmError = computed(() => {
    if (!rt.password_confirmation.value) return null;
    if (!registerForm.password_confirmation) return 'Konfirmasi password wajib diisi.';
    if (registerForm.password !== registerForm.password_confirmation) return 'Password tidak cocok.';
    return null;
});

// Password strength
const passwordStrength = computed(() => {
    const p = registerForm.password;
    if (!p) return { score: 0, label: '', color: '' };
    let score = 0;
    if (p.length >= 8) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    const levels = [
        { label: 'Lemah', color: 'bg-red-500' },
        { label: 'Lemah', color: 'bg-red-500' },
        { label: 'Sedang', color: 'bg-amber-500' },
        { label: 'Bagus', color: 'bg-emerald-500' },
        { label: 'Kuat', color: 'bg-emerald-600' },
    ];
    return { score, ...levels[score] };
});

const formCard = ref(null);
const showLoginPassword = ref(false);
const showRegPassword = ref(false);
const showRegConfirm = ref(false);

function submitLogin() {
    lt.company.value = lt.email.value = lt.password.value = true;
    if (loginCompanyError.value || loginEmailError.value || loginPasswordError.value) {
        formCard.value?.classList.add('shake');
        setTimeout(() => formCard.value?.classList.remove('shake'), 500);
        return;
    }
    loginForm.transform((data) => ({
        ...data,
        company_id: selectedCompany.value?.id ?? '',
    })).post('/login-pelanggan', {
        onFinish: () => loginForm.reset('password'),
    });
}

function submitRegister() {
    rt.company.value = rt.name.value = rt.email.value = rt.phone.value = rt.password.value = rt.password_confirmation.value = true;
    if (regCompanyError.value || regNameError.value || regEmailError.value || regPhoneError.value || regPasswordError.value || regPasswordConfirmError.value) {
        formCard.value?.classList.add('shake');
        setTimeout(() => formCard.value?.classList.remove('shake'), 500);
        return;
    }
    registerForm.transform((data) => ({
        ...data,
        company_id: selectedCompany.value?.id ?? '',
    })).post('/daftar-pelanggan', {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
}

function switchTab(tab) {
    activeTab.value = tab;
    formCard.value?.classList.add('tab-switch');
    setTimeout(() => formCard.value?.classList.remove('tab-switch'), 300);
}
</script>

<template>
    <Head title="Login Pelanggan" />
    <section class="min-h-[calc(100vh-200px)] flex">
        <div class="flex-1 grid lg:grid-cols-2">
            <div class="relative overflow-hidden bg-linear-to-br from-emerald-500 via-emerald-600 to-teal-700 flex items-center justify-center px-6 py-16 lg:py-0">
                <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl animate-blob"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-teal-400/20 rounded-full blur-3xl animate-blob-slow"></div>
                <div class="absolute top-1/3 right-1/3 w-48 h-48 bg-emerald-300/20 rounded-full blur-2xl animate-blob"></div>

                <div class="relative text-center text-white max-w-sm">
                    <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl ring-1 ring-white/20 hover:scale-105 transition-transform duration-500">
                        <i class="fas fa-user text-5xl"></i>
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold mb-3">Pelanggan</h2>
                    <p class="text-emerald-100 leading-relaxed text-sm md:text-base">
                        Pantau paket internet, tagihan, dan riwayat pembayaran Anda dengan mudah.
                    </p>
                    <ul class="mt-8 space-y-2.5 text-left text-sm text-emerald-100 max-w-xs mx-auto">
                        <li class="flex items-center gap-2.5">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <i class="fas fa-check text-2xs"></i>
                            </span>
                            Cek tagihan & sisa pembayaran realtime
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <i class="fas fa-check text-2xs"></i>
                            </span>
                            Bayar online via Midtrans Snap
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <i class="fas fa-check text-2xs"></i>
                            </span>
                            Riwayat pembayaran otomatis tercatat
                        </li>
                    </ul>
                </div>
            </div>

            <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
                <div class="w-full max-w-md">
                    <div ref="formCard" class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <div class="text-center mb-5">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Selamat Datang</h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Masuk atau daftar untuk mengakses akun Anda.</p>
                        </div>

                        <div class="flex mb-5 bg-gray-200/70 dark:bg-gray-800 rounded-lg p-1 relative">
                            <button
                                type="button"
                                @click="switchTab('login')"
                                :class="['flex-1 py-2 rounded-md text-sm font-medium transition-all duration-300 z-10', activeTab === 'login' ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white']"
                            >
                                <i class="fas fa-sign-in-alt mr-1"></i> Masuk
                            </button>
                            <button
                                type="button"
                                @click="switchTab('register')"
                                :class="['flex-1 py-2 rounded-md text-sm font-medium transition-all duration-300 z-10', activeTab === 'register' ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white']"
                            >
                                <i class="fas fa-user-plus mr-1"></i> Daftar
                            </button>
                            <div
                                class="absolute top-1 bottom-1 w-[calc(50%-0.25rem)] bg-white dark:bg-gray-700 rounded-md shadow-sm transition-transform duration-300"
                                :class="activeTab === 'login' ? 'translate-x-0' : 'translate-x-full'"
                            ></div>
                        </div>

                        <form v-if="activeTab === 'login'" class="space-y-4" @submit.prevent="submitLogin" novalidate>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan <span class="text-red-500">*</span></label>
                                <CompanySearchInput v-model="selectedCompany" @blur="lt.company.value = true" placeholder="Cari perusahaan Anda..." />
                                <p v-if="loginCompanyError" class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ loginCompanyError }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i :class="['fas fa-envelope text-sm transition-colors', loginEmailError ? 'text-red-400' : 'text-gray-400']"></i>
                                    </div>
                                    <input
                                        v-model="loginForm.email"
                                        @blur="lt.email.value = true"
                                        type="email"
                                        placeholder="pelanggan@email.com"
                                        class="w-full pl-10 pr-4 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                                        :class="loginEmailError || loginForm.errors.email
                                            ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:ring-emerald-500/30 focus:border-emerald-500'"
                                    />
                                </div>
                                <p v-if="loginEmailError || loginForm.errors.email" class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ loginEmailError || loginForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i :class="['fas fa-lock text-sm transition-colors', loginPasswordError ? 'text-red-400' : 'text-gray-400']"></i>
                                    </div>
                                    <input
                                        v-model="loginForm.password"
                                        @blur="lt.password.value = true"
                                        :type="showLoginPassword ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        class="w-full pl-10 pr-10 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                                        :class="loginPasswordError
                                            ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:ring-emerald-500/30 focus:border-emerald-500'"
                                    />
                                    <button
                                        type="button"
                                        @click="showLoginPassword = !showLoginPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors"
                                        :title="showLoginPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                    >
                                        <i :class="['fas text-sm', showLoginPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                                    </button>
                                </div>
                                <p v-if="loginPasswordError" class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ loginPasswordError }}</p>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <label class="flex items-center gap-2 text-gray-600 dark:text-gray-400 cursor-pointer group">
                                    <input v-model="loginForm.remember" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
                                    <span class="group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Ingat saya</span>
                                </label>
                                <a href="/lupa-password-pelanggan" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Lupa password?</a>
                            </div>
                            <div class="text-center -mt-2">
                                <a href="/verifikasi-email-pelanggan" class="text-xs text-gray-500 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline">
                                    Belum verifikasi email? Kirim ulang link
                                </a>
                            </div>
                            <button
                                type="submit"
                                :disabled="loginForm.processing"
                                class="w-full py-2.5 bg-linear-to-r from-emerald-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:shadow-emerald-500/30 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i v-if="loginForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                <i v-else class="fas fa-sign-in-alt mr-2"></i>
                                {{ loginForm.processing ? 'Memproses...' : 'Masuk' }}
                            </button>

                            <!-- Cloudflare Turnstile captcha widget (login) -->
                            <div v-if="siteKey" class="cf-turnstile" :data-sitekey="siteKey" data-callback="onLoginTurnstileSuccess" data-expired-callback="onLoginTurnstileExpired"></div>
                            <p v-if="loginForm.errors['cf-turnstile-response']" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ loginForm.errors['cf-turnstile-response'] }}
                            </p>
                        </form>

                        <form v-else class="space-y-3.5" @submit.prevent="submitRegister" novalidate>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan <span class="text-red-500">*</span></label>
                                <CompanySearchInput v-model="selectedCompany" @blur="rt.company.value = true" placeholder="Cari perusahaan Anda..." />
                                <p v-if="regCompanyError" class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ regCompanyError }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i :class="['fas fa-id-card text-sm transition-colors', regNameError ? 'text-red-400' : 'text-gray-400']"></i>
                                    </div>
                                    <input
                                        v-model="registerForm.name"
                                        @blur="rt.name.value = true"
                                        type="text"
                                        placeholder="Nama Anda"
                                        class="w-full pl-10 pr-4 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                                        :class="regNameError
                                            ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:ring-emerald-500/30 focus:border-emerald-500'"
                                    />
                                </div>
                                <p v-if="regNameError" class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ regNameError }}</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i :class="['fas fa-envelope text-sm transition-colors', regEmailError ? 'text-red-400' : 'text-gray-400']"></i>
                                        </div>
                                        <input
                                            v-model="registerForm.email"
                                            @blur="rt.email.value = true"
                                            type="email"
                                            placeholder="email@contoh.com"
                                            class="w-full pl-10 pr-4 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                                            :class="regEmailError
                                                ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                                                : 'border-gray-300 dark:border-gray-700 focus:ring-emerald-500/30 focus:border-emerald-500'"
                                        />
                                    </div>
                                    <p v-if="regEmailError" class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ regEmailError }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nomor HP</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i :class="['fas fa-phone text-sm transition-colors', regPhoneError ? 'text-red-400' : 'text-gray-400']"></i>
                                        </div>
                                        <input
                                            v-model="registerForm.phone"
                                            @blur="rt.phone.value = true"
                                            type="tel"
                                            placeholder="0812-3456-7890"
                                            class="w-full pl-10 pr-4 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                                            :class="regPhoneError
                                                ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                                                : 'border-gray-300 dark:border-gray-700 focus:ring-emerald-500/30 focus:border-emerald-500'"
                                        />
                                    </div>
                                    <p v-if="regPhoneError" class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ regPhoneError }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i :class="['fas fa-lock text-sm transition-colors', regPasswordError ? 'text-red-400' : 'text-gray-400']"></i>
                                    </div>
                                    <input
                                        v-model="registerForm.password"
                                        @blur="rt.password.value = true"
                                        :type="showRegPassword ? 'text' : 'password'"
                                        placeholder="Minimal 8 karakter"
                                        class="w-full pl-10 pr-10 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                                        :class="regPasswordError
                                            ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:ring-emerald-500/30 focus:border-emerald-500'"
                                    />
                                    <button
                                        type="button"
                                        @click="showRegPassword = !showRegPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors"
                                    >
                                        <i :class="['fas text-sm', showRegPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                                    </button>
                                </div>
                                <div v-if="registerForm.password" class="mt-1.5 flex items-center gap-2">
                                    <div class="flex-1 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div :class="['h-full transition-all duration-300', passwordStrength.color]" :style="{ width: (passwordStrength.score * 25) + '%' }"></div>
                                    </div>
                                    <span class="text-2xs text-gray-500 dark:text-gray-400 min-w-12">{{ passwordStrength.label }}</span>
                                </div>
                                <p v-if="regPasswordError" class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ regPasswordError }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i :class="['fas fa-lock text-sm transition-colors', regPasswordConfirmError ? 'text-red-400' : 'text-gray-400']"></i>
                                    </div>
                                    <input
                                        v-model="registerForm.password_confirmation"
                                        @blur="rt.password_confirmation.value = true"
                                        :type="showRegConfirm ? 'text' : 'password'"
                                        placeholder="Ulangi password"
                                        class="w-full pl-10 pr-10 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md"
                                        :class="regPasswordConfirmError
                                            ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:ring-emerald-500/30 focus:border-emerald-500'"
                                    />
                                    <button
                                        type="button"
                                        @click="showRegConfirm = !showRegConfirm"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors"
                                    >
                                        <i :class="['fas text-sm', showRegConfirm ? 'fa-eye-slash' : 'fa-eye']"></i>
                                    </button>
                                </div>
                                <p v-if="regPasswordConfirmError" class="text-red-500 text-xs mt-1 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>{{ regPasswordConfirmError }}</p>
                            </div>
                            <button
                                type="submit"
                                :disabled="registerForm.processing"
                                class="w-full py-2.5 bg-linear-to-r from-emerald-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:shadow-emerald-500/30 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i v-if="registerForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                <i v-else class="fas fa-user-plus mr-2"></i>
                                {{ registerForm.processing ? 'Mendaftar...' : 'Daftar Sekarang' }}
                            </button>

                            <!-- Cloudflare Turnstile captcha widget (register) -->
                            <div v-if="siteKey" class="cf-turnstile" :data-sitekey="siteKey" data-callback="onRegisterTurnstileSuccess" data-expired-callback="onRegisterTurnstileExpired"></div>
                            <p v-if="registerForm.errors['cf-turnstile-response']" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ registerForm.errors['cf-turnstile-response'] }}
                            </p>
                        </form>
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

.tab-switch { animation: tab-fade 0.3s ease-out; }
@keyframes tab-fade {
    0% { transform: scale(0.98); opacity: 0.5; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
