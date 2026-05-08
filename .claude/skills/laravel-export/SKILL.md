---
description: Long-running Laravel export/import jobs with progress tracking. Use when the user needs export data, import data, Excel processing, or any job that takes >3 detik.
---

## Prinsip Long-Running Job

### 1. Progress Bar & Persentase
```php
// Di dalam Job, kirim progress
event(new ExportProgress($jobId, $currentRow, $totalRows, $percentage));
```

- Selalu hitung total data sebelum proses
- Kirim event progress setiap N iterasi (misal tiap 100 baris)
- Beri estimasi waktu sisa jika memungkinkan
- Frontend tampilkan progress bar + persentase + status

### 2. Heartbeat
```php
// Kirim heartbeat setiap 5 detik untuk memastikan job masih hidup
event(new ExportHeartbeat($jobId, 'processing', $currentRow));
```

- Gunakan scheduler atau `while` loop dengan timer
- Jika heartbeat hilang >15 detik, anggap job stuck dan tampilkan warning di frontend
- Jangan kirim heartbeat terlalu sering (minimal 5 detik)

### 3. Gunakan Reverb
```php
// Broadcast event via Reverb
class ExportProgress implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function broadcastOn(): Channel
    {
        return new Channel("export.{$this->jobId}");
    }
}
```

- Gunakan private/presence channel jika butuh auth
- Channel name pakai format: `export.{jobId}` atau `import.{jobId}`

### 4. Alur: Create Job ID → Frontend Listen → Dispatch
```
1. POST /export → return jobId (UUID)
2. Frontend subscribe ke channel `export.{jobId}` via Echo/Reverb
3. Setelah subscribe sukses → panggil dispatch endpoint
4. Job mulai jalan, kirim progress event
```

Ini menghindari race condition di mana frontend belum siap listen tapi event sudah terkirim.

### 5. Fallback: Disconnect → Pooling AJAX
```js
// Jika Reverb disconnect, fallback ke polling
let intervalId = null;

Echo.connector.socket.on('disconnect', () => {
    intervalId = setInterval(() => {
        fetch(`/export/${jobId}/progress`)
            .then(r => r.json())
            .then(data => updateProgress(data));
    }, 2000);
});

Echo.connector.socket.on('connect', () => {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
});
```

- Polling interval: 2 detik
- Stop polling setelah Reverb reconnect
- Stop polling jika status selesai/gagal

### 6. Endpoint Progress (untuk polling fallback)
```php
// GET /api/export/{jobId}/progress
Route::get('/export/{jobId}/progress', function ($jobId) {
    return Cache::get("export:{$jobId}:progress", [
        'status' => 'unknown',
        'progress' => 0,
        'total' => 0,
    ]);
});
```

- Simpan progress terakhir di cache (Redis/memcached)
- Key format: `export:{jobId}:progress`
- Data: status, current, total, percentage, message
