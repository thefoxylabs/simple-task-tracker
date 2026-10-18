@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
@php($brand = preg_match('/^#[0-9a-fA-F]{6}$/', (string) settings('primary_color')) ? settings('primary_color') : '#4f46e5')
{{-- Buttons use the company brand colour from Settings. --}}
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" @if ($color === 'primary') style="background-color: {{ $brand }}; border-color: {{ $brand }};" @endif>{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
