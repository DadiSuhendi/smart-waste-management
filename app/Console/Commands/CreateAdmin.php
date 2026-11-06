<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'users:create-admin {--name=} {--email=}';

    protected $description = 'Create an administrator without seeding demo accounts';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nama admin');
        $email = $this->option('email') ?: $this->ask('Email admin');
        $password = $this->secret('Kata sandi (minimal 8 karakter, huruf dan angka)');
        $confirmation = $this->secret('Konfirmasi kata sandi');
        $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], [
            'name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        $admin = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $admin->role = 'admin';
        $admin->save();
        $this->info('Akun admin berhasil dibuat: '.$admin->email);

        return self::SUCCESS;
    }
}
