<?php

namespace Database\Seeders;

use App\Models\Artist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        $artists = [
            ['name' => 'Legião Urbana',  'genre' => 'Rock Nacional',   'country' => 'BR'],
            ['name' => 'Raul Seixas',    'genre' => 'Rock Nacional',   'country' => 'BR'],
            ['name' => 'Roberto Carlos', 'genre' => 'MPB',             'country' => 'BR'],
            ['name' => 'Djavan',         'genre' => 'MPB',             'country' => 'BR'],
            ['name' => 'Skank',          'genre' => 'Rock Nacional',   'country' => 'BR'],
        ];

        foreach ($artists as $data) {
            $data['slug'] = Str::slug($data['name']);
            Artist::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
