<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\AgriculturalJob;
use App\Models\Epaper;
use App\Models\Emagazine;
use App\Models\ForumComment;
use App\Models\ForumDiscussion;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_api_lists_imported_mart_products(): void
    {
        Product::create([
            'source_id' => '623',
            'name' => 'Agricultural pump',
            'slug' => 'agricultural-pump',
            'description' => 'Water pump for farm use',
            'permalink' => 'https://eruvaakamart.com/product/agricultural-pump/',
            'price_minor' => 125000,
            'currency_code' => 'INR',
            'currency_minor_unit' => 2,
            'image_url' => 'https://eruvaakamart.com/pump.jpg',
            'in_stock' => true,
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Agricultural pump')
            ->assertJsonPath('data.0.price_minor', 125000)
            ->assertJsonPath('data.0.currency_code', 'INR')
            ->assertJsonPath('data.0.in_stock', true)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_jobs_are_listed_and_details_are_limited_to_active_postings(): void
    {
        $activeJob = AgriculturalJob::create([
            'title' => 'Farm supervisor',
            'description' => 'Coordinate daily farm work',
            'location' => 'Guntur',
            'salary' => 'Rs. 25,000 per month',
            'contact_person' => 'Farm Manager',
            'contact_phone' => '9876543210',
            'status' => 'active',
        ]);
        $inactiveJob = AgriculturalJob::create([
            'title' => 'Closed position',
            'description' => 'No longer accepting applications',
            'location' => 'Vijayawada',
            'contact_person' => 'Farm Manager',
            'contact_phone' => '9876543210',
            'status' => 'inactive',
        ]);

        $this->getJson('/api/jobs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeJob->id)
            ->assertJsonPath('data.0.title', 'Farm supervisor');

        $this->getJson('/api/jobs/'.$activeJob->id)
            ->assertOk()
            ->assertJsonPath('data.contact_phone', '9876543210');

        $this->getJson('/api/jobs/'.$inactiveJob->id)->assertNotFound();
    }

    public function test_job_creation_requires_authentication_and_validates_fields(): void
    {
        $jobData = [
            'title' => 'Tractor operator',
            'description' => 'Operate and maintain farm equipment',
            'location' => 'Tenali',
            'salary' => 'Negotiable',
            'contact_person' => 'Hiring Manager',
            'contact_phone' => '9876543211',
        ];

        $this->postJson('/api/jobs', $jobData)->assertUnauthorized();

        $user = User::create([
            'first_name' => 'Job',
            'last_name' => 'Admin',
            'phone' => '9876543212',
            'password_hash' => 'unused',
            'state' => 'State',
            'district' => 'District',
            'mandal' => 'Mandal',
            'pincode' => '123456',
        ]);

        $this->withToken($this->tokenFor($user->id))
            ->postJson('/api/jobs', $jobData)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Tractor operator')
            ->assertJsonPath('data.status', 'active');

        $this->withToken($this->tokenFor($user->id))
            ->postJson('/api/jobs', ['title' => 'Missing required fields'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['description', 'location', 'contact_person', 'contact_phone']);
    }

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

    public function test_manual_discussion_topics_are_listed_and_accept_comments(): void
    {
        $this->seed(\Database\Seeders\ContentDemoSeeder::class);

        $topics = $this->getJson('/api/forums')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $topicIds = collect($topics->json('data'))->pluck('id', 'title');
        $this->assertSame(['Discussion1', 'Discussion2'], collect($topics->json('data'))->pluck('title')->sort()->values()->all());

        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'Participant',
            'phone' => '9876543291',
            'password_hash' => 'unused',
            'state' => 'State',
            'district' => 'District',
            'mandal' => 'Mandal',
            'pincode' => '123456',
        ]);

        $this->withToken($this->tokenFor($user->id))
            ->postJson('/api/forums/'.$topicIds['Discussion1'].'/comments', ['body' => 'My comment'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'My comment');
    }

    public function test_epaper_download_is_available_without_authentication_or_subscription(): void
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

        $this->get('/api/epapers/'.$publication->id.'/download')
            ->assertDownload('test-epaper.svg');

        $token = $this->tokenFor($user->id);

        $this->withToken($token)
            ->get('/api/epapers/'.$publication->id.'/download')
            ->assertDownload('test-epaper.svg');

        $this->assertSame(0, ForumComment::count());
    }

    public function test_emagazine_download_is_available_without_authentication_or_subscription(): void
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

        $this->get('/api/emagazines/'.$publication->id.'/download')
            ->assertDownload('test-emagazine.pdf');

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