<script setup>
import { Link } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });

defineProps({
  sections: Array,
});
</script>

<template>
  <div>
    <!-- Hero -->
    <section class="relative overflow-hidden bg-gradient-to-br from-sky-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-950 dark:to-indigo-950/30 transition-colors">
      <!-- Background blobs -->
      <div class="absolute -top-40 -right-40 w-96 h-96 bg-sky-300/20 dark:bg-sky-500/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-indigo-300/20 dark:bg-indigo-500/10 rounded-full blur-3xl"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
        <div class="text-center max-w-3xl mx-auto">
          <!-- Icon -->
          <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center mb-6 shadow-lg shadow-sky-500/25">
            <i class="fas fa-lock text-white text-3xl"></i>
          </div>
          <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white mb-4">Kebijakan Privasi</h1>
          <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
            Privasi Anda penting bagi kami. Pelajari bagaimana kami mengumpulkan, menggunakan, dan melindungi data Anda.
          </p>
        </div>
      </div>
    </section>

    <!-- Breadcrumbs -->
    <div class="bg-white dark:bg-gray-950 pt-8 pb-8 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-1.5 text-sm">
          <Link href="/" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
            <i class="fas fa-home"></i>
          </Link>
          <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
          <Link href="/" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Home</Link>
          <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
          <span class="text-gray-900 dark:text-white font-medium">Kebijakan Privasi</span>
        </nav>
      </div>
    </div>

    <!-- Content -->
    <section class="pb-16 bg-white dark:bg-gray-950 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="space-y-6 text-gray-700 dark:text-gray-300 lg:columns-2 lg:gap-8 [&>*]:break-inside-avoid">

          <div v-for="(s, i) in sections" :key="i" class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">{{ i + 1 }}</span>
              {{ s.title }}
            </h2>
            <div v-html="s.body"></div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
