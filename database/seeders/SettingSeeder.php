<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::create([
            'store_name' => 'NW Coffee and White House',
            'store_address' => 'Lorong Gotong Royong, Selamat, Kec. Telanaipura, Kota Jambi, Jambi',
            'store_phone' => '021-1234567',
            'store_email' => 'contact@nw.kopi.com',
            'receipt_footer' => 'Terima kasih telah berbelanja di toko kami!',
        ]);
    }
}
