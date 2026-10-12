<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsEndpointTest extends TestCase
{
    public function test_news_endpoint_returns_articles_from_the_rss_feed(): void
    {
        Http::fake([
            'eruvaaka.com/feed/*' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">
                    <channel>
                        <lastBuildDate>Mon, 21 Sep 2026 14:20:26 +0000</lastBuildDate>
                        <item>
                            <title>Test news</title>
                            <link>https://eruvaaka.com/test-news/</link>
                            <pubDate>Mon, 21 Sep 2026 14:18:54 +0000</pubDate>
                            <dc:creator>Test Author</dc:creator>
                            <category>Telangana</category>
                            <description><![CDATA[<p>Summary</p>]]></description>
                            <content:encoded><![CDATA[<p>Full story</p>]]></content:encoded>
                        </item>
                        <item>
                            <title>Second news</title>
                            <link>https://eruvaaka.com/second-news/</link>
                        </item>
                        <item>
                            <title>Third news</title>
                            <link>https://eruvaaka.com/third-news/</link>
                        </item>
                    </channel>
                </rss>
                XML),
            'eruvaaka.com/wp-json/wp/v2/posts*' => Http::response([[
                'link' => 'https://eruvaaka.com/test-news/',
                'featured_media' => 37966,
            ]]),
            'eruvaaka.com/wp-json/wp/v2/media*' => Http::response([[
                'id' => 37966,
                'media_details' => [
                    'sizes' => [
                        'tnm-m-2_1' => [
                            'source_url' => 'https://eruvaaka.com/wp-content/uploads/test-news-680x400.jpg',
                        ],
                    ],
                ],
                'source_url' => 'https://eruvaaka.com/wp-content/uploads/test-news.jpg',
            ]]),
        ]);

        $this->getJson('/api/news')
            ->assertOk()
            ->assertJsonPath('count', 3)
            ->assertJsonCount(3, 'items')
            ->assertJsonPath('items.0.title', 'Test news')
            ->assertJsonPath('items.0.creator', 'Test Author')
            ->assertJsonPath('items.0.categories.0', 'Telangana')
            ->assertJsonPath('items.0.thumbnail', 'https://eruvaaka.com/wp-content/uploads/test-news-680x400.jpg')
            ->assertJsonPath('items.0.content', '<p>Full story</p>');
    }
}