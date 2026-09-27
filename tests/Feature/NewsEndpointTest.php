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
                    </channel>
                </rss>
                XML),
        ]);

        $this->getJson('/api/news')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.title', 'Test news')
            ->assertJsonPath('items.0.creator', 'Test Author')
            ->assertJsonPath('items.0.categories.0', 'Telangana')
            ->assertJsonPath('items.0.content', '<p>Full story</p>');
    }
}