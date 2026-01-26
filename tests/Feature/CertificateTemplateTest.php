<?php

use App\Models\CertificateTemplate;
use App\Models\User;
use Livewire\Livewire;

test('doctors can visit the certificate templates page', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $this->get(route('certificate-template.list'))->assertOk();
});

test('secretaries cannot visit the certificate templates page', function () {
    $this->actingAs(User::factory()->secretary()->create());

    $this->get(route('certificate-template.list'))->assertForbidden();
});

test('a certificate template can be created', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::certificate-template.list')
        ->set('name', 'Atestado de comparecimento')
        ->set('content', 'Atesto que o(a) paciente compareceu a consulta médica nesta data.')
        ->set('cid', 'Z02.7')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('certificate_templates', [
        'name' => 'Atestado de comparecimento',
        'cid' => 'Z02.7',
    ]);
});

test('name and content are required', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::certificate-template.list')
        ->set('name', '')
        ->set('content', '')
        ->call('save')
        ->assertHasErrors(['name', 'content']);
});

test('cid is optional', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::certificate-template.list')
        ->set('name', 'Atestado simples')
        ->set('content', 'Conteúdo do atestado.')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('certificate_templates', [
        'name' => 'Atestado simples',
    ]);

    $template = CertificateTemplate::where('name', 'Atestado simples')->first();
    expect($template->cid)->toBeIn([null, '']);
});

test('a certificate template can be updated', function () {
    $template = CertificateTemplate::factory()->create();

    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::certificate-template.list')
        ->call('edit', $template->id)
        ->set('name', 'Nome atualizado')
        ->set('content', 'Conteúdo atualizado')
        ->call('save')
        ->assertHasNoErrors();

    $template->refresh();
    expect($template->name)->toBe('Nome atualizado');
    expect($template->content)->toBe('Conteúdo atualizado');
});

test('a certificate template can be deleted', function () {
    $template = CertificateTemplate::factory()->create();

    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::certificate-template.list')
        ->call('delete', $template->id);

    $this->assertDatabaseMissing('certificate_templates', ['id' => $template->id]);
});

test('cid max length is validated', function () {
    $this->actingAs(User::factory()->doctor()->create());

    Livewire::test('pages::certificate-template.list')
        ->set('name', 'Test')
        ->set('content', 'Content')
        ->set('cid', str_repeat('A', 21))
        ->call('save')
        ->assertHasErrors(['cid']);
});
