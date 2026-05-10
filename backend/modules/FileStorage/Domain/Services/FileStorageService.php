<?php

namespace Modules\FileStorage\Domain\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\FileStorage\Domain\Models\File;

/**
 * Single entry point for storing, presigning and deleting files.
 * Other modules (Designer, Distribution, …) depend on this — never on Storage:: directly.
 */
class FileStorageService
{
    public function disk(?string $disk = null): Filesystem
    {
        return Storage::disk($disk ?? config('filesystems.default'));
    }

    public function presignUpload(string $key, int $expiresMinutes = 15, ?string $disk = null): array
    {
        $disk ??= config('filesystems.default');

        /** @var \Illuminate\Filesystem\AwsS3V3Adapter $fs */
        $fs = $this->disk($disk);
        $client = $fs->getClient();

        $cmd = $client->getCommand('PutObject', [
            'Bucket' => config("filesystems.disks.$disk.bucket"),
            'Key' => $key,
        ]);

        $request = $client->createPresignedRequest($cmd, "+{$expiresMinutes} minutes");

        return [
            'method' => 'PUT',
            'url' => (string) $request->getUri(),
            'expires_in' => $expiresMinutes * 60,
            'disk' => $disk,
            'key' => $key,
        ];
    }

    public function signedReadUrl(File $file, int $expiresMinutes = 60): string
    {
        return $this->disk($file->disk)->temporaryUrl($file->path, now()->addMinutes($expiresMinutes));
    }

    public function record(array $attrs): File
    {
        return File::query()->create(array_merge([
            'visibility' => 'private',
            'attached' => false,
        ], $attrs));
    }

    public function newPath(string $folder, string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $slug = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'file';
        return rtrim($folder, '/') . '/' . now()->format('Y/m/d') . '/' . Str::uuid() . '-' . $slug . ($extension ? ".$extension" : '');
    }

    public function markAttached(File $file): void
    {
        $file->forceFill(['attached' => true])->save();
    }

    public function delete(File $file): void
    {
        $this->disk($file->disk)->delete($file->path);
        $file->delete();
    }
}
