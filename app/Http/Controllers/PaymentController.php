<?php

namespace App\Http\Controllers;

use App\Http\Requests\CouponValidationRequest;
use App\Http\Requests\CreatePaymentOrderRequest;
use App\Models\CouponUsage;
use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CouponService;
use App\Services\InfinitoSmsService;
use App\Services\MagazinePlanCatalog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    public function createOrder(CreatePaymentOrderRequest $request, CouponService $couponService, MagazinePlanCatalog $planCatalog): JsonResponse
    {
        $data = $request->validated();
        $userId = $this->userId($request);
        if (isset($data['userId']) && (int) $data['userId'] !== $userId) return response()->json(['message' => 'You can only create an order for yourself'], 403);
        $planType = $planCatalog->normalizeKey($data['plan']);
        $plan = $planCatalog->get($planType);
        if (!$plan) return response()->json(['message' => 'Unsupported plan'], 422);
        $originalAmountPaise = (int) $plan['amount'];
        $amountPaise = $originalAmountPaise;
        $coupon = null;
        $discountPaise = 0;
        if (!empty($data['coupon_code'])) {
            $quote = $couponService->quote($data['coupon_code'], $userId, $amountPaise);
            if (!$quote['valid']) return response()->json(['valid' => false, 'message' => $quote['message']], $quote['status']);
            $coupon = $quote['coupon'];
            $discountPaise = $quote['discount_amount'];
            $amountPaise = $quote['final_amount'];
        }
        $keyId = (string) config('services.razorpay.key_id');
        $keySecret = (string) config('services.razorpay.key_secret');
        if ($keyId === '' || $keySecret === '') return response()->json(['message' => 'Payment gateway is not configured'], 500);
        $receipt = 'eru_'.bin2hex(random_bytes(12));
        try {
            $gateway = (new Api($keyId, $keySecret))->order->create(['amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'notes' => ['userId' => (string) $userId, 'plan' => $planType]]);
        } catch (\Throwable $exception) {
            Log::error('Razorpay order creation failed', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'user_id' => $userId,
                'plan' => $planType,
                'key_id' => $keyId,
                'secret_fingerprint' => substr(hash('sha256', $keySecret), 0, 12),
            ]);
            return response()->json(['message' => 'Payment gateway request failed'], 502);
        }
        PaymentOrder::create(['user_id' => $userId, 'razorpay_order_id' => $gateway['id'], 'amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'plan_type' => $planType, 'shipping_address' => $data['shipping_address'] ?? null, 'status' => 'created', 'coupon_id' => $coupon?->id, 'discount_amount' => $discountPaise]);
        return response()->json([
            'message' => 'Payment order created',
            'order_id' => $gateway['id'],
            'payment_key' => $keyId,
            'original_amount' => round($originalAmountPaise / 100, 2),
            'discount' => round($discountPaise / 100, 2),
            'final_amount' => round($amountPaise / 100, 2),
            'currency' => 'INR',
            'order' => ['id' => $gateway['id'], 'amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'key_id' => $keyId],
        ], 201);
    }

    public function validateCoupon(CouponValidationRequest $request, CouponService $couponService, MagazinePlanCatalog $planCatalog): JsonResponse
    {
        $data = $request->validated();
        $plan = $planCatalog->get($data['plan']);
        if (!$plan) return response()->json(['valid' => false, 'message' => 'Unsupported plan'], 422);

        $quote = $couponService->quote($data['coupon_code'], $this->userId($request), (int) $plan['amount']);
        if (!$quote['valid']) return response()->json(['valid' => false, 'message' => $quote['message']], $quote['status']);

        return response()->json([
            'valid' => true,
            'original_amount' => round(((int) $plan['amount']) / 100, 2),
            'discount' => round($quote['discount_amount'] / 100, 2),
            'final_amount' => round($quote['final_amount'] / 100, 2),
            'currency' => 'INR',
        ]);
    }

    public function verifyPayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required_without:razorpay_order_id', 'string'],
            'payment_id' => ['required_without:razorpay_payment_id', 'string'],
            'signature' => ['required_without:razorpay_signature', 'string'],
            'razorpay_order_id' => ['required_without:order_id', 'string'],
            'razorpay_payment_id' => ['required_without:payment_id', 'string'],
            'razorpay_signature' => ['required_without:signature', 'string'],
        ]);
        $data['order_id'] ??= $data['razorpay_order_id'];
        $data['payment_id'] ??= $data['razorpay_payment_id'];
        $data['signature'] ??= $data['razorpay_signature'];
        $order = PaymentOrder::where('razorpay_order_id', $data['order_id'])->where('user_id', $this->userId($request))->first();
        if (!$order) return response()->json(['message' => 'Payment order not found'], 404);
        if ($order->currency !== 'INR' || $order->status === 'paid') return response()->json(['message' => 'Payment order cannot be verified'], 409);
        $planCatalog = app(MagazinePlanCatalog::class);
        $planType = $planCatalog->normalizeKey($order->plan_type ?? '');
        $plan = $planCatalog->get($planType);
        if (!$plan) return response()->json(['message' => 'Payment order has an unsupported plan'], 409);
        try { (new Api((string) config('services.razorpay.key_id'), (string) config('services.razorpay.key_secret')))->utility->verifyPaymentSignature(['razorpay_order_id' => $data['order_id'], 'razorpay_payment_id' => $data['payment_id'], 'razorpay_signature' => $data['signature']]); }
        catch (\Throwable) { return response()->json(['success' => false, 'message' => 'Payment signature verification failed'], 400); }
        DB::transaction(function () use ($order, $data, $plan, $planType, $planCatalog): void {
            $order->update(['razorpay_payment_id' => $data['payment_id'], 'signature' => $data['signature'], 'status' => 'paid']);
            if ($order->coupon_id) {
                CouponUsage::firstOrCreate(
                    ['coupon_id' => $order->coupon_id, 'user_id' => $order->user_id],
                    ['payment_order_id' => $order->id, 'discount_amount' => $order->discount_amount, 'used_at' => now()]
                );
            }
            $existingSubscriptions = Subscription::where('user_id', $order->user_id)
                ->where('status', 'active')
                ->get();
            foreach ($planCatalog->productsFor($planType) as $product) {
                $latestEntitlement = $existingSubscriptions
                    ->filter(fn (Subscription $subscription): bool => in_array($product, $planCatalog->productsFor($subscription->plan_type), true))
                    ->sortByDesc('end_date')
                    ->first();
                $start = $latestEntitlement && $latestEntitlement->end_date->isFuture()
                    ? $latestEntitlement->end_date->copy()
                    : Carbon::now();
                $end = isset($plan['months'])
                    ? $start->copy()->addMonths((int) $plan['months'])
                    : $start->copy()->addYears((int) ($plan['years'] ?? 1));
                Subscription::create([
                    'user_id' => $order->user_id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'plan_type' => $planType,
                    'status' => 'active',
                ]);
            }
            User::whereKey($order->user_id)->update(['subscription_status' => 'active']);
        });
        $user = User::find($order->user_id);
        if ($user) {
            try {
                app(InfinitoSmsService::class)->sendPaymentConfirmation($user->phone, $planType, $data['payment_id']);
            } catch (\Throwable $exception) {
                Log::warning('Payment confirmation SMS failed', [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'user_id' => $user->id,
                    'payment_id' => $data['payment_id'],
                ]);
            }
        }

        return response()->json(array_merge([
            'success' => true,
            'message' => 'Payment verified successfully',
            'payment_id' => $data['payment_id'],
            'order_id' => $data['order_id'],
        ], $this->subscriptionResponse((int) $order->user_id)));
    }

    public function subscriptionStatus(int $userId, Request $request): JsonResponse
    {
        if ($userId !== $this->userId($request)) return response()->json(['message' => 'You can only view your own subscription'], 403);
        $response = $this->subscriptionResponse($userId);
        return response()->json($response);
    }

    private function subscriptionResponse(int $userId): array
    {
        $planCatalog = app(MagazinePlanCatalog::class);
        $subscriptions = Subscription::where('user_id', $userId)->get();
        $currentSubscription = $subscriptions->sortByDesc('end_date')->first();
        $productAccess = [];
        foreach (['emagazine', 'print_magazine'] as $product) {
            $productAccess[$product] = $subscriptions->contains(fn (Subscription $subscription): bool =>
                $subscription->status === 'active'
                && $subscription->end_date->isFuture()
                && in_array($product, $planCatalog->productsFor($subscription->plan_type), true)
            );
        }
        $active = in_array(true, $productAccess, true);

        foreach ($subscriptions as $subscription) {
            if ($subscription->status === 'active' && !$subscription->end_date->isFuture()) {
                $subscription->update(['status' => 'expired']);
            }
        }

        User::whereKey($userId)->update(['subscription_status' => $active ? 'active' : 'expired']);

        $currentPlan = $currentSubscription ? $planCatalog->get($currentSubscription->plan_type) : null;
        $years = $currentPlan['years'] ?? null;

        return [
            'status' => $active ? 'active' : 'expired',
            'plan' => $currentSubscription?->plan_type,
            'start_date' => $currentSubscription?->start_date,
            'end_date' => $currentSubscription?->end_date,
            'subscription' => [
                'active' => $active,
                'plan_id' => $currentSubscription ? (string) ($years ?? $currentSubscription->plan_type) : null,
                'expiry_date' => $active ? $currentSubscription->end_date : null,
            ],
            'content_access' => [
                'emagazine' => ['visible' => true, 'accessible' => $productAccess['emagazine']],
                'print_magazine' => ['visible' => true, 'accessible' => $productAccess['print_magazine']],
                'subscription_plans' => ['visible' => !$active],
            ],
            'next_action' => $productAccess['emagazine'] ? 'open_emagazine_viewer' : 'show_magazine_options',
            'plans' => $this->availablePlans(),
        ];
    }

    private function availablePlans(): array
    {
        return app(MagazinePlanCatalog::class)->publicPlans();
    }

    private function userId(Request $request): int { return (int) $request->attributes->get('jwt_claims')['id']; }
}
