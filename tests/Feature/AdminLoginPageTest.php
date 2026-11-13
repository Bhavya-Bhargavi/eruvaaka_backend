<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminLoginPageTest extends TestCase
{
    public function test_admin_login_page_is_rendered(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Eruvaaka Admin')
            ->assertSee('name="_token"', false);
    }
}
