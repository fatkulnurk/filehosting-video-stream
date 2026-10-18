# Batch Operations

## 8. Batch Operations

### Upload

```php
// Multiple file upload
public function storeBatch(Request $request, FileServiceInterface $fileService)
{
    $files = $request->file('files');
    $results = [];

    foreach ($files as $file) {
        try {
            $fileModel = $fileService->store(
                $file,
                $request->directory_id,
                auth()->id()
            );
            $results[] = ['success' => true, 'file' => $fileModel];
        } catch (\Exception $e) {
            $results[] = ['success' => false, 'error' => $e->getMessage()];
        }
    }

    return response()->json($results);
}
```

### Download (Zip)

```php
public function downloadBatch(Request $request)
{
    $fileIds = $request->input('file_ids');
    $files = File::whereIn('id', $fileIds)->get();

    $zip = new ZipArchive();
    $zip->open(storage_path('app/batch-' . time() . '.zip'), ZipArchive::CREATE);

    foreach ($files as $file) {
        $media = $file->getFirstMedia();
        if ($media) {
            $zip->addFile($media->getPath(), $file->name . '.' . $file->extension);
        }
    }

    $zip->close();

    return response()->download(storage_path('app/batch-' . time() . '.zip'))
        ->deleteFileAfterSend();
}
```

---


---
[← Back to Main PRD](../PRD.md)
