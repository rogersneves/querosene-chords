<?php

namespace Tests\Unit;

use App\Services\ChordProRenderer;
use PHPUnit\Framework\TestCase;

class ChordProCommentFilterTest extends TestCase
{
    private ChordProRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ChordProRenderer();
    }

    public function test_exibe_apenas_comentario_do_locale_ativo(): void
    {
        $input = implode("\n", [
            '{comment: [PT] Cifra estrutural}',
            '{comment: [EN] Structural chart}',
            '{comment: [ES] Cifra estructural}',
            '{comment: [FR] Grille structurelle}',
        ]);

        $output = $this->renderer->filterCommentsByLocale($input, 'pt');

        $this->assertStringContainsString('{comment: Cifra estrutural}', $output);
        $this->assertStringNotContainsString('[PT]', $output);
        $this->assertStringNotContainsString('[EN]', $output);
        $this->assertStringNotContainsString('[ES]', $output);
        $this->assertStringNotContainsString('[FR]', $output);
        $this->assertStringNotContainsString('Structural chart', $output);
        $this->assertStringNotContainsString('Cifra estructural', $output);
        $this->assertStringNotContainsString('Grille structurelle', $output);
    }

    public function test_comentario_sem_prefixo_sempre_exibido(): void
    {
        $input = implode("\n", [
            '{comment: [PT] Texto PT}',
            '{comment: Sempre visível}',
            '{comment: [EN] Texto EN}',
        ]);

        $output = $this->renderer->filterCommentsByLocale($input, 'fr');

        $this->assertStringContainsString('{comment: Sempre visível}', $output);
        $this->assertStringNotContainsString('Texto PT', $output);
        $this->assertStringNotContainsString('Texto EN', $output);
    }

    public function test_remove_prefixo_do_texto_exibido(): void
    {
        $input  = '{comment: [EN] Orchestral ballad — slow tempo (77 BPM)}';
        $output = $this->renderer->filterCommentsByLocale($input, 'en');

        $this->assertStringContainsString(
            '{comment: Orchestral ballad — slow tempo (77 BPM)}',
            $output
        );
        $this->assertStringNotContainsString('[EN]', $output);
    }

    public function test_locale_case_insensitive(): void
    {
        $input = '{comment: [PT] Texto em português}';

        $this->assertStringContainsString(
            '{comment: Texto em português}',
            $this->renderer->filterCommentsByLocale($input, 'PT')
        );
        $this->assertStringContainsString(
            '{comment: Texto em português}',
            $this->renderer->filterCommentsByLocale($input, 'pt')
        );
    }

    public function test_linhas_nao_comment_passam_inalteradas(): void
    {
        $input = implode("\n", [
            '{title: My Way}',
            '[Am]Texto da [G]música',
            '{comment: [PT] Nota PT}',
            '{start_of_verse}',
        ]);

        $output = $this->renderer->filterCommentsByLocale($input, 'pt');

        $this->assertStringContainsString('{title: My Way}', $output);
        $this->assertStringContainsString('[Am]Texto da [G]música', $output);
        $this->assertStringContainsString('{start_of_verse}', $output);
        $this->assertStringContainsString('{comment: Nota PT}', $output);
    }
}
