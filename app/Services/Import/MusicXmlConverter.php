<?php

namespace App\Services\Import;

class MusicXmlConverter
{
    public function convert(string $filePath): array
    {
        $content = file_get_contents($filePath);

        // Handle compressed MusicXML (.mxl)
        if (str_ends_with(strtolower($filePath), '.mxl')) {
            $content = $this->extractMxl($filePath);
        }

        if ($content === false || empty($content)) {
            throw new \RuntimeException('Não foi possível ler o arquivo MusicXML.');
        }

        $xml = @simplexml_load_string($content);

        if ($xml === false) {
            throw new \InvalidArgumentException('O arquivo XML não é válido.');
        }

        $xml->registerXPathNamespace('m', 'http://www.musicxml.org/schema/mxl');

        $title = $this->extractTitle($xml);
        $artist = $this->extractArtist($xml);
        $chordPro = $this->buildChordPro($xml, $title, $artist);

        return [
            'title' => $title,
            'artist' => $artist,
            'key' => null,
            'content' => $chordPro,
            'tab_content' => null,
        ];
    }

    private function extractTitle(\SimpleXMLElement $xml): string
    {
        $title = (string) ($xml->{'work'}->{'work-title'} ?? '');

        if (empty($title)) {
            $title = (string) ($xml->{'movement-title'} ?? '');
        }

        return $title;
    }

    private function extractArtist(\SimpleXMLElement $xml): string
    {
        foreach ($xml->{'identification'}->{'creator'} ?? [] as $creator) {
            if ((string) $creator['type'] === 'composer') {
                return (string) $creator;
            }
        }
        return '';
    }

    private function buildChordPro(\SimpleXMLElement $xml, string $title, string $artist): string
    {
        $lines = [];

        if (!empty($title)) {
            $lines[] = "{title: {$title}}";
        }
        if (!empty($artist)) {
            $lines[] = "{artist: {$artist}}";
        }
        $lines[] = '';

        $parts = $xml->{'part-list'}->{'score-part'} ?? [];
        $partIds = [];
        foreach ($parts as $part) {
            $partIds[] = (string) $part['id'];
        }

        foreach ($xml->{'part'} ?? [] as $part) {
            $partId = (string) $part['id'];

            foreach ($part->{'measure'} ?? [] as $measure) {
                $harmony = null;

                foreach ($measure->children() as $element) {
                    $tag = $element->getName();

                    if ($tag === 'harmony') {
                        $root = (string) ($element->{'root'}->{'root-step'} ?? '');
                        $alter = (string) ($element->{'root'}->{'root-alter'} ?? '0');
                        $kind = (string) ($element->{'kind'} ?? '');

                        $harmony = $root . $this->alterToSymbol($alter) . $this->kindToChordSuffix($kind);
                    }

                    if ($tag === 'note') {
                        $lyric = $element->{'lyric'};
                        if ($lyric && !empty((string) $lyric->{'text'})) {
                            $text = (string) $lyric->{'text'};
                            $syllabic = (string) ($lyric->{'syllabic'} ?? 'single');

                            $prefix = $harmony ? "[{$harmony}]" : '';
                            $harmony = null;

                            $suffix = in_array($syllabic, ['begin', 'middle']) ? '-' : ' ';
                            $lines[] = $prefix . $text . $suffix;
                        }
                    }
                }
            }
        }

        return implode('', $lines);
    }

    private function alterToSymbol(string $alter): string
    {
        return match ($alter) {
            '1' => '#',
            '-1' => 'b',
            default => '',
        };
    }

    private function kindToChordSuffix(string $kind): string
    {
        return match ($kind) {
            'minor' => 'm',
            'dominant' => '7',
            'major-seventh' => 'maj7',
            'minor-seventh' => 'm7',
            'diminished' => 'dim',
            'augmented' => 'aug',
            'suspended-fourth' => 'sus4',
            'suspended-second' => 'sus2',
            default => '',
        };
    }

    private function extractMxl(string $filePath): string|false
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return false;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with(strtolower($name), '.xml') && !str_starts_with($name, 'META-INF')) {
                $content = $zip->getFromIndex($i);
                $zip->close();
                return $content;
            }
        }

        $zip->close();
        return false;
    }
}
