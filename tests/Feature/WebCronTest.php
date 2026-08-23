<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebCronTest extends TestCase
{
    use RefreshDatabase;

    public function test_cron_url_runs_the_scheduler(): void
    {
        $this->get('/cron.php')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertDontSee('<html', false);
    }

    public function test_short_cron_url_also_works(): void
    {
        $this->get('/cron')->assertOk();
    }

    public function test_cron_key_blocks_strangers_when_set(): void
    {
        config(['app.cron_key' => 'secret-clock']);

        $this->get('/cron.php')->assertNotFound();
        $this->get('/cron.php?key=wrong')->assertNotFound();
        $this->get('/cron.php?key=secret-clock')->assertOk();
    }
}
