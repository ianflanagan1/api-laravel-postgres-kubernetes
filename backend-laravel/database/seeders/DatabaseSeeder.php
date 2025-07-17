<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Item;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $testUser = User::create([  // Keep aligned with user in Tests\TestCase::loggedInUser()
            'name' => 'Test User',
            'email' => 'test@user.com',
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        // Create test item without factory
        // Item::create([
        //     'user_id' => $testUser->id,
        //     'name' => 'Item 1',
        //     'type' => 'Type 1',
        // ]);

        // $this->call([
        //     UserSeeder::class,
        //     ItemSeeder::class,
        // ]);

        $this->command->info('Database seeded successfully!');
    }
}
