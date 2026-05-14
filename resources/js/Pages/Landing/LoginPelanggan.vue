<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import CompanySearchInput from '@/Components/CompanySearchInput.vue';

defineOptions({ layout: LandingLayout });

const activeTab = ref('login');
const selectedCompany = ref(null);

const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const registerForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submitLogin = () => {
    loginForm.post('/login-pelanggan', {
        onFinish: () => loginForm.reset('password'),
    });
};
</script>

<template>
    <Head title="Login Pelanggan" />
    <div>
        <section class="min-h-[calc(100vh-200px)] flex">
            <div class="flex-1 grid lg:grid-cols-2">
                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-700 flex items-center justify-center px-6 py-16 lg:py-0">
                    <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-teal-400/20 rounded-full blur-3xl"></div>
                    <div class="relative text-center text-white max-w-sm">
                        <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl">
                            <i class="fas fa-user text-5xl"></i>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-bold mb-3">Pelanggan</h2>
                        <p class="text-emerald-100 leading-relaxed text-sm md:text-base">
                            Pantau paket internet, tagihan, dan riwayat pembayaran Anda.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
                    <div class="w-full max-w-md">
                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg">
                            <div class="flex mb-6 bg-gray-200 dark:bg-gray-800 rounded-lg p-1">
                                <button
                                    @click="activeTab = 'login'"
                                    :class="['flex-1 py-2 rounded-md text-sm font-medium transition-colors', activeTab === 'login' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white']"
                                >
                                    <i class="fas fa-sign-in-alt mr-1"></i> Masuk
                                </button>
                                <button
                                    @click="activeTab = 'register'"
                                    :class="['flex-1 py-2 rounded-md text-sm font-medium transition-colors', activeTab === 'register' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white']"
                                >
                                    <i class="fas fa-user-plus mr-1"></i> Daftar
                                </button>
                            </div>

                            <form v-if="activeTab === 'login'" class="space-y-4" @submit.prevent="submitLogin">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan</label>
                                    <CompanySearchInput v-model="selectedCompany" placeholder="Cari perusahaan Anda..." />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-envelope text-gray-400 text-sm"></i></div>
                                        <input v-model="loginForm.email" type="email" placeholder="pelanggan@email.com" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" />
                                    </div>
                                    <p v-if="loginForm.errors.email" class="text-red-500 text-xs mt-1">{{ loginForm.errors.email }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-lock text-gray-400 text-sm"></i></div>
                                        <input v-model="loginForm.password" type="password" placeholder="••••••••" class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" />
                                    </div>
                                    <p v-if="loginForm.errors.password" class="text-red-500 text-xs mt-1">{{ loginForm.errors.password }}</p>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <label class="flex items-center gap-2 text-gray-600 dark:text-gray-400 cursor-pointer">
                                        <input v-model="loginForm.remember" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-emerald-600 focus:ring-emerald-500" />
                                        <span>Ingat saya</span>
                                    </label>
                                </div>
                                <button type="submit" :disabled="loginForm.processing" class="w-full py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center disabled:opacity-50">
                                    <i v-if="loginForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                    <i v-else class="fas fa-sign-in-alt mr-2"></i>
                                    {{ loginForm.processing ? 'Memproses...' : 'Masuk' }}
                                </button>
                            </form>

                            <form v-if="activeTab === 'register'" class="space-y-4" @submit.prevent="">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan</label>
                                    <CompanySearchInput v-model="selectedCompany" placeholder="Cari perusahaan Anda..." />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap</label>
                                    <input v-model="registerForm.name" type="text" placeholder="Nama Anda" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                    <input v-model="registerForm.email" type="email" placeholder="pelanggan@email.com" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nomor HP</label>
                                    <input v-model="registerForm.phone" type="tel" placeholder="0812-3456-7890" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                                    <input v-model="registerForm.password" type="password" placeholder="Minimal 8 karakter" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password</label>
                                    <input v-model="registerForm.password_confirmation" type="password" placeholder="Ulangi password" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" />
                                </div>
                                <button type="button" class="w-full py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center">
                                    <i class="fas fa-user-plus mr-2"></i> Daftar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
