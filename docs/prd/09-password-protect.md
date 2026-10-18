# Password Protect

## 2. Password Protected Links

### Database

```php
Schema::table('files', function (Blueprint $table) {
    $table->string('password')->nullable(); // hashed password
    $table->unsignedInteger('download_limit')->nullable(); // max downloads
    $table->unsignedInteger('download_count')->default(0);
});
```

### Service

```php
public function accessProtectedFile(string $code, ?string $password): File
{
    $file = $this->fileRepository->findByCode($code);

    if (!$file) {
        abort(404);
    }

    // Check password
    if ($file->password && !Hash::check($password, $file->password)) {
        abort(403, 'Invalid password');
    }

    // Check download limit
    if ($file->download_limit && $file->download_count >= $file->download_limit) {
        abort(410, 'Download limit reached');
    }

    return $file;
}
```

---


---
[← Back to Main PRD](../PRD.md)
