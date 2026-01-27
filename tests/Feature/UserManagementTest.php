<?php

use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

test('authenticated users with manage-roles permission can visit the users page', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $this->get(route('user.list'))->assertOk();
});

test('users without manage-roles permission cannot visit the users page', function () {
    $this->actingAs(User::factory()->secretary()->create());

    $this->get(route('user.list'))->assertForbidden();
});

test('roles can be assigned to a user', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $user = User::factory()->create();
    $medicoRole = Role::where('name', 'medico')->first();
    $secretarioRole = Role::where('name', 'secretario')->first();

    Livewire::test('pages::user.list')
        ->call('editRoles', $user->id)
        ->set('selectedRoles', [(string) $medicoRole->id, (string) $secretarioRole->id])
        ->call('saveRoles')
        ->assertHasNoErrors();

    expect($user->fresh()->roles)->toHaveCount(2);
    expect($user->fresh()->hasRole('medico'))->toBeTrue();
    expect($user->fresh()->hasRole('secretario'))->toBeTrue();
});

test('roles can be removed from a user', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $user = User::factory()->doctor()->create();
    expect($user->roles)->toHaveCount(1);

    Livewire::test('pages::user.list')
        ->call('editRoles', $user->id)
        ->set('selectedRoles', [])
        ->call('saveRoles')
        ->assertHasNoErrors();

    expect($user->fresh()->roles)->toHaveCount(0);
});

test('users can be filtered by name', function () {
    $this->actingAs(User::factory()->doctor()->create());

    User::factory()->create(['name' => 'João Silva']);
    User::factory()->create(['name' => 'Maria Santos']);

    $component = Livewire::test('pages::user.list')
        ->set('search', 'João');

    $users = $component->get('users');

    // Should include João Silva (and possibly the acting-as doctor if name matches)
    $filteredNames = $users->pluck('name')->toArray();
    expect(in_array('João Silva', $filteredNames))->toBeTrue();
    expect(in_array('Maria Santos', $filteredNames))->toBeFalse();
});
