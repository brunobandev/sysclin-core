<?php

use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Usuários')] class extends Component {
    public ?User $selectedUser = null;
    public array $selectedRoles = [];
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('manage-roles');
    }

    #[Computed]
    public function users()
    {
        return User::with('roles')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function allRoles()
    {
        return Role::orderBy('name')->get();
    }

    public function editRoles(User $user): void
    {
        $this->selectedUser = $user;
        $this->selectedRoles = $user->roles->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->modal('user-roles-form')->show();
    }

    public function saveRoles(): void
    {
        $validated = $this->validate([
            'selectedRoles' => 'array',
            'selectedRoles.*' => 'exists:roles,id',
        ]);

        $this->selectedUser->roles()->sync($validated['selectedRoles']);

        Flux::toast(text: 'Cargos atualizados com sucesso.', heading: 'Cargos atualizados', variant: 'success');
        $this->modal('user-roles-form')->close();
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Usuários</flux:heading>
    <flux:text class="mb-6 mt-2 text-base">Gerencie os cargos dos usuários</flux:text>
    <flux:separator variant="subtle" />

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nome..." icon="magnifying-glass" class="max-w-xs" />
    </div>

    <div class="mt-8">
        @if ($this->users->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-12 dark:border-zinc-700">
                <flux:icon.users class="mb-4 size-10 text-zinc-400" />
                <flux:heading>Nenhum usuário encontrado</flux:heading>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Cargos</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->users as $user)
                        <flux:table.row :key="$user->id">
                            <flux:table.cell class="font-medium">{{ $user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($user->roles as $role)
                                        <flux:badge size="sm" variant="pill">{{ $role->label }}</flux:badge>
                                    @endforeach
                                    @if ($user->roles->isEmpty())
                                        <flux:text class="text-zinc-400">—</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button variant="ghost" icon="pencil-square" size="sm" inset="top bottom" wire:click="editRoles({{ $user->id }})" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="user-roles-form" class="md:w-96">
        <form wire:submit="saveRoles" class="space-y-6">
            <div>
                <flux:heading size="lg">Cargos do Usuário</flux:heading>
                <flux:subheading>{{ $selectedUser?->name }}</flux:subheading>
            </div>

            <div class="space-y-2">
                @foreach ($this->allRoles as $role)
                    <flux:checkbox wire:model="selectedRoles" value="{{ $role->id }}" label="{{ $role->label }}" />
                @endforeach
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
