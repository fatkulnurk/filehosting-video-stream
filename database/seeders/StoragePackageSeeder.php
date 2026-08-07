<?php

namespace Database\Seeders;

use App\Models\StoragePackage;
use Illuminate\Database\Seeder;

class StoragePackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Paket gratis untuk mulai hosting file.',
                'storage_limit' => 1073741824,
                'bandwidth_limit' => null,
                'file_limit' => null,
                'max_file_size' => 104857600,
                'features' => [],
                'price_monthly' => 0,
                'price_yearly' => 0,
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Paket untuk content creator pemula.',
                'storage_limit' => 10737418240,
                'bandwidth_limit' => null,
                'file_limit' => null,
                'max_file_size' => 524288000,
                'features' => ['custom_thumbnail'],
                'price_monthly' => 9.99,
                'price_yearly' => 99.90,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Paket lengkap untuk professional creator.',
                'storage_limit' => 53687091200,
                'bandwidth_limit' => null,
                'file_limit' => null,
                'max_file_size' => 1073741824,
                'features' => ['custom_thumbnail', 'password_protect', 'api_access', 'download_limit'],
                'price_monthly' => 29.99,
                'price_yearly' => 299.90,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Paket untuk bisnis dan enterprise.',
                'storage_limit' => 107374182400,
                'bandwidth_limit' => null,
                'file_limit' => null,
                'max_file_size' => 2147483648,
                'features' => ['custom_thumbnail', 'password_protect', 'api_access', 'download_limit', 'webhook', 'priority_support', 'file_versioning', 'batch_upload'],
                'price_monthly' => 99.99,
                'price_yearly' => 999.90,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($packages as $package) {
            StoragePackage::updateOrCreate(['slug' => $package['slug']], $package);
        }
    }
}
