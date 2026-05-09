<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

// ========================
// Theme Management
// ========================
const theme = ref(localStorage.getItem('theme') || 'system');

const isDark = computed(() => {
  if (theme.value === 'dark') return true;
  if (theme.value === 'system') {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
  return false;
});

const themeIcon = computed(() => {
  if (theme.value === 'light') return 'fa-sun';
  if (theme.value === 'dark') return 'fa-moon';
  return 'fa-circle-half-stroke';
});

function applyTheme() {
  const root = document.documentElement;
  if (isDark.value) {
    root.classList.add('dark');
  } else {
    root.classList.remove('dark');
  }
}

function setTheme(mode) {
  theme.value = mode;
  localStorage.setItem('theme', mode);
  applyTheme();
}

function toggleTheme() {
  if (theme.value === 'light') {
    setTheme('dark');
  } else if (theme.value === 'dark') {
    setTheme('system');
  } else {
    setTheme('light');
  }
}

let mediaQuery;
onMounted(() => {
  applyTheme();
  mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
  mediaQuery.addEventListener('change', applyTheme);
});
onUnmounted(() => {
  mediaQuery?.removeEventListener('change', applyTheme);
});

// ========================
// Mobile Menu
// ========================
const mobileMenuOpen = ref(false);
function closeMobileMenu() {
  mobileMenuOpen.value = false;
}
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300">
    <!-- ========================
    NAVBAR
    ======================== -->
    <nav class="sticky top-0 z-50 bg-gradient-to-r from-indigo-50 via-sky-50 to-white dark:from-indigo-950 dark:via-indigo-900/50 dark:to-slate-900 backdrop-blur-xl shadow-sm transition-colors duration-300">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

          <!-- Logo -->
          <Link href="/" class="flex items-center gap-2 shrink-0">
            <div class="w-9 h-9 bg-gradient-to-br from-sky-500 to-indigo-600 rounded-lg flex items-center justify-center">
              <i class="fas fa-wifi text-white text-sm"></i>
            </div>
            <span class="font-bold text-lg text-gray-900 dark:text-white hidden sm:block">
              RT/RW Net
            </span>
          </Link>

          <!-- Desktop Nav Links (Center) -->
          <div class="hidden md:flex items-center gap-1">
            <Link href="/" class="px-3 py-2 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
              Home
            </Link>
            <Link href="/tentang-kami" class="px-3 py-2 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
              Tentang Kami
            </Link>
            <Link href="/hubungi-kami" class="px-3 py-2 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
              Hubungi Kami
            </Link>
          </div>

          <!-- Desktop Right Buttons -->
          <div class="hidden md:flex items-center gap-2">
            <!-- Theme Toggle -->
            <button
              @click="toggleTheme"
              class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              :title="`Tema: ${theme}`"
            >
              <i :class="['fas', themeIcon, 'text-lg']"></i>
            </button>

            <!-- Operator SaaS -->
            <Link href="/login-operator-saas" class="px-3 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
              <i class="fas fa-cloud mr-1.5"></i> Operator SaaS
            </Link>

            <!-- Perusahaan -->
            <Link href="/login-perusahaan" class="px-3 py-2 text-sm font-medium rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition-colors">
              <i class="fas fa-building mr-1.5"></i> Perusahaan
            </Link>

            <!-- Pelanggan -->
            <Link href="/login-pelanggan" class="px-3 py-2 text-sm font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">
              <i class="fas fa-user mr-1.5"></i> Pelanggan
            </Link>
          </div>

          <!-- Mobile hamburger + theme -->
          <div class="flex items-center gap-2 md:hidden">
            <button @click="toggleTheme" class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
              <i :class="['fas', themeIcon, 'text-lg']"></i>
            </button>
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
              <i v-if="!mobileMenuOpen" class="fas fa-bars text-lg"></i>
              <i v-else class="fas fa-times text-lg"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div v-show="mobileMenuOpen" class="md:hidden bg-gradient-to-r from-indigo-50 via-sky-50 to-white dark:from-indigo-950 dark:via-indigo-900/50 dark:to-slate-900 backdrop-blur-xl shadow-sm">
        <div class="px-4 py-3 space-y-1">
          <Link href="/" @click="closeMobileMenu" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
            <i class="fas fa-home w-5 mr-2"></i> Home
          </Link>
          <Link href="/tentang-kami" @click="closeMobileMenu" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
            <i class="fas fa-info-circle w-5 mr-2"></i> Tentang Kami
          </Link>
          <Link href="/hubungi-kami" @click="closeMobileMenu" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
            <i class="fas fa-envelope w-5 mr-2"></i> Hubungi Kami
          </Link>

          <div class="pt-2 border-t border-gray-200 dark:border-gray-700 mt-2 space-y-1">
            <Link href="/login-operator-saas" class="block px-3 py-2 rounded-lg text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
              <i class="fas fa-cloud w-5 mr-2"></i> Login Operator SaaS
            </Link>
            <Link href="/login-perusahaan" class="block px-3 py-2 rounded-lg text-sm font-medium text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-900/30">
              <i class="fas fa-building w-5 mr-2"></i> Login Perusahaan
            </Link>
            <Link href="/login-pelanggan" class="block px-3 py-2 rounded-lg text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30">
              <i class="fas fa-user w-5 mr-2"></i> Login / Daftar Pelanggan
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- ========================
    MAIN CONTENT
    ======================== -->
    <main>
      <Transition name="page" mode="out-in">
        <slot />
      </Transition>
    </main>

    <!-- ========================
    FOOTER
    ======================== -->
    <footer class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-slate-300">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

          <!-- Brand -->
          <div class="lg:col-span-1">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-9 h-9 bg-gradient-to-br from-sky-500 to-indigo-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-wifi text-white text-sm"></i>
              </div>
              <span class="font-bold text-lg text-white">RT/RW Net</span>
            </div>
            <p class="text-sm text-slate-400 leading-relaxed">
              Solusi ERP modern untuk bisnis RT/RW Net Anda. Kelola pelanggan, tagihan, dan pembayaran dalam satu platform.
            </p>
          </div>

          <!-- Menu -->
          <div>
            <h4 class="font-semibold text-sm text-white uppercase tracking-wider mb-4">Menu</h4>
            <ul class="space-y-2">
              <li><Link href="/" class="text-sm text-slate-400 hover:text-sky-400 transition-colors">Home</Link></li>
              <li><Link href="/tentang-kami" class="text-sm text-slate-400 hover:text-sky-400 transition-colors">Tentang Kami</Link></li>
              <li><Link href="/hubungi-kami" class="text-sm text-slate-400 hover:text-sky-400 transition-colors">Hubungi Kami</Link></li>
            </ul>
          </div>

          <!-- Login -->
          <div>
            <h4 class="font-semibold text-sm text-white uppercase tracking-wider mb-4">Login</h4>
            <ul class="space-y-2">
              <li><Link href="/login-operator-saas" class="text-sm text-slate-400 hover:text-sky-400 transition-colors">Operator SaaS</Link></li>
              <li><Link href="/login-perusahaan" class="text-sm text-slate-400 hover:text-sky-400 transition-colors">Perusahaan</Link></li>
              <li><Link href="/login-pelanggan" class="text-sm text-slate-400 hover:text-sky-400 transition-colors">Pelanggan</Link></li>
            </ul>
          </div>

          <!-- Legal -->
          <div>
            <h4 class="font-semibold text-sm text-white uppercase tracking-wider mb-4">Legal</h4>
            <ul class="space-y-2">
              <li><Link href="/syarat-dan-ketentuan" class="text-sm text-slate-400 hover:text-sky-400 transition-colors">Syarat & Ketentuan</Link></li>
              <li><Link href="/kebijakan-privasi" class="text-sm text-slate-400 hover:text-sky-400 transition-colors">Kebijakan Privasi</Link></li>
            </ul>
          </div>
        </div>

        <!-- Bottom -->
        <div class="mt-10 pt-6 border-t border-slate-700/50 text-center">
          <p class="text-sm text-slate-500">
            &copy; {{ new Date().getFullYear() }} RT/RW Net. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>