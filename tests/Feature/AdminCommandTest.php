<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_be_created_without_demo_seed(): void
    {
        $this->artisan('users:create-admin', ['--name' => 'Operator', '--email' => 'admin@example.test'])
            ->expectsQuestion('Kata sandi (minimal 8 karakter, huruf dan angka)', 'Safe123!')
            ->expectsQuestion('Konfirmasi kata sandi', 'Safe123!')
            ->expectsOutput('Akun admin berhasil dibuat: admin@example.test')
            ->assertExitCode(0);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.test', 'role' => 'admin']);
    }

    public function test_admin_command_rejects_weak_password(): void
    {
        $this->artisan('users:create-admin', ['--name' => 'Operator', '--email' => 'admin@example.test'])
            ->expectsQuestion('Kata sandi (minimal 8 karakter, huruf dan angka)', 'a')
            ->expectsQuestion('Konfirmasi kata sandi', 'a')
            ->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }
}
