# 06 Roadmap

## 20. Roadmap

### 19.1 Version Milestones

| Version | Target | Features |
|---------|--------|----------|
| v1.0.0 | Q3 2026 | MVP - Core file hosting + video embed |
| v1.1.0 | Q4 2026 | Auto thumbnail, upload via URL |
| v1.2.0 | Q1 2027 | Chunked upload, password links |
| v2.0.0 | Q2 2027 | API Developer, Webhooks |
| v3.0.0 | Q4 2027 | Admin panel, reward/affiliate |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 21. Risiko

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Storage cost overrun | Medium | High | Set storage limits |
| Link abuse | High | Medium | Link expiry, rate limiting |
| Data breach | Low | Critical | Encryption, access control |
| DDoS attack | Medium | High | CDN, rate limiting |
| MySQL performance | Low | Medium | Indexing, caching |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 22. MVP Scope

### IN Scope

- User Registration and Login
- 2FA Authentication
- Dashboard with Statistics
- File Upload (drag and drop)
- Nested Folder System (unlimited)
- File Manager (list, search, pagination)
- Multi-Storage Server Support
- Public Download Page
- Embed Video Player
- Link Expiry (24h default)
- File Statistics (views, downloads)
- Video Streaming (HTML5)
- Copy Embed Code
- File Move/Rename
- Breadcrumb Navigation
- Storage Statistics

### OUT Scope (Phase 2+)

- Auto Generate Thumbnail
- Upload via URL/Clipboard
- Chunked Upload
- Password-Protected Links
- REST API Developer
- Webhook Notifications
- FFmpeg Integration
- Admin Panel

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 23. Phase 2

| Feature | Priority | Complexity |
|---------|----------|------------|
| Auto Generate Thumbnail | High | Medium |
| Upload via URL | High | Low |
| Chunked Upload | High | High |
| Password-Protected Links | High | Medium |
| Download Limit | Medium | Medium |
| REST API Developer | High | High |
| Webhook Notifications | Medium | High |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 24. Phase 3

| Feature | Target Users |
|---------|--------------|
| FFmpeg Transcoding | Content Creators |
| Video Multi-Quality | Enterprise |
| Reward System | Platform Admin |
| Affiliate System | Marketers |
| Admin Panel | Platform Admin |
| CDN Integration | Enterprise |
| Team/Workspace | Enterprise |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 25. Rekomendasi Teknis

### 24.1 Stack Decisions

| Decision | Rationale |
|----------|-----------|
| Laravel 13.x | Mature ecosystem, excellent docs |
| Inertia.js + Vue 3 | SPA without API overhead |
| Tailwind CSS 4 | Utility-first, fast development |
| Spatie Media Library | Best-in-class media management |
| Flysystem 3 | Multi-storage abstraction |
| MySQL 8 | Stable, widely supported |

### 24.2 Cost Estimation

| Item | Monthly Cost |
|------|--------------|
| VPS (4GB RAM, 2 vCPU) | $20-40 |
| Domain + SSL | $1-2 |
| S3 Storage (100GB) | $2-5 |
| CDN (1TB bandwidth) | $10-20 |
| **Total** | **$33-67/month** |

### 24.3 Development Timeline

| Phase | Duration | Team Size |
|-------|----------|-----------|
| Phase 1 (MVP) | 8-10 weeks | 1-2 developers |
| Phase 2 | 6-8 weeks | 1-2 developers |
| Phase 3 | 8-12 weeks | 2-3 developers |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## Glossary

### File Management

| Term | Definition |
|------|------------|
| Directory | Folder untuk organisasi file |
| Nested Directory | Folder dalam folder (unlimited depth) |
| Slug | URL-friendly version dari nama |
| Code | Kode pendek unik untuk public URL |
| Path | Lokasi file di storage |

### Storage

| Term | Definition |
|------|------------|
| Disk | Nama storage backend di Laravel |
| Driver | Tipe storage backend (local, s3, dropbox) |
| Flysystem | Laravel filesystem abstraction |
| Storage Server | Konfigurasi storage backend |

### Media

| Term | Definition |
|------|------------|
| MIME Type | Tipe file berdasarkan standar |
| Thumbnail | Gambar kecil preview (300x200) |
| Preview | Gambar besar preview (800x600) |
| Media Library | Spatie package untuk manage media |

### Access

| Term | Definition |
|------|------------|
| Embed Code | Iframe HTML untuk embed video |
| Public URL | URL untuk akses file tanpa login |
| Link Expiry | Waktu kadaluarsa link |
| Download Counter | Jumlah kali file didownload |
| View Counter | Jumlah kali file diakses |

### Security

| Term | Definition |
|------|------------|
| 2FA | Two-Factor Authentication |
| CSRF | Cross-Site Request Forgery protection |
| Rate Limiting | Batas jumlah request per waktu |
| Policy | Laravel authorization rules |

### Database

| Term | Definition |
|------|------------|
| Eloquent | Laravel ORM |
| Migration | Version control untuk database schema |
| Foreign Key | Relasi antar tabel |
| Cascade Delete | Hapus record terkait saat parent dihapus |
| Index | Optimasi query performance |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---
[← Back to Main PRD](../PRD.md)
