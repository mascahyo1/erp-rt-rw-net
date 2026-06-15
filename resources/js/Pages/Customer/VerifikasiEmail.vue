<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import CompanySearchInput from '@/Components/CompanySearchInput.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

defineOptions({ layout: LandingLayout });

const props = defineProps({
    email: { type: String, default: null },
    companyId: { type: String, default: null },
});

const page = usePage();
const toast = useToast();
const selectedCompany = ref(null);

const siteKey = computed(() => page.props.turnstile_site_key || '');

const form = useForm({
    email: props.email || '',
    company_id: props.companyId || '',
    'cf-turnstile-response': '',
});

function onTurnstileSuccess(token) {
    form['cf-turnstile-response'] = token;
}
function onTurnstileExpired() {
    form['cf-turnstile-response'] = '';
}
onMounted(() => {
    window.onTurnstileSuccess = onTurnstileSuccess;
    window.onTurnstileExpired = onTurnstileExpired;
});
onBeforeUnmount(() => {
    delete window.onTurnstileSuccess;
    delete window.onTurnstileExpired;
});

const submitting = ref(false);

const submit = () => {
    if (!selectedCompany.value?.id) {
        toast.error('Pilih perusahaan terlebih dahulu.');
        return;
    }
    if (!form.email) {
        toast.error('Email wajib diisi.');
        return;
    }
    submitting.value = true;
    form.transform((data) => ({ ...data, company_id: selectedCompany.value.id }))
        .post('/kirim-ulang-verifikasi-pelanggan', {
            onSuccess: () => {
                toast.success('Link verifikasi sudah dikirim ke email Anda.');
                form.reset('email');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                if (firstError) toast.error(firstError);
            },
            onFinish: () => {
                submitting.value = false;
            },
        });
};
</script>

<template>
    <Head title="Verifikasi Email - Pelanggan" />
    <ToastContainer />
    <section class="min-h-[calc(100vh-200px)] flex">
        <div class="flex-1 grid lg:grid-cols-2">
            <!-- Left: branded hero -->
            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-700 flex items-center justify-center px-6 py-16 lg:py-0">
                <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-teal-400/20 rounded-full blur-3xl"></div>
                <div class="absolute top-1/3 right-1/3 w-48 h-48 bg-emerald-300/20 rounded-full blur-2xl"></div>

                <div class="relative text-center text-white max-w-sm">
                    <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl ring-1 ring-white/20">
                        <i class="fas fa-envelope-circle-check text-5xl"></i>
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold mb-3">Verifikasi Email</h2>
                    <p class="text-emerald-100 leading-relaxed text-sm md:text-base">
                        Kami kirim link verifikasi ke email Anda. Klik link tersebut untuk mengaktifkan akun.
                    </p>
                    <ul class="mt-8 space-y-2.5 text-left text-sm text-emerald-100 max-w-xs mx-auto">
                        <li class="flex items-center gap-2.5">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <i class="fas fa-check text-2xs"></i>
                            </span>
                            Cek inbox &amp; folder spam Anda
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <i class="fas fa-check text-2xs"></i>
                            </span>
                            Link berlaku 60 menit
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <i class="fas fa-check text-2xs"></i>
                            </span>
                            Belum terima? Kirim ulang di bawah
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right: form -->
            <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
                <div class="w-full max-w-md">
                    <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                                <i class="fas fa-envelope-open-text text-2xl text-emerald-600 dark:text-emerald-400"></i>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cek Email Anda</h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                Kami sudah mengirim link verifikasi ke email Anda. Klik link di email untuk mengaktifkan akun.
                            </p>
                        </div>

                        <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3 mb-5">
                            <p class="text-xs text-amber-700 dark:text-amber-300 flex items-start gap-2">
                                <i class="fas fa-info-circle mt-0.5"></i>
                                <span>
                                    Link verifikasi berlaku selama <strong>60 menit</strong>. Jika tidak ditemukan di inbox, cek folder
                                    <strong>spam</strong> atau <strong>promotions</strong>.
                                </span>
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-4" novalidate>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan <span class="text-red-500">*</span></label>
                                <CompanySearchInput v-model="selectedCompany" placeholder="Cari perusahaan Anda..." />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-sm text-gray-400"></i>
                                    </div>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="email@contoh.com"
                                        class="w-full pl-10 pr-4 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm outline-none transition-all duration-200 focus:ring-2 focus:shadow-md border-gray-300 dark:border-gray-700 focus:ring-emerald-500/30 focus:border-emerald-500"
                                    />
                                </div>
                                <p v-if="form.errors.email" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i>{{ form.errors.email }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="w-full py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:shadow-emerald-500/30 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
                                <i v-else class="fas fa-paper-plane mr-2"></i>
                                {{ submitting ? 'Mengirim...' : 'Kirim Ulang Link Verifikasi' }}
                            </button>

                            <div v-if="siteKey" class="cf-turnstile" :data-sitekey="siteKey" data-callback="onTurnstileSuccess" data-expired-callback="onTurnstileExpired"></div>
                            <p v-if="form.errors['cf-turnstile-response']" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ form.errors['cf-turnstile-response'] }}
                            </p>
                        </form>

                        <div class="text-center mt-6 pt-5 border-t border-gray-200 dark:border-gray-800">
                            <Link href="/login-pelanggan" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline font-medium">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Login
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
