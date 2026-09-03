<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    protected $fillable = [
        'name',
        'filename',
        'path',
        'folder',
        'disk',
        'mime_type',
        'size',
        'imageable_id',
        'imageable_type',
    ];

    const BASE_PATH = 'project_img';

    public function imageable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        if ($this->disk && in_array($this->disk, ['public', 's3'], true) && $this->path) {
            return Storage::disk($this->disk)->url($this->path);
        }

        return asset($this->path);
    }

    public function getFullPathAttribute(): string
    {
        if ($this->disk && in_array($this->disk, ['public', 'local'], true) && $this->path) {
            try {
                return Storage::disk($this->disk)->path($this->path);
            } catch (\Throwable) {
                // Fall through to the historical path for legacy records.
            }
        }

        return base_path($this->path);
    }

    public function deleteFile(): bool
    {
        if ($this->disk && in_array($this->disk, ['public', 'local', 's3'], true) && $this->path) {
            try {
                if (Storage::disk($this->disk)->exists($this->path)) {
                    return Storage::disk($this->disk)->delete($this->path);
                }
            } catch (\Throwable) {
                // Fall through to the historical filesystem location.
            }
        }

        $fullPath = $this->full_path;
        if (File::exists($fullPath)) {
            return File::delete($fullPath);
        }

        return false;
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            $image->deleteFile();
        });
    }

    public static function folders(): array
    {
        return [
            'avatars' => self::BASE_PATH . '/avatars',
            'services' => self::BASE_PATH . '/services',
            'tenants' => self::BASE_PATH . '/tenants',
            'invoices' => self::BASE_PATH . '/invoices',
            'general' => self::BASE_PATH . '/general',
        ];
    }

    public static function getFolderPath(string $folder): string
    {
        $folders = self::folders();
        return $folders[$folder] ?? $folders['general'];
    }

    /**
     * Store uploaded images through Laravel's filesystem so the active
     * tenancy filesystem bootstrapper controls tenant-specific storage.
     */
    public static function upload($file, string $folder, $imageable = null): self
    {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('The uploaded image is invalid.');
        }

        $folderPath = self::getFolderPath($folder);
        $extension = strtolower((string) ($file->extension() ?: $file->getClientOriginalExtension()));

        if ($extension === '') {
            throw new \InvalidArgumentException('The uploaded image has no valid extension.');
        }

        $filename = now()->format('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $disk = 'public';
        $path = $folderPath . '/' . $filename;

        Storage::disk($disk)->putFileAs($folderPath, $file, $filename);

        return self::create([
            'name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'folder' => $folder,
            'disk' => $disk,
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'size' => Storage::disk($disk)->size($path),
            'imageable_id' => $imageable?->id,
            'imageable_type' => $imageable ? get_class($imageable) : null,
        ]);
    }
}
