<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    @include('partials.head')
</head>
<body class="h-full font-sans text-slate-900 antialiased" x-data="{ sidebar: false }">
    @php
        $user = auth()->user();
        $nav = [
            // [label, route, icon, visible, active pattern]
            ['Dashboard', 'dashboard', 'home', true, 'dashboard'],
            ['Board', 'board', 'board', true, 'board'],
            ['Projects', 'projects.index', 'folder', true, 'projects.*'],
            ['Plans', 'plans.index', 'target', true, 'plans.*'],
            ['Recurring tasks', 'recurring.index', 'repeat', $user->canManage(), 'recurring.*'],
            ['Staff', 'staff.index', 'users', $user->canManage(), 'staff.*'],
            ['My work', 'staff.show', 'chart', ! $user->canManage(), 'staff.*'],
            ['Staff groups', 'groups.index', 'tag', $user->canManage(), 'groups.*'],
            ['Settings', 'settings', 'cog', $user->isAdmin(), 'settings'],
        ];
    @endphp

    {{-- Mobile sidebar backdrop --}}
    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="sidebar = false"></div>

    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
        <div class="flex h-16 shrink-0 items-center px-5">
            <a href="{{ route('dashboard') }}" wire:navigate class="min-w-0">@include('partials.brand')</a>
        </div>

        <div class="px-3 pb-2">
            <button type="button" x-on:click="$dispatch('create-task'); sidebar = false" class="btn btn-primary w-full">
                <x-icon name="plus" class="size-4" /> New task
            </button>
        </div>

        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-2">
            @foreach ($nav as [$label, $route, $icon, $visible, $pattern])
                @continue(! $visible)
                @php
                    $url = $route === 'staff.show' ? route($route, $user) : route($route);
                    $active = request()->routeIs($pattern);
                @endphp
                <a href="{{ $url }}" wire:navigate @class([
                    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                    'bg-brand-50 text-brand-700' => $active,
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! $active,
                ])>
                    <x-icon :name="$icon" @class(['size-5', 'text-brand-600' => $active, 'text-slate-400 group-hover:text-slate-500' => ! $active]) />
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-slate-200 p-3" x-data="{ open: false }" @click.outside="open = false">
            <div x-cloak x-show="open" x-transition class="mb-2 rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200">
                <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    <x-icon name="user" class="size-4 text-slate-400" /> Your profile
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <x-icon name="logout" class="size-4 text-slate-400" /> Sign out
                    </button>
                </form>
            </div>
            <button type="button" @click="open = ! open" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-slate-50">
                <x-avatar :user="$user" size="size-9" class="ring-0" />
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $user->name }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $user->role->label() }}{{ $user->job_title ? ' · '.$user->job_title : '' }}</span>
                </span>
                <x-icon name="chevron-down" class="size-4 text-slate-400" />
            </button>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:hidden">
            <button type="button" @click="sidebar = true" class="rounded-md p-1.5 text-slate-600 hover:bg-slate-100">
                <x-icon name="menu" />
                <span class="sr-only">Open menu</span>
            </button>
            @include('partials.brand')
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </main>

        @if (settings('footer_text'))
            <footer class="px-4 pb-6 text-center text-xs text-slate-400 sm:px-6 lg:px-8">{{ settings('footer_text') }}</footer>
        @endif
    </div>

    <livewire:task-form />

    {{-- Toast notifications: dispatch('notify', message: '...') from any component --}}
    <div x-data="{ toasts: [] }"
         x-on:notify.window="const id = Date.now(); toasts.push({ id, message: $event.detail.message ?? $event.detail[0]?.message ?? $event.detail }); setTimeout(() => toasts = toasts.filter(t => t.id !== id), 3500)"
         class="pointer-events-none fixed right-4 bottom-4 z-[60] flex w-80 flex-col gap-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition class="pointer-events-auto flex items-center gap-3 rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-lg">
                <x-icon name="check-circle" class="size-5 text-emerald-400" />
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    @if (session('status'))
        <div x-data x-init="$nextTick(() => $dispatch('notify', { message: @js(session('status')) }))"></div>
    @endif
</body>
</html>
