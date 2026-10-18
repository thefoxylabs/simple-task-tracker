@props(['user', 'size' => 'size-7'])
@if ($user)
    <span title="{{ $user->name }}" {{ $attributes->merge(['class' => "$size inline-flex shrink-0 items-center justify-center rounded-full text-[0.65rem] font-semibold text-white ring-2 ring-white"]) }} style="background-color: {{ $user->avatarColor() }}">
        {{ $user->initials() }}
    </span>
@else
    <span title="Unassigned" {{ $attributes->merge(['class' => "$size inline-flex shrink-0 items-center justify-center rounded-full border border-dashed border-slate-300 bg-white text-slate-400"]) }}>
        <x-icon name="user" class="size-3.5" />
    </span>
@endif
