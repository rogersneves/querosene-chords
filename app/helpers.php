<?php

if (! function_exists('artist_slug')) {
    /**
     * Generates a consistent slug for an artist name, normalising common
     * connector variations so that "Bruno & Marrone" and "Bruno e Marrone"
     * resolve to the same slug (bruno-e-marrone).
     *
     * Rules applied before Str::slug():
     *   &  →  e
     *   +  →  e
     *   n' / n' (as in "Guns N' Roses") → stripped to just the surrounding words
     */
    function artist_slug(string $name): string
    {
        // Replace & and + with the Portuguese connector "e"
        $name = preg_replace('/\s*[&+]\s*/', ' e ', $name);
        // Collapse multiple spaces
        $name = preg_replace('/\s+/', ' ', trim($name));

        return \Illuminate\Support\Str::slug($name);
    }
}

if (! function_exists('country_flag')) {
    /**
     * Converts a 2-letter ISO-3166-1 country code to its Unicode flag emoji.
     * Each letter maps to a Regional Indicator Symbol (U+1F1E6–U+1F1FF).
     * Returns the original code unchanged if it is not exactly 2 ASCII letters.
     */
    function country_flag(string $code): string
    {
        $code = strtoupper(trim($code));
        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return $code;
        }
        return mb_chr(0x1F1E6 + ord($code[0]) - ord('A'))
             . mb_chr(0x1F1E6 + ord($code[1]) - ord('A'));
    }
}

if (! function_exists('genre_title')) {
    /**
     * Formats a music genre string with title case, keeping prepositions and
     * articles in lowercase (unless they are the first word).
     *
     * Examples:
     *   "rock"                      → "Rock"
     *   "rhythm and blues"          → "Rhythm and Blues"
     *   "música popular brasileira" → "Música Popular Brasileira"
     *   "forró de raiz"             → "Forró de Raiz"
     */
    function genre_title(string $genre): string
    {
        $exceptions = [
            // Portuguese prepositions / articles
            'de', 'do', 'da', 'dos', 'das', 'di',
            'e', 'em', 'no', 'na', 'nos', 'nas',
            'o', 'a', 'os', 'as', 'um', 'uma',
            'com', 'por', 'para', 'pelo', 'pela',
            'ao', 'à', 'aos', 'às',
            // English prepositions / articles / conjunctions
            'a', 'an', 'the',
            'and', 'or', 'but', 'nor',
            'of', 'in', 'on', 'at', 'to', 'by', 'for', 'from', 'with',
        ];

        $words = explode(' ', mb_strtolower(trim($genre)));

        foreach ($words as $i => &$word) {
            if ($word === '') {
                continue;
            }
            // Always capitalise the first word; keep exceptions in lower case
            if ($i === 0 || ! in_array($word, $exceptions, true)) {
                $word = mb_ucfirst($word);
            }
        }

        return implode(' ', $words);
    }
}
