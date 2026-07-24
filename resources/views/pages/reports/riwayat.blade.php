<?php

use App\Models\Transaction;
use Illuminate\Support\Number;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;
use function Livewire\Volt\computed;
use function Livewire\Volt\state;
use function Livewire\Volt\usesPagination;

name('reports.riwayat');

middleware('auth');
middleware('verified');

usesPagination();

state([
    'from_date' => now()->startOfMonth()->format('Y-m-d'),
    'to_date' => now()->format('Y-m-d'),
    'payment_filter' => '',
    'search' => '',
])->url();

$transactions = computed(function () {
    $query = Transaction::withCount('items')
        ->whereBetween('created_at', [$this->from_date . ' 00:00:00', $this->to_date . ' 23:59:59']);

    if ($this->payment_filter !== '') {
        $query->where('payment_method', $this->payment_filter);
    }

    if ($this->search !== '') {
        $query->where(function ($q) {
            $q->where('invoice_number', 'like', '%' . $this->search . '%')
              ->orWhere('customer', 'like', '%' . $this->search . '%');
        });
    }

    return $query->orderByDesc('id')->paginate(15);
});

$summary = computed(function () {
    $query = Transaction::whereBetween('created_at', [$this->from_date . ' 00:00:00', $this->to_date . ' 23:59:59']);

    if ($this->payment_filter !== '') {
        $query->where('payment_method', $this->payment_filter);
    }

    return [
        'total_transactions' => $query->count(),
        'total_revenue' => (float) $query->sum('total_amount'),
    ];
});

?>

<x-layouts::app :title="__('Transaction History')">
    @volt
        @php
            $methodLabels = [
                'cash' => 'Tunai',
                'transfer' => 'Transfer',
                'debit_card' => 'Kartu Debit',
                'credit_card' => 'Kartu Kredit',
            ];
            $methodColors = [
                'cash' => 'green',
                'transfer' => 'blue',
                'debit_card' => 'purple',
                'credit_card' => 'orange',
            ];
        @endphp
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="/dashboard">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('reports.index') }}">{{ __('Reports') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Transaction History') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Transaction History') }}</flux:heading>
                    <flux:subheading>{{ __('View, filter, and export transaction records.') }}</flux:subheading>
                </div>
            </div>

            {{-- Filter --}}
            <div class="flex flex-wrap items-end gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="w-48">
                    <flux:input wire:model.live="from_date" type="date" :label="__('From')" />
                </div>
                <div class="w-48">
                    <flux:input wire:model.live="to_date" type="date" :label="__('To')" />
                </div>
                <div class="w-48">
                    <flux:select wire:model.live="payment_filter" :label="__('Payment')">
                        <flux:select.option value="">
                            Semua
                        </flux:select.option>
                        @foreach ($methodLabels as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex-1">
                    <flux:input wire:model.live="search" type="search" :label="__('Search')"
                        placeholder="{{ __('Search by invoice or customer...') }}" />
                </div>
                <div class="flex gap-2">
                    <flux:button
                        href="/reports/cetak?from_date={{ $this->from_date }}&to_date={{ $this->to_date }}&payment={{ $this->payment_filter }}"
                        icon="printer" variant="primary" target="_blank">
                        {{ __('Print') }}
                    </flux:button>
                    <flux:button
                        href="{{ route('reports.export', ['from_date' => $this->from_date, 'to_date' => $this->to_date, 'payment' => $this->payment_filter]) }}"
                        icon="arrow-down-tray" variant="filled">
                        {{ __('Export Excel') }}
                    </flux:button>
                </div>
            </div>

            {{-- Summary --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-sm text-zinc-500">{{ __('Total Transactions') }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ Number::format($this->summary['total_transactions']) }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-sm text-zinc-500">{{ __('Total Revenue') }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ Number::currency($this->summary['total_revenue'], 'IDR', 'id') }}</p>
                </div>
            </div>

            {{-- Loading Overlay --}}
            <div wire:loading.flex wire:target="from_date,to_date,payment_filter,search"
                class="fixed inset-0 z-50 items-center justify-center bg-black/20 backdrop-blur-sm">
                <div class="flex items-center gap-2 rounded-lg bg-white px-4 py-3 shadow-lg dark:bg-zinc-800">
                    <flux:icon name="arrow-path" class="size-5 animate-spin text-zinc-500" />
                    <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Loading...') }}</span>
                </div>
            </div>

            {{-- Table --}}
            <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6"
                wire:loading.class="opacity-50" wire:target="from_date,to_date,payment_filter,search">
                <flux:table :paginate="$this->transactions">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Invoice') }}</flux:table.column>
                        <flux:table.column>{{ __('Customer') }}</flux:table.column>
                        <flux:table.column>{{ __('Items') }}</flux:table.column>
                        <flux:table.column>{{ __('Total') }}</flux:table.column>
                        <flux:table.column>{{ __('Payment') }}</flux:table.column>
                        <flux:table.column>{{ __('Date') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->transactions as $transaction)
                            <flux:table.row :key="$transaction->id">
                                <flux:table.cell class="font-medium">{{ $transaction->invoice_number }}</flux:table.cell>
                                <flux:table.cell>{{ $transaction->customer ?: '-' }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" inset="top bottom">
                                        {{ $transaction->items_count }} {{ __('item') }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell variant="strong">
                                    {{ Number::currency($transaction->total_amount, 'IDR', 'id') }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$methodColors[$transaction->payment_method] ?? 'gray'" inset="top bottom">
                                        {{ $methodLabels[$transaction->payment_method] ?? $transaction->payment_method }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endvolt
</x-layouts::app>
