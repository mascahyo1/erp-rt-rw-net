<script setup>
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: OperatorSaasLayout });
</script>

<template>
  <div>
    <Head title="Login | Operator SaaS" />

    <div class="flex items-center justify-center min-h-[calc(100vh-12rem)]">
    <div class="w-full max-w-md">
      <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-8 shadow-lg">
        <div class="text-center mb-6">
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Masuk Operator SaaS</h1>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Masukkan kredensial operator Anda.
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
                placeholder="admin@rtrwnet.id"
                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors"
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
                class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-colors"
              />
            </div>
          </div>

          <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-gray-600 dark:text-gray-400 cursor-pointer">
              <input type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500" />
              <span>Ingat saya</span>
            </label>
            <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:underline">Lupa password?</a>
          </div>

          <Link
            href="/operator-saas/dashboard"
            class="w-full py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center"
          >
            <i class="fas fa-sign-in-alt mr-2"></i> Masuk
          </Link>
        </form>
      </div>
      </div>
    </div>
  </div>
</template>
