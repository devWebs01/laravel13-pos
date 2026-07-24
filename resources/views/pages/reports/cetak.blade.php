<?php

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

name('reports.cetak');

middleware('auth');
middleware('verified');

state([
    'from_date' => now()->startOfMonth()->format('Y-m-d'),
    'to_date' => now()->format('Y-m-d'),
    'payment' => '',
])->url();

state([
    'setting' => null,
    'transactions' => collect(),
    'totalTransactions' => 0,
    'totalRevenue' => 0.0,
]);

mount(function () {
    $this->setting = Setting::first();

    $query = Transaction::withCount('items')
        ->whereBetween('created_at', [$this->from_date . ' 00:00:00', $this->to_date . ' 23:59:59']);

    if (! empty($this->payment)) {
        $query->where('payment_method', $this->payment);
    }

    $this->transactions = $query->orderByDesc('id')->get();
    $this->totalTransactions = $this->transactions->count();
    $this->totalRevenue = (float) $this->transactions->sum('total_amount');
});

?>

@volt
    @php
        $methodLabels = [
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'debit_card' => 'Kartu Debit',
            'credit_card' => 'Kartu Kredit',
        ];
    @endphp

    <div>
        <style>
            body {
                font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
                font-size: 12px;
                line-height: 1.5;
                margin: 0;
                padding: 0;
                color: #000;
                background: #fff;
            }
            .header {
                text-align: center;
                padding: 20px 40px 10px;
                border-bottom: 2px solid #000;
            }
            .header img {
                max-height: 60px;
                margin-bottom: 8px;
            }
            .header .store-name {
                font-size: 18px;
                font-weight: bold;
            }
            .header .store-detail {
                font-size: 11px;
                color: #555;
            }
            .title-section {
                text-align: center;
                padding: 15px 0;
            }
            .title-section h1 {
                font-size: 16px;
                font-weight: bold;
                margin: 0 0 5px;
                text-transform: uppercase;
            }
            .title-section .periode {
                font-size: 11px;
                color: #555;
            }
            .info-section {
                display: flex;
                justify-content: space-between;
                padding: 10px 40px;
                font-size: 11px;
            }
            .info-section .left, .info-section .right {
                flex: 1;
            }
            .info-section .right {
                text-align: right;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
                font-size: 11px;
            }
            table thead th {
                background: #f0f0f0;
                border: 1px solid #ccc;
                padding: 6px 8px;
                text-align: left;
                font-weight: 600;
                font-size: 11px;
            }
            table thead th.right {
                text-align: right;
            }
            table tbody td {
                border: 1px solid #ddd;
                padding: 5px 8px;
            }
            table tbody td.right {
                text-align: right;
            }
            table tbody tr:nth-child(even) {
                background: #fafafa;
            }
            table tfoot td {
                border: 1px solid #ccc;
                padding: 6px 8px;
                font-weight: bold;
                background: #f7f7f7;
            }
            table tfoot td.right {
                text-align: right;
            }
            .footer {
                text-align: center;
                padding: 20px 40px;
                font-size: 11px;
                color: #555;
            }
            .no-print {
                text-align: center;
                padding: 10px;
                background: #f0f0f0;
            }
            .no-print button {
                padding: 8px 20px;
                cursor: pointer;
                background: #18181b;
                color: white;
                border: none;
                border-radius: 4px;
                font-weight: bold;
            }
            @media print {
                @page {
                    size: A4 portrait;
                    margin: 15mm;
                }
                .no-print {
                    display: none !important;
                }
                body {
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                thead th {
                    background: #f0f0f0 !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
            }
        </style>

        <div class="no-print">
            <button onclick="window.print()">Cetak Laporan</button>
            <button onclick="window.close()" style="margin-left:8px;padding:8px 20px;cursor:pointer;background:#e4e4e7;color:#18181b;border:none;border-radius:4px;font-weight:bold;">Tutup</button>
        </div>

        <div class="header">
            @if($setting && $setting->logo_url)
                <img src="{{ $setting->logo_url }}" alt="Logo">
            @endif
            <div class="store-name">{{ $setting?->store_name ?? config('app.name') }}</div>
            @if($setting && $setting->store_address)
                <div class="store-detail">{{ $setting->store_address }}</div>
            @endif
            @if($setting && $setting->store_phone)
                <div class="store-detail">Telp: {{ $setting->store_phone }}</div>
            @endif
        </div>

        <div class="title-section">
            <h1>Laporan Transaksi</h1>
            <div class="periode">
                Periode: {{ Carbon::parse($from_date)->format('d M Y') }} s/d {{ Carbon::parse($to_date)->format('d M Y') }}
                @if(!empty($payment))
                    &nbsp;|&nbsp; Metode: {{ $methodLabels[$payment] ?? $payment }}
                @endif
            </div>
        </div>

        <div class="info-section">
            <div class="left">
                Dicetak: {{ now()->format('d/m/Y H:i') }}
            </div>
            <div class="right">
                Total Transaksi: {{ Number::format($totalTransactions) }}
                &nbsp;|&nbsp; Total Pendapatan: {{ Number::currency($totalRevenue, 'IDR', 'id') }}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:40px;text-align:center;">No</th>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th style="text-align:right;">Item</th>
                    <th style="text-align:right;">Total</th>
                    <th>Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $transaction)
                    <tr>
                        <td style="text-align:center;">{{ $loop->iteration }}</td>
                        <td>{{ $transaction->invoice_number }}</td>
                        <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $transaction->customer ?: '-' }}</td>
                        <td class="right">{{ $transaction->items_count }}</td>
                        <td class="right">{{ Number::currency($transaction->total_amount, 'IDR', 'id') }}</td>
                        <td>{{ $methodLabels[$transaction->payment_method] ?? $transaction->payment_method }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:20px;color:#888;">
                            Tidak ada transaksi pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;">Grand Total</td>
                    <td class="right">{{ Number::currency($totalRevenue, 'IDR', 'id') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>Terima Kasih</p>
        </div>

        <script>
            window.onload = function () {
                window.print();
            };
        </script>
    </div>
@endvolt
