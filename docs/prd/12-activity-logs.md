# Activity Logs

## 5. Activity Logs

### Database

```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('action'); // login, upload, delete, download, etc
    $table->string('subject_type')->nullable();
    $table->uuid('subject_id')->nullable();
    $table->json('properties')->nullable(); // additional data
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
    $table->index(['user_id', 'action']);
    $table->index(['subject_type', 'subject_id']);
});
```

### Contoh Data

| id | user_id | action | subject_type | subject_id | properties |
|----|---------|--------|--------------|------------|------------|
| uuid | user-1 | login | - | - | {"method": "email"} |
| uuid | user-1 | file.upload | File | file-1 | {"name": "video.mp4", "size": 104857600} |
| uuid | user-1 | file.download | File | file-1 | {"code": "abc123"} |
| uuid | user-1 | directory.create | Directory | dir-1 | {"name": "Videos"} |

---


---
[← Back to Main PRD](../PRD.md)
