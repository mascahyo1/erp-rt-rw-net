<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

const props = defineProps({
  tagihans: { type: Array, default: () => [] },
  snapScriptUrl: { type: String, default: 'https://app.sandbox.midtrans.com/snap/snap.js' },
});

const page = usePage();
const toast = useToast();
const submitting = ref(false);
const formErrors = ref({});
const processingMidtrans = ref(false);
const snapScriptLoaded = ref(false);

const form = ref({
  cust_internet_invc_id: '',
  amount_paid: '',
  payment_method: 'transfer_bank',
  status_description: '',
});

const selectedTagihan = computed(() => {
  if (!form.value.cust_internet_invc_id) return null;
  return props.tagihans.find(t => t.id === form.value.cust_internet_invc_id) || null;
});

function formatRupiah(n) {
  if (n === null || n === undefined) return '—';
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

/**
 * Load Midtrans Snap.js dari CDN secara lazy.
 * Pattern: cek apakah <script> sudah ada, kalau belum → inject <script src="..."> ke <head>.
 * Setelah load, window.snap siap dipakai.
 */
function loadSnapScript() {
  return new Promise((resolve, reject) => {
    if (typeof window.snap !== 'undefined') {
      snapScriptLoaded.value = true;
      return resolve(window.snap);
    }
    if (document.querySelector(`script[src="${props.snapScriptUrl}"]`)) {
      // Script sudah ada, tunggu load selesai
      const checkInterval = setInterval(() => {
        if (typeof window.snap !== 'undefined') {
          clearInterval(checkInterval);
          snapScriptLoaded.value = true;
          resolve(window.snap);
        }
      }, 100);
      setTimeout(() => {
        clearInterval(checkInterval);
        reject(new Error('Snap.js load timeout'));
      }, 10000);
      return;
    }
    const script = document.createElement('script');
    script.src = props.snapScriptUrl;
    script.setAttribute('data-client-key', page.props.config?.midtrans_client_key || '');
    script.onload = () => {
      snapScriptLoaded.value = true;
      resolve(window.snap);
    };
    script.onerror = () => reject(new Error('Failed to load Snap.js script'));
    document.head.appendChild(script);
  });
}

/**
 * Bayar via Midtrans Snap popup.
 * Flow:
 *  1. Validasi client-side
 *  2. POST create-snap-token ke backend
 *  3. Load Snap.js kalau belum
 *  4. window.snap.pay(snap_token, callbacks)
 */
async function payWithMidtrans() {
  const e = {};
  if (!form.value.cust_internet_invc_id) e.cust_internet_invc_id = 'Pilih tagihan terlebih dahulu';
  if (!form.value.amount_paid || Number(form.value.amount_paid) <= 0) e.amount_paid = 'Nominal tidak valid';
  formErrors.value = e;
  if (Object.keys(e).length > 0) {
    toast.error('Lengkapi tagihan dan nominal terlebih dahulu.');
    return;
  }

  processingMidtrans.value = true;

  try {
    // Step 1: backend creates payment + Snap transaction, returns snap_token
    const response = await fetch('/customer/pembayaran-tambah/create-snap-token', {
      method: 'POST',
      body: (() => {
        const fd = new FormData();
        fd.append('cust_internet_invc_id', form.value.cust_internet_invc_id);
        fd.append('amount_paid', form.value.amount_paid);
        return fd;
      })(),
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      const err = await response.json().catch(() => ({}));
      throw new Error(err.error || err.message || `HTTP ${response.status}`);
    }

    const data = await response.json();

    if (!data.snap_token) {
      throw new Error('Backend tidak mengembalikan snap_token');
    }

    // Step 2: load Snap.js (lazy)
    await loadSnapScript();

    if (typeof window.snap === 'undefined') {
      throw new Error('window.snap tidak tersedia setelah load');
    }

    // Step 3: open Snap popup
    window.snap.pay(data.snap_token, {
      onSuccess: (result) => {
        toast.success('Pembayaran berhasil! Halaman akan dimuat ulang.');
        setTimeout(() => router.visit('/customer/riwayat-pembayaran'), 1500);
      },
      onPending: (result) => {
        toast.info('Pembayaran tertunda. Selesaikan pembayaran Anda di halaman Midtrans.');
        setTimeout(() => router.visit('/customer/riwayat-pembayaran'), 2500);
      },
      onError: (result) => {
        toast.error('Pembayaran gagal. Silakan coba lagi.');
        processingMidtrans.value = false;
      },
      onClose: () => {
        toast.info('Popup ditutup. Pembayaran belum selesai.');
        processingMidtrans.value = false;
      },
    });
  } catch (err) {
    console.error('Midtrans error:', err);
    toast.error(err.message || 'Gagal memproses pembayaran Midtrans.');
    processingMidtrans.value = false;
  }
}

async function submitForm() {
  const e = {};
  if (!form.value.cust_internet_invc_id) e.cust_internet_invc_id = 'Pilih tagihan terlebih dahulu';
  if (!form.value.amount_paid || Number(form.value.amount_paid) <= 0) e.amount_paid = 'Nominal tidak valid';
  if (!form.value.payment_method) e.payment_method = 'Pilih metode pembayaran';
  formErrors.value = e;
  if (Object.keys(e).length > 0) {
    toast.error('Lengkapi field yang wajib diisi.');
    return;
  }

  submitting.value = true;

  const response = await fetch('/customer/pembayaran-tambah', {
    method: 'POST',
    body: (() => {
      const fd = new FormData();
      fd.append('cust_internet_invc_id', form.value.cust_internet_invc_id);
      fd.append('amount_paid', form.value.amount_paid);
      fd.append('payment_method', form.value.payment_method);
      if (form.value.status_description) fd.append('status_description', form.value.status_description);
      return fd;
    })(),
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
      'Accept': 'application/json',
    },
    credentials: 'same-origin',
  });

  submitting.value = false;

  if (response.ok) {
    toast.success('Pembayaran berhasil dicatat. Mohon tunggu verifikasi admin.');
    router.visit('/customer/riwayat-pembayaran');
  } else {
    let errs = {};
    try { errs = (await response.json()).errors || {}; } catch (e2) { /* ignore */ }
    formErrors.value = errs;
    toast.error('Gagal mencatat pembayaran. Periksa input Anda.');
  }
}
</script>

<template>
  <div>
    <Head title="Tambah Pembayaran | Pelanggan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/customer/riwayat-pembayaran" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors">Riwayat Pembayaran</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Tambah</span>
      </nav>

      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Catat Pembayaran</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Pilih tagihan dan pilih metode pembayaran. Pembayaran via Midtrans diverifikasi otomatis.</p>
      </div>

      <!-- Empty state -->
      <div v-if="tagihans.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-12 text-center">
        <i class="fas fa-check-circle text-5xl text-emerald-300 dark:text-emerald-700 mb-3 block"></i>
        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada tagihan yang perlu dibayar saat ini.</p>
        <Link href="/customer/riwayat-pembayaran" class="inline-flex items-center mt-4 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
          <i class="fas fa-arrow-left mr-1.5"></i>Kembali ke Riwayat Pembayaran
        </Link>
      </div>

      <div v-else class="space-y-6">
        <!-- SHARED: Tagihan & Nominal (dipakai 2 section) -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4 max-w-2xl">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pilih Tagihan & Nominal</h3>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Pilih Tagihan <span class="text-red-500">*</span></label>
            <select v-model="form.cust_internet_invc_id" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.cust_internet_invc_id ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-emerald-500']">
              <option value="">— Pilih tagihan —</option>
              <option v-for="t in tagihans" :key="t.id" :value="t.id">
                {{ t.kode }} — {{ formatRupiah(t.nominal) }} ({{ t.paket }})
              </option>
            </select>
            <p v-if="formErrors.cust_internet_invc_id" class="text-red-500 text-xs mt-1">{{ formErrors.cust_internet_invc_id }}</p>
          </div>

          <!-- Selected tagihan preview -->
          <div v-if="selectedTagihan" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 space-y-2">
            <div class="flex items-center gap-2">
              <i class="fas fa-info-circle text-emerald-600 dark:text-emerald-400"></i>
              <span class="text-xs font-semibold text-emerald-900 dark:text-emerald-200 uppercase tracking-wider">Tagihan Dipilih</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div><span class="text-gray-500 dark:text-gray-400">Kode:</span> <strong class="font-mono">{{ selectedTagihan.kode }}</strong></div>
              <div><span class="text-gray-500 dark:text-gray-400">Paket:</span> <strong>{{ selectedTagihan.paket }}</strong></div>
              <div><span class="text-gray-500 dark:text-gray-400">Nominal:</span> <strong class="text-emerald-600 dark:text-emerald-400">{{ formatRupiah(selectedTagihan.nominal) }}</strong></div>
              <div v-if="selectedTagihan.tgl_jatuh_tempo"><span class="text-gray-500 dark:text-gray-400">Jatuh Tempo:</span> <strong>{{ selectedTagihan.tgl_jatuh_tempo }}</strong></div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah Bayar (Rp) <span class="text-red-500">*</span></label>
            <input v-model="form.amount_paid" type="number" min="1" placeholder="0" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.amount_paid ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-emerald-500']" />
            <p v-if="formErrors.amount_paid" class="text-red-500 text-xs mt-1">{{ formErrors.amount_paid }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bisa berbeda dari nominal tagihan (untuk bayar sebagian atau bayar lebih).</p>
          </div>
        </div>

        <!-- SECTION 1: Bayar Online via Midtrans (RECOMMENDED) -->
        <div class="bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-emerald-900/20 dark:via-teal-900/20 dark:to-cyan-900/20 border-2 border-emerald-300 dark:border-emerald-700 rounded-xl shadow-md p-6 space-y-4 max-w-2xl relative overflow-hidden">
          <div class="absolute top-0 right-0 bg-emerald-600 text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-bl-lg"><i class="fas fa-bolt mr-1"></i>Direkomendasikan</div>
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shrink-0"><i class="fas fa-bolt text-lg"></i></div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">Bayar Online (Verifikasi Otomatis)</h3>
              <p class="text-xs text-gray-600 dark:text-gray-400">Virtual Account, E-Wallet, QRIS, Kartu Kredit. Status langsung terupdate.</p>
            </div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
            <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-2 text-center"><i class="fas fa-university text-emerald-600 dark:text-emerald-400 text-lg mb-1 block"></i><span class="text-gray-700 dark:text-gray-300">Virtual Account</span></div>
            <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-2 text-center"><i class="fas fa-mobile-alt text-emerald-600 dark:text-emerald-400 text-lg mb-1 block"></i><span class="text-gray-700 dark:text-gray-300">E-Wallet</span></div>
            <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-2 text-center"><i class="fas fa-qrcode text-emerald-600 dark:text-emerald-400 text-lg mb-1 block"></i><span class="text-gray-700 dark:text-gray-300">QRIS</span></div>
            <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-2 text-center"><i class="fas fa-credit-card text-emerald-600 dark:text-emerald-400 text-lg mb-1 block"></i><span class="text-gray-700 dark:text-gray-300">Kartu Kredit</span></div>
          </div>
          <button type="button" @click="payWithMidtrans" :disabled="processingMidtrans" class="w-full px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-sm font-bold rounded-lg transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-bolt mr-2"></i>{{ processingMidtrans ? 'Membuka Midtrans...' : 'Bayar Sekarang via Midtrans' }}
          </button>
          <p class="text-[10px] text-gray-500 dark:text-gray-400 text-center">Powered by Midtrans. Pembayaran aman & terenkripsi.</p>
        </div>

        <!-- DIVIDER -->
        <div class="max-w-2xl flex items-center gap-3">
          <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
          <span class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold">atau</span>
          <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
        </div>

        <!-- SECTION 2: Catat Manual (existing flow) -->
        <form @submit.prevent="submitForm" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4 max-w-2xl">
          <div class="flex items-center gap-3 pb-2 border-b border-gray-100 dark:border-gray-700">
            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 shrink-0"><i class="fas fa-edit text-base"></i></div>
            <div>
              <h3 class="text-base font-semibold text-gray-900 dark:text-white">Catat Manual</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Untuk pembayaran tunai/transfer yang sudah Anda lakukan di luar sistem.</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Metode Pembayaran <span class="text-red-500">*</span></label>
            <select v-model="form.payment_method" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.payment_method ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-emerald-500']">
              <option value="tunai">Tunai</option>
              <option value="transfer_bank">Transfer Bank</option>
              <option value="e_wallet">E-Wallet</option>
              <option value="qris">QRIS</option>
            </select>
            <p v-if="formErrors.payment_method" class="text-red-500 text-xs mt-1">{{ formErrors.payment_method }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Keterangan (opsional)</label>
            <textarea v-model="form.status_description" rows="2" placeholder="Misal: transfer BCA 13:30 via mobile banking" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none resize-none"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            <Link href="/customer/riwayat-pembayaran" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</Link>
            <button type="submit" :disabled="submitting" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50">
              <i class="fas fa-paper-plane mr-1.5"></i>{{ submitting ? 'Mengirim...' : 'Catat Pembayaran' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
