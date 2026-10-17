<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_login_page_loads_with_material_ui(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Rapid Track');
        $response->assertSee('Work Email Address');
    }

    public function test_super_admin_can_access_all_modules(): void
    {
        $admin = User::create([
            'name' => 'Alexander Vance',
            'email' => 'superadmin@apextask.com',
            'password' => bcrypt('Password123!'),
            'role' => 'super_admin',
            'department' => 'Executive',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/tasks')->assertStatus(200);
        $this->actingAs($admin)->get('/users')->assertStatus(200);
        $this->actingAs($admin)->get('/settings')->assertStatus(200);
    }

    public function test_staff_is_restricted_from_admin_modules(): void
    {
        $staff = User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@apextask.com',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'department' => 'Frontend',
            'status' => 'active',
        ]);

        $this->actingAs($staff)->get('/dashboard')->assertStatus(200);
        $this->actingAs($staff)->get('/tasks')->assertStatus(200);
        $this->actingAs($staff)->get('/users')->assertStatus(403);
        $this->actingAs($staff)->get('/settings')->assertStatus(403);
    }
}
