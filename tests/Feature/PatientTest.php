<?php

use App\Models\Patient;
use App\Models\User;

test('authenticated users can visit the patients page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('patient.list'))->assertOk();
});

test('a patient can be created with valid data', function () {
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test('pages::patient.list')
        ->set('name', 'João Silva')
        ->set('dob', '1990-05-15')
        ->set('gender', 'Masculino')
        ->set('phone', '(11) 99999-0000')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('patients', [
        'name' => 'João Silva',
        'dob' => '1990-05-15',
        'gender' => 'Masculino',
        'phone' => '(11) 99999-0000',
    ]);
});

test('a patient can be created without optional fields', function () {
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test('pages::patient.list')
        ->set('name', 'Maria Santos')
        ->set('dob', '1985-03-20')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('patients', [
        'name' => 'Maria Santos',
        'phone' => '',
    ]);
});

test('a patient requires name and date of birth', function () {
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test('pages::patient.list')
        ->set('name', '')
        ->set('dob', '')
        ->call('save')
        ->assertHasErrors(['name', 'dob']);
});

test('a patient can be updated', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create(['phone' => '(11) 11111-1111']);

    Livewire\Livewire::test('pages::patient.list')
        ->call('edit', $patient->id)
        ->set('phone', '(21) 99999-0000')
        ->call('save')
        ->assertHasNoErrors();

    expect($patient->fresh()->phone)->toBe('(21) 99999-0000');
});

test('phone field max length is validated', function () {
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test('pages::patient.list')
        ->set('name', 'Test Patient')
        ->set('dob', '1990-01-01')
        ->set('phone', str_repeat('1', 21))
        ->call('save')
        ->assertHasErrors(['phone']);
});
