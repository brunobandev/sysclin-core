<?php

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('doctors can visit the medical records page', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $this->get(route('medical-record.list'))->assertOk();
});

test('secretaries cannot visit the medical records page', function () {
    $this->actingAs(User::factory()->secretary()->create());

    $this->get(route('medical-record.list'))->assertForbidden();
});

test('technicians cannot visit the medical records page', function () {
    $this->actingAs(User::factory()->technician()->create());

    $this->get(route('medical-record.list'))->assertForbidden();
});

test('a doctor can create a medical record', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($doctor);

    Livewire::test('pages::medical-record.list')
        ->set('patient_id', $patient->id)
        ->set('reason', 'Dor de cabeça')
        ->set('disease_cid', 'R51')
        ->set('subjective', 'Paciente relata dor')
        ->set('objective', 'PA 120x80')
        ->set('exams', 'Hemograma normal')
        ->set('impression', 'Cefaleia tensional')
        ->set('conduct', 'Paracetamol 750mg')
        ->set('description', 'Retorno em 15 dias')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('medical_records', [
        'patient_id' => $patient->id,
        'user_id' => $doctor->id,
        'reason' => 'Dor de cabeça',
        'disease_cid' => 'R51',
    ]);
});

test('patient_id is required to create a medical record', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::medical-record.list')
        ->set('patient_id', '')
        ->set('reason', 'Dor')
        ->call('save')
        ->assertHasErrors(['patient_id']);
});

test('a medical record can be created with only required fields', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($doctor);

    Livewire::test('pages::medical-record.list')
        ->set('patient_id', $patient->id)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('medical_records', [
        'patient_id' => $patient->id,
        'user_id' => $doctor->id,
    ]);
});

test('a medical record can be updated', function () {
    $doctor = User::factory()->doctor()->create();
    $record = MedicalRecord::factory()->create(['user_id' => $doctor->id]);

    $this->actingAs($doctor);

    Livewire::test('pages::medical-record.list')
        ->call('edit', $record->id)
        ->set('reason', 'Motivo atualizado')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->reason)->toBe('Motivo atualizado');
});

test('a medical record can be deleted', function () {
    $doctor = User::factory()->doctor()->create();
    $record = MedicalRecord::factory()->create(['user_id' => $doctor->id]);

    $this->actingAs($doctor);

    Livewire::test('pages::medical-record.list')
        ->call('delete', $record->id);

    $this->assertDatabaseMissing('medical_records', ['id' => $record->id]);
});

test('photos can be uploaded with a medical record', function () {
    Storage::fake('private');

    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($doctor);

    $file = UploadedFile::fake()->image('xray.jpg');

    Livewire::test('pages::medical-record.list')
        ->set('patient_id', $patient->id)
        ->set('reason', 'Exame de imagem')
        ->set('photos', [$file])
        ->call('save')
        ->assertHasNoErrors();

    $record = MedicalRecord::where('patient_id', $patient->id)->first();

    expect($record->photos)->toHaveCount(1);
    expect($record->photos->first()->original_name)->toBe('xray.jpg');
});

test('medical records can be filtered by patient name', function () {
    $doctor = User::factory()->doctor()->create();

    $patient1 = Patient::factory()->create(['name' => 'João Silva']);
    $patient2 = Patient::factory()->create(['name' => 'Maria Santos']);

    MedicalRecord::factory()->create(['patient_id' => $patient1->id, 'user_id' => $doctor->id]);
    MedicalRecord::factory()->create(['patient_id' => $patient2->id, 'user_id' => $doctor->id]);

    $this->actingAs($doctor);

    $component = Livewire::test('pages::medical-record.list');

    $component->set('search', 'João');

    $records = $component->get('medicalRecords');

    expect($records)->toHaveCount(1);
    expect($records->first()->patient->name)->toBe('João Silva');
});

test('disease_cid max length is validated', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($doctor);

    Livewire::test('pages::medical-record.list')
        ->set('patient_id', $patient->id)
        ->set('disease_cid', str_repeat('A', 21))
        ->call('save')
        ->assertHasErrors(['disease_cid']);
});
