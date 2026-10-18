# 01 Requirements

## 6. Kebutuhan Fungsional

### 6.1 Modul Authentication

| ID | Fitur | Deskripsi | Acceptance Criteria |
|----|-------|-----------|---------------------|
| FR-AUTH-001 | Register | User baru daftar | Form name, email, password |
| FR-AUTH-002 | Login | User login | Email + password |
| FR-AUTH-003 | Logout | User keluar | Session destroyed |
| FR-AUTH-004 | 2FA | Two-Factor Auth | QR code via TOTP |
| FR-AUTH-005 | Password Reset | Reset via email | Link expire 60 menit |
| FR-AUTH-006 | Email Verification | Verifikasi email | Link expire 24 jam |
| FR-AUTH-007 | Profile Update | Update profil | Name, email, password |

### 6.2 Modul Dashboard

| ID | Fitur | Deskripsi |
|----|-------|-----------|
| FR-DASH-001 | Statistik Overview | Total files, views, downloads, storage |
| FR-DASH-002 | Recent Files | 5 file terakhir |
| FR-DASH-003 | Storage Info | List storage servers + usage |

### 6.3 Modul Directory (Folder)

| ID | Fitur | Deskripsi |
|----|-------|-----------|
| FR-DIR-001 | Create Folder | Input nama + parent folder |
| FR-DIR-002 | List Folder | Tampilkan folder di direktori |
| FR-DIR-003 | Navigate Folder | Klik folder, breadcrumb update |
| FR-DIR-004 | Rename Folder | Inline edit nama |
| FR-DIR-005 | Delete Folder | Hapus semua file subfolder recursive |
| FR-DIR-006 | Breadcrumb | Path lengkap, klik untuk navigasi |
| FR-DIR-007 | Move Folder | Select parent baru |
| FR-DIR-008 | Nested Support | Unlimited depth, self-referencing FK |

### 6.4 Modul File Management

| ID | Fitur | Deskripsi |
|----|-------|-----------|
| FR-FILE-001 | Upload File | Drag and drop, max 100MB |
| FR-FILE-002 | Upload to Storage | Pilih storage disk |
| FR-FILE-003 | List Files | Table: name, size, views, downloads |
| FR-FILE-004 | Search File | Search by name |
| FR-FILE-005 | Pagination | 20 file per halaman |
| FR-FILE-006 | File Detail | Preview, metadata, link, embed |
| FR-FILE-007 | Rename File | Inline edit nama |
| FR-FILE-008 | Move File | Select target folder |
| FR-FILE-009 | Delete File | Hapus dari storage + DB |
| FR-FILE-010 | Copy Link | One-click copy link |
| FR-FILE-011 | Copy Embed | One-click copy iframe |
| FR-FILE-012 | File Type Detection | Video, image, pdf, archive |

### 6.5 Modul Storage Server

| ID | Fitur | Deskripsi |
|----|-------|-----------|
| FR-STOR-001 | Add Storage | Form: name, driver, config |
| FR-STOR-002 | List Storage | Nama, driver, usage, status |
| FR-STOR-003 | Edit Storage | Update config |
| FR-STOR-004 | Delete Storage | Deactivate |
| FR-STOR-005 | Storage Stats | Used space, file count |
| FR-STOR-006 | Driver Support | Local, S3, Dropbox, GCS, Azure, FTP, SFTP, Rackspace, Wasabi, Backblaze B2 |

**Driver yang didukung:**

| Driver | Package | Config Required | Catatan |
|--------|---------|-----------------|---------|
| local | built-in | root path | Storage lokal server |
| s3 | league/flysystem-aws-s3-v3 | key, secret, region, bucket | Amazon S3, MinIO, DigitalOcean Spaces |
| dropbox | league/flysystem-dropbox | token | Dropbox |
| gcs | league/flysystem-google-cloud-storage | key_file, bucket | Google Cloud Storage |
| azure | league/flysystem-azure-blob-storage | account, key, container | Azure Blob Storage |
| ftp | league/flysystem-ftp | host, port, username, password, root | FTP server |
| sftp | league/flysystem-sftp | host, port, username, password, root, private_key | SFTP (SSH) |
| rackspace | league/flysystem-rackspace | username, API key, container, region | Rackspace Cloud Files |
| wasabi | league/flysystem-aws-s3-v3 | key, secret, region, bucket | Wasabi (S3-compatible) |
| b2 | league/flysystem-b2 | key_id, application_key, bucket | Backblaze B2 |

> **Driver Lainnya:** Flysystem mendukung banyak storage driver lainnya. Install package tambahan dari https://flysystem.thephpleague.com/ dan tambahkan enum case baru di `StorageDriverEnum` + config di `StorageService`. Contoh: Wasabi, MinIO, DigitalOcean Spaces sudah support via driver S3.

### 6.6 Modul Public Access

| ID | Fitur | Deskripsi |
|----|-------|-----------|
| FR-PUB-001 | Public Show Page | File info, preview, download |
| FR-PUB-002 | Download | Download file |
| FR-PUB-003 | Embed Player | Iframe player, no download |
| FR-PUB-004 | Video Streaming | HTML5 player |
| FR-PUB-005 | Link Expiry | Default 24 jam, 404 jika expired |
| FR-PUB-006 | View Counter | Increment setiap view |
| FR-PUB-007 | Download Counter | Increment setiap download |

### 6.7 Modul Statistics

| ID | Fitur | Deskripsi |
|----|-------|-----------|
| FR-STAT-001 | View Log | IP, user agent, timestamp |
| FR-STAT-002 | Download Log | IP, user agent, timestamp |
| FR-STAT-003 | File Stats | Total views, downloads |
| FR-STAT-004 | Dashboard Stats | Overview statistics |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 7. Kebutuhan Non-Fungsional

### 7.1 Performance

| No | Kebutuhan | Target |
|----|-----------|--------|
| 1 | Upload Speed | < 10 detik untuk 100MB |
| 2 | Page Load | < 2 detik |
| 3 | API Response | < 200ms |
| 4 | Video Stream | < 3 detik start |
| 5 | Concurrent Users | 100+ |

### 7.2 Security

| No | Kebutuhan | Target |
|----|-----------|--------|
| 1 | Auth | Session-based (Breeze) |
| 2 | Authorization | Per-user isolation (Policy) |
| 3 | Encryption | AES-256 |
| 4 | Input Validation | Form Requests |
| 5 | CSRF | Laravel CSRF |
| 6 | Rate Limiting | 100 req/min |
| 7 | File Validation | Whitelist MIME |
| 8 | Link Expiry | Time-based |

### 7.3 Usability

| No | Kebutuhan | Target |
|----|-----------|--------|
| 1 | Responsive | Mobile + Desktop |
| 2 | Accessibility | WCAG 2.1 AA |
| 3 | Dark Mode | Toggle |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 8. Standarisasi Database

### 8.1 Prinsip Utama

Semua interaksi database WAJIB menggunakan **Laravel Eloquent ORM** dan **Laravel Query Builder**. Tidak diperbolehkan raw SQL agar portabel ke RDBMS lain.

### 8.2 Aturan Wajib

| No | Aturan | Contoh Benar | Contoh Salah |
|----|--------|--------------|--------------|
| 1 | Query via Model | File::where('user_id', $id)->get() | DB::select('SELECT *...') |
| 2 | Tidak ada raw SQL | $query->whereColumn('a', 'b') | DB::raw('WHERE a = b') |
| 3 | Tidak ada DB-specific | $model->whereDate('created_at', today()) | DB::raw('DATE(created_at)') |
| 4 | PK UUID v7 | $table->uuid('id')->primary() | DB::raw('UUID()') |
| 5 | FK via Blueprint | $table->foreignId('user_id')->constrained() | DB::raw('FOREIGN KEY...') |
| 6 | Index via Blueprint | $table->index('code') | DB::raw('CREATE INDEX...') |
| 7 | Casting di Model | 'size' => 'integer' | Manual casting di controller |
| 8 | Accessor di Model | getFormattedSizeAttribute() | Logic di Blade |
| 9 | Relationship di Model | belongsTo(), hasMany() | Manual JOIN |
| 10 | Scope di Model | scopeActive($query) | Repeatable WHERE |

### 8.3 Portability Checklist

- Tidak ada DB::raw() di production code
- Tidak ada DB::select(), DB::statement()
- Tidak ada MySQL-specific: GROUP_CONCAT, FIND_IN_SET
- Semua timestamp gunakan $table->timestamps()
- Enum gunakan string + enum cast (bukan MySQL ENUM)
- JSON field gunakan $table->json()

[↑ Kembali ke Daftar Isi](#daftar-isi)

---


---
[← Back to Main PRD](../PRD.md)
