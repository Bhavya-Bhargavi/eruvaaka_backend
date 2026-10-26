<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MartProductSyncService
{
    public function sync(): int
    {
        $endpoint = (string) config('services.eruvaaka_mart.products_url');
        if ($endpoint === '') {
            throw new RuntimeException('Eruvaaka Mart products URL is not configured.');
        }

        $synced = 0;
        for ($page = 1; ; $page++) {
            $products = Http::acceptJson()
                ->timeout(20)
                ->get($endpoint, ['per_page' => 100, 'page' => $page])
                ->throw()
                ->json();

            if (! is_array($products)) {
                throw new RuntimeException('Eruvaaka Mart returned an invalid products response.');
            }

            foreach ($products as $item) {
                if (! is_array($item) || ! isset($item['id'], $item['name'], $item['permalink'])) {
                    continue;
                }

                $slug = (string) ($item['slug'] ?? '');
                if (str_starts_with($slug, 'auto-draft')) {
                    continue;
                }

                $prices = is_array($item['prices'] ?? null) ? $item['prices'] : [];
                $images = is_array($item['images'] ?? null) ? $item['images'] : [];
                $currencyMinorUnit = max(0, min(6, (int) ($prices['currency_minor_unit'] ?? 2)));
                $priceMinor = $prices['price'] ?? null;

                Product::updateOrCreate(
                    ['source_id' => (string) $item['id']],
                    [
                        'name' => trim(strip_tags((string) $item['name'])),
                        'slug' => $slug !== '' ? $slug : null,
                        'description' => $this->plainText((string) ($item['short_description'] ?? $item['description'] ?? '')),
                        'permalink' => (string) $item['permalink'],
                        'price_minor' => is_string($priceMinor) && ctype_digit($priceMinor) ? (int) $priceMinor : null,
                        'currency_code' => strtoupper((string) ($prices['currency_code'] ?? 'INR')),
                        'currency_minor_unit' => $currencyMinorUnit,
                        'image_url' => isset($images[0]['src']) ? (string) $images[0]['src'] : null,
                        'in_stock' => (bool) ($item['is_in_stock'] ?? false),
                    ],
                );
                $synced++;
            }

            if (count($products) < 100) {
                return $synced;
            }
        }
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}