<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login-karyawan', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login Karyawan" />
    <section class="min-h-[calc(100vh-200px)] flex">
        <div class="flex-1 grid lg:grid-cols-2">
            <div class="relative overflow-hidden bg-gradient-to-br from-amber-600 via-orange-700 to-amber-900 flex items-center justify-center px-6 py-16 lg:py-0">
                <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-amber-400/20 rounded-full blur-3xl"></div>
                <div class="relative text-center text-white max-w-sm">
                    <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl"><i class="fas fa-user-tie text-5xl"></i></div>
                    <h2 class="text-2xl md:text-3xl font-bold mb-3">Karyawan</h2>
                    <p class="text-amber-100 leading-relaxed text-sm md:text-base">Akses tagihan, customer, dan insentif Anda sebagai karyawan.</p>
                </div>
            </div>
            <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
                <div class="w-full max-w-md">
                    <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg">
                        <div class="text-center mb-6"><h1 class="text-2xl font-bold text-gray-900 dark:text-white">Login Karyawan</h1><p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Masuk dengan akun karyawan Anda.</p></div>
                        <form class="space-y-4" @submit.prevent="submit">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-envelope text-gray-400 text-sm"></i></div>
                                    <input v-model="form.email" type="email" placeholder="email@perusahaan.id" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors" />
                                </div>
                                <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-lock text-gray-400 text-sm"></i></div>
                                    <input v-model="form.password" type="password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors" />
                                </div>
                                <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                            </div>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center disabled:opacity-50"
                            >
                                <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                <i v-else class="fas fa-sign-in-alt mr-2"></i>
                                {{ form.processing ? 'Memproses...' : 'Masuk' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
