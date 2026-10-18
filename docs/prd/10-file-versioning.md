# File Versioning

## 3. File Versioning

### Database

```php
Schema::create('file_versions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('version'); // 1, 2, 3...
    $table->string('name');
    $table->text('path');
    $table->string('disk');
    $table->unsignedBigInteger('size');
    $table->string('checksum')->nullable();
    $table->text('changelog')->nullable();
    $table->foreignUuid('uploaded_by')->constrained('users');
    $table->timestamps();
    $table->unique(['file_id', 'version']);
});
```

### Flow

1. User upload new version
2. System create file_version record (version + 1)
3. System copy old media to version collection
4. System update file's main media
5. User can restore old version

---


---
[← Back to Main PRD](../PRD.md)
