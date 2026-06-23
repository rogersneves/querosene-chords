<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$song = App\Models\Song::where('slug', 'like', '%deixa%')->first();
if (!$song) { echo "Song not found\n"; exit; }
$chord = $song->defaultChord;
if (!$chord) { echo "Chord not found\n"; exit; }

$lines = explode("\n", $chord->content);
echo "Lines 70-90 (should be after first tab section):\n";
foreach (array_slice($lines, 70, 20) as $n => $l) {
    echo ($n + 70) . ": " . $l . "\n";
}
