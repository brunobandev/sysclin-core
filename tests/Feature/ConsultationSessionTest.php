<?php

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Livewire\Livewire;

test('assigned doctor can access consultation session', function () {
    $doctor = User::factory()->doctor()->create();
    $appointment = Appointment::factory()->create(['user_id' => $doctor->id]);

    $this->actingAs($doctor);

    $this->get(route('consultation.session', $appointment))->assertOk();
});

test('other doctors cannot access consultation session', function () {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $appointment = Appointment::factory()->create(['user_id' => $doctor->id]);

    $this->actingAs($otherDoctor);

    $this->get(route('consultation.session', $appointment))->assertForbidden();
});

test('secretaries cannot access consultation session', function () {
    $secretary = User::factory()->secretary()->create();
    $doctor = User::factory()->doctor()->create();
    $appointment = Appointment::factory()->create(['user_id' => $doctor->id]);

    $this->actingAs($secretary);

    $this->get(route('consultation.session', $appointment))->assertForbidden();
});

test('doctor can start consultation session', function () {
    $doctor = User::factory()->doctor()->create();
    $emAtendimentoStatus = AppointmentStatus::create(['name' => 'Em atendimento']);
    $appointment = Appointment::factory()->create(['user_id' => $doctor->id]);

    $this->actingAs($doctor);

    Livewire::test('pages::consultation.[Appointment]', ['appointment' => $appointment])
        ->call('startSession');

    $appointment->refresh();
    expect($appointment->session_started_at)->not->toBeNull();
    expect($appointment->appointment_status_id)->toBe($emAtendimentoStatus->id);
});

test('doctor can end consultation session', function () {
    $doctor = User::factory()->doctor()->create();
    $realizadaStatus = AppointmentStatus::create(['name' => 'Realizada']);
    $appointment = Appointment::factory()->create([
        'user_id' => $doctor->id,
        'session_started_at' => now(),
    ]);

    $this->actingAs($doctor);

    Livewire::test('pages::consultation.[Appointment]', ['appointment' => $appointment])
        ->call('endSession');

    $appointment->refresh();
    expect($appointment->session_ended_at)->not->toBeNull();
    expect($appointment->appointment_status_id)->toBe($realizadaStatus->id);
});

test('doctor can create medical record during session', function () {
    $doctor = User::factory()->doctor()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $doctor->id,
        'session_started_at' => now(),
    ]);

    $this->actingAs($doctor);

    Livewire::test('pages::consultation.[Appointment]', ['appointment' => $appointment])
        ->set('reason', 'Dor de cabeça')
        ->set('disease_cid', 'R51')
        ->set('subjective', 'Paciente relata dor intensa')
        ->set('objective', 'PA 120x80')
        ->set('impression', 'Cefaleia tensional')
        ->set('conduct', 'Paracetamol 750mg')
        ->call('saveMedicalRecord')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('medical_records', [
        'appointment_id' => $appointment->id,
        'patient_id' => $appointment->patient_id,
        'user_id' => $doctor->id,
        'reason' => 'Dor de cabeça',
        'disease_cid' => 'R51',
    ]);
});

test('medical record is linked to appointment', function () {
    $doctor = User::factory()->doctor()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $doctor->id,
        'session_started_at' => now(),
    ]);

    $this->actingAs($doctor);

    Livewire::test('pages::consultation.[Appointment]', ['appointment' => $appointment])
        ->set('reason', 'Consulta de rotina')
        ->call('saveMedicalRecord');

    $record = MedicalRecord::where('appointment_id', $appointment->id)->first();

    expect($record)->not->toBeNull();
    expect($appointment->fresh()->medicalRecord->id)->toBe($record->id);
});

test('doctor can update medical record during session', function () {
    $doctor = User::factory()->doctor()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $doctor->id,
        'session_started_at' => now(),
    ]);

    MedicalRecord::create([
        'appointment_id' => $appointment->id,
        'patient_id' => $appointment->patient_id,
        'user_id' => $doctor->id,
        'reason' => 'Motivo inicial',
    ]);

    $this->actingAs($doctor);

    Livewire::test('pages::consultation.[Appointment]', ['appointment' => $appointment])
        ->set('reason', 'Motivo atualizado')
        ->call('saveMedicalRecord');

    $appointment->refresh();

    expect($appointment->medicalRecord->reason)->toBe('Motivo atualizado');
    expect(MedicalRecord::where('appointment_id', $appointment->id)->count())->toBe(1);
});

test('patient data is displayed correctly', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create([
        'name' => 'João Silva',
        'dob' => '1990-05-15',
        'gender' => 'Masculino',
        'phone' => '(11) 99999-0000',
    ]);
    $appointment = Appointment::factory()->create([
        'user_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($doctor);

    $this->get(route('consultation.session', $appointment))
        ->assertOk()
        ->assertSee('João Silva')
        ->assertSee('Masculino')
        ->assertSee('(11) 99999-0000');
});

test('previous medical records are displayed', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    MedicalRecord::factory()->create([
        'patient_id' => $patient->id,
        'user_id' => $doctor->id,
        'reason' => 'Consulta anterior',
    ]);

    $this->actingAs($doctor);

    $component = Livewire::test('pages::consultation.[Appointment]', ['appointment' => $appointment]);

    expect($component->instance()->previousMedicalRecords)->toHaveCount(1);
    expect($component->instance()->previousMedicalRecords->first()->reason)->toBe('Consulta anterior');
});

test('previous prescriptions are displayed', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    Prescription::factory()->create([
        'patient_id' => $patient->id,
        'user_id' => $doctor->id,
    ]);

    $this->actingAs($doctor);

    $component = Livewire::test('pages::consultation.[Appointment]', ['appointment' => $appointment]);

    expect($component->instance()->previousPrescriptions)->toHaveCount(1);
});

test('patient age is calculated correctly', function () {
    $doctor = User::factory()->doctor()->create();
    $patient = Patient::factory()->create([
        'dob' => now()->subYears(35)->format('Y-m-d'),
    ]);
    $appointment = Appointment::factory()->create([
        'user_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($doctor);

    $component = Livewire::test('pages::consultation.[Appointment]', ['appointment' => $appointment]);

    expect($component->instance()->patientAge)->toBe(35);
});

test('session timer persists on page refresh', function () {
    $doctor = User::factory()->doctor()->create();
    $startTime = now()->subMinutes(10);
    $appointment = Appointment::factory()->create([
        'user_id' => $doctor->id,
        'session_started_at' => $startTime,
    ]);

    $this->actingAs($doctor);

    $this->get(route('consultation.session', $appointment))
        ->assertOk()
        ->assertSee($startTime->timestamp);
});

test('isSessionActive returns true when session is started but not ended', function () {
    $appointment = Appointment::factory()->create([
        'session_started_at' => now(),
        'session_ended_at' => null,
    ]);

    expect($appointment->isSessionActive())->toBeTrue();
});

test('isSessionActive returns false when session is ended', function () {
    $appointment = Appointment::factory()->create([
        'session_started_at' => now()->subHour(),
        'session_ended_at' => now(),
    ]);

    expect($appointment->isSessionActive())->toBeFalse();
});

test('isSessionActive returns false when session never started', function () {
    $appointment = Appointment::factory()->create([
        'session_started_at' => null,
        'session_ended_at' => null,
    ]);

    expect($appointment->isSessionActive())->toBeFalse();
});
