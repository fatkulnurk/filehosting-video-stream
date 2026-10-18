# 02 Database

## 9. Desain Database

### 9.1 Table Definitions

#### users

| Field | Type | Constraint | Fungsi |
|-------|------|------------|--------|
| id | uuid | PK, UUID v7 | ID unik user |
| name | varchar(255) | NOT NULL | Nama lengkap |
| email | varchar(255) | UNIQUE, NOT NULL | Email login |
| role | varchar(20) | DEFAULT 'member' | Role user (member/admin) |
| email_verified_at | timestamp | NULLABLE | Waktu verifikasi |
| password | varchar(255) | NOT NULL | Hashed password |
| two_factor_secret | text | NULLABLE | Secret TOTP 2FA |
| two_factor_recovery_codes | text | NULLABLE | Recovery codes |
| remember_token | varchar(100) | NULLABLE | Token remember me |
| created_at | timestamp | NOT NULL | Waktu dibuat |
| updated_at | timestamp | NOT NULL | Waktu update |

**Contoh Data:**

| id | name | email | role | created_at |
|----|------|-------|------|------------|
| 550e8400-e29b-41d4-a716-446655440001 | Budi Santoso | budi@example.com | member | 2026-08-01 |
| 550e8400-e29b-41d4-a716-446655440002 | Admin FHV | admin@fhv.stream | admin | 2026-08-01 |

**Enum Values:**

| Field | Values | Keterangan |
|-------|--------|------------|
| role | member, admin | Default: member |

#### profiles

| Field | Type | Constraint | Fungsi |
|-------|------|------------|--------|
| id | uuid | PK, UUID v7 | ID unik profile |
| user_id | uuid | FK users.id, UNIQUE, CASCADE | Relasi 1-to-1 ke user |
| phone | varchar(20) | NULLABLE | Nomor telepon |
| avatar | varchar(255) | NULLABLE | Path avatar |
| bio | text | NULLABLE | Deskripsi singkat |
| storage_limit | bigint | NULLABLE | Batas storage personal (bytes), NULL=unlimited |
| created_at | timestamp | NOT NULL | Waktu dibuat |
| updated_at | timestamp | NOT NULL | Waktu update |

**Contoh Data:**

| id | user_id | phone | storage_limit |
|----|---------|-------|---------------|
| 550e8400-e29b-41d4-a716-446655440011 | 550e8400-e29b-41d4-a716-446655440001 | +628123456789 | 10737418240 |

#### directories

| Field | Type | Constraint | Fungsi |
|-------|------|------------|--------|
| id | uuid | PK, UUID v7 | ID unik folder (digunakan untuk navigasi, bukan slug) |
| user_id | uuid | FK users.id, CASCADE | Pemilik folder |
| parent_id | uuid | FK directories.id, NULL ON DELETE | Parent folder |
| name | varchar(255) | NOT NULL | Nama folder |
| path | text | NOT NULL | Path lengkap (/a/b/c) |
| created_at | timestamp | NOT NULL | Waktu dibuat |
| updated_at | timestamp | NOT NULL | Waktu update |

**Catatan:**
- Navigasi folder menggunakan `id`, bukan `slug`
- Route: `/directories/{id}` bukan `/directories/{slug}`
- Path di-generate otomatis saat create

**Contoh Data:**

| id | user_id | parent_id | name | path |
|----|---------|-----------|------|------|
| 550e8400-e29b-41d4-a716-446655440021 | 550e8400-...-0001 | NULL | Videos | /videos |
| 550e8400-e29b-41d4-a716-446655440022 | 550e8400-...-0001 | 550e8400-...-0021 | Tutorials | /videos/tutorials |

#### files

Tabel ini hanya menyimpan metadata APLIKASI. Metadata file fisik (path, disk, mime_type, size, extension) disimpan di tabel `media` (Spatie Media Library) via relasi 1-to-1.

| Field | Type | Constraint | Fungsi |
|-------|------|------------|--------|
| id | uuid | PK, UUID v7 | ID unik file (digunakan untuk navigasi) |
| user_id | uuid | FK users.id, CASCADE | Pemilik file |
| directory_id | uuid | FK directories.id, NULL ON DELETE | Folder induk |
| name | varchar(255) | NOT NULL | Nama tampil (display name) |
| views | bigint | DEFAULT 0 | Jumlah view |
| downloads | bigint | DEFAULT 0 | Jumlah download |
| expires_at | timestamp | NULLABLE | Waktu link expired |
| is_public | boolean | DEFAULT true | Status public/private |
| created_at | timestamp | NOT NULL | Waktu upload |
| updated_at | timestamp | NOT NULL | Waktu update |

**Catatan:**
- Navigasi file menggunakan `id`, bukan `slug`
- Route: `/files/{id}` bukan `/files/{slug}`
- Public URL: `/f/{id}` (UUID v7 sebagai public code)

**Kolom yang TIDAK perlu (sudah ada di Spatie media):**

| Kolom | Alasan tidak perlu |
|-------|-------------------|
| original_name | Sudah ada di `media.file_name` |
| path | Sudah ada di `media.path` |
| disk | Sudah ada di `media.disk` |
| mime_type | Sudah ada di `media.mime_type` |
| extension | Bisa di derive dari `media.file_name` |
| size | Sudah ada di `media.size` |

**Contoh Data:**

| id | user_id | directory_id | name | views | downloads | is_public |
|----|---------|--------------|------|-------|-----------|-----------|
| 550e8400-...-0031 | 550e8400-...-0001 | 550e8400-...-0022 | Laravel Tutorial | 1234 | 567 | true |
| 550e8400-...-0032 | 550e8400-...-0001 | NULL | Profile Photo | 890 | 0 | false |

**Enum Values:**

| Field | Values | Keterangan |
|-------|--------|------------|
| is_public | true, false | Default: true |

#### storage_servers

**Catatan Penting: Storage server TIDAK BISA dihapus, hanya bisa di-deactivate (is_active=false). File harus di-migrate ke server lain sebelum deaktivasi.**

| Field | Type | Constraint | Fungsi |
|-------|------|------------|--------|
| id | uuid | PK, UUID v7 | ID unik storage |
| user_id | uuid | FK users.id, CASCADE | Pemilik config |
| name | varchar(255) | UNIQUE, NOT NULL | Nama disk Laravel |
| driver | varchar(50) | NOT NULL | Tipe driver |
| config | json | NOT NULL | Konfigurasi driver |
| is_active | boolean | DEFAULT true | Status aktif |
| is_primary | boolean | DEFAULT false | Apakah primary storage |
| backup_storage_server_id | uuid | NULLABLE, FK storage_servers.id | Backup storage |
| auto_backup | boolean | DEFAULT false | Auto backup ke backup storage |
| storage_limit | bigint | NULLABLE | Batas storage bytes |
| storage_used | bigint | DEFAULT 0 | Storage terpakai |
| created_at | timestamp | NOT NULL | Waktu dibuat |
| updated_at | timestamp | NOT NULL | Waktu update |

**Contoh Data:**

| id | user_id | name | driver | is_active | is_primary |
|----|---------|------|--------|-----------|------------|
| 550e8400-...-0041 | 550e8400-...-0001 | local-storage | local | true | true |
| 550e8400-...-0042 | 550e8400-...-0001 | s3-backup | s3 | true | false |

**Enum Values:**

| Field | Values | Keterangan |
|-------|--------|------------|
| driver | local, s3, dropbox, gcs, azure, ftp, sftp, rackspace, wasabi, b2 | Storage driver |
| is_active | true, false | Default: true |
| is_primary | true, false | Default: false |

**Flow Deaktivasi Storage Server:**

```
1. Admin pilih storage server ingin dinonaktifkan
2. System cek semua file di storage tersebut
3. Jika ada file:
   a. System tampilkan list file
   b. Admin pilih target storage untuk migrate
   c. System jalankan migrasi (via Spatie move/copy)
   d. Tunggu migrasi selesai
4. Jika semua file sudah di-migrate:
   a. Set is_active = false
   b. Log deaktivasi
5. Jika tidak ada file:
   a. Set is_active = false
   b. Log deaktivasi
```

#### file_views

| Field | Type | Constraint | Fungsi |
|-------|------|------------|--------|
| id | uuid | PK, UUID v7 | ID unik log |
| file_id | uuid | FK files.id, CASCADE | File di-view |
| ip | varchar(45) | NULLABLE | IP address |
| user_agent | text | NULLABLE | Browser/app |
| referer | text | NULLABLE | Halaman asal |
| created_at | timestamp | NOT NULL | Waktu view |

**Contoh Data:**

| id | file_id | ip | user_agent | created_at |
|----|---------|-----|------------|------------|
| 550e8400-...-0051 | 550e8400-...-0031 | 192.168.1.1 | Mozilla/5.0 | 2026-08-04 |

#### file_downloads

| Field | Type | Constraint | Fungsi |
|-------|------|------------|--------|
| id | uuid | PK, UUID v7 | ID unik log |
| file_id | uuid | FK files.id, CASCADE | File didownload |
| ip | varchar(45) | NULLABLE | IP address |
| user_agent | text | NULLABLE | Browser/app |
| created_at | timestamp | NOT NULL | Waktu download |

**Contoh Data:**

| id | file_id | ip | user_agent | created_at |
|----|---------|-----|------------|------------|
| 550e8400-...-0061 | 550e8400-...-0031 | 192.168.1.1 | Mozilla/5.0 | 2026-08-04 |

### 9.2 Laravel Migration Code

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('role', 20)->default('member');
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->text('two_factor_secret')->nullable();
    $table->text('two_factor_recovery_codes')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->index('role');
});

Schema::create('profiles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete()->unique();
    $table->string('phone')->nullable();
    $table->string('avatar')->nullable();
    $table->text('bio')->nullable();
    $table->unsignedBigInteger('storage_limit')->nullable();
    $table->timestamps();
});

Schema::create('directories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('parent_id')->nullable()->constrained('directories')->nullOnDelete();
    $table->string('name');
    $table->text('path');
    $table->timestamps();
    $table->index(['user_id', 'parent_id']);
});

Schema::create('files', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('directory_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->unsignedBigInteger('views')->default(0);
    $table->unsignedBigInteger('downloads')->default(0);
    $table->timestamp('expires_at')->nullable();
    $table->boolean('is_public')->default(true);
    $table->timestamps();
    $table->index(['user_id', 'directory_id']);
});

Schema::create('storage_servers', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->string('name')->unique();
    $table->string('driver');
    $table->json('config');
    $table->boolean('is_active')->default(true);
    $table->boolean('is_primary')->default(false);
    $table->foreignUuid('backup_storage_server_id')->nullable()->constrained('storage_servers');
    $table->boolean('auto_backup')->default(false);
    $table->unsignedBigInteger('storage_limit')->nullable();
    $table->unsignedBigInteger('storage_used')->default(0);
    $table->timestamps();
    $table->index('is_active');
    $table->index('is_primary');
});

Schema::create('file_views', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
    $table->string('ip', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->text('referer')->nullable();
    $table->timestamps();
    $table->index('file_id');
});

Schema::create('file_downloads', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
    $table->string('ip', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
    $table->index('file_id');
});
```

---

## 10. Contoh Data & Enum Values

### 10.1 users

**Contoh Data:**

| id | name | email | role | email_verified_at | created_at |
|----|------|-------|------|-------------------|------------|
| 550e8400-e29b-41d4-a716-446655440001 | Budi Santoso | budi@example.com | member | 2026-08-01 | 2026-08-01 |
| 550e8400-e29b-41d4-a716-446655440002 | Admin FHV | admin@fhv.stream | admin | 2026-08-01 | 2026-08-01 |

**Enum Values:**

| Field | Values | Keterangan |
|-------|--------|------------|
| role | member, admin | Default: member |

### 10.2 profiles

**Contoh Data:**

| id | user_id | phone | avatar | bio | storage_limit |
|----|---------|-------|--------|-----|---------------|
| 550e8400-e29b-41d4-a716-446655440011 | 550e8400-e29b-41d4-a716-446655440001 | +628123456789 | avatars/budi.jpg | Content Creator | 10737418240 |

### 10.3 directories

**Contoh Data:**

| id | user_id | parent_id | name | path |
|----|---------|-----------|------|------|
| 550e8400-e29b-41d4-a716-446655440021 | 550e8400-e29b-41d4-a716-446655440001 | NULL | Videos | /videos |
| 550e8400-e29b-41d4-a716-446655440022 | 550e8400-e29b-41d4-a716-446655440001 | 550e8400-e29b-41d4-a716-446655440021 | Tutorials | /videos/tutorials |

### 10.4 files

**Contoh Data:**

| id | user_id | directory_id | name | status | views | downloads | is_public |
|----|---------|--------------|------|--------|-------|-----------|-----------|
| 550e8400-e29b-41d4-a716-446655440031 | 550e8400-e29b-41d4-a716-446655440001 | 550e8400-e29b-41d4-a716-446655440022 | Laravel Tutorial | active | 1234 | 567 | true |
| 550e8400-e29b-41d4-a716-446655440032 | 550e8400-e29b-41d4-a716-446655440001 | NULL | Profile Photo | active | 890 | 0 | false |

**Enum Values:**

| Field | Values | Keterangan |
|-------|--------|------------|
| status | active, archived, deleted | Default: active |
| is_public | true, false | Default: true |

### 10.5 storage_servers

**Contoh Data:**

| id | user_id | name | driver | is_active | is_primary | auto_backup | storage_used |
|----|---------|------|--------|-----------|------------|-------------|--------------|
| 550e8400-e29b-41d4-a716-446655440041 | 550e8400-e29b-41d4-a716-446655440001 | local-storage | local | true | true | false | 1073741824 |
| 550e8400-e29b-41d4-a716-446655440042 | 550e8400-e29b-41d4-a716-446655440001 | s3-backup | s3 | true | false | true | 0 |

**Enum Values:**

| Field | Values | Keterangan |
|-------|--------|------------|
| driver | local, s3, dropbox, gcs, azure, ftp, sftp, rackspace, wasabi, b2 | Storage driver |
| is_active | true, false | Default: true |
| is_primary | true, false | Default: false |
| auto_backup | true, false | Default: false |

### 10.6 file_views

**Contoh Data:**

| id | file_id | ip | user_agent | referer | created_at |
|----|---------|-----|------------|---------|------------|
| 550e8400-e29b-41d4-a716-446655440051 | 550e8400-e29b-41d4-a716-446655440031 | 192.168.1.1 | Mozilla/5.0 | https://example.com | 2026-08-04 |

### 10.7 file_downloads

**Contoh Data:**

| id | file_id | ip | user_agent | created_at |
|----|---------|-----|------------|------------|
| 550e8400-e29b-41d4-a716-446655440061 | 550e8400-e29b-41d4-a716-446655440031 | 192.168.1.1 | Mozilla/5.0 | 2026-08-04 |

---

[← Back to Main PRD](../PRD.md)
