<?php

use App\Models\Permission;
use App\Models\Role;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cargos')] class extends Component {
    public ?Role $editing = null;
    public string $name = '';
    public string $label = '';
    public array $selectedPermissions = [];

    public function mount(): void
    {
        $this->authorize('manage-roles');
    }

    #[Computed]
    public function roles()
    {
        return Role::with('permissions')->orderBy('name')->get();
    }

    #[Computed]
    public function allPermissions()
    {
        return Permission::orderBy('name')->get();
    }

    public function create(): void
    {
        $this->reset('editing', 'name', 'label', 'selectedPermissions');
        $this->modal('role-form')->show();
    }

    public function edit(Role $role): void
    {
        $this->editing = $role;
        $this->name = $role->name;
        $this->label = $role->label;
        $this->selectedPermissions = $role->permissions->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->modal('role-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255|unique:roles,name' . ($this->editing ? ',' . $this->editing->id : ''),
            'label' => 'required|string|max:255',
            'selectedPermissions' => 'array',
            'selectedPermissions.*' => 'exists:permissions,id',
        ]);

        if ($this->editing) {
            $this->editing->update(['name' => $validated['name'], 'label' => $validated['label']]);
            $role = $this->editing;
            $toastHeading = 'Cargo atualizado';
            $toastText = 'Cargo atualizado com sucesso.';
        } else {
            $role = Role::create(['name' => $validated['name'], 'label' => $validated['label']]);
            $toastHeading = 'Cargo criado';
            $toastText = 'Cargo criado com sucesso.';
        }

        $role->permissions()->sync($validated['selectedPermissions']);

        Flux::toast(text: $toastText, heading: $toastHeading, variant: 'success');
        $this->modal('role-form')->close();
    }

    public function delete(Role $role): void
    {
        $role->delete();
        Flux::toast(text: 'Cargo removido com sucesso.', heading: 'Cargo removido', variant: 'success');
    }
};
?>

<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Cargos</flux:heading>
            <flux:text class="mb-6 mt-2 text-base">Gerencie os cargos e suas permissões</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">Novo Cargo</flux:button>
    </div>
    <flux:separator variant="subtle" />

    <div class="mt-8">
        @if ($this->roles->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.shield-check class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhum cargo encontrado</flux:heading>
                <flux:text>Crie cargos para gerenciar permissões de acesso.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>Label</flux:table.column>
                    <flux:table.column>Permissões</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->roles as $role)
                        <flux:table.row :key="$role->id">
                            <flux:table.cell class="font-medium">{{ $role->name }}</flux:table.cell>
                            <flux:table.cell>{{ $role->label }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($role->permissions as $permission)
                                        <flux:badge size="sm" variant="pill">{{ $permission->label }}</flux:badge>
                                    @endforeach
                                    @if ($role->permissions->isEmpty())
                                        <flux:text class="text-zinc-400">—</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-1">
                                    <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="edit({{ $role->id }})" />
                                    <flux:button variant="ghost" icon="trash" size="sm" inset="top bottom" wire:click="delete({{ $role->id }})" wire:confirm="Tem certeza que deseja remover este cargo?" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="role-form" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? 'Editar Cargo' : 'Novo Cargo' }}</flux:heading>
                <flux:subheading>{{ $editing ? 'Atualize as informações do cargo' : 'Preencha as informações do novo cargo' }}</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Nome (slug)" placeholder="ex: medico" required />
            <flux:input wire:model="label" label="Label" placeholder="ex: Médico" required />

            <div>
                <flux:heading size="sm" class="mb-2">Permissões</flux:heading>
                <div class="space-y-2">
                    @foreach ($this->allPermissions as $permission)
                        <flux:checkbox wire:model="selectedPermissions" value="{{ $permission->id }}" label="{{ $permission->label }}" />
                    @endforeach
                </div>
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
