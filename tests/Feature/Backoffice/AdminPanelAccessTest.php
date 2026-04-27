<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Filament\Facades\Filament;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    public function test_admin_login_page_renders_successfully(): void
    {
        $this->get('/admin/login')
            ->assertOk();
    }

    public function test_admin_panel_redirects_guests_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_user_panel_access_requires_admin_flag(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertFalse((new User(['is_admin' => false]))->canAccessPanel($panel));
        $this->assertTrue((new User(['is_admin' => true]))->canAccessPanel($panel));
    }
}
