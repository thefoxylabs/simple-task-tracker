<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) ? $title.' · ' : '' }}{{ settings('app_name') }}</title>
@if ($logo = settings()->logoUrl())
    <link rel="icon" href="{{ $logo }}">
@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
{{-- After the stylesheet so the company colour overrides the default. --}}
<style>:root { --brand: {{ preg_match('/^#[0-9a-fA-F]{6}$/', settings('primary_color')) ? settings('primary_color') : '#4f46e5' }}; }</style>
