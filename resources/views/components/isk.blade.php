@props(['amount' => 0])

{{-- Single source of truth for ISK formatting: 1250 => "1.250 kr." --}}
<span {{ $attributes }}>{{ \App\Support\Isk::format((int) $amount) }}</span>
