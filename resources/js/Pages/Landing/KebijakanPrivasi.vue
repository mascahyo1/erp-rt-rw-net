<script setup>
import { Link } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });

defineProps({
  emailPrivacy: String,
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

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">1</span>
              Informasi yang Kami Kumpulkan
            </h2>
            <p class="text-sm leading-relaxed mb-2">Kami mengumpulkan informasi berikut saat Anda menggunakan Platform:</p>
            <ul class="list-disc list-inside text-sm leading-relaxed space-y-1">
              <li><strong>Informasi Akun:</strong> Nama, email, nomor telepon, alamat.</li>
              <li><strong>Informasi Perusahaan:</strong> Nama perusahaan, logo, alamat bisnis.</li>
              <li><strong>Data Transaksi:</strong> Data pelanggan, tagihan, pembayaran, insentif.</li>
              <li><strong>Data Log:</strong> Alamat IP, jenis browser, halaman yang diakses, waktu akses.</li>
            </ul>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">2</span>
              Penggunaan Informasi
            </h2>
            <p class="text-sm leading-relaxed mb-2">Informasi yang kami kumpulkan digunakan untuk:</p>
            <ul class="list-disc list-inside text-sm leading-relaxed space-y-1">
              <li>Menyediakan dan memelihara Layanan.</li>
              <li>Memproses data bisnis Anda (pelanggan, tagihan, pembayaran).</li>
              <li>Mengirim pemberitahuan terkait akun dan layanan.</li>
              <li>Meningkatkan kualitas Layanan berdasarkan analisis penggunaan.</li>
              <li>Mencegah aktivitas penipuan dan menjaga keamanan Platform.</li>
            </ul>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">3</span>
              Penyimpanan Data
            </h2>
            <p class="text-sm leading-relaxed">
              Data Anda disimpan di server yang aman dengan enkripsi. Kami menerapkan langkah-langkah keamanan teknis dan organisasi
              untuk melindungi data Anda dari akses, perubahan, pengungkapan, atau penghancuran yang tidak sah.
              File yang diunggah (seperti bukti transfer) disimpan di penyimpanan objek S3-compatible dengan signed URL.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">4</span>
              Berbagi Data
            </h2>
            <p class="text-sm leading-relaxed">
              Kami tidak menjual, memperdagangkan, atau mentransfer data pribadi Anda kepada pihak ketiga di luar yang diperlukan
              untuk menyediakan Layanan. Ini tidak termasuk pihak ketiga tepercaya yang membantu kami dalam mengoperasikan Platform,
              selama pihak tersebut setuju untuk menjaga kerahasiaan data.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">5</span>
              Hak Pengguna
            </h2>
            <p class="text-sm leading-relaxed mb-2">Anda memiliki hak untuk:</p>
            <ul class="list-disc list-inside text-sm leading-relaxed space-y-1">
              <li>Mengakses data pribadi Anda yang kami simpan.</li>
              <li>Memperbaiki data yang tidak akurat.</li>
              <li>Menghapus data Anda (subject to legal retention requirements).</li>
              <li>Menarik persetujuan pemrosesan data kapan saja.</li>
            </ul>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">6</span>
              Cookie
            </h2>
            <p class="text-sm leading-relaxed">
              Kami menggunakan cookie esensial untuk menjaga sesi login dan preferensi tema (dark/light mode).
              Kami tidak menggunakan cookie pelacakan pihak ketiga. Anda dapat menonaktifkan cookie di browser Anda,
              tetapi beberapa fitur Platform mungkin tidak berfungsi dengan baik.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">7</span>
              Perubahan Kebijakan
            </h2>
            <p class="text-sm leading-relaxed">
              Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan akan diumumkan melalui email
              atau pemberitahuan di Platform. Penggunaan Layanan setelah perubahan berarti Anda menerima kebijakan yang diperbarui.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 transition-colors">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 text-sm font-bold shrink-0">8</span>
              Kontak
            </h2>
            <p class="text-sm leading-relaxed">
              Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, silakan hubungi kami di
              <a :href="'mailto:' + emailPrivacy" class="text-sky-600 dark:text-sky-400 underline">{{ emailPrivacy }}</a>.
            </p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
