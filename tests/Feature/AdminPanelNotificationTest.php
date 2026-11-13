<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Article;
use App\Models\Book;
use App\Models\Emagazine;
use App\Notifications\AdminEmailChangeVerificationNotification;
use App\Notifications\AdminPanelChangeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_change_waits_for_new_address_verification_and_notifies_recipients(): void
    {
        Notification::fake();
        config(['admin.notification_email' => 'alerts@example.test']);
        $superAdmin = $this->createAdmin('super_admin', 'owner@example.test');
        $manager = $this->createAdmin('content_manager', 'manager@example.test');
        $newEmail = 'new-manager@example.test';

        $this->actingAs($superAdmin, 'admin')
            ->put(route('admin.users.update', $manager), [
                'name' => $manager->name,
                'email' => $newEmail,
                'password' => '',
                'password_confirmation' => '',
                'role' => $manager->role,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $manager->refresh();
        $this->assertSame('manager@example.test', $manager->email);
        $this->assertSame($newEmail, $manager->pending_email);

        $verificationUrl = null;
        Notification::assertSentOnDemand(
            AdminEmailChangeVerificationNotification::class,
            function ($notification, array $channels, $notifiable) use (&$verificationUrl, $newEmail): bool {
                if ($notifiable->routeNotificationFor('mail') !== $newEmail) {
                    return false;
                }

                $verificationUrl = $notification->verificationUrl;

                return in_array('mail', $channels, true);
            },
        );
        $this->assertNotNull($verificationUrl);
        $this->assertNotSame('', $verificationUrl);
        $this->assertAlertedAt('manager@example.test');
        $this->assertAlertedAt('owner@example.test');
        $this->assertAlertedAt('alerts@example.test');

        $this->get($verificationUrl)
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('admins', [
            'id' => $manager->id,
            'email' => $newEmail,
            'pending_email' => null,
        ]);
    }

    public function test_password_change_notifies_the_admin_super_admins_and_configured_address(): void
    {
        Notification::fake();
        config(['admin.notification_email' => 'alerts@example.test']);
        $superAdmin = $this->createAdmin('super_admin', 'owner@example.test');
        $manager = $this->createAdmin('content_manager', 'manager@example.test');

        $this->actingAs($manager, 'admin')
            ->put(route('admin.password.update'), [
                'current_password' => 'correct horse battery staple',
                'password' => 'new unique passphrase 2026!',
                'password_confirmation' => 'new unique passphrase 2026!',
            ])
            ->assertRedirect(route('admin.password.edit'));

        $this->assertAlertedAt('manager@example.test');
        $this->assertAlertedAt('owner@example.test');
        $this->assertAlertedAt('alerts@example.test');
        Notification::assertSentOnDemandTimes(AdminPanelChangeNotification::class, 3);
    }

    public function test_admin_account_creation_update_and_deletion_send_alerts(): void
    {
        Notification::fake();
        config(['admin.notification_email' => 'alerts@example.test']);
        $superAdmin = $this->createAdmin('super_admin', 'owner@example.test');
        $manager = $this->createAdmin('content_manager', 'manager@example.test');

        $this->actingAs($superAdmin, 'admin')
            ->post(route('admin.users.store'), [
                'name' => 'New Manager',
                'email' => 'new-manager@example.test',
                'password' => 'initial admin passphrase 2026!',
                'password_confirmation' => 'initial admin passphrase 2026!',
                'role' => 'content_manager',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $newAdmin = Admin::where('email', 'new-manager@example.test')->firstOrFail();
        $this->assertAlertedAt('new-manager@example.test');

        $this->actingAs($superAdmin, 'admin')
            ->put(route('admin.users.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'password' => 'replacement admin passphrase 2026!',
                'password_confirmation' => 'replacement admin passphrase 2026!',
                'role' => 'super_admin',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));
        $this->assertAlertedAt('manager@example.test');

        $this->actingAs($superAdmin, 'admin')
            ->delete(route('admin.users.destroy', $newAdmin))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('admins', ['id' => $newAdmin->id]);
        $this->assertAlertedAt('alerts@example.test');
        Notification::assertSentOnDemandTimes(AdminPanelChangeNotification::class, 10);
    }

    public function test_book_magazine_and_article_changes_send_admin_alerts(): void
    {
        Notification::fake();
        Storage::fake('local');
        Storage::fake('public');
        config(['admin.notification_email' => 'alerts@example.test']);
        $superAdmin = $this->createAdmin('super_admin', 'owner@example.test');
        $manager = $this->createAdmin('content_manager', 'manager@example.test');

        $this->actingAs($manager, 'admin')
            ->post(route('admin.books.store'), [
                'title' => 'Soil Health',
                'pdf' => UploadedFile::fake()->create('soil.pdf', 5, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.books.index'));
        $book = Book::firstOrFail();

        $this->actingAs($manager, 'admin')
            ->put(route('admin.books.update', $book), [
                'title' => 'Updated Soil Health',
                'description' => 'Updated description',
            ])
            ->assertRedirect(route('admin.books.index'));

        $this->actingAs($superAdmin, 'admin')
            ->patch(route('admin.books.publication', $book))
            ->assertRedirect();
        $this->actingAs($superAdmin, 'admin')
            ->delete(route('admin.books.destroy', $book))
            ->assertRedirect(route('admin.books.index'));

        $this->actingAs($manager, 'admin')
            ->post(route('admin.magazines.store'), [
                'title' => 'November edition',
                'month' => 11,
                'year' => 2026,
                'pdf' => UploadedFile::fake()->create('magazine.pdf', 5, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.magazines.index'));
        $magazine = Emagazine::firstOrFail();

        $this->actingAs($manager, 'admin')
            ->put(route('admin.magazines.update', $magazine), [
                'title' => 'Updated November edition',
                'month' => 11,
                'year' => 2026,
            ])
            ->assertRedirect(route('admin.magazines.index'));

        $this->actingAs($superAdmin, 'admin')
            ->patch(route('admin.magazines.publication', $magazine))
            ->assertRedirect();
        $this->actingAs($superAdmin, 'admin')
            ->delete(route('admin.magazines.destroy', $magazine))
            ->assertRedirect(route('admin.magazines.index'));

        $this->actingAs($manager, 'admin')
            ->post(route('admin.articles.store'), [
                'title' => 'Water conservation',
                'content' => 'Practical water-saving guidance.',
            ])
            ->assertRedirect(route('admin.articles.index'));
        $article = Article::firstOrFail();

        $this->actingAs($manager, 'admin')
            ->put(route('admin.articles.update', $article), [
                'title' => 'Updated water conservation',
                'content' => 'Updated practical water-saving guidance.',
            ])
            ->assertRedirect(route('admin.articles.index'));

        $this->actingAs($manager, 'admin')
            ->patch(route('admin.articles.publication', $article))
            ->assertRedirect();
        $this->actingAs($manager, 'admin')
            ->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertAlertedAt('alerts@example.test');
        Notification::assertSentOnDemandTimes(AdminPanelChangeNotification::class, 32);
    }

    private function assertAlertedAt(string $email): void
    {
        Notification::assertSentOnDemand(
            AdminPanelChangeNotification::class,
            fn ($notification, array $channels, $notifiable): bool => $notifiable->routeNotificationFor('mail') === $email
                && in_array('mail', $channels, true),
        );
    }

    private function createAdmin(string $role, string $email): Admin
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
