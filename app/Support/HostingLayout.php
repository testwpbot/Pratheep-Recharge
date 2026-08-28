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

    /**
     * Do not create a storage symlink on DirectAdmin.
     * is_link() / symlink() hit open_basedir and take the whole site down.
     */
    public static function ensurePublicStorage(): void
    {
        // no-op on purpose
    }
}
