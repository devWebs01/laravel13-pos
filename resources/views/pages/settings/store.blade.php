<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\WithFileUploads;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;
use function Livewire\Volt\state;
use function Livewire\Volt\mount;
use function Livewire\Volt\uses;

uses(WithFileUploads::class);

name('settings.store');
middleware('auth');
middleware('verified');

state([
    'store_name' => '',
    'store_address' => '',
    'store_phone' => '',
    'store_email' => '',
    'store_logo' => null,
    'receipt_footer' => '',
]);

mount(function () {
    $setting = Setting::first();
    if ($setting) {
        $this->store_name = $setting->store_name;
        $this->store_address = $setting->store_address;
        $this->store_phone = $setting->store_phone;
        $this->store_email = $setting->store_email;
        $this->receipt_footer = $setting->receipt_footer;
    }
});

$save = function () {
    $this->validate([
        'store_name' => 'required|string|max:255',
        'store_address' => 'nullable|string',
        'store_phone' => 'nullable|string|max:50',
        'store_email' => 'nullable|email|max:255',
        'store_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'receipt_footer' => 'nullable|string',
    ]);

    $setting = Setting::first();
    if (!$setting) {
        $setting = new Setting();
    }

    $setting->store_name = $this->store_name;
    $setting->store_address = $this->store_address;
    $setting->store_phone = $this->store_phone;
    $setting->store_email = $this->store_email;
    $setting->receipt_footer = $this->receipt_footer;

    if ($this->store_logo) {
        $setting->store_logo = $this->store_logo->store('logo', 'public');
    }

    $setting->save();

    Flux::toast(variant: 'success', text: __('Settings updated.'));
};

?>

<x-layouts::app :title="__('Store Settings')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="/dashboard">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Store Settings') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Store Settings') }}</flux:heading>
                <flux:subheading>{{ __('Manage your store profile and receipt details.') }}</flux:subheading>
            </div>
        </div>

        <div class="w-full rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="store_name" :label="__('Store Name')" required />

                <flux:textarea wire:model="store_address" :label="__('Store Address')" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="store_phone" :label="__('Store Phone')" />
                    <flux:input wire:model="store_email" type="email" :label="__('Store Email')" />
                </div>

                <div>
                    <flux:label>{{ __('Store Logo') }}</flux:label>
                    <input type="file" wire:model="store_logo" accept="image/jpeg,image/png,image/jpg,image/webp"
                        class="mt-1 block w-full text-sm text-zinc-500 file:me-3 file:rounded-lg file:border-0 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white file:shadow-xs file:bg-zinc-800 hover:file:bg-zinc-700 dark:file:bg-white/10 dark:hover:file:bg-white/20 dark:file:text-zinc-200">
                    @error('store_logo')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                    @php $settingLogo = \App\Models\Setting::first()?->logo_url; @endphp
                    @if ($settingLogo)
                        <div class="mt-2 flex items-center gap-3">
                            <img src="{{ $settingLogo }}" alt="Current logo" class="h-10 w-10 rounded-lg border object-cover">
                            <span class="text-xs text-zinc-400">{{ __('Current logo') }}</span>
                        </div>
                    @endif
                </div>

                <flux:textarea wire:model="receipt_footer" :label="__('Receipt Footer')"
                    :placeholder="__('Example: Thank you for shopping!')" />

                <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button variant="primary" type="submit" icon="check">
                        {{ __('Update Settings') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
    @endvolt
</x-layouts::app>