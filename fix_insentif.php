<?php
$file = 'C:\laragon\www\erp-rt-rw-net\resources\js\Pages\OperatorPerusahaan\RiwayatInsentif.vue';
$c = file_get_contents($file);

// Add bulanFilter and tahunFilter to state
$c = str_replace(
  "const searchInput = ref(''); const search = ref(''); const statusInsentifFilter = ref(''); const statusPembayaranFilter = ref(''); const currentPage = ref(1);",
  "const searchInput = ref(''); const search = ref(''); const statusInsentifFilter = ref(''); const statusPembayaranFilter = ref(''); const bulanFilter = ref(''); const tahunFilter = ref(''); const currentPage = ref(1);"
);

// Add apply functions after applyStatusPembayaranFilter
$c = str_replace(
  "function applyStatusPembayaranFilter(s) { statusPembayaranFilter.value = s; currentPage.value = 1; }",
  "function applyStatusPembayaranFilter(s) { statusPembayaranFilter.value = s; currentPage.value = 1; }" . "\n" . "function applyBulanFilter(b) { bulanFilter.value = b; currentPage.value = 1; }" . "\n" . "function applyTahunFilter(t) { tahunFilter.value = t; currentPage.value = 1; }"
);

// Add bulan/tahun filter logic to computed
$c = str_replace(
  "  if (statusPembayaranFilter.value) r = r.filter(x => x.status_pembayaran === statusPembayaranFilter.value);",
  "  if (statusPembayaranFilter.value) r = r.filter(x => x.status_pembayaran === statusPembayaranFilter.value);\n  if (bulanFilter.value) r = r.filter(x => x.created_at && Number(x.created_at.split('-')[1]) === Number(bulanFilter.value));\n  if (tahunFilter.value) r = r.filter(x => x.created_at && x.created_at.split('-')[0] === tahunFilter.value);"
);

// Add bulan/tahun selects to filter bar - two selects after search input
$searchBlock = '<div class="flex flex-row flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-64">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div>
          <input v-model="searchInput" type="text" placeholder="Cari kode tagihan / karyawan..." class="w-full pl-10 pr-9 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors" @keydown.enter="applySearch" />
          <div class="absolute inset-y-0 right-0 flex items-center pr-1.5"><button v-if="searchInput" @click="clearSearch" class="p-1.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors" title="Clear"><i class="fas fa-times text-xs"></i></button></div>
        </div>
        <select :value="statusInsentifFilter" @change="applyStatusInsentifFilter($event.target.value)" class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Status Insentif</option><option value="menunggu">Menunggu</option><option value="disetujui">Disetujui</option><option value="ditolak">Ditolak</option></select>
        <select :value="statusPembayaranFilter" @change="applyStatusPembayaranFilter($event.target.value)" class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Status Pembayaran</option><option value="lunas">Lunas</option><option value="belum lunas">Belum Lunas</option></select>
        <select :value="bulanFilter" @change="applyBulanFilter($event.target.value)" class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Bulan</option><option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option><option value="5">Mei</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Agu</option><option value="9">Sep</option><option value="10">Okt</option><option value="11">Nov</option><option value="12">Des</option></select>
        <select :value="tahunFilter" @change="applyTahunFilter($event.target.value)" class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-colors"><option value="">Tahun</option><option value="2024">2024</option><option value="2025">2025</option><option value="2026">2026</option></select>
        <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ filteredRiwayats.length }} data</span>
        <button v-if="search || statusInsentifFilter || statusPembayaranFilter || bulanFilter || tahunFilter" @click="searchInput = \'\'; search = \'\'; applyStatusInsentifFilter(\'\'); statusPembayaranFilter = \'\'; bulanFilter = \'\'; tahunFilter = \'\'" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 underline whitespace-nowrap">Reset filter</button>
      </div>';

// Replace the old filter block
$find = '<div class="flex flex-row flex-wrap items-center gap-3">';
$idx = strpos($c, $find);
$end = strpos($c, 'Reset filter</button>', $idx) + strlen('Reset filter</button>') + 7; // +7 for </div>
$end = strpos($c, '</div>', $end) + 6; // close outer div
$oldBlock = substr($c, $idx, $end - $idx);
$c = str_replace($oldBlock, $searchBlock, $c);

file_put_contents($file, $c);
echo "RiwayatInsentif DONE\n";
?>