<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    private const SUPPORTED = ['pt', 'en', 'es', 'fr'];

    public function set(string $locale): RedirectResponse
    {
        if (in_array($locale, self::SUPPORTED, true)) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    }
}
