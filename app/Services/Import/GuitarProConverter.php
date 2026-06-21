<?php

namespace App\Services\Import;

class GuitarProConverter
{
    public function convert(string $filePath): array
    {
        // php-guitarpro não está disponível no Packagist.
        // Esta implementação é um stub documentado para v1.1.
        // Para habilitar suporte GP4/GP5, instale manualmente a biblioteca
        // e substitua este stub pela integração real.
        throw new \RuntimeException(
            'Importação de GuitarPro não está disponível nesta versão. ' .
            'Converta o arquivo para ChordPro ou TXT Cifra Club antes de importar.'
        );
    }

    public function isSupported(): bool
    {
        return false;
    }
}
