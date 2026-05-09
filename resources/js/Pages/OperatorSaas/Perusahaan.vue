<script setup>
import OperatorSaasLayout from '@/Layouts/OperatorSaasLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: OperatorSaasLayout });
</script>

<template>
  <div>
    <Head title="Perusahaan | Operator SaaS" />

    <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Perusahaan</h2>
      <button class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
        <i class="fas fa-plus mr-1.5"></i> Tambah Perusahaan
      </button>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
      <div class="p-6">
        <p class="text-gray-500 dark:text-gray-400 text-center py-12">
          <i class="fas fa-building text-4xl mb-3 block"></i>
          Belum ada data perusahaan.
        </p>
      </div>
    </div>
    </div>
  </div>
</template>
