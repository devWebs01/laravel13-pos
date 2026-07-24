<?php

use App\Models\Transaction;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.auth.login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'verified', 'can:reports.view'])->group(function () {
    Route::get('/reports/export', function () {
        $from_date = request('from_date', now()->startOfMonth()->format('Y-m-d'));
        $to_date = request('to_date', now()->format('Y-m-d'));
        $payment = request('payment', '');

        $query = Transaction::withCount('items')
            ->whereBetween('created_at', [$from_date.' 00:00:00', $to_date.' 23:59:59']);

        if (! empty($payment)) {
            $query->where('payment_method', $payment);
        }

        $transactions = $query->orderByDesc('id')->get();

        $methodLabels = [
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'debit_card' => 'Kartu Debit',
            'credit_card' => 'Kartu Kredit',
        ];

        $filename = 'laporan_transaksi_'.$from_date.'_s.d_'.$to_date.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($transactions, $methodLabels) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['No', 'Invoice', 'Tanggal', 'Customer', 'Item', 'Total', 'Pembayaran'], ';');

            foreach ($transactions as $index => $transaction) {
                fputcsv($handle, [
                    $index + 1,
                    $transaction->invoice_number,
                    $transaction->created_at->format('d/m/Y H:i'),
                    $transaction->customer ?: '-',
                    $transaction->items_count,
                    (int) $transaction->total_amount,
                    $methodLabels[$transaction->payment_method] ?? $transaction->payment_method,
                ], ';');
            }

            $totalRevenue = (int) $transactions->sum('total_amount');
            fputcsv($handle, ['', '', '', '', 'Grand Total', $totalRevenue, ''], ';');

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    })->name('reports.export');
});
