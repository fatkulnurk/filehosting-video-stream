# Webhooks

## 6. Webhook Notifications

### Database

```php
Schema::create('webhooks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->string('url');
    $table->string('secret')->nullable();
    $table->json('events'); // ['file.uploaded', 'file.downloaded', 'storage.exceeded']
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_triggered_at')->nullable();
    $table->unsignedInteger('failure_count')->default(0);
    $table->timestamps();
});

Schema::create('webhook_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('webhook_id')->constrained()->cascadeOnDelete();
    $table->string('event');
    $table->json('payload');
    $table->integer('response_code')->nullable();
    $table->text('response_body')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();
    $table->boolean('success')->default(false);
    $table->timestamps();
});
```

### Events

| Event | Description | Payload |
|-------|-------------|---------|
| file.uploaded | File berhasil diupload | file_id, name, size |
| file.downloaded | File didownload | file_id, code, ip |
| file.deleted | File dihapus | file_id, name |
| directory.created | Folder dibuat | directory_id, name |
| directory.deleted | Folder dihapus | directory_id, name |
| storage.exceeded | Quota hampir habis | user_id, usage, limit |
| subscription.expired | Subscription expired | user_id, package |
| file.expired | File expired | file_id, code |

---


---
[← Back to Main PRD](../PRD.md)
