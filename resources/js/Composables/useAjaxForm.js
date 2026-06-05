import { ref } from 'vue';

/**
 * Helper untuk submit form via pure AJAX (fetch + FormData).
 * Lihat dokumentasi/CONVENTIONS.md section 2.
 *
 * Pattern: pakai composable ini untuk form yang TIDAK pindah halaman setelah submit.
 * Untuk navigasi (sidebar, breadcrumb, redirect), tetap pakai Inertia router.
 *
 * @returns { submit, processing, errors }
 *   - submit(url, data, options) → Promise<{ ok, json, status }>
 *   - processing: ref<boolean>
 *   - errors: ref<{ [field]: string[] }>
 */
export function useAjaxForm() {
    const processing = ref(false);
    const errors = ref({});

    function clearErrors() {
        errors.value = {};
    }

    function setError(fieldErrors) {
        // fieldErrors format: { field: ['msg1', 'msg2'] } dari Laravel validation
        errors.value = fieldErrors || {};
    }

    /**
     * Build FormData dari object (recursive) + append _method kalau ada di options.
     * Cocok untuk useForm.data() output atau plain object.
     */
    function buildFormData(data) {
        const fd = new FormData();
        if (!data) return fd;
        for (const [key, value] of Object.entries(data)) {
            if (value === null || value === undefined) continue;
            if (value instanceof File || value instanceof Blob) {
                fd.append(key, value);
            } else if (Array.isArray(value)) {
                for (const item of value) {
                    if (item instanceof File || item instanceof Blob) {
                        fd.append(`${key}[]`, item);
                    } else {
                        fd.append(`${key}[]`, item);
                    }
                }
            } else {
                fd.append(key, value);
            }
        }
        return fd;
    }

    /**
     * Submit form via fetch().
     *
     * @param {string} url - Endpoint AJAX (mis. /api/operator-perusahaan/perusahaan-saya/123)
     * @param {object} data - Form data (object, File akan di-append sebagai file)
     * @param {object} [options]
     * @param {string} [options.method='POST']
     * @param {function} [options.onSuccess] - callback(json)
     * @param {function} [options.onError] - callback(json, status)
     * @returns {Promise<{ ok: boolean, json: any, status: number }>}
     */
    async function submit(url, data = {}, options = {}) {
        const method = options.method || 'POST';
        const fd = buildFormData(data);

        processing.value = true;
        clearErrors();

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const res = await fetch(url, {
                method,
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            let json = {};
            try { json = await res.json(); } catch { /* response may be empty */ }

            if (!res.ok) {
                if (json.errors) setError(json.errors);
                options.onError?.(json, res.status);
                return { ok: false, json, status: res.status };
            }

            options.onSuccess?.(json);
            return { ok: true, json, status: res.status };
        } catch (e) {
            options.onError?.({ message: e.message || 'Network error' }, 0);
            return { ok: false, json: { message: e.message }, status: 0 };
        } finally {
            processing.value = false;
        }
    }

    return { submit, processing, errors, clearErrors, setError };
}
