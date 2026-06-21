<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Rock Nacional',         'color' => '#E53E3E'],
            ['name' => 'MPB',                   'color' => '#D69E2E'],
            ['name' => 'Sertanejo',             'color' => '#38A169'],
            ['name' => 'Gospel',                'color' => '#3182CE'],
            ['name' => 'Pop Internacional',     'color' => '#805AD5'],
            ['name' => 'Rock Internacional',    'color' => '#DD6B20'],
            ['name' => 'Pagode',                'color' => '#D53F8C'],
            ['name' => 'Forró',                 'color' => '#319795'],
        ];

        foreach ($categories as $data) {
            $data['slug'] = Str::slug($data['name']);
            Category::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
