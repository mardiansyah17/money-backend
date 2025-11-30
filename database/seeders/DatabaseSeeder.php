<?php

namespace Database\Seeders;

use App\Models\Counterparty;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        User::create([
            'name' => 'Muhammad Mardiansyah',
            'email' => 'mardiansyah@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('Password123!'),
        ]);

        Counterparty::create([
            'user_id' => 1,
            'name' => 'Budi',
            'type' => 'tes'
        ]);

        Wallet::create([
            'type' => 'cash',
            'user_id' => 1,
            'name' => 'Dompet Utama',
            'balance' => 1000000,

        ]);

        $this->call([
        ]);
    }
}
