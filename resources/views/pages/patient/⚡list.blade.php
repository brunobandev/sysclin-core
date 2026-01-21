<?php

use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Patient;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Pacientes')] class extends Component {
    public ?Patient $editing = null;
    public string $name = '';
    public string $dob = '';
    public string $gender = '';

    #[Computed]
    public function patients()
    {
        return Patient::latest()->get();
    }

    public function create()
    {
        $this->editing = null;
        $this->name = '';
        $this->dob = '';
        $this->gender = '';

        $this->modal('patient-form')->show();
    }

    public function edit(Patient $patient)
    {
        $this->editing = $patient;
        $this->name = $patient->name;
        $this->dob = $patient->dob;
        $this->gender = $patient->gender ?? '';

        $this->modal('patient-form')->show();
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'nullable|string',
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
            Flux::toast(
                text: 'Paciente atualizado com sucesso.',
                heading: 'Registro atualizado',
                variant: 'success'
            );
        } else {
            Patient::create($validated);
            Flux::toast(
                text: 'Paciente criado com sucesso.',
                heading: 'Registro criado',
                variant: 'success'
            );
        }

        $this->modal('patient-form')->close();
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Pacientes</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie pacientes de maneira intuitiva</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6">
        <flux:button variant="primary" icon="plus" wire:click="create">Novo paciente</flux:button>
    </div>

    <div class="mt-8">
        @if ($this->patients->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.users class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhum paciente cadastrado</flux:heading>
                <flux:text>Comece cadastrando um novo paciente acima.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>Nascimento</flux:table.column>
                    <flux:table.column>Gênero</flux:table.column>
                    <flux:table.column>Cadastrado em</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->patients as $patient)
                        <flux:table.row :key="$patient->id">
                            <flux:table.cell class="font-medium">{{ $patient->name }}</flux:table.cell>
                            <flux:table.cell>{{ \Carbon\Carbon::parse($patient->dob)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>{{ $patient->gender ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $patient->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="edit({{ $patient->id }})" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="patient-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar paciente' : 'Novo paciente' }}</flux:heading>
                <flux:subheading>{{ $editing ? 'Atualize os dados do paciente.' : 'Preencha os dados para cadastrar um novo paciente.' }}</flux:subheading>
            </div>

            <div class="space-y-6">
                <flux:input wire:model="name" label="Nome completo" placeholder="Ex: João Silva" />

                <flux:date-picker wire:model="dob" label="Data de nascimento" selectable-header />

                <flux:select wire:model="gender" label="Gênero" placeholder="Selecione...">
                    <flux:select.option value="Masculino">Masculino</flux:select.option>
                    <flux:select.option value="Feminino">Feminino</flux:select.option>
                    <flux:select.option value="Outro">Outro</flux:select.option>
                </flux:select>
            </div>

            <div class="flex">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" class="ml-2">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:toast position="top center" />
</div>
