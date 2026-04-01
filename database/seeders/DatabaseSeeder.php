<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $walletService = app(WalletService::class);

        $demoUsers = collect([
            ['name' => 'Ana Martins', 'email' => 'ana@legionbankin.test'],
            ['name' => 'Bruno Alves', 'email' => 'bruno@legionbankin.test'],
        ])->map(function (array $attributes) {
            return User::query()->firstOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => Hash::make('Password123'),
                ],
            );
        });

        $demoUsers->each(function (User $user) use ($walletService): void {
            if ((float) $user->wallet->balance === 0.0) {
                $walletService->deposit($user, '1000.00', 'Saldo inicial de demonstração');
            }
        });
    }
}
