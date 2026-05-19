<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileProxyController extends Controller
{
    /**
     * Serve file from MinIO disk via Laravel proxy.
     * This avoids MinIO pre-signed URL SignatureDoesNotMatch issues.
     *
     * Route: GET /file/minio/{path?}
     *   - path: relative path in minio disk, e.g. customers/photos/abc.jpg
     * Query params:
     *   - disk: disk name (default: minio)
     */
    public function show(Request $request, string $path = null)
    {
        $disk = $request->query('disk', 'minio');

        if (!$path || !Storage::disk($disk)->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $mimeType = Storage::disk($disk)->mimeType($path);
        $size = Storage::disk($disk)->size($path);
        $lastModified = Storage::disk($disk)->lastModified($path);

        // Stream file — efisien untuk file besar
        return new StreamedResponse(
            function () use ($disk, $path) {
                $stream = Storage::disk($disk)->readStream($path);
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type' => $mimeType ?: 'application/octet-stream',
                'Content-Length' => $size,
                'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
