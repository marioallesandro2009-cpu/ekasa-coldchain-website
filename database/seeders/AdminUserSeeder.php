<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('site.admin.email');
        $password = config('site.admin.password');

        if (! $email || ! $password) {
            $this->command->warn('ADMIN_EMAIL / ADMIN_PASSWORD belum diisi di .env, admin tidak dibuat.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('site.admin.name'),
                'password' => $password,
                'email_verified_at' => now(),
            ],
        )->forceFill(['is_admin' => true])->save();
    }
}
