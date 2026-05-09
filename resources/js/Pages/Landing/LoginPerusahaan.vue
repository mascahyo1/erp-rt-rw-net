<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });
</script>

<template>
  <div>
    <!-- Hero + Form Split -->
    <section class="min-h-[calc(100vh-200px)] flex">
      <div class="flex-1 grid lg:grid-cols-2">
        <!-- Left: Hero Illustration -->
        <div class="relative overflow-hidden bg-gradient-to-br from-sky-600 via-sky-700 to-indigo-700 flex items-center justify-center px-6 py-16 lg:py-0">
          <!-- Decorative blobs -->
          <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
          <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-indigo-400/20 rounded-full blur-3xl"></div>
          <div class="absolute top-1/3 right-1/3 w-48 h-48 bg-sky-300/20 rounded-full blur-2xl"></div>

          <div class="relative text-center text-white max-w-sm">
            <!-- Illustration icon -->
            <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl">
              <i class="fas fa-building text-5xl"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold mb-3">Perusahaan</h2>
            <p class="text-sky-100 leading-relaxed text-sm md:text-base">
              Kelola pelanggan, tagihan, pembayaran, dan insentif karyawan dalam satu platform terintegrasi.
            </p>

            <!-- Feature dots -->
            <div class="mt-8 flex justify-center gap-3">
              <span class="w-2.5 h-2.5 rounded-full bg-white/60"></span>
              <span class="w-2.5 h-2.5 rounded-full bg-white/40"></span>
              <span class="w-2.5 h-2.5 rounded-full bg-white/20"></span>
            </div>
          </div>
        </div>

        <!-- Right: Form -->
        <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors">
          <div class="w-full max-w-md">
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg">
              <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Masuk Perusahaan</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Masuk ke dashboard perusahaan Anda.
                </p>
              </div>

              <form class="space-y-4" @submit.prevent="">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <i class="fas fa-envelope text-gray-400 text-sm"></i>
                    </div>
                    <input
                      type="email"
                      placeholder="perusahaan@contoh.com"
                      class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <i class="fas fa-lock text-gray-400 text-sm"></i>
                    </div>
                    <input
                      type="password"
                      placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
                      class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"
                    />
                  </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                  <label class="flex items-center gap-2 text-gray-600 dark:text-gray-400 cursor-pointer">
                    <input type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-sky-600 focus:ring-sky-500" />
                    <span>Ingat saya</span>
                  </label>
                  <a href="#" class="text-sky-600 dark:text-sky-400 hover:underline">Lupa password?</a>
                </div>

                <button
                  type="submit"
                  class="w-full py-2.5 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all"
                >
                  <i class="fas fa-sign-in-alt mr-2"></i> Masuk
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
