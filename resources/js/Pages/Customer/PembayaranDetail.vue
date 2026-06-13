<script setup>
import { ref, computed } from 'vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

const props = defineProps({ pembayaran: Object });
const toast = useToast();
const retrying = ref(false);

function metodeBadge(m) {
  if (!m) return 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400';
  const s = m.toLowerCase();
  if (s === 'tunai' || s === 'cash') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
  if (s === 'transfer_bank' || s === 'transfer') return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400';
  if (s === 'midtrans') return 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400';
  return 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400';
}

function statusBadge(s) {
  if (s === 'paid') return { label: 'Lunas', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' };
  if (s === 'pending') return { label: 'Menunggu Pembayaran', class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' };
  if (s === 'expired') return { label: 'Kadaluarsa', class: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' };
  if (s === 'cancelled' || s === 'rejected') return { label: 'Dibatalkan', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
  return { label: s || '—', class: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' };
}

const isMidtransPending = computed(() => {
  return props.pembayaran?.provider === 'midtrans' && props.pembayaran?.status === 'pending';
});

const isMidtransPaid = computed(() => {
  return props.pembayaran?.provider === 'midtrans' && props.pembayaran?.status === 'paid';
});

const snapScriptUrl = 'https://app.sandbox.midtrans.com/snap/snap.js';
const clientKey = props.pembayaran?.client_key || '';

function loadSnapScript() {
  return new Promise((resolve, reject) => {
    if (typeof window.snap !== 'undefined') return resolve(window.snap);
    if (document.querySelector(`script[src="${snapScriptUrl}"]`)) {
      const checkInterval = setInterval(() => {
        if (typeof window.snap !== 'undefined') { clearInterval(checkInterval); resolve(window.snap); }
      }, 100);
      setTimeout(() => { clearInterval(checkInterval); reject(new Error('Snap.js load timeout')); }, 10000);
      return;
    }
    const script = document.createElement('script');
    script.src = snapScriptUrl;
    script.setAttribute('data-client-key', clientKey);
    script.onload = () => resolve(window.snap);
    script.onerror = () => reject(new Error('Failed to load Snap.js script'));
    document.head.appendChild(script);
  });
}

async function payNow() {
  if (!props.pembayaran?.snap_token) {
    toast.error('Snap token tidak tersedia. Silakan buat pembayaran baru.');
    router.visit('/customer/pembayaran-tambah');
    return;
  }
  retrying.value = true;
  try {
    await loadSnapScript();
    if (typeof window.snap === 'undefined') throw new Error('window.snap tidak tersedia');
    window.snap.pay(props.pembayaran.snap_token, {
      onSuccess: () => { toast.success('Pembayaran berhasil!'); setTimeout(() => router.reload(), 1500); },
      onPending: () => { toast.info('Menunggu pembayaran. Selesaikan di Midtrans.'); retrying.value = false; },
      onError: () => { toast.error('Pembayaran gagal.'); retrying.value = false; },
      onClose: () => { retrying.value = false; },
    });
  } catch (err) {
    toast.error(err.message || 'Gagal membuka pembayaran');
    retrying.value = false;
  }
}
</script>

<template>
  <div>
    <Head title="Detail Pembayaran | Pelanggan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><Link href="/customer/riwayat-pembayaran" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors">Riwayat Pembayaran</Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Detail</span></nav>

      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 sm:p-8">
        <div class="flex items-center gap-4 pb-6 border-b border-gray-100 dark:border-gray-700">
          <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xl shrink-0"><i class="fas fa-check-circle"></i></div>
          <div>
            <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ pembayaran?.kode }}</h4>
            <div class="flex flex-wrap items-center gap-2 mt-1">
              <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', metodeBadge(pembayaran?.metode)]">{{ pembayaran?.metode }}</span>
              <span v-if="pembayaran?.status" :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(pembayaran.status).class]">{{ statusBadge(pembayaran.status).label }}</span>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jumlah</label><p class="text-sm text-gray-900 dark:text-white mt-1 font-medium">Rp {{ Number(pembayaran?.jumlah || 0).toLocaleString('id') }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Bayar</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ pembayaran?.tgl_bayar || '—' }}</p></div>
          <div><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metode</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ pembayaran?.metode || '—' }}</p></div>
          <div v-if="pembayaran?.midtrans_order_id"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Midtrans Order ID</label><p class="text-sm text-gray-900 dark:text-white mt-1 font-mono">{{ pembayaran.midtrans_order_id }}</p></div>
        </div>
        <div class="mt-4"><label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keterangan</label><p class="text-sm text-gray-900 dark:text-white mt-1">{{ pembayaran?.keterangan || '—' }}</p></div>
      </div>

      <!-- Midtrans: Pending alert + Bayar Sekarang -->
      <div v-if="isMidtransPending" class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-2 border-amber-300 dark:border-amber-700 rounded-xl p-6 space-y-4">
        <div class="flex items-start gap-3">
          <div class="w-12 h-12 rounded-lg bg-amber-500 flex items-center justify-center text-white shrink-0"><i class="fas fa-clock text-xl"></i></div>
          <div class="flex-1">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Pembayaran Menunggu</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">Anda memiliki pembayaran Midtrans yang belum diselesaikan. Selesaikan sekarang untuk menghindari tagihan kadaluarsa.</p>
            <p v-if="pembayaran?.midtrans_expires_at" class="text-xs text-gray-600 dark:text-gray-400 mt-2"><i class="fas fa-hourglass-half mr-1"></i>Batas pembayaran: <strong>{{ pembayaran.midtrans_expires_at }}</strong></p>
          </div>
        </div>
        <button @click="payNow" :disabled="retrying" class="w-full sm:w-auto px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition-colors shadow-sm disabled:opacity-50">
          <i class="fas fa-bolt mr-2"></i>{{ retrying ? 'Membuka Midtrans...' : 'Bayar Sekarang via Midtrans' }}
        </button>
      </div>

      <!-- Midtrans: Paid info (VA number, payment type, settled_at) -->
      <div v-if="isMidtransPaid" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2"><i class="fas fa-bolt text-violet-500"></i>Info Pembayaran Midtrans</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
          <div v-if="pembayaran?.midtrans_payment_type"><span class="text-gray-500 dark:text-gray-400">Tipe Pembayaran:</span> <strong class="text-gray-900 dark:text-white ml-1">{{ pembayaran.midtrans_payment_type }}</strong></div>
          <div v-if="pembayaran?.midtrans_va_number"><span class="text-gray-500 dark:text-gray-400">Nomor VA:</span> <strong class="font-mono text-gray-900 dark:text-white ml-1">{{ pembayaran.midtrans_va_number }}</strong></div>
          <div v-if="pembayaran?.midtrans_settled_at"><span class="text-gray-500 dark:text-gray-400">Selesai pada:</span> <strong class="text-gray-900 dark:text-white ml-1">{{ pembayaran.midtrans_settled_at }}</strong></div>
        </div>
      </div>
    </div>
  </div>
</template>
