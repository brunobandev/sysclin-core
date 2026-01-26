<?php

use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Facility;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Sedes')] class extends Component {
    public ?Facility $editing = null;
    public string $name = '';
    public string $address = '';
    public string $phone = '';

    #[Computed]
    public function facilities()
    {
        return Facility::latest()->get();
    }

    public function create()
    {
        $this->editing = null;
        $this->name = '';
        $this->address = '';
        $this->phone = '';

        $this->modal('facility-form')->show();
    }

    public function edit(Facility $facility)
    {
        $this->editing = $facility;
        $this->name = $facility->name;
        $this->address = $facility->address ?? '';
        $this->phone = $facility->phone ?? '';

        $this->modal('facility-form')->show();
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
            Flux::toast(
                text: 'Sede atualizada com sucesso.',
                heading: 'Registro atualizado',
                variant: 'success'
            );
        } else {
            Facility::create($validated);
            Flux::toast(
                text: 'Sede criada com sucesso.',
                heading: 'Registro criado',
                variant: 'success'
            );
        }

        $this->modal('facility-form')->close();
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Sedes</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie sedes de maneira intuitiva</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6">
        <flux:button variant="primary" icon="plus" wire:click="create">Nova sede</flux:button>
    </div>

    <div class="mt-8">
        @if ($this->facilities->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.home class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhuma sede cadastrada</flux:heading>
                <flux:text>Comece cadastrando uma nova sede acima.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>Endereço</flux:table.column>
                    <flux:table.column>Telefone</flux:table.column>
                    <flux:table.column>Cadastrada em</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->facilities as $facility)
                        <flux:table.row :key="$facility->id">
                            <flux:table.cell class="font-medium">{{ $facility->name }}</flux:table.cell>
                            <flux:table.cell>{{ $facility->address ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $facility->phone ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $facility->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="edit({{ $facility->id }})" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="facility-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar sede' : 'Nova sede' }}</flux:heading>
                <flux:subheading>{{ $editing ? 'Atualize os dados da sede.' : 'Preencha os dados para cadastrar uma nova sede.' }}</flux:subheading>
            </div>

            <div class="space-y-6">
                <flux:input wire:model="name" label="Nome" placeholder="Ex: Sede Principal" />

                <flux:input wire:model="address" label="Endereço" placeholder="Ex: Rua das Flores, 123" />

                <flux:input wire:model="phone" label="Telefone" placeholder="(00) 00000-0000" />
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
