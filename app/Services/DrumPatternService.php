<?php

namespace App\Services;

class DrumPatternService
{
    /**
     * Retorna o padrão de bateria minimalista.
     *
     * Regra fixa para toda a música:
     * - Step 0  → kick (bumbo na primeira batida do compasso)
     * - Steps 1-15 → hihat (hihat nas demais batidas)
     *
     * Grid de 16 steps (semicolcheias) em 4/4.
     * Step 0 = tempo 1, step 4 = tempo 2, step 8 = tempo 3, step 12 = tempo 4.
     */
    public function getPattern(
        string  $genre,
        int     $bpm,
        string  $timeSig   = '4/4',
        ?string $drumStyle = null
    ): array {
        return [
            'bpm'            => $bpm,
            'style'          => 'minimal',
            'time_signature' => $this->parseTimeSig($timeSig),
            'patterns'       => [
                'main'  => $this->minimalPattern(),
                'fill'  => $this->minimalPattern(),
                'intro' => $this->minimalPattern(),
                'outro' => $this->minimalPattern(),
            ],
        ];
    }

    private function minimalPattern(): array
    {
        return [
            'kick'  => [0],
            'hihat' => [2, 4, 6, 8, 10, 12, 14],
        ];
    }

    private function parseTimeSig(string $ts): array
    {
        [$num, $den] = explode('/', $ts . '/4');
        return [(int) $num, (int) $den];
    }
}
