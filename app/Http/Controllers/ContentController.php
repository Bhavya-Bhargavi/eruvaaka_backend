<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Epaper;
use App\Models\Emagazine;
use App\Models\ForumComment;
use App\Models\ForumDiscussion;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ContentController extends Controller
{
    public function books(): JsonResponse
    {
        return response()->json([
            'data' => Book::where('is_published', true)
                ->latest('id')
                ->get(['id', 'slug', 'title', 'author', 'description', 'cover_image']),
        ]);
    }

    public function book(string $bookId): JsonResponse
    {
        $book = Book::where('is_published', true)->findOrFail($bookId);

        return response()->json(['data' => $book->only(['id', 'slug', 'title', 'author', 'description', 'content', 'cover_image'])]);
    }

    public function forums(): JsonResponse
    {
        return response()->json([
            'data' => ForumDiscussion::where('is_published', true)
                ->withCount('comments')
                ->latest('id')
                ->get(['id', 'slug', 'title', 'body', 'created_at']),
        ]);
    }

    public function forum(string $forumId): JsonResponse
    {
        $discussion = ForumDiscussion::where('is_published', true)->withCount('comments')->findOrFail($forumId);

        return response()->json(['data' => $discussion->only(['id', 'slug', 'title', 'body', 'comments_count', 'created_at'])]);
    }

    public function comments(string $forumId): JsonResponse
    {
        $discussion = ForumDiscussion::where('is_published', true)->findOrFail($forumId);
        $comments = ForumComment::where('forum_discussion_id', $discussion->id)
            ->with('user:id,first_name,last_name')
            ->oldest()
            ->get(['id', 'forum_discussion_id', 'user_id', 'body', 'created_at']);

        return response()->json(['data' => $comments->map(fn (ForumComment $comment): array => [
            'id' => $comment->id,
            'body' => $comment->body,
            'author' => trim($comment->user->first_name.' '.$comment->user->last_name),
            'created_at' => $comment->created_at,
        ])]);
    }

    public function postComment(string $forumId, Request $request): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:1', 'max:5000']]);
        $discussion = ForumDiscussion::where('is_published', true)->findOrFail($forumId);
        $user = User::findOrFail((int) $request->attributes->get('jwt_claims')['id']);
        $comment = ForumComment::create([
            'forum_discussion_id' => $discussion->id,
            'user_id' => $user->id,
            'body' => trim($data['body']),
        ]);

        return response()->json([
            'message' => 'Comment posted',
            'data' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author' => trim($user->first_name.' '.$user->last_name),
                'created_at' => $comment->created_at,
            ],
        ], 201);
    }

    public function epapers(): JsonResponse
    {
        return $this->publications(Epaper::class, 'epapers');
    }

    public function emagazines(): JsonResponse
    {
        return $this->publications(Emagazine::class, 'emagazines');
    }

    public function downloadEpaper(string $publicationId, Request $request): Response
    {
        if (!$this->hasActiveSubscription($request)) {
            return $this->subscriptionRequiredResponse();
        }

        return $this->downloadPublication(Epaper::class, $publicationId);
    }

    public function downloadEmagazine(string $publicationId, Request $request): Response
    {
        if (!$this->hasActiveSubscription($request)) {
            return $this->subscriptionRequiredResponse();
        }

        return $this->downloadPublication(Emagazine::class, $publicationId);
    }

    private function hasActiveSubscription(Request $request): bool
    {
        return Subscription::where('user_id', (int) $request->attributes->get('jwt_claims')['id'])
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->exists();
    }

    private function subscriptionRequiredResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'An active subscription is required to download magazines',
            'next_action' => 'show_magazine_options',
        ], 403);
    }

    private function publications(string $modelClass, string $route): JsonResponse
    {
        return response()->json([
            'data' => $modelClass::where('is_available', true)
                ->latest('id')
                ->get(['id', 'slug', 'title', 'issue_date'])
                ->map(fn (Model $publication): array => [
                    'id' => $publication->id,
                    'slug' => $publication->slug,
                    'title' => $publication->title,
                    'issue_date' => $publication->issue_date,
                    'download_url' => '/api/'.$route.'/'.$publication->id.'/download',
                ]),
        ]);
    }

    private function downloadPublication(string $modelClass, int $publicationId): BinaryFileResponse
    {
        $publication = $modelClass::where('is_available', true)->findOrFail($publicationId);
        $relativePath = $publication->file_path;

        abort_if(str_contains($relativePath, '..') || str_starts_with($relativePath, '/'), 404);
        abort_unless(Storage::disk('local')->exists($relativePath), 404);

        $path = Storage::disk('local')->path($relativePath);
        $mimeType = Storage::disk('local')->mimeType($relativePath) ?: 'application/octet-stream';
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'image/svg+xml'], true), 404);

        return response()->download($path, basename($relativePath), [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}