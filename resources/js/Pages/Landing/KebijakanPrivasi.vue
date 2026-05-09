<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });
</script>

<template>
  <div>
    <!-- Header -->
    <section class="py-16 md:py-20 bg-gradient-to-br from-sky-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-950 dark:to-indigo-950/30 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white">Kebijakan Privasi</h1>
        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          Privasi Anda penting bagi kami. Pelajari bagaimana kami mengumpulkan, menggunakan, dan melindungi data Anda.
        </p>
      </div>
    </section>

    <!-- Content -->
    <section class="py-16 bg-white dark:bg-gray-950 transition-colors">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose dark:prose-invert max-w-none space-y-8 text-gray-700 dark:text-gray-300">

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">1. Informasi yang Kami Kumpulkan</h2>
            <p class="text-sm leading-relaxed mb-2">Kami mengumpulkan informasi berikut saat Anda menggunakan Platform:</p>
            <ul class="list-disc list-inside text-sm leading-relaxed space-y-1">
              <li><strong>Informasi Akun:</strong> Nama, email, nomor telepon, alamat.</li>
              <li><strong>Informasi Perusahaan:</strong> Nama perusahaan, logo, alamat bisnis.</li>
              <li><strong>Data Transaksi:</strong> Data pelanggan, tagihan, pembayaran, insentif.</li>
              <li><strong>Data Log:</strong> Alamat IP, jenis browser, halaman yang diakses, waktu akses.</li>
            </ul>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">2. Penggunaan Informasi</h2>
            <p class="text-sm leading-relaxed mb-2">Informasi yang kami kumpulkan digunakan untuk:</p>
            <ul class="list-disc list-inside text-sm leading-relaxed space-y-1">
              <li>Menyediakan dan memelihara Layanan.</li>
              <li>Memproses data bisnis Anda (pelanggan, tagihan, pembayaran).</li>
              <li>Mengirim pemberitahuan terkait akun dan layanan.</li>
              <li>Meningkatkan kualitas Layanan berdasarkan analisis penggunaan.</li>
              <li>Mencegah aktivitas penipuan dan menjaga keamanan Platform.</li>
            </ul>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">3. Penyimpanan Data</h2>
            <p class="text-sm leading-relaxed">
              Data Anda disimpan di server yang aman dengan enkripsi. Kami menerapkan langkah-langkah keamanan teknis dan organisasi
              untuk melindungi data Anda dari akses, perubahan, pengungkapan, atau penghancuran yang tidak sah.
              File yang diunggah (seperti bukti transfer) disimpan di penyimpanan objek S3-compatible dengan signed URL.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">4. Berbagi Data</h2>
            <p class="text-sm leading-relaxed">
              Kami tidak menjual, memperdagangkan, atau mentransfer data pribadi Anda kepada pihak ketiga di luar yang diperlukan
              untuk menyediakan Layanan. Ini tidak termasuk pihak ketiga tepercaya yang membantu kami dalam mengoperasikan Platform,
              selama pihak tersebut setuju untuk menjaga kerahasiaan data.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">5. Hak Pengguna</h2>
            <p class="text-sm leading-relaxed mb-2">Anda memiliki hak untuk:</p>
            <ul class="list-disc list-inside text-sm leading-relaxed space-y-1">
              <li>Mengakses data pribadi Anda yang kami simpan.</li>
              <li>Memperbaiki data yang tidak akurat.</li>
              <li>Menghapus data Anda (subject to legal retention requirements).</li>
              <li>Menarik persetujuan pemrosesan data kapan saja.</li>
            </ul>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">6. Cookie</h2>
            <p class="text-sm leading-relaxed">
              Kami menggunakan cookie esensial untuk menjaga sesi login dan preferensi tema (dark/light mode).
              Kami tidak menggunakan cookie pelacakan pihak ketiga. Anda dapat menonaktifkan cookie di browser Anda,
              tetapi beberapa fitur Platform mungkin tidak berfungsi dengan baik.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">7. Perubahan Kebijakan</h2>
            <p class="text-sm leading-relaxed">
              Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan akan diumumkan melalui email
              atau pemberitahuan di Platform. Penggunaan Layanan setelah perubahan berarti Anda menerima kebijakan yang diperbarui.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">8. Kontak</h2>
            <p class="text-sm leading-relaxed">
              Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, silakan hubungi kami di
              <a href="mailto:privacy@rtrwnet.id" class="text-sky-600 dark:text-sky-400 underline">privacy@rtrwnet.id</a>.
            </p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>