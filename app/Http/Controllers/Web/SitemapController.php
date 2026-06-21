<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $songs      = Song::where('is_published', true)->get(['slug', 'updated_at']);
        $artists    = Artist::all(['slug', 'updated_at']);
        $categories = Category::all(['slug', 'updated_at']);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= '<url><loc>' . url('/') . '</loc></url>';
        $xml .= '<url><loc>' . url('/busca') . '</loc></url>';

        foreach ($songs as $s) {
            $xml .= "<url><loc>" . url("/cifras/{$s->slug}") . "</loc>"
                . "<lastmod>{$s->updated_at->toDateString()}</lastmod></url>";
        }
        foreach ($artists as $a) {
            $xml .= "<url><loc>" . url("/artistas/{$a->slug}") . "</loc></url>";
        }
        foreach ($categories as $c) {
            $xml .= "<url><loc>" . url("/categorias/{$c->slug}") . "</loc></url>";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
