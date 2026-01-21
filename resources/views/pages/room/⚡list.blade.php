<?php

use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Room;
use App\Models\Facility;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Salas')] class extends Component {
    public ?Room $editing = null;
    public string $name = '';
    public string $facility_id = '';

    #[Computed]
    public function rooms()
    {
        return Room::with('facility')->latest()->get();
    }

    #[Computed]
    public function facilities()
    {
        return Facility::orderBy('name')->get();
    }

    public function create()
    {
        $this->editing = null;
        $this->name = '';
        $this->facility_id = '';

        $this->modal('room-form')->show();
    }

    public function edit(Room $room)
    {
        $this->editing = $room;
        $this->name = $room->name;
        $this->facility_id = (string) $room->facility_id;

        $this->modal('room-form')->show();
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'facility_id' => 'required|exists:facilities,id',
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
            Flux::toast(
                text: 'Sala atualizada com sucesso.',
                heading: 'Registro atualizado',
                variant: 'success'
            );
        } else {
            Room::create($validated);
            Flux::toast(
                text: 'Sala criada com sucesso.',
                heading: 'Registro criado',
                variant: 'success'
            );
        }

        $this->modal('room-form')->close();
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Salas</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie salas de maneira intuitiva</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6">
        <flux:button variant="primary" icon="plus" wire:click="create">Nova sala</flux:button>
    </div>

    <div class="mt-8">
        @if ($this->rooms->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.squares-2x2 class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhuma sala cadastrada</flux:heading>
                <flux:text>Comece cadastrando uma nova sala acima.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>Sede</flux:table.column>
                    <flux:table.column>Cadastrada em</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->rooms as $room)
                        <flux:table.row :key="$room->id">
                            <flux:table.cell class="font-medium">{{ $room->name }}</flux:table.cell>
                            <flux:table.cell>{{ $room->facility?->name ?? 'N/A' }}</flux:table.cell>
                            <flux:table.cell>{{ $room->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="edit({{ $room->id }})" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="room-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar sala' : 'Nova sala' }}</flux:heading>
                <flux:subheading>{{ $editing ? 'Atualize os dados da sala.' : 'Preencha os dados para cadastrar uma nova sala.' }}</flux:subheading>
            </div>

            <div class="space-y-6">
                <flux:select wire:model="facility_id" label="Sede" placeholder="Selecione uma sede...">
                    @foreach ($this->facilities as $facility)
                        <flux:select.option :value="$facility->id">{{ $facility->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="name" label="Nome da sala" placeholder="Ex: Sala 01" />
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
