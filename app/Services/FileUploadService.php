<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileUploadService
{
    private int $maxWidth;
    private int $maxHeight;
    private int $maxFileSizeKb;
    private bool $autoCompress;

    public function __construct()
    {
        $maxDim = (int) \App\Models\SaasConfig::getValue('default_upload_max_width_and_height_image', 1920);
        $this->maxWidth = $maxDim;
        $this->maxHeight = $maxDim;
        $this->maxFileSizeKb = (int) \App\Models\SaasConfig::getValue('default_upload_max_file_size_in_kb', 2048);
        $this->autoCompress = (bool) \App\Models\SaasConfig::getValue('default_auto_compress_file_upload', true);
    }

    public function processImage(UploadedFile $file, string $folder = 'general'): ?string
    {
        $mime = $file->getMimeType();

        // Check if PDF - compress with limited settings
        if ($mime === 'application/pdf') {
            return $this->processPdf($file, $folder);
        }

        // For images: compress to webp if autoCompress is enabled
        if ($this->autoCompress) {
            return $this->compressImageToWebp($file, $folder);
        }

        // Otherwise store as original
        return $this->storeFile($file, $folder);
    }

    public function processDocument(UploadedFile $file, string $folder = 'documents'): ?string
    {
        $mime = $file->getMimeType();

        if ($mime === 'application/pdf') {
            return $this->processPdf($file, $folder);
        }

        return $this->processImage($file, $folder);
    }

    private function compressImageToWebp(UploadedFile $file, string $folder): ?string
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getPathname());

            // Resize if needed maintaining aspect ratio
            $width = $image->width();
            $height = $image->height();

            if ($width > $this->maxWidth || $height > $this->maxHeight) {
                $image = $image->scaleDown($this->maxWidth, $this->maxHeight);
            }

            // Convert to webp
            $filename = (string) Str::uuid7() . '.webp';
            $path = $folder . '/photos/' . $filename;

            $image->toWebp(80)->saveInto(Storage::disk('minio')->path($path));

            return $path;
        } catch (\Exception $e) {
            // Fallback to original file if compression fails
            return $this->storeFile($file, $folder);
        }
    }

    private function processPdf(UploadedFile $file, string $folder): ?string
    {
        // For PDF, just store as-is for now
        // Future: implement PDF compression if needed
        return $this->storeFile($file, $folder);
    }

    private function storeFile(UploadedFile $file, string $folder): ?string
    {
        try {
            $ext = $file->getClientOriginalExtension() ?: 'bin';
            $filename = (string) Str::uuid7() . '.' . $ext;
            $path = $folder . '/photos/' . $filename;

            return $file->storeAs($folder . '/photos', $filename, ['disk' => 'minio', 'visibility' => 'private']);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function deleteFile(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        try {
            return Storage::disk('minio')->delete($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getAcceptedImageExtensions(): string
    {
        return 'jpg,jpeg,png,webp';
    }

    public static function getAcceptedDocumentExtensions(): string
    {
        return 'jpg,jpeg,png,webp,pdf';
    }

    public static function getMaxFileSizeMb(): int
    {
        return 2; // 2MB
    }

    public static function getMaxFileSizeKb(): int
    {
        return 2048;
    }
}