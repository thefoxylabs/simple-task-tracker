<div wire:init="runLiveChecks">
    <x-page-header title="Settings" description="Make this workspace your own." />

    @include('partials.settings-tabs', ['current' => 'status'])

    <div class="max-w-4xl">
        {{-- Overall --}}
        @php
            $tone = ! $live ? 'checking' : ($errors ? 'error' : ($warnings ? 'warning' : 'ok'));
            $banner = [
                'checking' => ['bg-slate-50 ring-slate-200', 'bg-slate-200 text-slate-600', 'Checking your system…', 'Running live tests on email and file storage.'],
                'error' => ['bg-red-50 ring-red-600/20', 'bg-red-100 text-red-600', $errors.' '.\Illuminate\Support\Str::plural('problem', $errors).' need fixing', 'Some things Task Tracker relies on aren\'t working. Fix the items marked in red.'],
                'warning' => ['bg-amber-50 ring-amber-600/20', 'bg-amber-100 text-amber-600', 'Working, with '.$warnings.' '.\Illuminate\Support\Str::plural('recommendation', $warnings), 'Everything essential works. The items in amber would make things safer or more reliable.'],
                'ok' => ['bg-emerald-50 ring-emerald-600/20', 'bg-emerald-100 text-emerald-600', 'All systems working', 'Everything Task Tracker needs is set up and working.'],
            ][$tone];
        @endphp
        <div class="card mb-6 flex flex-col gap-4 p-6 ring-1 sm:flex-row sm:items-center {{ $banner[0] }}">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $banner[1] }}">
                @if ($tone === 'checking')
                    <svg class="size-6 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                @else
                    <x-icon :name="$tone === 'ok' ? 'check-circle' : 'alert'" class="size-6" />
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold text-slate-900">{{ $banner[2] }}</h2>
                <p class="text-sm text-slate-600">{{ $banner[3] }}</p>
                @if ($live)
                    <p class="mt-1 text-xs text-slate-500">{{ $passing }} of {{ $total }} checks passing · checked {{ \Carbon\Carbon::parse($checkedAt)->diffForHumans() }}</p>
                @endif
            </div>
            <button type="button" wire:click="runLiveChecks" class="btn btn-secondary shrink-0" wire:loading.attr="disabled">
                <x-icon name="repeat" class="size-4" wire:loading.class="animate-spin" wire:target="runLiveChecks" />
                Check again
            </button>
        </div>

        {{-- Groups --}}
        <div class="space-y-6">
            @foreach ($groups as $group => $checks)
                <section class="card overflow-hidden" wire:key="group-{{ \Illuminate\Support\Str::slug($group) }}">
                    <h3 class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $group }}</h3>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($checks as $key => $check)
                            @php
                                $styles = [
                                    'ok' => ['bg-emerald-50 text-emerald-600', 'check', 'Working', 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'],
                                    'warning' => ['bg-amber-50 text-amber-600', 'alert', 'Check', 'bg-amber-50 text-amber-700 ring-amber-600/20'],
                                    'error' => ['bg-red-50 text-red-600', 'x', 'Not working', 'bg-red-50 text-red-700 ring-red-600/20'],
                                    'info' => ['bg-slate-100 text-slate-500', 'list', 'Info', 'bg-slate-50 text-slate-600 ring-slate-500/20'],
                                    'pending' => ['bg-slate-100 text-slate-400', 'clock', 'Checking…', 'bg-slate-50 text-slate-500 ring-slate-500/20'],
                                ][$check['status']];
                            @endphp
                            <li class="flex gap-4 px-5 py-4" wire:key="check-{{ $key }}">
                                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full {{ $styles[0] }}">
                                    @if ($check['status'] === 'pending')
                                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity=".3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                                    @else
                                        <x-icon :name="$styles[1]" class="size-4" />
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-medium text-slate-900">{{ $check['title'] }}</p>
                                        <span class="badge {{ $styles[3] }}">{{ $styles[2] }}</span>
                                    </div>
                                    <p class="mt-1 text-sm break-words text-slate-600">{{ $check['message'] }}</p>
                                    @if ($check['fix'] && in_array($check['status'], ['error', 'warning'], true))
                                        <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 ring-1 ring-slate-200">
                                            <span class="font-semibold">How to fix:</span>
                                            {!! preg_replace('/`([^`]+)`/', '<code class="rounded bg-white px-1 py-0.5 font-mono text-xs ring-1 ring-slate-200">$1</code>', e($check['fix'])) !!}
                                        </p>
                                    @endif
                                </div>
                                @if ($check['link'])
                                    <a href="{{ $check['link'][1] }}" wire:navigate class="hidden shrink-0 self-center text-sm font-medium text-brand-600 hover:text-brand-700 sm:block">{{ $check['link'][0] }} →</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>
</div>
