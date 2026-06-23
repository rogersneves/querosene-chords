<?php

namespace App\Console\Commands;

use App\Models\Song;
use Illuminate\Console\Command;

class BackfillChordList extends Command
{
    protected $signature   = 'songs:backfill-chords';
    protected $description = 'Populate chord_list JSON for songs that have a default chord but no chord_list yet';

    public function handle(): int
    {
        $songs = Song::whereNull('chord_list')
            ->with('defaultChord')
            ->get();

        $this->info("Processing {$songs->count()} songs…");
        $bar = $this->output->createProgressBar($songs->count());

        $updated = 0;
        foreach ($songs as $song) {
            $content = $song->defaultChord?->content;
            if ($content) {
                $song->updateQuietly(['chord_list' => Song::extractChordList($content)]);
                $updated++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$updated} songs updated.");

        return self::SUCCESS;
    }
}
