<?php

namespace App\Services;

class MagazinePlanCatalog
{
    public function normalizeKey(string $key): string
    {
        return $key === 'yearly' ? '1year' : $key;
    }

    public function get(string $key): ?array
    {
        $plan = config('services.razorpay.plans.'.$this->normalizeKey($key));

        if (is_array($plan)) {
            return $plan;
        }

        if (!preg_match('/^(emagazine|print_magazine|combo)_(1year|3year|5year)$/', $key, $matches)) {
            return null;
        }

        $durationPlan = config('services.razorpay.plans.'.$matches[2]);
        if (!is_array($durationPlan)) {
            return null;
        }

        $products = match ($matches[1]) {
            'emagazine' => ['emagazine'],
            'print_magazine' => ['print_magazine'],
            default => ['emagazine', 'print_magazine'],
        };

        return array_merge($durationPlan, [
            'name' => ucfirst(str_replace('_', ' ', $matches[1])).' '.$durationPlan['name'],
            'type' => $matches[1],
            'products' => $products,
        ]);
    }

    public function productsFor(string $planKey): array
    {
        $plan = $this->get($planKey);

        if ($plan && isset($plan['products']) && is_array($plan['products'])) {
            return $plan['products'];
        }

        return in_array($planKey, ['emagazine', 'print_magazine'], true) ? [$planKey] : [];
    }

    public function entitlementPlanFor(string $product): string
    {
        return (string) config('services.razorpay.entitlement_plans.'.$product, $product);
    }

    public function publicPlans(): array
    {
        return collect(config('services.razorpay.plans', []))
            ->reject(fn (array $plan): bool => ($plan['type'] ?? null) === 'legacy')
            ->map(function (array $plan, string $key): array {
                $years = $plan['years'] ?? null;
                $months = $plan['months'] ?? null;
                $result = [
                    'id' => (string) ($years ?? $key),
                    'key' => $key,
                    'name' => $plan['name'] ?? $key,
                    'type' => $plan['type'] ?? 'subscription',
                    'price' => (int) ($plan['amount'] / 100),
                    'currency' => 'INR',
                    'products' => $plan['products'] ?? [],
                ];

                if ($years !== null) {
                    $result['years'] = (int) $years;
                    $result['base_yearly_price'] = (int) ((config('services.razorpay.plans.1year.amount', 0)) / 100);
                }

                if ($months !== null) {
                    $result['months'] = (int) $months;
                }

                return $result;
            })
            ->values()
            ->all();
    }
}