<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeSearchService
{
    public function searchVideoId(string $title, string $artist = ''): ?string
    {
        $apiKey = config('services.youtube.api_key');

        if (!$apiKey) {
            return null;
        }

        try {
            $query   = trim($title . ' ' . $artist);
            $cacert  = storage_path('app/cacert.pem');
            $options = file_exists($cacert) ? ['verify' => $cacert] : [];

            $response = Http::timeout(5)->withOptions($options)->get('https://www.googleapis.com/youtube/v3/search', [
                'part' => 'snippet',
                'q' => $query,
                'type' => 'video',
                'maxResults' => 1,
                'key' => $apiKey,
            ]);

            if ($response->successful() && $response->json('pageInfo.totalResults', 0) > 0) {
                $items = $response->json('items', []);
                if (!empty($items[0]['id']['videoId'])) {
                    return $items[0]['id']['videoId'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('YouTube search failed: ' . $e->getMessage());
        }

        return null;
    }
}
