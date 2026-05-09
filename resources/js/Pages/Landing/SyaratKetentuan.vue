<script setup>
import { Link } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });
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
            <i class="fas fa-file-contract text-white text-3xl"></i>
          </div>
          <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white mb-4">Syarat & Ketentuan</h1>
          <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
            Harap baca syarat dan ketentuan ini dengan saksama sebelum menggunakan layanan kami.
          </p>
        </div>
      </div>
    </section>

    <!-- Breadcrumbs -->
    <div class="bg-white dark:bg-gray-950 transition-colors pt-8 pb-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-1.5 text-sm">
          <Link href="/" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
            <i class="fas fa-home"></i>
          </Link>
          <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
          <Link href="/" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Home</Link>
          <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
          <span class="text-gray-900 dark:text-white font-medium">Syarat & Ketentuan</span>
        </nav>
      </div>
    </div>

    <!-- Content -->
    <section class="pb-16 bg-white dark:bg-gray-950 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="space-y-6 text-gray-700 dark:text-gray-300 lg:columns-2 lg:gap-8 [&>*]:break-inside-avoid">

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">1</span>
              Penerimaan Persyaratan
            </h2>
            <p class="text-sm leading-relaxed">
              Dengan mengakses atau menggunakan layanan RT/RW Net ERP ("Layanan"), Anda setuju untuk terikat oleh Syarat dan Ketentuan ini.
              Jika Anda tidak setuju dengan bagian mana pun dari syarat dan ketentuan ini, Anda tidak boleh menggunakan Layanan kami.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">2</span>
              Definisi
            </h2>
            <ul class="list-disc list-inside text-sm leading-relaxed space-y-1">
              <li><strong>"Platform"</strong> adalah aplikasi ERP RT/RW Net berbasis web.</li>
              <li><strong>"Tenant"</strong> adalah perusahaan atau entitas yang menggunakan Platform.</li>
              <li><strong>"Pengguna"</strong> adalah individu yang memiliki akun dan mengakses Platform.</li>
              <li><strong>"Pelanggan"</strong> adalah pelanggan akhir dari Tenant (pelanggan RT/RW Net).</li>
            </ul>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">3</span>
              Akun dan Keamanan
            </h2>
            <p class="text-sm leading-relaxed mb-2">
              Anda bertanggung jawab untuk menjaga kerahasiaan informasi login akun Anda dan untuk semua aktivitas yang terjadi di bawah akun Anda.
              Anda setuju untuk segera memberi tahu kami tentang penggunaan tidak sah atas akun Anda atau pelanggaran keamanan lainnya.
            </p>
            <p class="text-sm leading-relaxed">
              Platform tidak akan bertanggung jawab atas kerugian apa pun yang timbul dari penggunaan akun Anda yang tidak sah.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">4</span>
              Penggunaan Layanan
            </h2>
            <p class="text-sm leading-relaxed">
              Anda setuju untuk menggunakan Layanan hanya untuk tujuan yang sah dan sesuai dengan ketentuan yang berlaku.
              Anda tidak diperbolehkan menyalahgunakan Layanan untuk aktivitas ilegal, penipuan, atau yang merugikan pihak lain.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">5</span>
              Harga dan Pembayaran
            </h2>
            <p class="text-sm leading-relaxed">
              Biaya berlangganan Layanan ditentukan berdasarkan paket yang dipilih. Semua pembayaran dilakukan di muka dan tidak dapat dikembalikan
              kecuali ditentukan lain. Platform berhak mengubah harga dengan pemberitahuan 30 hari sebelumnya.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">6</span>
              Kekayaan Intelektual
            </h2>
            <p class="text-sm leading-relaxed">
              Semua konten, kode, desain, dan materi dalam Platform adalah milik Platform atau pemberi lisensinya dan dilindungi oleh
              hukum kekayaan intelektual. Anda tidak diperbolehkan menyalin, memodifikasi, atau mendistribusikan konten Platform tanpa izin.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">7</span>
              Batasan Tanggung Jawab
            </h2>
            <p class="text-sm leading-relaxed">
              Platform disediakan "apa adanya" tanpa jaminan apa pun, tersurat maupun tersirat.
              Kami tidak bertanggung jawab atas kerugian langsung, tidak langsung, insidental, atau konsekuensial yang timbul dari penggunaan Layanan.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">8</span>
              Perubahan Ketentuan
            </h2>
            <p class="text-sm leading-relaxed">
              Kami berhak mengubah Syarat dan Ketentuan ini kapan saja. Perubahan akan diumumkan melalui Platform atau email.
              Penggunaan Layanan setelah perubahan berarti Anda menerima ketentuan yang diperbarui.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">9</span>
              Hukum yang Berlaku
            </h2>
            <p class="text-sm leading-relaxed">
              Syarat dan Ketentuan ini diatur oleh hukum Republik Indonesia. Setiap sengketa akan diselesaikan di pengadilan negeri
              yang berwenang di wilayah Jakarta Selatan.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">10</span>
              Kontak
            </h2>
            <p class="text-sm leading-relaxed">
              Jika Anda memiliki pertanyaan tentang Syarat dan Ketentuan ini, silakan hubungi kami di
              <a href="mailto:legal@rtrwnet.id" class="text-sky-600 dark:text-sky-400 underline">legal@rtrwnet.id</a>.
            </p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
