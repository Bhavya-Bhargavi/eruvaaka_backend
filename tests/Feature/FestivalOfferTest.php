<?php

namespace Tests\Feature;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FestivalOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_active_diwali_offer_is_returned_during_campaign_dates(): void
    {
        Carbon::setTestNow('2026-11-08 12:00:00');
        $this->createDiwaliCoupon();

        $this->getJson('/api/offers/active')
            ->assertOk()
            ->assertJsonPath('offers.0.title', 'Diwali Special')
            ->assertJsonPath('offers.0.coupon_code', 'DIWALI2026')
            ->assertJsonPath('offers.0.discount_type', 'percentage')
            ->assertJsonPath('offers.0.discount_value', 10)
            ->assertJsonPath('offers.0.eligible_plans.8', 'combo_5year');
    }

    public function test_diwali_offer_is_hidden_outside_campaign_dates(): void
    {
        Carbon::setTestNow('2026-11-16 12:00:00');
        $this->createDiwaliCoupon();

        $this->getJson('/api/offers/active')
            ->assertOk()
            ->assertExactJson(['offers' => []]);
    }

    private function createDiwaliCoupon(): Coupon
    {
        return Coupon::create([
            'code' => 'DIWALI2026',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'starts_at' => '2026-10-27',
            'expires_at' => '2026-11-15',
            'offer_title' => 'Diwali Special',
            'offer_message' => 'Celebrate Diwali with 10% off your magazine plan.',
        ]);
    }
}