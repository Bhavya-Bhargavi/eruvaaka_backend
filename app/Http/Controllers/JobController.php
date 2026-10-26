<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => AgriculturalJob::where('status', 'active')
                ->latest('id')
                ->get(),
        ]);
    }

    public function show(int $jobId): JsonResponse
    {
        $job = AgriculturalJob::where('status', 'active')->findOrFail($jobId);

        return response()->json(['data' => $job]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'location' => ['required', 'string', 'max:255'],
            'salary' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $job = AgriculturalJob::create($data);

        return response()->json([
            'message' => 'Job created',
            'data' => $job,
        ], 201);
    }
}