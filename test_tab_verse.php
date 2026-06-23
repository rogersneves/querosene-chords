<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$content = "Zeca Pagodinho - Deixa a Vida Me Levar
[Intro]
A  B7  C#m7  C#7

[Tab - Solo da Introdução]
Parte 1 de 2
E|-------4----7--|
B|---4-5---7-----|
G|-6-------------|

Parte 2 de 2
E|---8-----------|
B|---9-----------|

[Primeira Parte]
E               B7
Eu já passei por quase tudo
E
Que eu quero
";

$converter = new App\Services\Import\CifraClubConverter(new App\Services\Import\FormatDetector());
$result = $converter->convert($content);
echo $result['content'];
