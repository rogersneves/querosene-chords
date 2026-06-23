<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$content = "Zeca Pagodinho - Deixa a Vida Me Levar
[Intro]
A  B7  C#m7  C#7
F#m7 G#m7 A B7

E  B7

[Primeira Parte]
Am          G
Deixa a vida me levar
";

$converter = new App\Services\Import\CifraClubConverter(new App\Services\Import\FormatDetector());
$result = $converter->convert($content);
echo "=== ChordPro output ===\n";
echo $result['content'];
