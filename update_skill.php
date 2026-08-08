<?php

declare(strict_types=1);

$root = __DIR__.'/docs/ai-skills/laravel-full-skill';

$replacements = [
    // Full FQCNs first (longest → safest)
    'App\Http\Controllers\Api\Stories\StoryController' => 'App\Http\Controllers\Api\FileController',
    'App\Http\Controllers\Stories\StoryController' => 'App\Http\Controllers\FileController',
    'App\Contracts\Repositories\Stories\StoryRepositoryInterface' => 'App\Contracts\Repositories\Files\FileRepositoryInterface',
    'App\Repositories\Stories\StoryRepository' => 'App\Repositories\Files\FileRepository',
    'App\Contracts\Services\Stories\StoryServiceInterface' => 'App\Contracts\Services\Files\FileServiceInterface',
    'App\Services\Stories\StoryService' => 'App\Services\Files\FileService',
    'App\Http\Requests\Stories\StoryIndexRequest' => 'App\Http\Requests\Files\FileIndexRequest',
    'App\Http\Requests\Stories\StoryStoreRequest' => 'App\Http\Requests\Files\FileStoreRequest',
    'App\Http\Requests\Stories\StoryUpdateRequest' => 'App\Http\Requests\Files\FileUpdateRequest',
    'App\Http\Resources\Stories\StoryCollection' => 'App\Http\Resources\Files\FileCollection',
    'App\Http\Resources\Stories\StoryResource' => 'App\Http\Resources\Files\FileResource',
    'App\Exceptions\Stories\StoryNotFoundException' => 'App\Exceptions\Files\FileNotFoundException',
    'App\Models\Stories\Story' => 'App\Models\Files\File',
    'App\Models\Stories\Category' => 'App\Models\Files\Directory',
    'StoryCollection' => 'FileCollection',
    'StoryResource' => 'FileResource',
    'StoryIndexRequest' => 'FileIndexRequest',
    'StoryStoreRequest' => 'FileStoreRequest',
    'StoryUpdateRequest' => 'FileUpdateRequest',
    'StoryRepositoryInterface' => 'FileRepositoryInterface',
    'StoryServiceInterface' => 'FileServiceInterface',
    'StoryRepository' => 'FileRepository',
    'StoryService' => 'FileService',
    'StoryNotFoundException' => 'FileNotFoundException',
    'StoryAlreadyExistsException' => 'FileAlreadyExistsException',
    'StoryCreatedEvent' => 'FileUploadedEvent',
    'StoryLevel' => 'FileLevel',
    'StoryStatus' => 'FileStatus',
    'StoryFactory' => 'FileFactory',
    'StorySeeder' => 'FileSeeder',
    'StoryCategory' => 'FileDirectory',
    // Words
    'Stories' => 'Files',
    'stories' => 'files',
    'Story' => 'File',
    'story' => 'file',
];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$changed = 0;

foreach ($files as $file) {
    if ($file->getExtension() !== 'md') {
        continue;
    }

    $path = $file->getPathname();
    $contents = file_get_contents($path);
    $original = $contents;

    foreach ($replacements as $from => $to) {
        $contents = str_replace($from, $to, $contents);
    }

    if ($contents !== $original) {
        file_put_contents($path, $contents);
        $changed++;
        echo "updated: {$path}\n";
    }
}

echo "Done. {$changed} files changed.\n";
