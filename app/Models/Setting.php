<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'store_name',
        'store_address',
        'store_phone',
        'store_email',
        'store_logo',
        'receipt_footer',
    ];

    public function getLogoUrlAttribute(): string
    {
        if (! $this->store_logo) {
            return '';
        }

        if (str_starts_with($this->store_logo, 'http')) {
            return $this->store_logo;
        }

        if (file_exists(storage_path('app/public/'.$this->store_logo))) {
            return asset('storage/'.$this->store_logo);
        }

        return '';
    }
}
