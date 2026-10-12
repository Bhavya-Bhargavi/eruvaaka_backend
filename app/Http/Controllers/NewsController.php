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
                    '_fields' => 'link,featured_media',
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

        $postsByMediaId = [];
        foreach ($posts as $post) {
            $link = $post['link'] ?? null;
            $mediaId = $post['featured_media'] ?? null;

            if (is_string($link) && is_numeric($mediaId) && (int) $mediaId > 0) {
                $postsByMediaId[(int) $mediaId] = $this->normalizeArticleUrl($link);
            }
        }

        if ($postsByMediaId === []) {
            return [];
        }

        try {
            $mediaItems = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->get((string) config('services.eruvaaka.wp_api_media_url'), [
                    'include' => array_keys($postsByMediaId),
                    'per_page' => 100,
                    '_fields' => 'id,source_url,media_details',
                ])
                ->throw()
                ->json();
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        if (!is_array($mediaItems)) {
            return [];
        }

        $images = [];
        foreach ($mediaItems as $media) {
            $mediaId = $media['id'] ?? null;
            $sizes = $media['media_details']['sizes'] ?? [];
            $thumbnail = $sizes['tnm-m-2_1']['source_url']
                ?? $sizes['medium_large']['source_url']
                ?? $media['source_url']
                ?? null;

            if (is_numeric($mediaId) && isset($postsByMediaId[(int) $mediaId]) && is_string($thumbnail) && $thumbnail !== '') {
                $images[$postsByMediaId[(int) $mediaId]] = $thumbnail;
            }
        }

        return $images;
    }

    private function normalizeArticleUrl(string $url): string
    {
        return rtrim($url, '/');
    }
}