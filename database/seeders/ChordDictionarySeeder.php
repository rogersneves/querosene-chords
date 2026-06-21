<?php

namespace Database\Seeders;

use App\Services\Import\ChordDictionary;
use Illuminate\Database\Seeder;

class ChordDictionarySeeder extends Seeder
{
    public function run(): void
    {
        ChordDictionary::seedMissing(array_keys(ChordDictionary::all()));
        $this->command->info('Chord dictionary seeded: ' . count(ChordDictionary::all()) . ' chords.');
    }
}
