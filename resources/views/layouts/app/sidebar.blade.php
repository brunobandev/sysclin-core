<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="chart-bar" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar-days" :href="route('appointment.list')" :current="request()->routeIs('appointment.list')" wire:navigate>
                        {{ __('Agenda') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('patient.list')" :current="request()->routeIs('patient.list')" wire:navigate>
                        {{ __('Pacientes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="heart" :href="route('chronic-medication.list')" :current="request()->routeIs('chronic-medication.list')" wire:navigate>
                        {{ __('Medicações Crônicas') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @can('manage-medical-records')
                    @if (Route::has('medical-record.list'))
                        <flux:sidebar.group :heading="__('Clínico')" class="grid">
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('medical-record.list')" :current="request()->routeIs('medical-record.list')" wire:navigate>
                                {{ __('Prontuários') }}
                            </flux:sidebar.item>
                            @if (Route::has('prescription.list'))
                                <flux:sidebar.item icon="document-text" :href="route('prescription.list')" :current="request()->routeIs('prescription.list')" wire:navigate>
                                    {{ __('Receituários') }}
                                </flux:sidebar.item>
                            @endif
                        </flux:sidebar.group>
                    @endif
                @endcan

                @can('manage-certificate-templates')
                    @if (Route::has('prescription-template.list') || Route::has('certificate-template.list'))
                        <flux:sidebar.group :heading="__('Modelos')" class="grid">
                            @if (Route::has('prescription-template.list'))
                                <flux:sidebar.item icon="document-duplicate" :href="route('prescription-template.list')" :current="request()->routeIs('prescription-template.list')" wire:navigate>
                                    {{ __('Modelos de Receituário') }}
                                </flux:sidebar.item>
                            @endif
                            @if (Route::has('certificate-template.list'))
                                <flux:sidebar.item icon="document-check" :href="route('certificate-template.list')" :current="request()->routeIs('certificate-template.list')" wire:navigate>
                                    {{ __('Modelos de Atestado') }}
                                </flux:sidebar.item>
                            @endif
                        </flux:sidebar.group>
                    @endif
                @endcan

                @can('manage-roles')
                    <flux:sidebar.group :heading="__('Administração')" class="grid">
                        @if (Route::has('role.list'))
                            <flux:sidebar.item icon="shield-check" :href="route('role.list')" :current="request()->routeIs('role.list')" wire:navigate>
                                {{ __('Cargos') }}
                            </flux:sidebar.item>
                        @endif
                        @if (Route::has('user.list'))
                            <flux:sidebar.item icon="user-group" :href="route('user.list')" :current="request()->routeIs('user.list')" wire:navigate>
                                {{ __('Usuários') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endcan

                <flux:sidebar.group :heading="__('Cadastros')" class="grid">
                    <flux:sidebar.item icon="rectangle-stack" :href="route('health-insurance.list')" :current="request()->routeIs('health-insurance.list')" wire:navigate>
                        {{ __('Planos de saúde') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="squares-2x2" :href="route('room.list')" :current="request()->routeIs('room.list')" wire:navigate>
                        {{ __('Salas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="home" :href="route('facility.list')" :current="request()->routeIs('facility.list')" wire:navigate>
                        {{ __('Sedes') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
</body>
</html>
