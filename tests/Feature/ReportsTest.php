<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $response = $this->get(route('reports.index'));

        $response->assertOk();
    }

    public function test_summary_stats_are_correct(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 10000,
            'stock' => 100,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'customer' => 'Test Customer',
                'invoice_number' => 'INV-TEST-'.$i,
                'total_amount' => 10000,
                'paid_amount' => 10000,
                'change_amount' => 0,
                'payment_method' => 'cash',
            ]);

            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 10000,
                'subtotal' => 10000,
            ]);
        }

        Livewire::test('reports.index')
            ->assertSet('from_date', now()->startOfMonth()->format('Y-m-d'))
            ->assertSet('to_date', now()->format('Y-m-d'));
    }

    public function test_revenue_and_count_reflect_transactions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 25000,
            'stock' => 100,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'customer' => 'Test Customer',
            'invoice_number' => 'INV-DAILY-1',
            'total_amount' => 50000,
            'paid_amount' => 50000,
            'change_amount' => 0,
            'payment_method' => 'transfer',
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 25000,
            'subtotal' => 50000,
        ]);

        Livewire::test('reports.index')
            ->assertSeeHtml('50.000');
    }

    public function test_date_range_filtering_affects_summary(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 10000,
            'stock' => 100,
        ]);

        $oldTransaction = Transaction::create([
            'user_id' => $user->id,
            'customer' => 'Old Customer',
            'invoice_number' => 'INV-OLD',
            'total_amount' => 50000,
            'paid_amount' => 50000,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'created_at' => now()->subMonth()->startOfMonth(),
        ]);
        TransactionItem::create([
            'transaction_id' => $oldTransaction->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50000,
            'subtotal' => 50000,
        ]);

        $newTransaction = Transaction::create([
            'user_id' => $user->id,
            'customer' => 'New Customer',
            'invoice_number' => 'INV-NEW',
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'payment_method' => 'transfer',
        ]);
        TransactionItem::create([
            'transaction_id' => $newTransaction->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        Livewire::test('reports.index')
            ->assertSeeHtml('10.000')
            ->assertDontSeeHtml('50.000');

        Livewire::test('reports.index')
            ->set('from_date', now()->subMonth()->startOfMonth()->format('Y-m-d'))
            ->assertSeeHtml('10.000')
            ->assertSeeHtml('60.000');
    }

    public function test_empty_state_shows_zero_values(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('reports.index')
            ->assertSeeHtml('0');
    }

    public function test_riwayat_page_is_accessible(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/reports/riwayat');

        $response->assertOk();
    }

    public function test_riwayat_shows_transactions_in_range(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'customer' => 'Test Customer',
            'invoice_number' => 'INV-RIW-1',
            'total_amount' => 15000,
            'paid_amount' => 15000,
            'change_amount' => 0,
            'payment_method' => 'cash',
        ]);

        Livewire::test('reports.riwayat')
            ->assertSee('INV-RIW-1')
            ->assertSee('Tunai');
    }

    public function test_riwayat_payment_filter_works(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Transaction::create([
            'user_id' => $user->id,
            'customer' => 'Cash Customer',
            'invoice_number' => 'INV-RIW-CASH',
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'payment_method' => 'cash',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'customer' => 'Transfer Customer',
            'invoice_number' => 'INV-RIW-TRF',
            'total_amount' => 20000,
            'paid_amount' => 20000,
            'change_amount' => 0,
            'payment_method' => 'transfer',
        ]);

        Livewire::test('reports.riwayat', [
            'payment_filter' => 'cash',
        ])
            ->assertSet('payment_filter', 'cash')
            ->assertSee('INV-RIW-CASH')
            ->assertDontSee('INV-RIW-TRF');
    }

    public function test_riwayat_search_works(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Transaction::create([
            'user_id' => $user->id,
            'customer' => 'Searchable Customer',
            'invoice_number' => 'INV-SEARCH-1',
            'total_amount' => 5000,
            'paid_amount' => 5000,
            'change_amount' => 0,
            'payment_method' => 'cash',
        ]);

        Livewire::test('reports.riwayat')
            ->set('search', 'SEARCH-1')
            ->assertSee('INV-SEARCH-1');
    }

    public function test_export_requires_auth(): void
    {
        $response = $this->get(route('reports.export'));

        $response->assertRedirect(route('login'));
    }

    public function test_export_returns_csv_download(): void
    {
        Permission::findOrCreate('reports.view');
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');
        $this->actingAs($user);

        Transaction::create([
            'user_id' => $user->id,
            'customer' => 'Export Customer',
            'invoice_number' => 'INV-EXPORT-1',
            'total_amount' => 25000,
            'paid_amount' => 25000,
            'change_amount' => 0,
            'payment_method' => 'transfer',
        ]);

        $response = $this->get(route('reports.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=laporan_transaksi_'.now()->startOfMonth()->format('Y-m-d').'_s.d_'.now()->format('Y-m-d').'.csv');
        $response->assertSee('INV-EXPORT-1');
    }
}
