<?php

namespace App\Services\Products;

final class ProductImagePath
{
    public static function normalize(?string $path): ?string
    {
        $path = trim((string) $path);

        return $path === '' ? null : ltrim($path, '/');
    }

    public static function isSafe(?string $path): bool
    {
        if ($path === null || $path === '') {
            return true;
        }

        if (str_contains($path, '..')
            || str_contains($path, '\\')
            || str_contains($path, '//')
            || str_contains($path, ':')
            || str_contains($path, '?')
            || str_contains($path, '#')
            || preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return false;
        }

        return preg_match('/\Aassets\/[A-Za-z0-9][A-Za-z0-9._\/-]*\.(?:avif|gif|jpe?g|png|svg|webp)\z/i', $path) === 1;
    }

    public static function exists(?string $path): bool
    {
        $path = self::normalize($path);

        return $path !== null && self::isSafe($path) && is_file(public_path($path));
    }

    public static function previewUrl(?string $path): ?string
    {
        $path = self::normalize($path);

        if (! self::exists($path)) {
            return null;
        }

        return asset($path);
    }
}
