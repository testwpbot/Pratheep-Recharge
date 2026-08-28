<?php

namespace Tests\Unit;

use App\Support\HostingLayout;
use PHPUnit\Framework\TestCase;

class HostingLayoutTest extends TestCase
{
    public function test_local_public_folder_uses_parent_as_app(): void
    {
        $public = sys_get_temp_dir() . '/hpr-public-' . uniqid();
        mkdir($public, 0777, true);

        $this->assertSame(dirname($public), HostingLayout::appPathFromPublic($public));

        rmdir($public);
    }

    public function test_directadmin_public_html_uses_laravel_subfolder(): void
    {
        $web = sys_get_temp_dir() . '/hpr-web-' . uniqid();
        $app = $web . '/laravel';
        mkdir($app . '/bootstrap', 0777, true);
        file_put_contents($app . '/bootstrap/app.php', '<?php');

        $this->assertSame($app, HostingLayout::appPathFromPublic($web));

        unlink($app . '/bootstrap/app.php');
        rmdir($app . '/bootstrap');
        rmdir($app);
        rmdir($web);
    }

    public function test_shared_hosting_is_detected_when_folder_is_named_laravel(): void
    {
        $web = sys_get_temp_dir() . '/hpr-da-' . uniqid();
        $app = $web . '/laravel';
        mkdir($app, 0777, true);
        file_put_contents($web . '/index.php', '<?php');
        file_put_contents($app . '/artisan', '<?php');

        $this->assertTrue(HostingLayout::isSharedHosting($app));

        unlink($web . '/index.php');
        unlink($app . '/artisan');
        rmdir($app);
        rmdir($web);
    }

    public function test_normal_project_folder_is_not_shared_hosting(): void
    {
        $root = sys_get_temp_dir() . '/Pratheep-Recharge-' . uniqid();
        mkdir($root, 0777, true);
        file_put_contents($root . '/artisan', '<?php');

        $this->assertFalse(HostingLayout::isSharedHosting($root));

        unlink($root . '/artisan');
        rmdir($root);
    }
}
