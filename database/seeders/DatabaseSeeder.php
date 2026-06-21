<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin user for Filament
        User::firstOrCreate(
            ['email' => 'admin@querosene.test'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            CategorySeeder::class,
            ArtistSeeder::class,
            SongSeeder::class,
            ChordDictionarySeeder::class,
        ]);
    }
}
