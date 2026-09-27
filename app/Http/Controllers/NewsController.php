<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class NewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['limit' => ['sometimes', 'integer', 'min:1', 'max:50']]);

        try {
            $feed = Cache::remember('eruvaaka.news.feed', now()->addMinutes(10), fn (): array => $this->fetchFeed());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'News feed is temporarily unavailable'], 502);
        }

        $limit = (int) ($data['limit'] ?? 50);

        return response()->json([
            'source' => config('services.eruvaaka.rss_url'),
            'last_build_date' => $feed['last_build_date'],
            'count' => min(count($feed['items']), $limit),
            'items' => array_slice($feed['items'], 0, $limit),
        ]);
    }

    private function fetchFeed(): array
    {
        $response = Http::accept('application/rss+xml, application/xml, text/xml')
            ->connectTimeout(5)
            ->timeout(10)
            ->get((string) config('services.eruvaaka.rss_url'))
            ->throw();

        $previousErrorSetting = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($response->body(), SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorSetting);
        }

        if (!$xml || !isset($xml->channel)) {
            throw new RuntimeException('The news feed returned invalid RSS XML.');
        }

        $items = [];
        foreach ($xml->channel->item as $item) {
            $content = $item->children('content', true);
            $creator = $item->children('dc', true);
            $categories = [];

            foreach ($item->category as $category) {
                $categories[] = (string) $category;
            }

            $items[] = [
                'title' => (string) $item->title,
                'link' => (string) $item->link,
                'published_at' => (string) $item->pubDate,
                'creator' => (string) $creator->creator,
                'categories' => $categories,
                'description' => (string) $item->description,
                'content' => (string) $content->encoded,
            ];
        }

        return [
            'last_build_date' => (string) $xml->channel->lastBuildDate,
            'items' => $items,
        ];
    }
}