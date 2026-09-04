<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate(['amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'], 'currency' => ['required', 'in:INR']]);
        $amountPaise = $this->rupeesToPaise($data['amount']);
        if ($amountPaise < 100 || $amountPaise > 50000000) return response()->json(['message' => 'Amount must be between 1.00 and 500000.00 INR'], 400);
        $keyId = (string) env('RAZORPAY_KEY_ID');
        $keySecret = (string) env('RAZORPAY_KEY_SECRET');
        if ($keyId === '' || $keySecret === '') return response()->json(['message' => 'Payment gateway is not configured'], 500);
        $receipt = 'eru_'.bin2hex(random_bytes(12));
        try {
            $gateway = Http::withBasicAuth($keyId, $keySecret)->timeout(20)->post('https://api.razorpay.com/v1/orders', ['amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'notes' => ['user_id' => (string) $this->userId($request)]])->throw()->json();
        } catch (\Throwable) { return response()->json(['message' => 'Payment gateway request failed'], 502); }
        PaymentOrder::create(['user_id' => $this->userId($request), 'razorpay_order_id' => $gateway['id'], 'amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'status' => 'created']);
        return response()->json(['message' => 'Payment order created', 'order' => ['id' => $gateway['id'], 'amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $receipt, 'key_id' => $keyId]], 201);
    }

    public function verifyPayment(Request $request): JsonResponse
    {
        $data = $request->validate(['razorpay_order_id' => ['required', 'string'], 'razorpay_payment_id' => ['required', 'string'], 'razorpay_signature' => ['required', 'string']]);
        $order = PaymentOrder::where('razorpay_order_id', $data['razorpay_order_id'])->where('user_id', $this->userId($request))->first();
        if (!$order) return response()->json(['message' => 'Payment order not found'], 404);
        if ($order->currency !== 'INR' || $order->status === 'paid') return response()->json(['message' => 'Payment order cannot be verified'], 409);
        $expected = hash_hmac('sha256', $data['razorpay_order_id'].'|'.$data['razorpay_payment_id'], (string) env('RAZORPAY_KEY_SECRET'));
        if (!hash_equals($expected, $data['razorpay_signature'])) return response()->json(['message' => 'Payment signature verification failed'], 400);
        $updated = PaymentOrder::whereKey($order->id)->where('status', 'created')->update(['razorpay_payment_id' => $data['razorpay_payment_id'], 'signature' => $data['razorpay_signature'], 'status' => 'paid']);
        if ($updated !== 1) return response()->json(['message' => 'Payment order cannot be verified'], 409);
        return response()->json(['message' => 'Payment verified successfully', 'payment_id' => $data['razorpay_payment_id'], 'order_id' => $data['razorpay_order_id']]);
    }

    private function userId(Request $request): int { return (int) $request->attributes->get('jwt_claims')['id']; }
    private function rupeesToPaise(string $amount): int { [$rupees, $paise] = array_pad(explode('.', $amount, 2), 2, '0'); return ((int) $rupees * 100) + (int) str_pad($paise, 2, '0'); }
}
