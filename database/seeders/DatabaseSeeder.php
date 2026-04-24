<?php

namespace Database\Seeders;

use App\Models\User;
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
        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'balance' => 250,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );

        $this->call(SlotsSeeder::class);
        $this->call(ItemsSeeder::class);
        $this->call(NotesFoldersSeeder::class);
    }
}
