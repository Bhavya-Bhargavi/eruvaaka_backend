<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsAndContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_endpoint_stores_valid_submission_with_optional_email(): void
    {
        $this->postJson('/api/contact', [
            'name' => 'Test User',
            'phone' => '+91 98765 43210',
            'subject' => 'Question',
            'message' => 'Please contact me.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Your message has been submitted successfully');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Test User',
            'phone' => '+91 98765 43210',
            'email' => null,
            'subject' => 'Question',
            'message' => 'Please contact me.',
        ]);
    }

    public function test_contact_endpoint_validates_required_fields_and_email(): void
    {
        $this->postJson('/api/contact', [
            'name' => 'Test User',
            'phone' => 'not-a-phone',
            'email' => 'invalid-email',
            'subject' => 'Question',
            'message' => 'Please contact me.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'email']);

        $this->assertSame(0, ContactMessage::count());
    }
}