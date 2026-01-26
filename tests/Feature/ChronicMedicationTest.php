<?php

use App\Models\ChronicMedication;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

test('authenticated users can visit the chronic medications page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('chronic-medication.list'))->assertOk();
});

test('chronic medications can be saved for a patient', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    Livewire::test('pages::chronic-medication.list')
        ->call('editMedications', $patient->id)
        ->set('medications', 'Losartana 50mg, Metformina 850mg')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('chronic_medications', [
        'patient_id' => $patient->id,
        'medications' => 'Losartana 50mg, Metformina 850mg',
    ]);
});

test('chronic medications use updateOrCreate for idempotent saves', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    ChronicMedication::create([
        'patient_id' => $patient->id,
        'medications' => 'Old medications',
    ]);

    Livewire::test('pages::chronic-medication.list')
        ->call('editMedications', $patient->id)
        ->set('medications', 'Updated medications')
        ->call('save')
        ->assertHasNoErrors();

    expect(ChronicMedication::where('patient_id', $patient->id)->count())->toBe(1);
    expect($patient->fresh()->chronicMedication->medications)->toBe('Updated medications');
});

test('medications text is required', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    Livewire::test('pages::chronic-medication.list')
        ->call('editMedications', $patient->id)
        ->set('medications', '')
        ->call('save')
        ->assertHasErrors(['medications']);
});

test('existing medications are loaded when editing', function () {
    $this->actingAs(User::factory()->create());

    $patient = Patient::factory()->create();

    ChronicMedication::create([
        'patient_id' => $patient->id,
        'medications' => 'Existing meds',
    ]);

    Livewire::test('pages::chronic-medication.list')
        ->call('editMedications', $patient->id)
        ->assertSet('medications', 'Existing meds');
});

test('patients can be filtered by name', function () {
    $this->actingAs(User::factory()->create());

    Patient::factory()->create(['name' => 'João Silva']);
    Patient::factory()->create(['name' => 'Maria Santos']);

    $component = Livewire::test('pages::chronic-medication.list')
        ->set('search', 'João');

    $patients = $component->get('patients');

    expect($patients)->toHaveCount(1);
    expect($patients->first()->name)->toBe('João Silva');
});

test('patient has one chronic medication relationship', function () {
    $patient = Patient::factory()->create();

    $medication = ChronicMedication::factory()->create(['patient_id' => $patient->id]);

    expect($patient->chronicMedication->id)->toBe($medication->id);
});
