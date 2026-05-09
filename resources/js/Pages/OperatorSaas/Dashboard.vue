<script setup>
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: OperatorSaasLayout });
</script>

<template>
  <div>
    <Head title="Dashboard | Operator SaaS" />

    <div class="space-y-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
              <i class="fas fa-building text-indigo-600 dark:text-indigo-400"></i>
            </div>
          </div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">0</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Perusahaan</div>
        </div>

        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center">
              <i class="fas fa-user-tie text-sky-600 dark:text-sky-400"></i>
            </div>
          </div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">0</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Admin Perusahaan</div>
        </div>

        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
              <i class="fas fa-user-shield text-emerald-600 dark:text-emerald-400"></i>
            </div>
          </div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">0</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Admin SaaS</div>
        </div>

        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
              <i class="fas fa-user-tag text-amber-600 dark:text-amber-400"></i>
            </div>
          </div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">0</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Role</div>
        </div>
      </div>
    </div>
  </div>
</template>
