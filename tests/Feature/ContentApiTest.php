<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Epaper;
use App\Models\Emagazine;
use App\Models\ForumComment;
use App\Models\ForumDiscussion;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_read_but_have_no_download_endpoint(): void
    {
        $book = Book::create([
            'slug' => 'test-book',
            'title' => 'Test Book',
            'author' => 'Test Author',
            'description' => 'A test book',
            'content' => 'Readable book content',
            'is_published' => true,
        ]);

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Test Book')
            ->assertJsonMissingPath('data.0.content');

        $this->getJson('/api/books/'.$book->id)
            ->assertOk()
            ->assertJsonPath('data.content', 'Readable book content');

        $this->getJson('/api/books/'.$book->id.'/download')->assertNotFound();
    }

    public function test_forum_comments_require_authentication_and_do_not_expose_user_contact_data(): void
    {
        $discussion = ForumDiscussion::create([
            'slug' => 'test-discussion',
            'title' => 'Test Discussion',
            'body' => 'Discuss this topic',
            'is_published' => true,
        ]);

        $this->postJson('/api/forums/'.$discussion->id.'/comments', ['body' => 'A comment'])->assertUnauthorized();

        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'Member',
            'phone' => '9876543201',
            'email' => 'private@example.test',
            'password_hash' => 'unused',
            'state' => 'State',
            'district' => 'District',
            'mandal' => 'Mandal',
            'pincode' => '123456',
        ]);

        $this->withToken($this->tokenFor($user->id))
            ->postJson('/api/forums/'.$discussion->id.'/comments', ['body' => 'A comment'])
            ->assertCreated()
            ->assertJsonPath('data.author', 'Test Member')
            ->assertJsonMissingPath('data.email');

        $this->getJson('/api/forums/'.$discussion->id.'/comments')
            ->assertOk()
            ->assertJsonPath('data.0.body', 'A comment')
            ->assertJsonPath('data.0.author', 'Test Member')
            ->assertJsonMissingPath('data.0.user.phone');
    }

    public function test_publication_download_is_served_from_private_storage_after_authentication(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('images/test-epaper.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $publication = Epaper::create([
            'slug' => 'test-epaper',
            'title' => 'Test E-Paper',
            'file_path' => 'images/test-epaper.svg',
            'is_available' => true,
        ]);
        $user = User::create([
            'first_name' => 'Download',
            'last_name' => 'Member',
            'phone' => '9876543202',
            'state' => 'State',
            'district' => 'District',
            'mandal' => 'Mandal',
            'pincode' => '123456',
        ]);

        $this->getJson('/api/epapers')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Test E-Paper')
            ->assertJsonPath('data.0.download_url', '/api/epapers/'.$publication->id.'/download');

        $this->get('/api/epapers/'.$publication->id.'/download')->assertUnauthorized();

        $token = $this->tokenFor($user->id);

        $this->withToken($token)
            ->get('/api/epapers/'.$publication->id.'/download')
            ->assertForbidden();

        Subscription::create([
            'user_id' => $user->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            'plan_type' => '1year',
            'status' => 'active',
        ]);

        $this->withToken($token)
            ->get('/api/epapers/'.$publication->id.'/download')
            ->assertDownload('test-epaper.svg');

        $this->assertSame(0, ForumComment::count());
    }

    public function test_emagazine_download_requires_an_active_subscription(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('images/test-emagazine.pdf', 'test magazine');
        $publication = Emagazine::create([
            'slug' => 'test-emagazine',
            'title' => 'Test E-Magazine',
            'file_path' => 'images/test-emagazine.pdf',
            'is_available' => true,
        ]);
        $user = User::create([
            'first_name' => 'Magazine',
            'last_name' => 'Reader',
            'phone' => '9876543203',
            'state' => 'State',
            'district' => 'District',
            'mandal' => 'Mandal',
            'pincode' => '123456',
        ]);
        $token = $this->tokenFor($user->id);

        $this->withToken($token)
            ->getJson('/api/emagazines/'.$publication->id.'/download')
            ->assertForbidden()
            ->assertJsonPath('next_action', 'show_magazine_options');

        Subscription::create([
            'user_id' => $user->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            'plan_type' => '1year',
            'status' => 'active',
        ]);

        $this->withToken($token)
            ->get('/api/emagazines/'.$publication->id.'/download')
            ->assertDownload('test-emagazine.pdf');
    }

    private function tokenFor(int $userId): string
    {
        $secret = 'content-api-test-secret';
        config(['app.jwt_secret' => $secret]);
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = $this->base64UrlEncode(json_encode(['id' => $userId, 'exp' => time() + 3600]));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$payload, $secret, true));

        return $header.'.'.$payload.'.'.$signature;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}