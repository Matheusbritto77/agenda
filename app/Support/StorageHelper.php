<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Resolves a standard public URL for a given stored path or existing URL.
     */
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $cleanPath = self::cleanPath($path);

        if (empty($cleanPath)) {
            return null;
        }

        return Storage::disk('public')->url($cleanPath);
    }

    /**
     * Extracts the relative path within the public disk.
     */
    public static function cleanPath(?string $path): ?string
    {
        if (empty($path) || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        return ltrim(preg_replace('#^/?storage/#', '', $path), '/');
    }

    /**
     * Safely deletes a file from the public disk if it exists.
     */
    public static function delete(?string $path): bool
    {
        $cleanPath = self::cleanPath($path);

        if ($cleanPath && Storage::disk('public')->exists($cleanPath)) {
            return Storage::disk('public')->delete($cleanPath);
        }

        return false;
    }
}
