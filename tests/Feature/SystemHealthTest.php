<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_system_health_checks_table(): void
    {
        $this->assertTrue(Schema::hasTable('system_health_checks'));

        $this->assertTrue(Schema::hasColumns('system_health_checks', [
            'id',
            'check_key',
            'status',
            'last_checked_at',
            'details',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_health_check_record_can_be_stored(): void
    {
        DB::table('system_health_checks')->insert([
            'check_key' => 'database',
            'status' => 'ok',
            'details' => 'SQLite health check during Gate 0.',
            'last_checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseCount('system_health_checks', 1);
        $this->assertDatabaseHas('system_health_checks', [
            'check_key' => 'database',
            'status' => 'ok',
        ]);
    }

    public function test_application_returns_successful_response(): void
    {
        $this->get('/')->assertOk();
    }
}
