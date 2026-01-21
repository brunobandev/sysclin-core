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

require __DIR__.'/settings.php';
