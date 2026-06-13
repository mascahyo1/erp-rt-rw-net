<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

const props = defineProps({
  tagihans: { type: Array, default: () => [] },
});

const toast = useToast();
const submitting = ref(false);
const formErrors = ref({});

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

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
      'X-CSRF-TOKEN': csrfToken,
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
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Pilih tagihan dan catat pembayaran Anda. Admin akan memverifikasi pembayaran Anda.</p>
      </div>

      <!-- Empty state -->
      <div v-if="tagihans.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-12 text-center">
        <i class="fas fa-check-circle text-5xl text-emerald-300 dark:text-emerald-700 mb-3 block"></i>
        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada tagihan yang perlu dibayar saat ini.</p>
        <Link href="/customer/riwayat-pembayaran" class="inline-flex items-center mt-4 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
          <i class="fas fa-arrow-left mr-1.5"></i>Kembali ke Riwayat Pembayaran
        </Link>
      </div>

      <form v-else @submit.prevent="submitForm" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4 max-w-2xl">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Jumlah Bayar (Rp) <span class="text-red-500">*</span></label>
            <input v-model="form.amount_paid" type="number" min="1" placeholder="0" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.amount_paid ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-emerald-500']" />
            <p v-if="formErrors.amount_paid" class="text-red-500 text-xs mt-1">{{ formErrors.amount_paid }}</p>
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
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Keterangan (opsional)</label>
          <textarea v-model="form.status_description" rows="2" placeholder="Tambahkan catatan jika perlu" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none resize-none"></textarea>
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
</template>
