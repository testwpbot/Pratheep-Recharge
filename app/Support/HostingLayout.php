<?php

namespace App\Support;

use Illuminate\Foundation\Application;

/**
 * DirectAdmin layout:
 *   public_html/              ← website (contents of /public)
 *   public_html/laravel/      ← the rest of the app (.env lives here)
 *
 * Local layout is unchanged (project/public + project/vendor).
 */
class HostingLayout
{
    public static function appPathFromPublic(string $publicDir): string
    {
        $publicDir = rtrim($publicDir, '/\\');
        $hosted = $publicDir . DIRECTORY_SEPARATOR . 'laravel';
        if (is_file($hosted . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php')) {
            return $hosted;
        }

        return dirname($publicDir);
    }

    public static function isSharedHosting(?string $basePath = null): bool
    {
        $base = rtrim($basePath ?: base_path(), '/\\');
        $parent = dirname($base);

        return is_file($parent . DIRECTORY_SEPARATOR . 'index.php')
            && is_file($base . DIRECTORY_SEPARATOR . 'artisan')
            && (
                basename($base) === 'laravel'
                || (is_dir($parent . DIRECTORY_SEPARATOR . 'css') && is_dir($parent . DIRECTORY_SEPARATOR . 'assets'))
            );
    }

    public static function apply(Application $app): void
    {
        $base = rtrim($app->basePath(), '/\\');
        if (! static::isSharedHosting($base)) {
            return;
        }

        $app->usePublicPath(dirname($base));
    }

    /** Create public/storage → storage/app/public when the host allows it. */
    public static function ensurePublicStorage(): void
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (is_link($link) || is_dir($link) || is_file($link)) {
            return;
        }

        if (! is_dir($target)) {
            @mkdir($target, 0755, true);
        }

        if (@symlink($target, $link)) {
            return;
        }

        // Some hosts block symlink(). A real folder still lets Apache serve
        // files if they are written here; artisan storage:link is preferred.
        @mkdir($link, 0755, true);
    }
}
