<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Article;
use App\Models\Book;
use App\Models\Emagazine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_is_separate_from_customer_authentication(): void
    {
        $admin = $this->createAdmin();

        $this->get('/admin/dashboard')
            ->assertRedirect(route('admin.login'));

        $this->get('/admin/login')->assertOk();
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'correct horse battery staple',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_content_manager_cannot_manage_admins_or_publish_or_delete_books(): void
    {
        $manager = $this->createAdmin('content_manager');
        $book = Book::create([
            'slug' => 'draft-book',
            'title' => 'Draft Book',
            'content' => 'Draft content',
            'is_published' => false,
        ]);

        $this->actingAs($manager, 'admin')
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($manager, 'admin')
            ->patch(route('admin.books.publication', $book))
            ->assertForbidden();

        $this->actingAs($manager, 'admin')
            ->delete(route('admin.books.destroy', $book))
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'is_published' => false]);
    }

    public function test_books_index_handles_legacy_rows_without_created_at(): void
    {
        $admin = $this->createAdmin();
        $book = Book::create([
            'slug' => 'legacy-book',
            'title' => 'Legacy Book',
            'content' => 'Legacy content',
        ]);
        Book::whereKey($book->id)->update(['created_at' => null]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.books.index'))
            ->assertOk()
            ->assertSee('Legacy Book')
            ->assertSee('—');
    }

    public function test_admin_can_change_password_only_after_verifying_current_password(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.password.update'), [
                'current_password' => 'incorrect password',
                'password' => 'new unique passphrase 2026!',
                'password_confirmation' => 'new unique passphrase 2026!',
            ])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.password.update'), [
                'current_password' => 'correct horse battery staple',
                'password' => 'new unique passphrase 2026!',
                'password_confirmation' => 'new unique passphrase 2026!',
            ])
            ->assertRedirect(route('admin.password.edit'));

        $this->assertTrue(Hash::check('new unique passphrase 2026!', $admin->fresh()->password));
    }

    public function test_content_manager_can_upload_book_and_published_api_hides_drafts(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $manager = $this->createAdmin('content_manager');
        $superAdmin = $this->createAdmin('super_admin', 'owner@example.test');

        $this->actingAs($manager, 'admin')
            ->post(route('admin.books.store'), [
                'title' => 'Soil Health',
                'author' => 'Farm Expert',
                'category' => 'Soil',
                'description' => 'A practical guide.',
                'pdf' => UploadedFile::fake()->create('soil.pdf', 5, 'application/pdf'),
                'cover' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->assertRedirect(route('admin.books.index'));

        $book = Book::firstOrFail();
        $this->assertFalse($book->is_published);
        Storage::disk('local')->assertExists($book->pdf_path);
        Storage::disk('public')->assertExists($book->cover_image);
        $this->getJson('/api/v2/books')->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($superAdmin, 'admin')
            ->patch(route('admin.books.publication', $book))
            ->assertRedirect();

        $this->getJson('/api/v2/books')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Soil Health')
            ->assertJsonPath('data.0.category', 'Soil')
            ->assertJsonStructure(['data', 'pagination']);
        $this->getJson('/api/v2/books/'.$book->id.'/download')->assertDownload();
    }

    public function test_published_content_apis_never_return_unpublished_records(): void
    {
        Book::create(['slug' => 'published-book', 'title' => 'Published book', 'content' => 'Text', 'is_published' => true]);
        $privateBook = Book::create(['slug' => 'private-book', 'title' => 'Private book', 'content' => 'Text', 'is_published' => false]);
        Emagazine::create([
            'slug' => 'published-magazine',
            'title' => 'Published magazine',
            'file_path' => 'magazines/published.pdf',
            'is_available' => true,
        ]);
        $privateMagazine = Emagazine::create([
            'slug' => 'private-magazine',
            'title' => 'Private magazine',
            'file_path' => 'magazines/private.pdf',
            'is_available' => false,
        ]);
        Article::create(['slug' => 'published-article', 'title' => 'Published article', 'content' => 'Body', 'is_published' => true]);
        $privateArticle = Article::create(['slug' => 'private-article', 'title' => 'Private article', 'content' => 'Body', 'is_published' => false]);

        $this->getJson('/api/v2/books')->assertJsonCount(1, 'data')->assertJsonMissing(['title' => 'Private book']);
        $this->getJson('/api/v2/magazines')->assertJsonCount(1, 'data')->assertJsonMissing(['title' => 'Private magazine']);
        $this->getJson('/api/v2/articles')->assertJsonCount(1, 'data')->assertJsonMissing(['title' => 'Private article']);

        $this->getJson('/api/v2/books/'.$privateBook->id)->assertNotFound();
        $this->getJson('/api/v2/magazines/'.$privateMagazine->id)->assertNotFound();
        $this->getJson('/api/v2/articles/'.$privateArticle->id)->assertNotFound();
    }

    public function test_paid_book_detail_and_pdf_download_keep_purchase_authentication(): void
    {
        Storage::fake('local');
        $book = Book::create([
            'slug' => 'paid-book',
            'title' => 'Paid book',
            'content' => 'Paid text',
            'pdf_path' => 'books/paid.pdf',
            'price_minor' => 10000,
            'is_published' => true,
        ]);
        Storage::disk('local')->put($book->pdf_path, 'PDF');

        $this->getJson('/api/v2/books/'.$book->id)->assertUnauthorized();
        $this->getJson('/api/v2/books/'.$book->id.'/download')->assertUnauthorized();

        $user = User::factory()->create();
        $this->withToken($this->tokenFor($user->id))
            ->getJson('/api/v2/books/'.$book->id)
            ->assertForbidden();
    }

    public function test_magazine_and_article_creation_use_drafts_and_allow_duplicate_issue_months(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $manager = $this->createAdmin('content_manager');

        foreach (['First Issue', 'Second Issue'] as $title) {
            $this->actingAs($manager, 'admin')
                ->post(route('admin.magazines.store'), [
                    'title' => $title,
                    'month' => 11,
                    'year' => 2026,
                    'pdf' => UploadedFile::fake()->create('magazine.pdf', 5, 'application/pdf'),
                ])
                ->assertRedirect(route('admin.magazines.index'));
        }

        $this->assertDatabaseCount('digital_publications', 2);
        $this->assertDatabaseHas('digital_publications', ['title' => 'First Issue', 'is_available' => false]);

        $this->actingAs($manager, 'admin')
            ->post(route('admin.articles.store'), [
                'title' => 'Water conservation',
                'category' => 'Water',
                'description' => 'Short overview.',
                'content' => 'Full article content.',
            ])
            ->assertRedirect(route('admin.articles.index'));

        $article = Article::firstOrFail();
        $this->assertFalse($article->is_published);
        $this->actingAs($manager, 'admin')
            ->patch(route('admin.articles.publication', $article))
            ->assertRedirect();
        $this->getJson('/api/v2/articles')->assertJsonPath('data.0.title', 'Water conservation');
    }

    private function tokenFor(int $userId): string
    {
        $secret = 'admin-panel-test-secret';
        config(['app.jwt_secret' => $secret]);
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = $this->base64UrlEncode(json_encode(['id' => $userId, 'exp' => time() + 3600]));

        return $header.'.'.$payload.'.'.$this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$payload, $secret, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function createAdmin(string $role = 'super_admin', string $email = 'admin@example.test'): Admin
    {
        return Admin::create([
            'name' => 'Test Admin',
            'email' => $email,
            'password' => Hash::make('correct horse battery staple'),
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
