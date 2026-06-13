<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

const page = usePage();
const toast = useToast();
const props = defineProps({
  pembayarans: { type: Array, default: () => [] },
  snapScriptUrl: { type: String, default: 'https://app.sandbox.midtrans.com/snap/snap.js' },
  midtransClientKey: { type: String, default: '' },
});
const payingId = ref(null);
const verifyingId = ref(null);

function formatRupiah(n) {
  if (n === null || n === undefined) return '—';
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
}

function metodeBadge(m) {
  if (!m) return 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400';
  const s = m.toLowerCase();
  if (s === 'tunai' || s === 'cash') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
  if (s === 'transfer_bank' || s === 'transfer' || s === 'transfer manual') return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400';
  if (s === 'midtrans') return 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400';
  if (s === 'e_wallet') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
  if (s === 'qris') return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
  return 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400';
}

function statusBadge(s) {
  if (s === 'paid') return { label: 'Lunas', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' };
  if (s === 'pending') return { label: 'Menunggu', class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' };
  if (s === 'expired') return { label: 'Kadaluarsa', class: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' };
  if (s === 'cancelled' || s === 'rejected') return { label: 'Dibatalkan', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
  return { label: s || '—', class: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' };
}

function loadSnapScript() {
  return new Promise((resolve, reject) => {
    if (typeof window.snap !== 'undefined') return resolve(window.snap);
    if (document.querySelector(`script[src="${props.snapScriptUrl}"]`)) {
      const checkInterval = setInterval(() => {
        if (typeof window.snap !== 'undefined') {
          clearInterval(checkInterval);
          resolve(window.snap);
        }
      }, 100);
      setTimeout(() => { clearInterval(checkInterval); reject(new Error('Snap.js load timeout')); }, 10000);
      return;
    }
    const script = document.createElement('script');
    script.src = props.snapScriptUrl;
    script.setAttribute('data-client-key', props.midtransClientKey);
    script.onload = () => resolve(window.snap);
    script.onerror = () => reject(new Error('Failed to load Snap.js script'));
    document.head.appendChild(script);
  });
}

/**
 * Re-pay pending Midtrans payment. Backend akan re-fetch status real-time,
 * lalu frontend buka Snap popup pakai snap_token existing.
 */
async function retryPayment(p) {
  payingId.value = p.id;
  try {
    // 1. Cek status real-time (webhook mungkin sudah processed)
    const statusResp = await fetch(`/customer/pembayaran-tambah/${p.id}/status`, {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
      credentials: 'same-origin',
    });
    const statusData = await statusResp.json();

    if (statusData.status === 'paid') {
      toast.success('Pembayaran sudah lunas!');
      router.reload();
      return;
    }
    if (statusData.status === 'expired' || statusData.status === 'cancelled' || statusData.status === 'rejected') {
      toast.error(`Pembayaran ${statusData.status}. Silakan buat pembayaran baru.`);
      router.visit('/customer/pembayaran-tambah');
      return;
    }

    if (!statusData.snap_token) {
      toast.error('Snap token tidak tersedia. Silakan buat pembayaran baru.');
      router.visit('/customer/pembayaran-tambah');
      return;
    }

    // 2. Load Snap.js
    await loadSnapScript();

    if (typeof window.snap === 'undefined') {
      throw new Error('window.snap tidak tersedia');
    }

    // 3. Buka Snap popup
    window.snap.pay(statusData.snap_token, {
      onSuccess: () => { toast.success('Pembayaran berhasil!'); setTimeout(() => router.reload(), 1500); },
      onPending: () => { toast.info('Menunggu pembayaran. Selesaikan di Midtrans.'); payingId.value = null; },
      onError: () => { toast.error('Pembayaran gagal.'); payingId.value = null; },
      onClose: () => { payingId.value = null; },
    });
  } catch (err) {
    console.error('Retry payment error:', err);
    toast.error(err.message || 'Gagal membuka pembayaran');
    payingId.value = null;
  }
}

// Sinkron Status Midtrans (manual verify) — fallback saat webhook lambat/gagal.
// TIDAK buka Snap popup, hanya fetch status real-time dan update DB lokal.
async function verifyMidtrans(p) {
  if (verifyingId.value) return;
  verifyingId.value = p.id;
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const res = await fetch(`/customer/pembayaran-tambah/${p.id}/verify-midtrans`, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    if (data.changed) {
      toast.success(data.message || `Status diperbarui ke ${data.payment?.status}.`);
    } else {
      toast.info(data.message || 'Status tidak berubah.');
    }
    router.reload();
  } catch (err) {
    toast.error(err.message || 'Gagal sinkron status Midtrans.');
  } finally {
    verifyingId.value = null;
  }
}
</script>

<template>
  <div>
    <Head title="Riwayat Pembayaran | Pelanggan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm"><Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link><i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i><span class="text-gray-900 dark:text-white font-medium">Riwayat Pembayaran</span></nav>
      <div class="flex items-center justify-between"><div><h2 class="text-2xl font-bold text-gray-900 dark:text-white">Riwayat Pembayaran</h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Riwayat pembayaran yang pernah Anda lakukan.</p></div></div>

      <div v-if="!pembayarans || pembayarans.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-12 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-history text-4xl mb-3 block"></i><span class="text-sm">Belum ada riwayat pembayaran.</span></div>

      <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-sm min-w-[800px]"><thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Tanggal</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Kode</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Jumlah</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Metode</th><th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Status</th><th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400 w-40">Aksi</th></tr></thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="p in pembayarans" :key="p.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ p.tgl_bayar }}</td>
              <td class="px-4 py-3"><code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ p.kode }}</code></td>
              <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ formatRupiah(p.jumlah) }}</td>
              <td class="px-4 py-3 whitespace-nowrap"><span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', metodeBadge(p.metode)]">{{ p.metode }}</span></td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadge(p.status).class]">
                  {{ statusBadge(p.status).label }}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button v-if="p.provider === 'midtrans' && p.status === 'pending'" @click="retryPayment(p)" :disabled="payingId === p.id" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition-colors disabled:opacity-50">
                    <i class="fas fa-bolt"></i>{{ payingId === p.id ? 'Membuka...' : 'Bayar Sekarang' }}
                  </button>
                  <button v-if="p.provider === 'midtrans' && p.status === 'pending'" @click="verifyMidtrans(p)" :disabled="verifyingId === p.id" :title="verifyingId === p.id ? 'Sinkron sedang berjalan...' : 'Sinkron Status Midtrans (verifikasi manual saat webhook lambat)'" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-medium rounded-lg transition-colors disabled:opacity-50">
                    <i :class="['fas', verifyingId === p.id ? 'fa-spinner fa-spin' : 'fa-sync-alt']"></i>
                    {{ verifyingId === p.id ? 'Sinkron...' : 'Sinkron Status' }}
                  </button>
                  <Link :href="`/customer/riwayat-pembayaran/detail?id=${p.id}`" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors inline-block" title="Detail"><i class="fas fa-eye text-sm"></i></Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table></div>
      </div>
    </div>
  </div>
</template>
