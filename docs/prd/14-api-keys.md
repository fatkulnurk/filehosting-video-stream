# Api Keys

## 7. API Keys

### Database

```php
Schema::create('api_keys', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->string('name'); // "My App", "CI/CD Pipeline"
    $table->string('key', 64)->unique();
    $table->text('permissions')->nullable(); // json array
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index('key');
});
```

### Service

```php
public function createApiKey(string $userId, string $name, ?array $permissions = null): ApiKey
{
    return ApiKey:***
        'user_id' => $userId,
        'name' => $name,
        'key' => 'sk_' . bin2hex(random_bytes(32)),
        'permissions' => $permissions ?? ['file.read', 'file.write'],
        'expires_at' => now()->addYear(),
    ]);
}

public function validateApiKey(string $key): ?User
{
    $apiKey = ApiKey:*** $key)
        ->where('is_active', true)
        ->where('expires_at', '>', now())
        ->first();

    if (!$apiKey) {
        return null;
    }

    $apiKey->update(['last_used_at' => now()]);

    return $apiKey->user;
}
```

---


---
[← Back to Main PRD](../PRD.md)
