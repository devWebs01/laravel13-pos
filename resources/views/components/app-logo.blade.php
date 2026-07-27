@php
    $setting = \App\Models\Setting::first();
    $store_name = $setting?->store_name ?? config('app.name');
    $logo_url = $setting?->logo_url;
@endphp

@props([
    'sidebar' => false,
    'store_name' => $store_name,
])

@if($sidebar)
    <flux:sidebar.brand name="{{ $store_name }}" {{ $attributes->merge(['class' => 'text-wrap break-words']) }}>
        <x-slot name="logo" class="{{ $logo_url ? 'size-8' : 'hidden aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground' }}">
            @if ($logo_url)
                <img src="{{ $logo_url }}" alt="{{ $store_name }}" class="size-8 rounded-md object-cover">
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="{{ $store_name }}" {{ $attributes }}>
        <x-slot name="logo" class="{{ $logo_url ? 'size-8' : 'hidden aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground' }}">
            @if ($logo_url)
                <img src="{{ $logo_url }}" alt="{{ $store_name }}" class="size-8 rounded-md object-cover">
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </x-slot>
    </flux:brand>
@endif
