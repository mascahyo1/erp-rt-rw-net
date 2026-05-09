<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });
</script>

<template>
  <div>
    <!-- Header -->
    <section class="py-16 md:py-20 bg-gradient-to-br from-sky-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-950 dark:to-indigo-950/30 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white">Hubungi Kami</h1>
        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          Punya pertanyaan atau butuh bantuan? Tim kami siap membantu Anda.
        </p>
      </div>
    </section>

    <!-- Contact Content -->
    <section class="py-16 bg-white dark:bg-gray-950 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-10">

          <!-- Info Cards -->
          <div class="space-y-6">
            <div class="flex items-start gap-4 p-5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
              <div class="w-12 h-12 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center shrink-0">
                <i class="fas fa-phone text-sky-600 dark:text-sky-400"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">Telepon</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">+62 812-3456-7890</p>
              </div>
            </div>

            <div class="flex items-start gap-4 p-5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
              <div class="w-12 h-12 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                <i class="fas fa-envelope text-emerald-600 dark:text-emerald-400"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">Email</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">support@rtrwnet.id</p>
              </div>
            </div>

            <div class="flex items-start gap-4 p-5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
              <div class="w-12 h-12 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center shrink-0">
                <i class="fas fa-location-dot text-violet-600 dark:text-violet-400"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">Alamat</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Jl. Teknologi No. 10, Jakarta Selatan, DKI Jakarta 12950</p>
              </div>
            </div>

            <div class="flex items-start gap-4 p-5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
              <div class="w-12 h-12 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                <i class="fas fa-clock text-amber-600 dark:text-amber-400"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">Jam Operasional</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Senin — Jumat: 08:00 — 20:00 WIB</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Sabtu: 09:00 — 15:00 WIB</p>
              </div>
            </div>
          </div>

          <!-- Form (static display) -->
          <div class="lg:col-span-2 p-8 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Kirim Pesan</h2>
            <form class="space-y-5" @submit.prevent="">
              <div class="grid sm:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nama Lengkap</label>
                  <input type="text" placeholder="Nama Anda" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                  <input type="email" placeholder="email@contoh.com" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Subjek</label>
                <input type="text" placeholder="Subjek pesan" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Pesan</label>
                <textarea rows="5" placeholder="Tulis pesan Anda..." class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors resize-none"></textarea>
              </div>
              <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all">
                <i class="fas fa-paper-plane mr-2"></i> Kirim Pesan
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>