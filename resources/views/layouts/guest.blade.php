<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    @include('partials.head')
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    <div class="flex min-h-full">
        <div class="relative hidden w-0 flex-1 overflow-hidden bg-brand-700 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.18),transparent_45%),radial-gradient(circle_at_80%_80%,rgba(0,0,0,0.25),transparent_50%)]"></div>
            <div class="relative">@include('partials.brand', ['dark' => true])</div>
            <div class="relative max-w-md">
                <p class="text-3xl leading-tight font-semibold text-white">{{ settings('tagline') }}</p>
                @if (settings('company_name'))
                    <p class="mt-4 text-sm text-white/70">{{ settings('company_name') }}</p>
                @endif
            </div>
            <p class="relative text-xs text-white/60">{{ settings('footer_text') ?: '© '.date('Y').' '.(settings('company_name') ?: settings('app_name')) }}</p>
        </div>
        <div class="flex flex-1 flex-col justify-center px-6 py-12 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm">
                <div class="mb-10 lg:hidden">@include('partials.brand')</div>
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
