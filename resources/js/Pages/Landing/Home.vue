<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';

defineOptions({ layout: LandingLayout });

// ========================
// Scroll Reveal (IntersectionObserver)
// ========================
const revealElements = ref([]);
let revealObserver = null;

onMounted(() => {
  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );
  document.querySelectorAll('.reveal').forEach((el) => revealObserver.observe(el));

  // Animated counters
  const counterObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.4 }
  );
  document.querySelectorAll('[data-counter]').forEach((el) => counterObserver.observe(el));
});

onUnmounted(() => {
  revealObserver?.disconnect();
});

// ========================
// Animated Counters
// ========================
function animateCounter(el) {
  const target = parseInt(el.dataset.counter, 10);
  const suffix = el.dataset.suffix || '';
  const duration = 1800;
  const startTime = performance.now();
  function tick(now) {
    const elapsed = now - startTime;
    const progress = Math.min(elapsed / duration, 1);
    // ease-out cubic
    const eased = 1 - Math.pow(1 - progress, 3);
    const value = Math.floor(eased * target);
    el.textContent = value.toLocaleString('id-ID') + suffix;
    if (progress < 1) requestAnimationFrame(tick);
    else el.textContent = target.toLocaleString('id-ID') + suffix;
  }
  requestAnimationFrame(tick);
}

// ========================
// Feature data
// ========================
const features = [
  { icon: 'fas fa-users', title: 'Manajemen Pelanggan', desc: 'CRUD pelanggan lengkap, status aktif/nonaktif, riwayat paket, dan koordinat GPS.', gradient: 'bg-linear-to-br from-sky-500 to-indigo-600' },
  { icon: 'fas fa-file-invoice', title: 'Penagihan Otomatis', desc: 'Generate invoice otomatis tiap bulan. Tracking status: belum bayar, menunggu konfirmasi, lunas, kadaluarsa.', gradient: 'bg-linear-to-br from-emerald-500 to-teal-600' },
  { icon: 'fas fa-hand-holding-usd', title: 'Pembayaran Tunai & Non-Tunai', desc: 'Input pembayaran tunai oleh kolektor dan upload bukti transfer non-tunai. Semua tercatat rapi.', gradient: 'bg-linear-to-br from-amber-500 to-orange-600' },
  { icon: 'fas fa-coins', title: 'Insentif Karyawan', desc: 'Kalkulasi otomatis insentif kolektor dari pembayaran tunai & non-tunai. Riwayat lengkap & approval.', gradient: 'bg-linear-to-br from-pink-500 to-rose-600' },
  { icon: 'fas fa-chart-line', title: 'Laporan & Dashboard', desc: 'Laporan harian, mingguan, bulanan, tahunan. Dashboard real-time dengan notifikasi via WebSocket.', gradient: 'bg-linear-to-br from-violet-500 to-purple-600' },
  { icon: 'fas fa-building-shield', title: 'Multi-Perusahaan', desc: 'Kelola banyak perusahaan RT/RW Net dalam satu sistem. Data terisolasi, branding per tenant.', gradient: 'bg-linear-to-br from-cyan-500 to-blue-600' },
];

// ========================
// FAQ Accordion
// ========================
const openFaq = ref(0);
function toggleFaq(i) {
  openFaq.value = openFaq.value === i ? -1 : i;
}

const faqs = [
  {
    q: 'Apakah ada masa trial gratis?',
    a: 'Ya, kami menyediakan masa trial gratis 30 hari untuk perusahaan baru. Anda bisa mencoba semua fitur Premium tanpa biaya. Tidak perlu kartu kredit untuk daftar.',
  },
  {
    q: 'Berapa biaya langganan ERP RT/RW Net?',
    a: 'Biaya langganan bervariasi tergantung jumlah pelanggan aktif. Tersedia paket Starter, Professional, dan Enterprise. Hubungi tim sales untuk penawaran custom.',
  },
  {
    q: 'Apakah data saya aman?',
    a: 'Sangat aman. Data terisolasi per perusahaan (multi-tenant), backup harian otomatis, dan menggunakan enkripsi end-to-end. Server di-host di data center tier-3 Indonesia.',
  },
  {
    q: 'Bagaimana cara migrasi data dari sistem lama?',
    a: 'Tim kami siap membantu migrasi data dari Excel, sistem lama, atau aplikasi pencatatan lainnya. Impor massal via CSV/Excel — pelanggan, tagihan, dan paket sekaligus.',
  },
  {
    q: 'Apakah bisa diakses dari HP?',
    a: 'Tentu! Portal Pelanggan, Karyawan, dan Admin bisa diakses dari smartphone. Tampilan responsif dan ringan untuk semua device.',
  },
  {
    q: 'Bagaimana jika butuh bantuan teknis?',
    a: 'Tim dukungan kami siap via WhatsApp, email, dan telepon Senin–Sabtu 08.00–20.00 WIB. Paket Enterprise mendapat dedicated account manager.',
  },
];

const testimonials = [
  {
    name: 'Budi Santoso',
    role: 'Pemilik — Net Sejahtera Abadi',
    avatar: 'BS',
    color: 'from-sky-500 to-indigo-600',
    quote: 'Sebelum pakai ERP ini, kami rekap tagihan di Excel dan sering salah hitung. Sekarang semua otomatis, kolektor lebih produktif, dan pelanggan lebih senang.',
  },
  {
    name: 'Siti Aminah',
    role: 'Admin — Angkasa Netindo',
    avatar: 'SA',
    color: 'from-emerald-500 to-teal-600',
    quote: 'Dashboard real-time-nya sangat membantu. Saya bisa lihat status pembayaran hari ini, minggu ini, atau bulan ini dalam satu klik. Tidak perlu lagi bikin laporan manual.',
  },
  {
    name: 'Andi Wijaya',
    role: 'Kolektor — Jaringan Prima',
    avatar: 'AW',
    color: 'from-amber-500 to-orange-600',
    quote: 'Saya bisa input pembayaran langsung dari HP saat di lapangan. Insentif langsung terhitung dan transparan. Gaji tidak pernah salah lagi.',
  },
];

// ========================
// Ripple Effect
// ========================
function handleRipple(e) {
  const btn = e.currentTarget;
  const circle = document.createElement('span');
  const diameter = Math.max(btn.clientWidth, btn.clientHeight);
  const radius = diameter / 2;
  const rect = btn.getBoundingClientRect();
  circle.style.width = circle.style.height = `${diameter}px`;
  circle.style.left = `${e.clientX - rect.left - radius}px`;
  circle.style.top = `${e.clientY - rect.top - radius}px`;
  circle.classList.add('ripple');
  const existing = btn.getElementsByClassName('ripple')[0];
  if (existing) existing.remove();
  btn.appendChild(circle);
}
</script>

<template>
  <div>
    <!-- HERO -->
    <section class="relative overflow-hidden bg-linear-to-br from-sky-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-950 dark:to-indigo-950/30 transition-colors">
      <!-- Background blobs -->
      <div class="absolute -top-40 -right-40 w-96 h-96 bg-sky-300/20 dark:bg-sky-500/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-indigo-300/20 dark:bg-indigo-500/10 rounded-full blur-3xl"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-60 h-60 bg-violet-300/10 dark:bg-violet-500/5 rounded-full blur-2xl"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <!-- Left: text -->
          <div class="text-center lg:text-left">
            <!-- Badge -->
            <div class="reveal inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/70 dark:bg-gray-800/70 border border-sky-200 dark:border-sky-800 backdrop-blur-sm mb-6">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
              </span>
              <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Dipercaya 500+ perusahaan RT/RW Net</span>
            </div>

            <h1 class="reveal text-4xl sm:text-5xl md:text-6xl font-extrabold text-gray-900 dark:text-white leading-tight tracking-tight" style="transition-delay: 60ms">
              ERP Modern untuk
              <span class="bg-linear-to-r from-sky-500 to-indigo-600 bg-clip-text text-transparent">
                RT/RW Net
              </span>
            </h1>
            <p class="reveal mt-6 text-lg text-gray-600 dark:text-gray-400 leading-relaxed" style="transition-delay: 120ms">
              Kelola pelanggan, tagihan, pembayaran, dan insentif karyawan dalam satu platform.
              Dibangun khusus untuk bisnis RT/RW Net skala kecil hingga besar.
            </p>
            <div class="reveal mt-10 flex flex-col sm:flex-row gap-3 lg:justify-start justify-center" style="transition-delay: 180ms">
              <Link href="/login-perusahaan" @click="handleRipple" class="ripple-btn inline-flex items-center justify-center px-6 py-3 bg-linear-to-r from-sky-500 to-indigo-600 text-white font-semibold rounded-xl shadow-lg shadow-sky-500/30 hover:shadow-xl hover:shadow-sky-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all">
                <i class="fas fa-rocket mr-2"></i> Mulai Sekarang
              </Link>
              <Link href="/tentang-kami" class="inline-flex items-center justify-center px-6 py-3 bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm text-gray-700 dark:text-gray-300 font-semibold rounded-xl border border-gray-300 dark:border-gray-700 hover:border-sky-500 dark:hover:border-sky-500 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">
                <i class="fas fa-info-circle mr-2"></i> Pelajari Lebih Lanjut
              </Link>
            </div>
            <!-- Trust microcopy -->
            <div class="reveal mt-8 flex flex-wrap items-center gap-x-6 gap-y-2 lg:justify-start justify-center text-sm text-gray-500 dark:text-gray-400" style="transition-delay: 240ms">
              <span class="flex items-center gap-1.5"><i class="fas fa-check text-emerald-500"></i> Free trial 30 hari</span>
              <span class="flex items-center gap-1.5"><i class="fas fa-check text-emerald-500"></i> Tanpa kartu kredit</span>
              <span class="flex items-center gap-1.5"><i class="fas fa-check text-emerald-500"></i> Setup 5 menit</span>
            </div>
          </div>

          <!-- Right: dashboard mockup -->
          <div class="reveal hidden lg:block" style="transition-delay: 200ms">
            <div class="relative">
              <!-- Main card -->
              <div class="relative rounded-2xl bg-white dark:bg-gray-900 shadow-2xl shadow-sky-500/10 dark:shadow-sky-500/5 border border-gray-200 dark:border-gray-800 p-6 hover:scale-[1.01] transition-transform duration-500">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Pendapatan Bulan Ini</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp 12.450.000</p>
                  </div>
                  <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">
                    <i class="fas fa-arrow-up text-[10px]"></i> 12,5%
                  </span>
                </div>
                <!-- Mini bar chart -->
                <div class="flex items-end gap-1.5 h-24 mt-4">
                  <div v-for="(h, i) in [40, 65, 50, 80, 60, 90, 75]" :key="i" :style="{ height: h + '%' }"
                    class="flex-1 rounded-t bg-linear-to-t from-sky-500 to-indigo-500 opacity-80 hover:opacity-100 transition-opacity"></div>
                </div>
                <div class="flex justify-between text-[10px] text-gray-400 mt-2">
                  <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                </div>
                <!-- Mini stats row -->
                <div class="mt-5 grid grid-cols-3 gap-2">
                  <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
                    <i class="fas fa-users text-sky-500 text-xs"></i>
                    <p class="text-base font-bold text-gray-900 dark:text-white mt-1">1.234</p>
                    <p class="text-[10px] text-gray-500">Pelanggan</p>
                  </div>
                  <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
                    <i class="fas fa-file-invoice text-emerald-500 text-xs"></i>
                    <p class="text-base font-bold text-gray-900 dark:text-white mt-1">987</p>
                    <p class="text-[10px] text-gray-500">Lunas</p>
                  </div>
                  <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
                    <i class="fas fa-clock text-amber-500 text-xs"></i>
                    <p class="text-base font-bold text-gray-900 dark:text-white mt-1">42</p>
                    <p class="text-[10px] text-gray-500">Pending</p>
                  </div>
                </div>
              </div>
              <!-- Floating card 1 -->
              <div class="absolute -top-4 -left-4 bg-emerald-500 text-white rounded-xl px-3 py-2 shadow-lg shadow-emerald-500/30 flex items-center gap-2 text-xs font-semibold animate-float-slow">
                <i class="fas fa-check-circle"></i> Pembayaran diterima
              </div>
              <!-- Floating card 2 -->
              <div class="absolute -bottom-4 -right-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 shadow-lg flex items-center gap-2 text-xs animate-float-slower">
                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                <span class="text-gray-700 dark:text-gray-300 font-medium">+ Rp 250.000 baru saja</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- TRUSTED BY (logo strip) -->
    <section class="py-10 bg-white dark:bg-gray-950 border-y border-gray-100 dark:border-gray-900 reveal">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-6 font-semibold">
          Dipercaya oleh operator RT/RW Net di seluruh Indonesia
        </p>
        <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-4 opacity-60">
          <div v-for="(name, i) in ['Net Sejahtera', 'Angkasa Net', 'Jaringan Prima', 'Net Mandiri', 'PlusNet', 'MitraNet']" :key="i"
            class="text-base sm:text-lg font-bold text-gray-400 dark:text-gray-500 hover:text-sky-500 dark:hover:text-sky-400 transition-colors cursor-default">
            <i class="fas fa-wifi mr-1.5 text-sky-500/60"></i>{{ name }}
          </div>
        </div>
      </div>
    </section>

    <!-- LOGIN / DAFTAR -->
    <section class="py-20 md:py-28 bg-linear-to-br from-sky-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-950 dark:to-indigo-950/30 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">Akses Platform</h2>
          <p class="mt-3 text-gray-600 dark:text-gray-400 max-w-xl mx-auto">Pilih portal sesuai peran Anda untuk masuk atau mendaftar.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <Link href="/login-pelanggan" class="reveal portal-card group p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-xl hover:shadow-emerald-500/10 hover:-translate-y-1 transition-all duration-300 text-center" style="transition-delay: 0ms">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-linear-to-br from-emerald-500 to-teal-600 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg shadow-emerald-500/30">
              <i class="fas fa-user text-white text-2xl"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-1.5">Pelanggan</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Cek tagihan, riwayat pembayaran, dan status layanan internet Anda.</p>
            <span class="inline-flex items-center gap-1 mt-4 text-sm font-medium text-emerald-600 dark:text-emerald-400 group-hover:gap-2 transition-all">
              Login / Daftar <i class="fas fa-arrow-right text-xs"></i>
            </span>
          </Link>

          <Link href="/login-operator-saas" class="reveal portal-card group p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-xl hover:shadow-indigo-500/10 hover:-translate-y-1 transition-all duration-300 text-center" style="transition-delay: 80ms">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-linear-to-br from-indigo-500 to-purple-600 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg shadow-indigo-500/30">
              <i class="fas fa-cloud text-white text-2xl"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-1.5">Operator SaaS</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Kelola semua perusahaan, admin, role, dan konfigurasi sistem secara terpusat.</p>
            <span class="inline-flex items-center gap-1 mt-4 text-sm font-medium text-indigo-600 dark:text-indigo-400 group-hover:gap-2 transition-all">
              Login <i class="fas fa-arrow-right text-xs"></i>
            </span>
          </Link>

          <Link href="/login-perusahaan" class="reveal portal-card group p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-xl hover:shadow-sky-500/10 hover:-translate-y-1 transition-all duration-300 text-center" style="transition-delay: 160ms">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-linear-to-br from-sky-500 to-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg shadow-sky-500/30">
              <i class="fas fa-building text-white text-2xl"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-1.5">Perusahaan</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Dashboard perusahaan: kelola pelanggan, tagihan, paket, dan insentif karyawan.</p>
            <span class="inline-flex items-center gap-1 mt-4 text-sm font-medium text-sky-600 dark:text-sky-400 group-hover:gap-2 transition-all">
              Login <i class="fas fa-arrow-right text-xs"></i>
            </span>
          </Link>

          <Link href="/login-karyawan" class="reveal portal-card group p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-amber-300 dark:hover:border-amber-700 hover:shadow-xl hover:shadow-amber-500/10 hover:-translate-y-1 transition-all duration-300 text-center" style="transition-delay: 240ms">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-linear-to-br from-amber-500 to-orange-600 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg shadow-amber-500/30">
              <i class="fas fa-user-tie text-white text-2xl"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-1.5">Karyawan</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Akses data pelanggan, tagihan, input pembayaran, dan lihat insentif Anda.</p>
            <span class="inline-flex items-center gap-1 mt-4 text-sm font-medium text-amber-600 dark:text-amber-400 group-hover:gap-2 transition-all">
              Login <i class="fas fa-arrow-right text-xs"></i>
            </span>
          </Link>
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section class="py-20 md:py-28 bg-white dark:bg-gray-950 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">Fitur Unggulan</h2>
          <p class="mt-3 text-gray-600 dark:text-gray-400 max-w-xl mx-auto">Semua yang Anda butuhkan untuk mengelola bisnis RT/RW Net dalam satu dashboard.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="(f, i) in features" :key="i" class="reveal feature-card group p-6 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-xl hover:shadow-sky-500/10 hover:-translate-y-1 transition-all duration-300" :style="{ transitionDelay: (i * 60) + 'ms' }">
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 shadow-lg', f.gradient]">
              <i :class="[f.icon, 'text-white text-lg']"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">{{ f.title }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- STATS -->
    <section class="py-16 bg-linear-to-r from-sky-500 to-indigo-600 relative overflow-hidden">
      <div class="absolute inset-0 opacity-20">
        <div class="absolute -top-20 -left-20 w-72 h-72 bg-white rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-white rounded-full blur-3xl"></div>
      </div>
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center text-white">
          <div class="reveal">
            <div class="text-3xl md:text-4xl font-extrabold mb-1" data-counter="500" data-suffix="+">0</div>
            <div class="text-sm opacity-80">Perusahaan RT/RW Net</div>
          </div>
          <div class="reveal" style="transition-delay: 80ms">
            <div class="text-3xl md:text-4xl font-extrabold mb-1" data-counter="50000" data-suffix="+">0</div>
            <div class="text-sm opacity-80">Pelanggan Terkelola</div>
          </div>
          <div class="reveal" style="transition-delay: 160ms">
            <div class="text-3xl md:text-4xl font-extrabold mb-1"><span data-counter="99" data-suffix=",9%">0</span></div>
            <div class="text-sm opacity-80">Uptime Server</div>
          </div>
          <div class="reveal" style="transition-delay: 240ms">
            <div class="text-3xl md:text-4xl font-extrabold mb-1">24/7</div>
            <div class="text-sm opacity-80">Dukungan</div>
          </div>
        </div>
      </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="py-20 md:py-28 bg-white dark:bg-gray-950 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">Apa Kata Mereka?</h2>
          <p class="mt-3 text-gray-600 dark:text-gray-400 max-w-xl mx-auto">Pengalaman nyata dari pelanggan kami di lapangan.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="(t, i) in testimonials" :key="i" class="reveal testimonial-card p-6 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:shadow-lg hover:-translate-y-1 transition-all duration-300" :style="{ transitionDelay: (i * 80) + 'ms' }">
            <div class="flex items-center gap-1 mb-3">
              <i v-for="s in 5" :key="s" class="fas fa-star text-amber-400 text-sm"></i>
            </div>
            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-5 italic">"{{ t.quote }}"</p>
            <div class="flex items-center gap-3">
              <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm bg-linear-to-br', t.color]">
                {{ t.avatar }}
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ t.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t.role }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="py-20 md:py-28 bg-gray-50 dark:bg-gray-900 transition-colors">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 reveal">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">Pertanyaan Umum</h2>
          <p class="mt-3 text-gray-600 dark:text-gray-400">Belum yakin? Mungkin pertanyaan Anda ada di sini.</p>
        </div>
        <div class="space-y-3">
          <div v-for="(f, i) in faqs" :key="i" class="reveal faq-item rounded-xl bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 overflow-hidden hover:border-sky-300 dark:hover:border-sky-700 hover:shadow-md transition-all duration-300" :style="{ transitionDelay: (i * 40) + 'ms' }">
            <button @click="toggleFaq(i)" class="w-full flex items-center justify-between gap-4 p-5 text-left">
              <span class="font-medium text-gray-900 dark:text-white">{{ f.q }}</span>
              <i :class="['fas fa-chevron-down text-sm text-gray-500 transition-transform duration-300', openFaq === i ? 'rotate-180 text-sky-500' : '']"></i>
            </button>
            <div :class="['grid transition-all duration-300 ease-in-out', openFaq === i ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0']">
              <div class="overflow-hidden">
                <p class="px-5 pb-5 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ f.a }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-20 bg-gray-50 dark:bg-gray-900 transition-colors">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Siap Mengelola Bisnis RT/RW Net Anda?</h2>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">Daftar sekarang dan dapatkan free trial 30 hari.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <Link href="/login-pelanggan" @click="handleRipple" class="ripple-btn inline-flex items-center justify-center px-8 py-3 bg-linear-to-r from-sky-500 to-indigo-600 text-white font-semibold rounded-xl shadow-lg shadow-sky-500/30 hover:shadow-xl hover:shadow-sky-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all">
            <i class="fas fa-user-plus mr-2"></i> Daftar / Login Pelanggan
          </Link>
          <Link href="/hubungi-kami" class="inline-flex items-center justify-center px-8 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl border border-gray-300 dark:border-gray-700 hover:border-sky-500 dark:hover:border-sky-500 transition-colors">
            <i class="fas fa-envelope mr-2"></i> Hubungi Kami
          </Link>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* Scroll reveal */
.reveal {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.7s ease, transform 0.7s ease;
}
.reveal-visible {
  opacity: 1;
  transform: translateY(0);
}

/* Floating mockup cards */
@keyframes float-slow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
@keyframes float-slower {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}
.animate-float-slow { animation: float-slow 4s ease-in-out infinite; }
.animate-float-slower { animation: float-slower 5.5s ease-in-out infinite; }

/* Ripple effect */
.ripple-btn { position: relative; overflow: hidden; }
.ripple {
  position: absolute;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.4);
  transform: scale(0);
  animation: ripple-anim 0.6s linear;
  pointer-events: none;
}
@keyframes ripple-anim {
  to { transform: scale(4); opacity: 0; }
}

/* Feature card tilt on hover (subtle 3D) */
.feature-card {
  transform-style: preserve-3d;
}
.feature-card:hover {
  transform: translateY(-4px) rotateX(2deg) rotateY(-1deg);
}

/* Smooth scroll for anchor links */
html { scroll-behavior: smooth; }
</style>
