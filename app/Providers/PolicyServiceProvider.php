<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Files\Directory;
use App\Models\Files\File;
use App\Models\Storage\StorageServer;
use App\Policies\Directories\DirectoryPolicy;
use App\Policies\Files\FilePolicy;
use App\Policies\Storage\StorageServerPolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;

class PolicyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(Gate $gate): void
    {
        $gate->policy(File::class, FilePolicy::class);
        $gate->policy(Directory::class, DirectoryPolicy::class);
        $gate->policy(StorageServer::class, StorageServerPolicy::class);
    }
}
