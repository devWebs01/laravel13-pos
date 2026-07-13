<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Transformation;

class CloudinaryService
{
    private Cloudinary $client;

    public function __construct()
    {
        $this->client = new Cloudinary(config('cloudinary.cloud_url'));
    }

    public function upload(string $filePath, ?string $folder = 'products'): array
    {
        $options = [];

        if ($folder) {
            $options['folder'] = $folder;
        }

        $options['transformation'] = (new Transformation())
            ->quality('auto')
            ->format('auto');

        $result = $this->client->uploadApi()->upload($filePath, $options);

        return [
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
        ];
    }

    public function delete(string $publicId): void
    {
        $this->client->uploadApi()->destroy($publicId);
    }

    public function getUrl(string $publicId): string
    {
        return $this->client->image($publicId)->toUrl();
    }

    public function isCloudinaryId(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        if (str_starts_with($value, 'http')) {
            return false;
        }

        return !pathinfo($value, PATHINFO_EXTENSION);
    }
}
