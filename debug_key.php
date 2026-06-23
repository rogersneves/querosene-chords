<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$job = new App\Jobs\ProcessBatchImportJob(1, '', null, false, false);
$reflection = new ReflectionClass($job);
$method = $reflection->getMethod('inferKeyFromChords');
$method->setAccessible(true);

$content = '{title: Test}\n[E] [B7] [C#m7] [C#7] [F#m7] [G#m7] [A] [B7]';
echo "Testing: " . substr($content, 0, 50) . "...\n";

// Manually trace through
preg_match_all('/\[([A-G][#b]?[^\]]*)\]/', $content, $matches);
echo "Chords found: " . implode(', ', $matches[1]) . "\n";

$result = $method->invoke($job, $content);
echo "Result: $result\n";
