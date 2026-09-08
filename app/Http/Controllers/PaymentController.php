<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Msg91SmsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate(['userId' => ['nullable', 'integer'], 'plan' => ['required', 'string', 'in:yearly']]);
        $userId = $this->userId($request);
        if (isset($data['userId']) && (int) $data['userId'] !== $userId) return response()->json(['message' => 'You can only create an order for yourself'], 403);
        $plan = config('services.razorpay.plans.'.$data['plan']);
        if (!is_array($plan)) return response()->json(['message' => 'Unsupported plan'], 422);
        $amountPaise = (int) $plan['amount'];
        $keyId = (string) config('services.razorpay.key_id');
        $keySecret = (string) config('services.razorpay.key_secret');
        if ($keyId === '' || $keySecret === '') return response()->json(['message' => 'Payment gateway is not configured'], 500);
        $receipt = 'eru_'.bin2hex(random_bytes(12));
        try {
            $gateway = (new Api($keyId, $keySecret))->order->create(['amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'notes' => ['userId' => (string) $userId, 'plan' => $data['plan']]]);
        } catch (\Throwable $exception) {
            Log::error('Razorpay order creation failed', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'user_id' => $userId,
                'plan' => $data['plan'],
                'key_id' => $keyId,
                'secret_fingerprint' => substr(hash('sha256', $keySecret), 0, 12),
            ]);
            return response()->json(['message' => 'Payment gateway request failed'], 502);
        }
        PaymentOrder::create(['user_id' => $userId, 'razorpay_order_id' => $gateway['id'], 'amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'plan_type' => $data['plan'], 'status' => 'created']);
        return response()->json(['message' => 'Payment order created', 'order_id' => $gateway['id'], 'payment_key' => $keyId, 'order' => ['id' => $gateway['id'], 'amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'key_id' => $keyId]], 201);
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
        try { (new Api((string) config('services.razorpay.key_id'), (string) config('services.razorpay.key_secret')))->utility->verifyPaymentSignature(['razorpay_order_id' => $data['order_id'], 'razorpay_payment_id' => $data['payment_id'], 'razorpay_signature' => $data['signature']]); }
        catch (\Throwable) { return response()->json(['success' => false, 'message' => 'Payment signature verification failed'], 400); }
        DB::transaction(function () use ($order, $data): void {
            $order->update(['razorpay_payment_id' => $data['payment_id'], 'signature' => $data['signature'], 'status' => 'paid']);
            $plan = config('services.razorpay.plans.'.$order->plan_type);
            $start = Carbon::now();
            $subscription = Subscription::where('user_id', $order->user_id)->latest('end_date')->first();
            $start = $subscription && $subscription->end_date->isFuture() ? $subscription->end_date : $start;
            Subscription::create(['user_id' => $order->user_id, 'start_date' => $start, 'end_date' => $start->copy()->addDays((int) $plan['days']), 'plan_type' => $order->plan_type, 'status' => 'active']);
            User::whereKey($order->user_id)->update(['subscription_status' => 'active']);
        });
        $user = User::find($order->user_id);
        if ($user) {
            try {
                app(Msg91SmsService::class)->sendPaymentConfirmation($user->phone, $order->plan_type, $data['payment_id']);
            } catch (\Throwable $exception) {
                Log::warning('Payment confirmation SMS failed', [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'user_id' => $user->id,
                    'payment_id' => $data['payment_id'],
                ]);
            }
        }
        return response()->json(['success' => true, 'message' => 'Payment verified successfully', 'payment_id' => $data['payment_id'], 'order_id' => $data['order_id']]);
    }

    public function subscriptionStatus(int $userId, Request $request): JsonResponse
    {
        if ($userId !== $this->userId($request)) return response()->json(['message' => 'You can only view your own subscription'], 403);
        $subscription = Subscription::where('user_id', $userId)->latest('end_date')->first();
        $active = $subscription?->status === 'active' && $subscription->end_date->isFuture();
        if (!$active && $subscription?->status === 'active') $subscription->update(['status' => 'expired']);
        User::whereKey($userId)->update(['subscription_status' => $active ? 'active' : 'expired']);
        return response()->json(['status' => $active ? 'active' : 'expired', 'plan' => $subscription?->plan_type, 'start_date' => $subscription?->start_date, 'end_date' => $subscription?->end_date]);
    }

    private function userId(Request $request): int { return (int) $request->attributes->get('jwt_claims')['id']; }
}
