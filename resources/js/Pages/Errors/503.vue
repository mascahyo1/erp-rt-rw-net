<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  contact: Object,
});
</script>

<template>
  <div class="min-h-screen relative overflow-hidden bg-gradient-to-br from-orange-50 via-white to-yellow-50 dark:from-gray-950 dark:via-gray-950 dark:to-orange-950/20 transition-colors">
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-orange-300/15 dark:bg-orange-500/8 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-yellow-300/15 dark:bg-yellow-500/8 rounded-full blur-3xl"></div>

    <div class="relative min-h-screen flex items-center justify-center px-4 sm:px-8 py-12">
      <div class="w-full max-w-5xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 lg:gap-16 items-center">

          <!-- Left: Illustration + Status Code -->
          <div class="flex flex-col items-center md:items-end text-center md:text-right">
            <div class="relative mb-6">
              <div class="absolute inset-0 -m-6 border-2 border-dashed border-orange-200 dark:border-orange-800 rounded-full opacity-40" style="animation: spin 20s linear infinite;"></div>
              <div class="absolute inset-0 -m-3 border border-orange-100 dark:border-orange-900 rounded-full opacity-60"></div>
              <div class="relative w-28 h-28 sm:w-36 sm:h-36 rounded-full bg-gradient-to-br from-orange-400 to-amber-600 flex items-center justify-center shadow-2xl shadow-orange-500/30 dark:shadow-orange-700/20">
                <i class="fas fa-tools text-4xl sm:text-5xl text-white"></i>
              </div>
            </div>
            <div class="inline-block border-2 border-dashed border-orange-300 dark:border-orange-800 rounded-3xl px-6 sm:px-10 py-3 sm:py-5 bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm shadow-lg shadow-orange-100/50 dark:shadow-orange-900/20">
              <div class="text-xs sm:text-sm text-orange-600 dark:text-orange-400 font-semibold tracking-[0.3em] uppercase mb-1">Error</div>
              <span class="inline-block text-[5rem] sm:text-[7rem] lg:text-[9rem] font-black leading-tight bg-gradient-to-br from-orange-400 via-amber-500 to-yellow-500 bg-clip-text text-transparent tracking-tighter">
                503
              </span>
            </div>
          </div>

          <!-- Right: Content + Actions -->
          <div class="flex flex-col items-center md:items-start text-center md:text-left">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 dark:text-white leading-tight mb-3">
              Layanan
              <span class="bg-gradient-to-r from-orange-500 to-amber-600 bg-clip-text text-transparent">Tidak Tersedia</span>
            </h1>

            <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-8 max-w-sm">
              Server sedang dalam pemeliharaan. Silakan muat ulang halaman beberapa saat lagi atau hubungi tim dukungan kami.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto mb-10">
              <Link
                href="/"
                class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all"
              >
                <i class="fas fa-home mr-2"></i> Kembali ke Beranda
              </Link>
              <button
                onclick="location.reload()"
                class="inline-flex items-center justify-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl border border-gray-300 dark:border-gray-700 hover:border-orange-500 dark:hover:border-orange-500 hover:text-orange-600 dark:hover:text-orange-400 transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
              >
                <i class="fas fa-sync-alt mr-2"></i> Muat Ulang
              </button>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-800 pt-6 w-full sm:w-auto">
              <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                <i class="fas fa-headset mr-2"></i> Hubungi Kami
              </p>
              <div class="flex flex-col sm:flex-row gap-3 text-sm">
                <a :href="'mailto:' + contact.email" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
                  <i class="fas fa-envelope text-orange-500"></i>
                  {{ contact.email }}
                </a>
                <a :href="'tel:' + contact.phone.replace(/[^0-9+]/g, '')" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
                  <i class="fas fa-phone text-orange-500"></i>
                  {{ contact.phone }}
                </a>
                <a :href="'https://wa.me/' + contact.whatsapp.replace(/[^0-9]/g, '')" target="_blank" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                  <i class="fab fa-whatsapp text-emerald-500"></i>
                  {{ contact.whatsapp }}
                </a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
