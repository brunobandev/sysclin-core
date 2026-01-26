<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::livewire('agenda', 'pages::appointment.list')->name('appointment.list');
Route::livewire('pacientes', 'pages::patient.list')->name('patient.list');
Route::livewire('planos-de-saude', 'pages::health-insurance.list')->name('health-insurance.list');
Route::livewire('sedes', 'pages::facility.list')->name('facility.list');
Route::livewire('salas', 'pages::room.list')->name('room.list');
Route::livewire('prontuarios', 'pages::medical-record.list')->middleware('can:manage-medical-records')->name('medical-record.list');
Route::livewire('receituarios', 'pages::prescription.list')->middleware('can:manage-prescriptions')->name('prescription.list');
Route::livewire('modelos-receituario', 'pages::prescription-template.list')->middleware('can:manage-prescription-templates')->name('prescription-template.list');
Route::livewire('modelos-atestado', 'pages::certificate-template.list')->middleware('can:manage-certificate-templates')->name('certificate-template.list');
Route::livewire('medicacoes-cronicas', 'pages::chronic-medication.list')->name('chronic-medication.list');

require __DIR__.'/settings.php';
