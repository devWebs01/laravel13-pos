<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Transformation;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    private $client = null;

    public function __construct()
    {
        $url = config('cloudinary.cloud_url');
        if ($url) {
            try {
                $this->client = new Cloudinary($url);
            } catch (\Throwable $e) {
                Log::warning('Cloudinary config failed: '.$e->getMessage());
            }
        }
    }

    public function ready(): bool
    {
        return $this->client !== null;
    }

    public function upload(string $filePath, ?string $folder = 'pos_dw'): ?array
    {
        if (! $this->ready()) {
            return null;
        }

        try {
            $options = ['folder' => $folder, 'transformation' => (new Transformation())->quality('auto')->format('auto')];
            $result = $this->client->uploadApi()->upload($filePath, $options);
            return ['public_id' => $result['public_id'], 'url' => $result['secure_url']];
        } catch (\Throwable $e) {
            Log::error('Cloudinary upload failed: '.$e->getMessage());
            return null;
        }
    }

    public function delete(string $publicId): void
    {
        if (! $this->ready()) return;
        try {
            $this->client->uploadApi()->destroy($publicId);
        } catch (\Throwable $e) {
            Log::warning('Cloudinary delete failed: '.$e->getMessage());
        }
    }

    public function getUrl(string $publicId): ?string
    {
        if (! $this->ready()) return null;
        try {
            return $this->client->image($publicId)->toUrl();
        } catch (\Throwable $e) {
            Log::warning('Cloudinary URL generation failed: '.$e->getMessage());
            return null;
        }
    }

    public function isCloudinaryId(string $value): bool
    {
        if (empty($value) || str_starts_with($value, 'http')) return false;
        return ! pathinfo($value, PATHINFO_EXTENSION);
    }
}
