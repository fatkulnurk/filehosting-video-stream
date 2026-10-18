# FileHosting Video Stream - PRD Documentation

## Table of Contents

| File | Description |
|------|-------------|
| [00-overview.md](00-overview.md) | Ringkasan Eksekutif, Visi, Scope, Persona, Journey |
| [01-requirements.md](01-requirements.md) | Kebutuhan Fungsional & Non-Fungsional |
| [02-database.md](02-database.md) | Desain Database (7 tabel + contoh data) |
| [03-architecture.md](03-architecture.md) | Arsitektur Aplikasi, Modul, Service, Interface |
| [04-ui.md](04-ui.md) | UI Flow, Struktur Folder, Service Architecture |
| [05-deployment.md](05-deployment.md) | Deployment, Skalabilitas, Keamanan |
| [06-roadmap.md](06-roadmap.md) | Roadmap, Risiko, MVP, Phase 2/3 |
| [07-additional-features.md](07-additional-features.md) | Overview fitur tambahan |
| [08-storage-packages.md](08-storage-packages.md) | Storage Packages (Admin Configurable) |
| [09-password-protect.md](09-password-protect.md) | Password Protected Links |
| [10-file-versioning.md](10-file-versioning.md) | File Versioning |
| [11-trash-recycle.md](11-trash-recycle.md) | Trash / Recycle Bin |
| [12-activity-logs.md](12-activity-logs.md) | Activity Logs |
| [13-webhooks.md](13-webhooks.md) | Webhook Notifications |
| [14-api-keys.md](14-api-keys.md) | API Keys |
| [15-batch-operations.md](15-batch-operations.md) | Batch Operations |
| [16-improvements.md](16-improvements.md) | Bug Fixes & Improvements (Rate Limit, Sharing, etc) |
| [17-file-deduplication.md](17-file-deduplication.md) | File Deduplication via Checksum (Storage Savings) |

## Tech Stack

| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| PHP | PHP | 8.5+ |
| Framework | Laravel | 13.x |
| Frontend | Vue.js | 3.5.x |
| Inertia | @inertiajs/vue3 | 3.x |
| CSS | Tailwind CSS | 4.x |
| Media | Spatie Media Library | 11.x |
| Storage | Flysystem | 3.x |
| Database | MySQL | 8.x |

## Architecture Pattern

```
Controller -> Service (Interface) -> Repository (Interface) -> Model -> Database
```

## User Roles

| Role | Description |
|------|-------------|
| Member | Upload, manage own files, view stats |
| Admin | All member features + manage users, packages, system |

## Storage Packages

| Package | Storage | Max File | Price |
|---------|---------|----------|-------|
| Free | 1 GB | 100 MB | $0 |
| Basic | 10 GB | 500 MB | $9.99/mo |
| Pro | 50 GB | 1 GB | $29.99/mo |
| Enterprise | 100 GB | 2 GB | $99.99/mo |

## Quick Links

- [Main PRD](../PRD.md)
- [GitHub Repository](https://github.com/fatkulnurk/filehosting-video-stream)
