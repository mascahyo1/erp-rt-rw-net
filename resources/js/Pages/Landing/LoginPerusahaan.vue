<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });
</script>

<template>
  <div>
    <section class="min-h-[calc(100vh-200px)] flex items-center justify-center py-16 bg-white dark:bg-gray-950 transition-colors">
      <div class="w-full max-w-md px-4">
        <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg">
          <!-- Icon + Title -->
          <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center mb-4">
              <i class="fas fa-building text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Login Perusahaan</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Masuk ke dashboard perusahaan untuk mengelola bisnis RT/RW Net.
            </p>
          </div>

          <!-- Form -->
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
                  placeholder="••••••••"
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
    </section>
  </div>
</template>