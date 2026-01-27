<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

test('authenticated users with manage-roles permission can visit the roles page', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $this->get(route('role.list'))->assertOk();
});

test('users without manage-roles permission cannot visit the roles page', function () {
    $this->actingAs(User::factory()->secretary()->create());

    $this->get(route('role.list'))->assertForbidden();
});

test('a role can be created with permissions', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $permissions = Permission::all();

    Livewire::test('pages::role.list')
        ->set('name', 'admin')
        ->set('label', 'Administrador')
        ->set('selectedPermissions', $permissions->pluck('id')->map(fn ($id) => (string) $id)->toArray())
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('roles', [
        'name' => 'admin',
        'label' => 'Administrador',
    ]);

    $role = Role::where('name', 'admin')->first();
    expect($role->permissions)->toHaveCount($permissions->count());
});

test('a role can be updated', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $role = Role::factory()->create(['name' => 'custom', 'label' => 'Custom']);

    Livewire::test('pages::role.list')
        ->call('edit', $role->id)
        ->set('label', 'Custom Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($role->fresh()->label)->toBe('Custom Updated');
});

test('a role can be deleted', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $role = Role::factory()->create();

    Livewire::test('pages::role.list')
        ->call('delete', $role->id);

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('role name is required', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::role.list')
        ->set('name', '')
        ->set('label', 'Test')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('role name must be unique', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Role::factory()->create(['name' => 'existing']);

    Livewire::test('pages::role.list')
        ->set('name', 'existing')
        ->set('label', 'Existing')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('permissions can be assigned and removed from a role', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $role = Role::factory()->create();
    $permission = Permission::first();

    // Assign permission
    Livewire::test('pages::role.list')
        ->call('edit', $role->id)
        ->set('selectedPermissions', [(string) $permission->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($role->fresh()->permissions)->toHaveCount(1);

    // Remove permission
    Livewire::test('pages::role.list')
        ->call('edit', $role->id)
        ->set('selectedPermissions', [])
        ->call('save')
        ->assertHasNoErrors();

    expect($role->fresh()->permissions)->toHaveCount(0);
});
