<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });
</script>

<template>
  <div>
    <!-- Header -->
    <section class="py-16 md:py-20 bg-gradient-to-br from-sky-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-950 dark:to-indigo-950/30 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white">Syarat & Ketentuan</h1>
        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          Harap baca syarat dan ketentuan ini dengan saksama sebelum menggunakan layanan kami.
        </p>
      </div>
    </section>

    <!-- Content -->
    <section class="py-16 bg-white dark:bg-gray-950 transition-colors">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose dark:prose-invert max-w-none space-y-8 text-gray-700 dark:text-gray-300">

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">1. Penerimaan Persyaratan</h2>
            <p class="text-sm leading-relaxed">
              Dengan mengakses atau menggunakan layanan RT/RW Net ERP ("Layanan"), Anda setuju untuk terikat oleh Syarat dan Ketentuan ini.
              Jika Anda tidak setuju dengan bagian mana pun dari syarat dan ketentuan ini, Anda tidak boleh menggunakan Layanan kami.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">2. Definisi</h2>
            <ul class="list-disc list-inside text-sm leading-relaxed space-y-1">
              <li><strong>"Platform"</strong> adalah aplikasi ERP RT/RW Net berbasis web.</li>
              <li><strong>"Tenant"</strong> adalah perusahaan atau entitas yang menggunakan Platform.</li>
              <li><strong>"Pengguna"</strong> adalah individu yang memiliki akun dan mengakses Platform.</li>
              <li><strong>"Pelanggan"</strong> adalah pelanggan akhir dari Tenant (pelanggan RT/RW Net).</li>
            </ul>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">3. Akun dan Keamanan</h2>
            <p class="text-sm leading-relaxed mb-2">
              Anda bertanggung jawab untuk menjaga kerahasiaan informasi login akun Anda dan untuk semua aktivitas yang terjadi di bawah akun Anda.
              Anda setuju untuk segera memberi tahu kami tentang penggunaan tidak sah atas akun Anda atau pelanggaran keamanan lainnya.
            </p>
            <p class="text-sm leading-relaxed">
              Platform tidak akan bertanggung jawab atas kerugian apa pun yang timbul dari penggunaan akun Anda yang tidak sah.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">4. Penggunaan Layanan</h2>
            <p class="text-sm leading-relaxed">
              Anda setuju untuk menggunakan Layanan hanya untuk tujuan yang sah dan sesuai dengan ketentuan yang berlaku.
              Anda tidak diperbolehkan menyalahgunakan Layanan untuk aktivitas ilegal, penipuan, atau yang merugikan pihak lain.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">5. Harga dan Pembayaran</h2>
            <p class="text-sm leading-relaxed">
              Biaya berlangganan Layanan ditentukan berdasarkan paket yang dipilih. Semua pembayaran dilakukan di muka dan tidak dapat dikembalikan
              kecuali ditentukan lain. Platform berhak mengubah harga dengan pemberitahuan 30 hari sebelumnya.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">6. Kekayaan Intelektual</h2>
            <p class="text-sm leading-relaxed">
              Semua konten, kode, desain, dan materi dalam Platform adalah milik Platform atau pemberi lisensinya dan dilindungi oleh
              hukum kekayaan intelektual. Anda tidak diperbolehkan menyalin, memodifikasi, atau mendistribusikan konten Platform tanpa izin.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">7. Batasan Tanggung Jawab</h2>
            <p class="text-sm leading-relaxed">
              Platform disediakan "apa adanya" tanpa jaminan apa pun, tersurat maupun tersirat.
              Kami tidak bertanggung jawab atas kerugian langsung, tidak langsung, insidental, atau konsekuensial yang timbul dari penggunaan Layanan.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">8. Perubahan Ketentuan</h2>
            <p class="text-sm leading-relaxed">
              Kami berhak mengubah Syarat dan Ketentuan ini kapan saja. Perubahan akan diumumkan melalui Platform atau email.
              Penggunaan Layanan setelah perubahan berarti Anda menerima ketentuan yang diperbarui.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">9. Hukum yang Berlaku</h2>
            <p class="text-sm leading-relaxed">
              Syarat dan Ketentuan ini diatur oleh hukum Republik Indonesia. Setiap sengketa akan diselesaikan di pengadilan negeri
              yang berwenang di wilayah Jakarta Selatan.
            </p>
          </div>

          <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">10. Kontak</h2>
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