<script setup>
import { ref } from "vue";
import LandingLayout from "@/Layouts/LandingLayout.vue";

defineOptions({ layout: LandingLayout });

const activeTab = ref("login");
</script>

<template>
  <div>
    <section
      class="min-h-[calc(100vh-200px)] flex items-center justify-center py-16 bg-white dark:bg-gray-950 transition-colors"
    >
      <div class="w-full max-w-md px-4">
        <div
          class="rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-8 shadow-lg"
        >
          <!-- Icon + Title -->
          <div class="text-center mb-6">
            <div
              class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mb-4"
            >
              <i class="fas fa-user text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              Pelanggan
            </h1>
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
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >Email</label
              >
              <div class="relative">
                <div
                  class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                >
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
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >Password</label
              >
              <div class="relative">
                <div
                  class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                >
                  <i class="fas fa-lock text-gray-400 text-sm"></i>
                </div>
                <input
                  type="password"
                  placeholder="••••••••"
                  class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
                />
              </div>
            </div>

            <div class="flex items-center justify-between text-sm">
              <label
                class="flex items-center gap-2 text-gray-600 dark:text-gray-400 cursor-pointer"
              >
                <input
                  type="checkbox"
                  class="rounded border-gray-300 dark:border-gray-700 text-emerald-600 focus:ring-emerald-500"
                />
                <span>Ingat saya</span>
              </label>
              <a href="#" class="text-emerald-600 dark:text-emerald-400 hover:underline"
                >Lupa password?</a
              >
            </div>

            <button
              type="submit"
              class="w-full py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all"
            >
              <i class="fas fa-sign-in-alt mr-2"></i> Masuk
            </button>
          </form>

          <!-- Register Form -->
          <form
            v-if="activeTab === 'register'"
            class="space-y-4"
            @submit.prevent=""
          >
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >Nama Lengkap</label
              >
              <input
                type="text"
                placeholder="Nama Anda"
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
              />
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >Email</label
              >
              <input
                type="email"
                placeholder="pelanggan@email.com"
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
              />
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >Nomor HP</label
              >
              <input
                type="tel"
                placeholder="0812-3456-7890"
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
              />
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >Password</label
              >
              <input
                type="password"
                placeholder="Minimal 8 karakter"
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
              />
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >Konfirmasi Password</label
              >
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
    </section>
  </div>
</template>