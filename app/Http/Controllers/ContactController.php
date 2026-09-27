<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s().-]{7,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $contactMessage = ContactMessage::create([
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'email' => isset($data['email']) ? trim($data['email']) : null,
            'subject' => trim($data['subject']),
            'message' => trim($data['message']),
        ]);

        return response()->json([
            'message' => 'Your message has been submitted successfully',
            'id' => $contactMessage->id,
        ], 201);
    }
}