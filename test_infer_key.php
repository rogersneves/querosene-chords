<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create a minimal job instance
$job = new App\Jobs\ProcessBatchImportJob(1, '', null, false, false);

// Use reflection to call private method
$reflection = new ReflectionClass($job);
$method = $reflection->getMethod('inferKeyFromChords');
$method->setAccessible(true);

// Test cases
$tests = [
    '{title: Test}\n[E] [B7] [C#m7] [C#7] [F#m7] [G#m7] [A] [B7]' => 'E',
    '[C] [F] [C] [G7] [C]' => 'C',
    '[Am] [Dm] [E] [Am]' => 'Am',
    '{title: Test}\n[D] [D] [D] [A] [Bm]' => 'D',
];

foreach ($tests as $content => $expected) {
    $result = $method->invoke($job, $content);
    $status = ($result === $expected) ? '✓' : '✗';
    echo "$status Input: " . substr($content, 0, 30) . "... => Got: $result, Expected: $expected\n";
}
