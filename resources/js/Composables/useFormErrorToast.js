export function errorSummary(errors, max = 3) {
  const msgs = Object.entries(errors || {})
    .filter(([, v]) => v)
    .map(([, v]) => (Array.isArray(v) ? v[0] : v));
  if (!msgs.length) return 'Validasi gagal. Periksa kembali isian form.';
  const shown = msgs.slice(0, max).join(' | ');
  return msgs.length > max ? `${shown} (+${msgs.length - max} error lainnya)` : shown;
}
