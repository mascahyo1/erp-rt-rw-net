<script setup>
import { ref } from "vue";
import LandingLayout from "@/Layouts/LandingLayout.vue";

defineOptions({ layout: LandingLayout });

const activeTab = ref("login");
</script>

<template>
  <div>
    <!-- Hero + Form Split -->
    <section class="min-h-[calc(100vh-200px)] flex">
      <div class="flex-1 grid lg:grid-cols-2">
        <!-- Left: Hero Illustration -->
        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-700 flex items-center justify-center px-6 py-16 lg:py-0 order-1 lg:order-1">
          <!-- Decorative blobs -->
          <div class="absolute -top-32 -right-32 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
          <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-teal-400/20 rounded-full blur-3xl"></div>
          <div class="absolute top-1/2 left-1/4 w-40 h-40 bg-emerald-300/20 rounded-full blur-2xl"></div>

          <div class="relative text-center text-white max-w-sm">
            <!-- Illustration icon -->
            <div class="w-24 h-24 mx-auto rounded-3xl bg-white/15 backdrop-blur-sm flex items-center justify-center mb-6 shadow-2xl">
              <i class="fas fa-user text-5xl"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold mb-3">Pelanggan</h2>
            <p class="text-emerald-100 leading-relaxed text-sm md:text-base">
              Cek informasi tagihan, riwayat pembayaran, dan status layanan internet Anda kapan saja.
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
        <div class="flex items-center justify-center px-4 py-16 bg-white dark:bg-gray-950 transition-colors order-2">
          <div class="w-full max-w-md">
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg">
              <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pelanggan</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Login atau daftar untuk melihat informasi tagihan Anda.
                </p>
              </div>

              <!-- Tab Toggle -->
              <div class="flex mb-6 bg-gray-200 dark:bg-gray-800 rounded-lg p-1">
                <button
                  @click="activeTab = 'login'"
                  :class="[
                    'flex-1 py-2 text-sm font-medium rounded-md transition-all',
                    activeTab === 'login'
                      ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white',
                  ]"
                >
                  <i class="fas fa-sign-in-alt mr-1"></i> Login
                </button>
                <button
                  @click="activeTab = 'register'"
                  :class="[
                    'flex-1 py-2 text-sm font-medium rounded-md transition-all',
                    activeTab === 'register'
                      ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white',
                  ]"
                >
                  <i class="fas fa-user-plus mr-1"></i> Daftar
                </button>
              </div>

              <!-- Login Form -->
              <form v-if="activeTab === 'login'" class="space-y-4" @submit.prevent="">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <i class="fas fa-envelope text-gray-400 text-sm"></i>
                    </div>
                    <input
                      type="email"
                      placeholder="pelanggan@email.com"
                      class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
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
                      class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                    />
                  </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                  <label class="flex items-center gap-2 text-gray-600 dark:text-gray-400 cursor-pointer">
                    <input type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-emerald-600 focus:ring-emerald-500" />
                    <span>Ingat saya</span>
                  </label>
                  <a href="#" class="text-emerald-600 dark:text-emerald-400 hover:underline">Lupa password?</a>
                </div>

                <button
                  type="submit"
                  class="w-full py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all"
                >
                  <i class="fas fa-sign-in-alt mr-2"></i> Masuk
                </button>
              </form>

              <!-- Register Form -->
              <form v-if="activeTab === 'register'" class="space-y-4" @submit.prevent="">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap</label>
                  <input
                    type="text"
                    placeholder="Nama Anda"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                  <input
                    type="email"
                    placeholder="pelanggan@email.com"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nomor HP</label>
                  <input
                    type="tel"
                    placeholder="0812-3456-7890"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                  <input
                    type="password"
                    placeholder="Minimal 8 karakter"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi Password</label>
                  <input
                    type="password"
                    placeholder="Ulangi password"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                  />
                </div>

                <button
                  type="submit"
                  class="w-full py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all"
                >
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
