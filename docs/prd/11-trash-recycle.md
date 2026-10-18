# Trash Recycle

## 4. Trash / Recycle Bin

### Database

```php
Schema::table('files', function (Blueprint $table) {
    $table->softDeletes();
    $table->timestamp('deleted_at')->nullable();
});

Schema::table('directories', function (Blueprint $table) {
    $table->softDeletes();
    $table->timestamp('deleted_at')->nullable();
});
```

### Flow

1. User delete file
2. File soft deleted (deleted_at set)
3. File hidden from normal view
4. User can view trash
5. User can restore or permanent delete
6. After 30 days, auto permanent delete (schedule)

---


---
[← Back to Main PRD](../PRD.md)
