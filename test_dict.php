<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$content = "Zeca Pagodinho - Test
[Verso]
E
Test line

Am/C = X 3 2 2 5 X
B/D# = X 6 X 4 7 7
E/G# = 4 X 2 4 5 X
F#m/C# = X 4 4 2 2 2
";

$converter = new App\Services\Import\CifraClubConverter(new App\Services\Import\FormatDetector());
$result = $converter->convert($content);
echo "Output:\n";
echo $result['content'];
echo "\n\nDictionary found? " . (count(json_decode('{}')) ? 'yes' : (strpos($result['content'], 'Am/C') ? 'FOUND IN OUTPUT' : 'not in output'));
