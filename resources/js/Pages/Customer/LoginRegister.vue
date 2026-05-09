<script setup>
import { Link } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';
defineOptions({ layout: LandingLayout });
const perusahaanList = ['PT Net Sejahtera', 'CV Digital Media', 'UD Net Mandiri', 'PT Teknologi Nusantara', 'CV Media Connect'];
</script>

<template>
  <div>
    <section class="min-h-[calc(100vh-200px)] flex">
      <div class="flex-1 grid lg:grid-cols-2">
        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-700 flex items-center justify-center px-6 py-16 lg:py-0">
          <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
          <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-teal-400/20 rounded-full blur-3xl"></div>
          <div class="relative text-center text-white max-w-sm">
            <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl"><i class="fas fa-user text-5xl"></i></div>
            <h2 class="text-2xl md:text-3xl font-bold mb-3">Pelanggan</h2>
            <p class="text-emerald-100 leading-relaxed text-sm md:text-base">Akses tagihan, paket, dan riwayat pembayaran Anda dengan mudah.</p>
          </div>
        </div>
        <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
          <div class="w-full max-w-md">
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg">
              <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Masuk / Daftar</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Masuk atau daftar untuk mengakses layanan Anda.</p>
              </div>
              <form class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap</label>
                  <div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-user text-gray-400 text-sm"></i></div><input type="text" placeholder="Nama Anda" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" /></div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                  <div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-envelope text-gray-400 text-sm"></i></div><input type="email" placeholder="email@contoh.com" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" /></div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                  <div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-lock text-gray-400 text-sm"></i></div><input type="password" placeholder="Minimal 8 karakter" class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" /></div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Perusahaan <span class="text-gray-400 text-xs font-normal">(pilih perusahaan Anda)</span></label>
                  <select class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                    <option value="">— Pilih Perusahaan —</option>
                    <option v-for="p in perusahaanList" :key="p" :value="p">{{ p }}</option>
                  </select>
                </div>
                <Link href="/customer/dashboard" class="w-full py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center">
                  <i class="fas fa-sign-in-alt mr-2"></i> Masuk / Daftar
                </Link>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
