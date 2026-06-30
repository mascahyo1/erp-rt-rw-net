<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet default icon paths untuk Vite bundling.
// Default icon pakai path relatif yang gak resolve di bundle Vite.
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

const props = defineProps({
    lat: { type: [String, Number], default: '' },
    lng: { type: [String, Number], default: '' },
    height: { type: String, default: '260px' },
    readonly: { type: Boolean, default: false },
});

const emit = defineEmits(['update:lat', 'update:lng']);

// Dark mode detection — observe <html class="dark"> untuk sync tile filter
// dengan theme toggle di LandingLayout/KaryawanLayout/CustomerLayout.
const isDark = ref(false);
let themeObserver = null;

function applyTileFilter() {
    if (!mapContainer.value) return;
    const tilePane = mapContainer.value.querySelector('.leaflet-tile-pane');
    if (tilePane) tilePane.classList.toggle('dark-tiles', isDark.value);
    const markerPane = mapContainer.value.querySelector('.leaflet-marker-pane');
    if (markerPane) markerPane.classList.toggle('dark-marker', isDark.value);
}

// Default center: Yogyakarta (cover sebagian besar operasi RT/RW Net di ID)
const DEFAULT_CENTER = [-7.7956, 110.3695];
const DEFAULT_ZOOM = 12;

const mapContainer = ref(null);
const searchQuery = ref('');
const searching = ref(false);
const searchResults = ref([]);
const searchError = ref(null);
const locating = ref(false);

let map = null;
let marker = null;
let searchTimeout = null;

function parseCoord(v) {
    if (v === '' || v === null || v === undefined) return null;
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : null;
}

function getInitialLatLng() {
    const lat = parseCoord(props.lat);
    const lng = parseCoord(props.lng);
    if (lat !== null && lng !== null) return [lat, lng];
    return DEFAULT_CENTER;
}

function setMarker(lat, lng, flyTo = false) {
    if (!map) return;
    if (!marker) {
        marker = L.marker([lat, lng], { draggable: !props.readonly }).addTo(map);
        if (!props.readonly) {
            marker.on('dragend', (e) => {
                const { lat: nlat, lng: nlng } = e.target.getLatLng();
                emit('update:lat', nlat.toFixed(7));
                emit('update:lng', nlng.toFixed(7));
            });
        }
    } else {
        marker.setLatLng([lat, lng]);
    }
    if (flyTo) {
        map.setView([lat, lng], Math.max(map.getZoom(), 15));
    }
}

function ensureMapSize() {
    // Leaflet kadang render 0-height kalau container awalnya hidden.
    // invalidateSize() paksa dia recalc.
    if (map) {
        setTimeout(() => map.invalidateSize(), 100);
    }
}

onMounted(() => {
    if (!mapContainer.value) return;
    map = L.map(mapContainer.value, {
        zoomControl: !props.readonly,
        attributionControl: true,
        dragging: !props.readonly,
        scrollWheelZoom: !props.readonly,
        doubleClickZoom: !props.readonly,
        touchZoom: !props.readonly,
        boxZoom: !props.readonly,
        keyboard: !props.readonly,
    });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors',
    }).addTo(map);

    const parsedLat = parseCoord(props.lat);
    const parsedLng = parseCoord(props.lng);
    if (parsedLat !== null && parsedLng !== null) {
        map.setView([parsedLat, parsedLng], 15);
        setMarker(parsedLat, parsedLng);
    } else {
        map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
    }

    if (!props.readonly) {
        map.on('click', (e) => {
            const { lat: nlat, lng: nlng } = e.latlng;
            setMarker(nlat, nlng);
            emit('update:lat', nlat.toFixed(7));
            emit('update:lng', nlng.toFixed(7));
        });
    }

    // Dark mode: detect html.dark + watch class changes (theme toggle)
    isDark.value = document.documentElement.classList.contains('dark');
    themeObserver = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
        applyTileFilter();
    });
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
    // Tunggu tile pane ready dulu (Leaflet append async) baru apply filter
    setTimeout(applyTileFilter, 50);

    // Modal anim mungkin delay mount size — panggil invalidate setelah frame
    ensureMapSize();
});

onBeforeUnmount(() => {
    clearTimeout(searchTimeout);
    themeObserver?.disconnect();
    themeObserver = null;
    if (map) {
        map.remove();
        map = null;
        marker = null;
    }
});

// Expose method supaya parent bisa panggil saat modal dibuka
defineExpose({ invalidateSize: ensureMapSize });

// Sync external prop changes → marker
watch(
    () => [props.lat, props.lng],
    ([newLat, newLng]) => {
        if (!map) return;
        const lat = parseCoord(newLat);
        const lng = parseCoord(newLng);
        if (lat === null || lng === null) return;
        const current = marker?.getLatLng();
        if (!current || Math.abs(current.lat - lat) > 0.00001 || Math.abs(current.lng - lng) > 0.00001) {
            setMarker(lat, lng, true);
        }
    },
);

// === Nominatim search (debounced 500ms) ===
function onSearchInput() {
    clearTimeout(searchTimeout);
    const q = searchQuery.value.trim();
    if (!q) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(doSearch, 500);
}

async function doSearch() {
    const q = searchQuery.value.trim();
    if (!q) return;
    searching.value = true;
    searchError.value = null;
    try {
        const url = new URL('https://nominatim.openstreetmap.org/search');
        url.searchParams.set('format', 'json');
        url.searchParams.set('limit', '5');
        url.searchParams.set('q', q);
        url.searchParams.set('countrycodes', 'id');
        const res = await fetch(url, {
            headers: { 'Accept-Language': 'id,en' },
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        searchResults.value = Array.isArray(data) ? data : [];
    } catch (e) {
        searchError.value = 'Pencarian gagal. Coba lagi.';
        searchResults.value = [];
    } finally {
        searching.value = false;
    }
}

function selectResult(r) {
    const lat = parseFloat(r.lat);
    const lng = parseFloat(r.lon);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
    setMarker(lat, lng, true);
    emit('update:lat', lat.toFixed(7));
    emit('update:lng', lng.toFixed(7));
    searchResults.value = [];
    searchQuery.value = r.display_name;
}

// === Browser geolocation ===
function useMyLocation() {
    if (!navigator.geolocation) {
        searchError.value = 'Browser tidak mendukung geolocation.';
        return;
    }
    locating.value = true;
    searchError.value = null;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            setMarker(lat, lng, true);
            emit('update:lat', lat.toFixed(7));
            emit('update:lng', lng.toFixed(7));
            locating.value = false;
        },
        (err) => {
            searchError.value = 'Gagal dapat lokasi: ' + (err.message || err.code);
            locating.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
}

function clearMarker() {
    if (marker) {
        marker.remove();
        marker = null;
    }
    emit('update:lat', '');
    emit('update:lng', '');
}

// Geolocation API requires HTTPS (window.isSecureContext).
// Di dev (.test TLD) atau HTTP, tombol geolocation di-hide supaya
// gak muncul error "Only secure origins are allowed".
const isSecureContext = computed(() => typeof window !== 'undefined' && window.isSecureContext === true);

// Google Maps URL dari lat/lng (untuk link "Open in Google Maps" di readonly mode)
const googleMapsUrl = computed(() => {
    const lat = parseCoord(props.lat);
    const lng = parseCoord(props.lng);
    if (lat === null || lng === null) return null;
    return `https://www.google.com/maps?q=${lat},${lng}`;
});
</script>

<template>
    <div>
        <!-- Search input (hidden in readonly mode) -->
        <div v-if="!readonly" class="relative">
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 text-sm"></i>
                    </div>
                    <input
                        v-model="searchQuery"
                        @input="onSearchInput"
                        @keydown.enter.prevent="doSearch"
                        type="text"
                        data-testid="location-search"
                        placeholder="Cari alamat / tempat (contoh: Malioboro Yogyakarta)"
                        class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''; searchResults = [];"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-white"
                        title="Bersihkan"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <button
                    v-if="isSecureContext"
                    type="button"
                    @click="useMyLocation"
                    :disabled="locating"
                    class="shrink-0 px-3 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-300 text-sm transition-colors disabled:opacity-50"
                    title="Gunakan lokasi saya"
                >
                    <i v-if="locating" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-location-crosshairs"></i>
                </button>
            </div>
            <!-- Search results dropdown -->
            <div
                v-if="searchResults.length > 0"
                class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg"
            >
                <button
                    v-for="(r, i) in searchResults"
                    :key="i"
                    type="button"
                    @click="selectResult(r)"
                    class="w-full text-left px-3 py-2 hover:bg-amber-50 dark:hover:bg-amber-900/20 border-b border-gray-100 dark:border-gray-700 last:border-0 text-sm transition-colors"
                >
                    <div class="font-medium text-gray-900 dark:text-white line-clamp-1">{{ r.display_name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ r.type }} · {{ r.class }}</div>
                </button>
            </div>
            <p v-if="searching" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                <i class="fas fa-spinner fa-spin mr-1"></i>Mencari...
            </p>
            <p v-else-if="searchError" class="text-red-500 text-xs mt-1">
                <i class="fas fa-exclamation-circle mr-1"></i>{{ searchError }}
            </p>
        </div>

        <!-- Map (read-only mode hides interaction controls visually) -->
        <div
            ref="mapContainer"
            :style="{ height: height }"
            data-testid="location-map"
            class="mt-2 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600 z-0 bg-gray-100 dark:bg-gray-800"
        ></div>

        <!-- Google Maps link (read-only mode, when lat/lng valid) -->
        <a
            v-if="readonly && googleMapsUrl"
            :href="googleMapsUrl"
            target="_blank"
            rel="noopener noreferrer"
            data-testid="location-googlemaps"
            class="mt-1.5 inline-flex items-center gap-1.5 text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 font-medium"
        >
            <i class="fas fa-external-link-alt text-2xs"></i>Buka di Google Maps
        </a>

        <!-- Lat/Lng: input (editable, untuk create/edit) ATAU plain text (untuk detail) -->
        <div data-testid="location-coords" class="mt-1.5">
            <div v-if="readonly" class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        <i class="fas fa-map-pin mr-1"></i>Lat
                    </label>
                    <p class="text-xs font-mono text-gray-900 dark:text-white px-2.5 py-1.5">{{ lat || '—' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        <i class="fas fa-map-pin mr-1"></i>Long
                    </label>
                    <p class="text-xs font-mono text-gray-900 dark:text-white px-2.5 py-1.5">{{ lng || '—' }}</p>
                </div>
            </div>
            <div v-else class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        <i class="fas fa-map-pin mr-1"></i>Lat
                    </label>
                    <input
                        :value="lat"
                        @input="$emit('update:lat', $event.target.value)"
                        type="text"
                        placeholder="-7.xxx"
                        data-testid="location-lat"
                        class="w-full px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs font-mono focus:ring-1 focus:ring-amber-500 outline-none"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        <i class="fas fa-map-pin mr-1"></i>Long
                    </label>
                    <input
                        :value="lng"
                        @input="$emit('update:lng', $event.target.value)"
                        type="text"
                        placeholder="110.xxx"
                        data-testid="location-lng"
                        class="w-full px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs font-mono focus:ring-1 focus:ring-amber-500 outline-none"
                    />
                </div>
            </div>
            <div v-if="!readonly" class="flex items-center justify-between mt-1.5 text-2xs text-gray-400">
                <span>klik peta / drag pin / ketik manual</span>
                <button
                    v-if="lat || lng"
                    type="button"
                    @click="clearMarker"
                    class="text-red-500 hover:text-red-700 dark:hover:text-red-300 font-medium"
                >
                    <i class="fas fa-times mr-0.5"></i>Bersihkan
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Override Leaflet default z-index supaya gak overflow modal header */
:deep(.leaflet-pane) { z-index: 1 !important; }
:deep(.leaflet-top),
:deep(.leaflet-bottom) { z-index: 2 !important; }

/* Dark mode tile filter — invert + hue-rotate bikin OSM tiles readable
   di dark theme. Apply via class yg di-toggle dari script. */
:deep(.leaflet-tile-pane.dark-tiles) {
    filter: invert(1) hue-rotate(180deg) brightness(0.95) contrast(0.85);
}
/* Marker di-keep natural color (counter-invert) supaya tetap visible */
:deep(.leaflet-marker-pane.dark-marker) {
    filter: invert(1) hue-rotate(180deg);
}
/* Attribution control di dark mode juga perlu di-invert */
:deep(.leaflet-control-attribution.dark-tiles) {
    filter: invert(1) hue-rotate(180deg) brightness(0.9);
    background: rgba(255, 255, 255, 0.8) !important;
    color: #1f2937 !important;
}
</style>