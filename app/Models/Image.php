<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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

    /**
     * Base path for project images
     */
    const BASE_PATH = 'project_img';

    /**
     * Get the parent imageable model (User, Service, etc.).
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Get the full URL of the image
     */
    public function getUrlAttribute(): string
    {
        return asset($this->path);
    }

    /**
     * Get the full storage path
     */
    public function getFullPathAttribute(): string
    {
        return base_path($this->path);
    }

    /**
     * Delete the image file from storage
     */
    public function deleteFile(): bool
    {
        $fullPath = $this->full_path;
        if (File::exists($fullPath)) {
            return File::delete($fullPath);
        }
        return false;
    }

    /**
     * Boot method to delete file when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            $image->deleteFile();
        });
    }

    /**
     * Available image folders
     */
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

    /**
     * Get folder path by key
     */
    public static function getFolderPath(string $folder): string
    {
        $folders = self::folders();
        return $folders[$folder] ?? $folders['general'];
    }

    /**
     * Upload image helper
     */
    public static function upload($file, string $folder, $imageable = null): self
    {
        $folderPath = self::getFolderPath($folder);
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $folderPath . '/' . $filename;
        $fullPath = base_path($folderPath);

        // Ensure directory exists
        if (!File::isDirectory($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        // Move the file to project_img folder
        $file->move($fullPath, $filename);

        // Create the image record
        $image = self::create([
            'name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'folder' => $folder,
            'disk' => 'local',
            'mime_type' => $file->getClientMimeType(),
            'size' => File::size($fullPath . '/' . $filename),
            'imageable_id' => $imageable?->id,
            'imageable_type' => $imageable ? get_class($imageable) : null,
        ]);

        return $image;
    }
}
