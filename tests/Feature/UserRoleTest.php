<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

test('default factory user has no roles', function () {
    $user = User::factory()->create();

    expect($user->roles)->toHaveCount(0);
});

test('doctor factory state assigns medico role', function () {
    $user = User::factory()->doctor()->create();

    expect($user->hasRole('medico'))->toBeTrue();
});

test('secretary factory state assigns secretario role', function () {
    $user = User::factory()->secretary()->create();

    expect($user->hasRole('secretario'))->toBeTrue();
});

test('technician factory state assigns tecnico role', function () {
    $user = User::factory()->technician()->create();

    expect($user->hasRole('tecnico'))->toBeTrue();
});

test('user can have multiple roles', function () {
    $user = User::factory()->doctor()->create();
    $secretarioRole = Role::where('name', 'secretario')->first();
    $user->roles()->attach($secretarioRole);

    expect($user->roles)->toHaveCount(2);
    expect($user->hasRole('medico'))->toBeTrue();
    expect($user->hasRole('secretario'))->toBeTrue();
});

test('hasAnyRole works correctly', function () {
    $user = User::factory()->doctor()->create();

    expect($user->hasAnyRole(['medico', 'secretario']))->toBeTrue();
    expect($user->hasAnyRole(['secretario', 'tecnico']))->toBeFalse();
});

test('doctor can manage clinical features through role permissions', function () {
    $user = User::factory()->doctor()->create();

    $this->actingAs($user);

    expect($user->can('manage-medical-records'))->toBeTrue();
    expect($user->can('manage-prescriptions'))->toBeTrue();
    expect($user->can('manage-certificate-templates'))->toBeTrue();
    expect($user->can('manage-prescription-templates'))->toBeTrue();
});

test('secretary cannot manage clinical features', function () {
    $user = User::factory()->secretary()->create();

    $this->actingAs($user);

    expect($user->can('manage-medical-records'))->toBeFalse();
    expect($user->can('manage-prescriptions'))->toBeFalse();
    expect($user->can('manage-certificate-templates'))->toBeFalse();
    expect($user->can('manage-prescription-templates'))->toBeFalse();
});

test('technician cannot manage clinical features', function () {
    $user = User::factory()->technician()->create();

    $this->actingAs($user);

    expect($user->can('manage-medical-records'))->toBeFalse();
    expect($user->can('manage-prescriptions'))->toBeFalse();
});

test('user with direct permission can access feature', function () {
    $user = User::factory()->secretary()->create();
    $permission = Permission::where('name', 'manage-medical-records')->first();
    $user->permissions()->attach($permission);

    $this->actingAs($user);

    expect($user->can('manage-medical-records'))->toBeTrue();
});

test('requiresCrmCoren returns true for medico and tecnico roles', function () {
    $doctor = User::factory()->doctor()->create();
    $technician = User::factory()->technician()->create();
    $secretary = User::factory()->secretary()->create();

    expect($doctor->requiresCrmCoren())->toBeTrue();
    expect($technician->requiresCrmCoren())->toBeTrue();
    expect($secretary->requiresCrmCoren())->toBeFalse();
});

test('profile page shows role fields', function () {
    $user = User::factory()->doctor()->create();

    $this->actingAs($user);

    $this->get(route('profile.edit'))->assertOk();
});
