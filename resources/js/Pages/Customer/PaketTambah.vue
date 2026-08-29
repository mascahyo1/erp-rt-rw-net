<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import { useToast } from '@/Composables/useToast';
import ToastContainer from '@/Components/ToastContainer.vue';

defineOptions({ layout: CustomerLayout });

const page = usePage();
const toast = useToast();
const submitting = ref(false);
const formErrors = ref({});

const props = defineProps({
  pakets: { type: Array, default: () => [] },
  preselected: { type: Object, default: null },
});

const form = ref({
  internet_package_id: '',
  customer_address: '',
  customer_address_long: '',
  customer_address_lat: '',
  company_notes: '',
});
const locationPicker = ref(null);

onMounted(() => {
  // Pre-select paket from query param or preselected prop
  const queryId = new URLSearchParams(window.location.search).get('id_paket');
  if (queryId) form.value.internet_package_id = queryId;
  else if (props.preselected?.id) form.value.internet_package_id = props.preselected.id;

  // Pre-fill customer address
  if (page.props.auth?.user?.address) {
    form.value.customer_address = page.props.auth.user.address;
  }
});

const selectedPaket = computed(() => {
  if (!form.value.internet_package_id) return null;
  return props.pakets.find(p => p.id === form.value.internet_package_id) || null;
});

function formatRupiah(n) {
  if (n === null || n === undefined) return '—';
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
}

function formatSpeed(downKbps, upKbps) {
  if (!downKbps) return '—';
  const down = downKbps >= 1000 ? (downKbps / 1000) + ' Mbps' : downKbps + ' Kbps';
  if (!upKbps) return down;
  const up = upKbps >= 1000 ? (upKbps / 1000) + ' Mbps' : upKbps + ' Kbps';
  return `${down} / ${up}`;
}

async function submitForm() {
  const e = {};
  if (!form.value.internet_package_id) e.internet_package_id = 'Pilih paket terlebih dahulu';
  if (!form.value.customer_address.trim()) e.customer_address = 'Alamat instalasi wajib diisi';
  formErrors.value = e;
  if (Object.keys(e).length > 0) {
    toast.error('Lengkapi field yang wajib diisi.');
    return;
  }

  submitting.value = true;

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const response = await fetch('/customer/paket-tambah', {
    method: 'POST',
    body: (() => {
      const fd = new FormData();
      fd.append('internet_package_id', form.value.internet_package_id);
      fd.append('customer_address', form.value.customer_address);
      fd.append('customer_address_long', form.value.customer_address_long || '');
      fd.append('customer_address_lat', form.value.customer_address_lat || '');
      fd.append('company_notes', form.value.company_notes || '');
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
    toast.success('Paket berhasil ditambahkan. Mohon tunggu konfirmasi admin.');
    router.visit('/customer/paket-saya');
  } else {
    let errs = {};
    try { errs = (await response.json()).errors || {}; } catch (e2) { /* ignore */ }
    formErrors.value = errs;
    toast.error('Gagal menambahkan paket. Periksa input Anda.');
  }
}
</script>

<template>
  <div>
    <Head title="Tambah Paket | Pelanggan" />
    <ToastContainer />
    <div class="space-y-6">
      <nav class="flex items-center gap-1.5 text-sm">
        <Link href="/customer/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors"><i class="fas fa-home"></i></Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <Link href="/customer/paket-saya" class="text-gray-500 dark:text-gray-400 hover:text-emerald-600 transition-colors">Paket Saya</Link>
        <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
        <span class="text-gray-900 dark:text-white font-medium">Tambah</span>
      </nav>

      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Berlangganan Paket Baru</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Pilih paket internet dan lengkapi data untuk pengajuan langganan.</p>
      </div>

      <form @submit.prevent="submitForm" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-4 max-w-2xl" data-testid="form-main">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Pilih Paket <span class="text-red-500">*</span></label>
          <select v-model="form.internet_package_id" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white', formErrors.internet_package_id ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-emerald-500']">
            <option value="">— Pilih paket internet —</option>
            <option v-for="paket in pakets" :key="paket.id" :value="paket.id">
              {{ paket.nama }} — {{ formatRupiah(paket.harga) }}{{ paket.billing_cycle ? ' / ' + paket.billing_cycle : '' }}
            </option>
          </select>
          <p v-if="formErrors.internet_package_id" class="text-red-500 text-xs mt-1">{{ formErrors.internet_package_id }}</p>
          <p v-else class="text-xs text-gray-400 mt-1">
            <Link href="/customer/daftar-paket" class="text-emerald-600 dark:text-emerald-400 hover:underline">Lihat katalog paket →</Link>
          </p>
        </div>

        <!-- Selected paket preview -->
        <div v-if="selectedPaket" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 space-y-2">
          <div class="flex items-center gap-2">
            <i class="fas fa-info-circle text-emerald-600 dark:text-emerald-400"></i>
            <span class="text-xs font-semibold text-emerald-900 dark:text-emerald-200 uppercase tracking-wider">Paket Dipilih</span>
          </div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ selectedPaket.nama }}</h3>
          <div class="grid grid-cols-2 gap-2 text-sm">
            <div>
              <span class="text-gray-500 dark:text-gray-400">Harga:</span>
              <strong class="text-emerald-600 dark:text-emerald-400">{{ formatRupiah(selectedPaket.harga) }}</strong>
              <span v-if="selectedPaket.billing_cycle" class="text-gray-500 dark:text-gray-400">{{ ' / ' + selectedPaket.billing_cycle }}</span>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400">Kecepatan:</span>
              <strong class="text-gray-900 dark:text-white">{{ formatSpeed(selectedPaket.kecepatan_down_kbps, selectedPaket.kecepatan_up_kbps) }}</strong>
            </div>
            <div v-if="selectedPaket.kuota_gb">
              <span class="text-gray-500 dark:text-gray-400">Kuota:</span>
              <strong class="text-gray-900 dark:text-white">{{ selectedPaket.kuota_gb }} GB</strong>
            </div>
            <div v-if="selectedPaket.max_devices">
              <span class="text-gray-500 dark:text-gray-400">Maks Perangkat:</span>
              <strong class="text-gray-900 dark:text-white">{{ selectedPaket.max_devices }}</strong>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Alamat Instalasi <span class="text-red-500">*</span></label>
          <textarea v-model="form.customer_address" rows="3" placeholder="Alamat lengkap tempat pemasangan" :class="['w-full px-3 py-2.5 rounded-lg border text-sm outline-none transition-colors bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none', formErrors.customer_address ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-emerald-500']"></textarea>
          <p v-if="formErrors.customer_address" class="text-red-500 text-xs mt-1">{{ formErrors.customer_address }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            <i class="fas fa-map-marker-alt mr-1 text-emerald-600 dark:text-emerald-400"></i>Titik Lokasi
          </label>
          <LocationPicker
            ref="locationPicker"
            v-model:lat="form.customer_address_lat"
            v-model:lng="form.customer_address_long"
          />
          <p v-if="formErrors.customer_address_long || formErrors.customer_address_lat" class="text-red-500 text-xs mt-1">
            {{ formErrors.customer_address_long?.[0] || formErrors.customer_address_lat?.[0] }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Catatan (opsional)</label>
          <textarea v-model="form.company_notes" rows="2" placeholder="Catatan tambahan untuk admin" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 outline-none resize-none"></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
          <Link href="/customer/paket-saya" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Batal</Link>
          <button type="submit" :disabled="submitting" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm disabled:opacity-50" data-testid="btn-simpan">
            <i class="fas fa-paper-plane mr-1.5"></i>{{ submitting ? 'Mengirim...' : 'Ajukan Langganan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
