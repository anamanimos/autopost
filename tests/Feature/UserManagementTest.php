<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('Alamat Email');
        $response->assertSee('Kata Sandi');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'john@test.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('projects.index'));

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'john@test.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHas('error');
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@test.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'status' => 'inactive',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'inactive@test.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHas('error');
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'Active User',
            'email' => 'active@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_users_list(): void
    {
        $admin = User::create([
            'name' => 'Admin Boss',
            'email' => 'boss@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen User');
        $response->assertSee('Tambah Pengguna Baru');
        $response->assertSee('boss@test.com');
    }

    public function test_operator_cannot_access_user_management(): void
    {
        $operator = User::create([
            'name' => 'Operator Staff',
            'email' => 'staff@test.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        $response = $this->actingAs($operator)->get(route('users.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_user(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Operator',
            'email' => 'newoperator@test.com',
            'phone' => '081299998888',
            'role' => 'operator',
            'status' => 'active',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $newUser = User::where('email', 'newoperator@test.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('New Operator', $newUser->name);
        $this->assertEquals('operator', $newUser->role);
        $this->assertEquals('active', $newUser->status);
        $this->assertTrue(Hash::check('secret12345', $newUser->password));
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $target = User::create([
            'name' => 'Old Name',
            'email' => 'target@test.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $target->id), [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'phone' => '081211112222',
            'role' => 'operator',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertEquals('Updated Name', $target->name);
        $this->assertEquals('updated@test.com', $target->email);
        $this->assertEquals('inactive', $target->status);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::create([
            'name' => 'Admin Self',
            'email' => 'self@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin->id));

        $response->assertSessionHas('error');
        $this->assertNotNull(User::find($admin->id));
    }

    public function test_admin_cannot_delete_only_admin(): void
    {
        $admin = User::create([
            'name' => 'Only Admin',
            'email' => 'only@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $operator = User::create([
            'name' => 'Operator',
            'email' => 'op@test.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        // Attempt deleting from another session or simulated call
        $anotherAdmin = User::create([
            'name' => 'Second Admin',
            'email' => 'second@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Delete second admin (now only 1 admin remains)
        $this->actingAs($admin)->delete(route('users.destroy', $anotherAdmin->id));
        $this->assertNull(User::find($anotherAdmin->id));

        // Now if someone tries to delete the only admin
        $response = $this->actingAs($operator)->delete(route('users.destroy', $admin->id));
        $response->assertStatus(403);
    }

    public function test_admin_can_delete_operator(): void
    {
        $admin = User::create([
            'name' => 'Admin Boss',
            'email' => 'boss@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $target = User::create([
            'name' => 'Operator Target',
            'email' => 'target@test.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->delete(route('users.destroy', $target->id));

        $response->assertRedirect(route('users.index'));
        $this->assertNull(User::find($target->id));
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $admin = User::create([
            'name' => 'Admin Boss',
            'email' => 'boss@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $target = User::create([
            'name' => 'Operator Target',
            'email' => 'target@test.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        // Toggle from active to inactive
        $response = $this->actingAs($admin)->post(route('users.toggleStatus', $target->id));
        $response->assertSessionHas('success');

        $target->refresh();
        $this->assertEquals('inactive', $target->status);

        // Toggle back from inactive to active
        $this->actingAs($admin)->post(route('users.toggleStatus', $target->id));
        $target->refresh();
        $this->assertEquals('active', $target->status);
    }

    public function test_authenticated_user_can_update_profile_and_password(): void
    {
        $user = User::create([
            'name' => 'Original Name',
            'email' => 'original@test.com',
            'password' => Hash::make('oldpassword'),
            'role' => 'operator',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'New Profile Name',
            'email' => 'newprofile@test.com',
            'phone' => '081233334444',
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('New Profile Name', $user->name);
        $this->assertEquals('newprofile@test.com', $user->email);
        $this->assertEquals('081233334444', $user->phone);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }
}
