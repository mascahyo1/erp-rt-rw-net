<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';
import FormErrorSummary from '@/Components/FormErrorSummary.vue';
import { errorSummary } from '@/Composables/useFormErrorToast';
import { computed, onBeforeUnmount } from 'vue';

defineOptions({ layout: LandingLayout });

const props = defineProps({
    token: { type: String, default: null },
    email: { type: String, default: null },
});

const page = usePage();
const toast = useToast();

const isResetMode = computed(() => !!props.token);
const siteKey = computed(() => page.props.turnstile_site_key || '');
const turnstileSolved = computed(() => !siteKey.value || !!form['cf-turnstile-response']);

const form = useForm({
    email: props.email || '',
    token: props.token || '',
    password: '',
    password_confirmation: '',
    'cf-turnstile-response': '',
});

function onTurnstileSuccess(token) {
    form['cf-turnstile-response'] = token;
}
function onTurnstileExpired() {
    form['cf-turnstile-response'] = '';
}
// Set SYNCHRONOUSLY (bukan onMounted) — widget Turnstile bisa render
// duluan; kalau callback belum terdaftar, token tidak masuk form.
window.onTurnstileSuccess = onTurnstileSuccess;
window.onTurnstileExpired = onTurnstileExpired;
onBeforeUnmount(() => {
    delete window.onTurnstileSuccess;
    delete window.onTurnstileExpired;
});

const submit = () => {
    if (isResetMode.value) {
        form.post('/lupa-password-operator-saas/reset', {
            onSuccess: () => toast.success('Password berhasil direset! Silakan login.'),
            onError: (errors) => {
                toast.error('Gagal: ' + errorSummary(errors), 6000);
                if (errors['cf-turnstile-response']) {
                    document.querySelectorAll('.cf-turnstile').forEach(w => {
                        w.innerHTML = '';
                        w.removeAttribute('data-ts-rendered');
                    });
                }
            },
        });
    } else {
        form.post('/lupa-password-operator-saas', {
            onSuccess: () => {
                toast.success('Link reset password sudah dikirim ke email Anda.');
                form.reset('email');
            },
            onError: (errors) => {
                toast.error('Gagal: ' + errorSummary(errors), 6000);
                if (errors['cf-turnstile-response']) {
                    document.querySelectorAll('.cf-turnstile').forEach(w => {
                        w.innerHTML = '';
                        w.removeAttribute('data-ts-rendered');
                    });
                }
            },
        });
    }
};
</script>

<template>
    <Head title="Lupa Password - Operator SaaS" />
    <ToastContainer />
    <section class="min-h-[calc(100vh-200px)] flex overflow-x-hidden">
        <div class="flex-1 grid grid-cols-1 lg:grid-cols-2 min-w-0">
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-700 to-fuchsia-700 flex items-center justify-center px-4 sm:px-6 py-16 lg:py-0 min-w-0">
                <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-purple-400/20 rounded-full blur-3xl"></div>

                <div class="relative text-center text-white max-w-sm">
                    <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl">
                        <i class="fas fa-key text-5xl"></i>
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold mb-3">Reset Password</h2>
                    <p class="text-indigo-100 leading-relaxed text-sm md:text-base">
                        Masukkan email Anda dan kami akan kirimkan link untuk membuat password baru.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
                <div class="w-full max-w-md">
                    <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg">
                        <div class="text-center mb-6">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ isResetMode ? 'Buat Password Baru' : 'Lupa Password' }}
                            </h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ isResetMode ? 'Masukkan password baru untuk akun Operator SaaS Anda.' : 'Masukkan email akun Anda untuk menerima link reset.' }}
                            </p>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <FormErrorSummary :errors="form.errors" title="Gagal — periksa isian berikut:" />
                            <div v-if="!isResetMode">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400 text-sm"></i>
                                    </div>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="admin@contoh.com"
                                        required
                                        :class="['w-full pl-10 pr-4 py-2.5 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-colors', form.errors.email ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500' : 'border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"
                                    />
                                </div>
                                <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                            </div>

                            <template v-else>
                                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 p-3 text-sm">
                                    <p class="text-indigo-700 dark:text-indigo-300"><i class="fas fa-info-circle mr-1.5"></i>Email: <strong>{{ props.email }}</strong></p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password Baru <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-gray-400 text-sm"></i>
                                        </div>
                                        <input
                                            v-model="form.password"
                                            type="password"
                                            placeholder="Minimal 8 karakter"
                                            required
                                            :class="['w-full pl-10 pr-4 py-2.5 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-colors', form.errors.password ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500' : 'border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"
                                        />
                                    </div>
                                    <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-gray-400 text-sm"></i>
                                        </div>
                                        <input
                                            v-model="form.password_confirmation"
                                            type="password"
                                            placeholder="Ulangi password baru"
                                            required
                                            :class="['w-full pl-10 pr-4 py-2.5 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-colors', form.errors.password_confirmation ? 'border-red-400 focus:ring-red-500/30 focus:border-red-500' : 'border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500']"
                                        />
                                    </div>
                                </div>
                            </template>

                            <button
                                type="submit"
                                :disabled="form.processing || !turnstileSolved"
                                class="w-full py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center disabled:opacity-50"
                            >
                                <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                <i v-else :class="isResetMode ? 'fas fa-check mr-2' : 'fas fa-paper-plane mr-2'"></i>
                                {{ form.processing ? 'Memproses...' : (!turnstileSolved ? 'Tunggu verifikasi captcha...' : (isResetMode ? 'Reset Password' : 'Kirim Link Reset')) }}
                            </button>

                            <!-- Cloudflare Turnstile captcha -->
                            <div v-if="siteKey" class="cf-turnstile" :data-sitekey="siteKey" data-callback="onTurnstileSuccess" data-expired-callback="onTurnstileExpired"></div>
                            <p v-if="form.errors['cf-turnstile-response']" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ form.errors['cf-turnstile-response'] }}
                            </p>
                        </form>

                        <div class="mt-6 text-center text-sm">
                            <Link href="/login-operator-saas" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                <i class="fas fa-arrow-left mr-1"></i>Kembali ke halaman login
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
