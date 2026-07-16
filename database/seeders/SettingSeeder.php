<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create([
            'store_name' => 'NW Coffee and White House',
            'store_address' => 'Lorong Gotong Royong, Selamat, Kec. Telanaipura, Kota Jambi, Jambi',
            'store_phone' => '021-1234567',
            'store_email' => 'contact@nw.kopi.com',
            'store_logo' => 'logo/logo-kopi.png',
            'receipt_footer' => 'Terima kasih telah berbelanja di toko kami!',
        ]);

        $target = 'logo/logo-kopi.png';
        if (Storage::disk('public')->exists($target)) {
            return;
        }

        $url = 'https://instagram.fdjb1-1.fna.fbcdn.net/v/t51.82787-19/626580014_17980616198949802_8405617363524082254_n.jpg?stp=dst-jpg_s150x150_tt6&efg=eyJ2ZW5jb2RlX3RhZyI6InByb2ZpbGVfcGljLmRqYW5nby4xMDgwLmMyIn0&_nc_ht=instagram.fdjb1-1.fna.fbcdn.net&_nc_cat=110&_nc_oc=Q6cZ2gHlJMDMfOHaay7oUmwFeipZ6G5BHI4V_G4EpYoWvZ3fMuk6GpNdInvEf3VS6FOypdg&_nc_ohc=a9THfMlnFMcQ7kNvwEpq_Sv&_nc_gid=DG_Xeg7GF4n6gDJs3skBtw&edm=AOQ1c0wBAAAA&ccb=7-5&oh=00_AQAWYseplchZI83LkRIDrsTpr5Nnbqgsk6l1W6KKtGhD-Q&oe=6A5EDDB6&_nc_sid=8b3546';

        try {
            $contents = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 10]]));
            if ($contents !== false) {
                Storage::disk('public')->put('logo/logo-kopi.png', $contents);
                return;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        $fallback = public_path('nwkopilogo.png');
        if (file_exists($fallback)) {
            Storage::disk('public')->put('logo/logo-kopi.png', file_get_contents($fallback));
        }
    }
}
