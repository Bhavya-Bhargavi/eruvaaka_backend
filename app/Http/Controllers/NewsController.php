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
        try {
            $feed = Cache::remember('eruvaaka.news.feed.v2', now()->addMinutes(10), fn (): array => $this->fetchFeed());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'News feed is temporarily unavailable'], 502);
        }

        return response()->json([
            'source' => config('services.eruvaaka.rss_url'),
            'last_build_date' => $feed['last_build_date'],
            'count' => count($feed['items']),
            'items' => $feed['items'],
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

        $featuredImages = $this->fetchFeaturedImages();
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
                'thumbnail' => $featuredImages[$this->normalizeArticleUrl((string) $item->link)] ?? null,
            ];
        }

        return [
            'last_build_date' => (string) $xml->channel->lastBuildDate,
            'items' => $items,
        ];
    }

    private function fetchFeaturedImages(): array
    {
        try {
            $posts = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->get((string) config('services.eruvaaka.wp_api_posts_url'), [
                    'per_page' => 100,
                    '_embed' => 'wp:featuredmedia',
                    '_fields' => 'link,_embedded',
                ])
                ->throw()
                ->json();
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        if (!is_array($posts)) {
            return [];
        }

        $images = [];
        foreach ($posts as $post) {
            $link = $post['link'] ?? null;
            $media = $post['_embedded']['wp:featuredmedia'][0] ?? [];
            $sizes = $media['media_details']['sizes'] ?? [];
            $thumbnail = $sizes['tnm-m-2_1']['source_url']
                ?? $sizes['medium_large']['source_url']
                ?? $media['source_url']
                ?? null;

            if (is_string($link) && is_string($thumbnail) && $thumbnail !== '') {
                $images[$this->normalizeArticleUrl($link)] = $thumbnail;
            }
        }

        return $images;
    }

    private function normalizeArticleUrl(string $url): string
    {
        return rtrim($url, '/');
    }
}