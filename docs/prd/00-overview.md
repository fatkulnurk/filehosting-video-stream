# 00 Overview

# PRD: FileHosting Video Stream (FHV Stream)

**Version:** 1.0
**Date:** 2026-08-04
**Author:** Rudi (fatkulnurk)
**Status:** Draft

---

## Daftar Isi

- [1. Ringkasan Eksekutif](#1-ringkasan-eksekutif)
- [2. Visi Produk](#2-visi-produk)
- [3. Ruang Lingkup Produk](#3-ruang-lingkup-produk)
- [4. User Persona](#4-user-persona)
- [5. User Journey](#5-user-journey)
- [6. Kebutuhan Fungsional](#6-kebutuhan-fungsional)
- [7. Kebutuhan Non-Fungsional](#7-kebutuhan-non-fungsional)
- [8. Standarisasi Database](#8-standarisasi-database)
- [9. Desain Database](#9-desain-database)
- [10. Arsitektur Aplikasi](#10-arsitektur-aplikasi)
- [11. Arsitektur Modul](#11-arsitektur-modul)
- [12. Desain API](#12-desain-api)
- [13. Flow Scheduler](#13-flow-scheduler)
- [14. UI Flow](#14-ui-flow)
- [15. Struktur Folder](#15-struktur-folder)
- [16. Arsitektur Service](#16-arsitektur-service)
- [17. Deployment](#17-deployment)
- [18. Skalabilitas](#18-skalabilitas)
- [19. Keamanan](#19-keamanan)
- [20. Roadmap](#20-roadmap)
- [21. Risiko](#21-risiko)
- [22. MVP Scope](#22-mvp-scope)
- [23. Phase 2](#23-phase-2)
- [24. Phase 3](#24-phase-3)
- [25. Rekomendasi Teknis](#25-rekomendasi-teknis)
- [Glossary](#glossary)

---

## 1. Ringkasan Eksekutif

**FileHosting Video Stream (FHV Stream)** adalah platform file hosting yang fokus pada video sharing dan embed.

### 1.1 Arsitektur Aplikasi

| Aspek | Detail |
|-------|--------|
| Backend | Laravel 13.x + PHP 8.5+ |
| Frontend | Inertia.js + Vue 3.5.x |
| CSS | Tailwind CSS 4.x |
| Media | Spatie Media Library 11.x |
| Storage | Flysystem 3.x (multi-disk) |
| Auth | Laravel Breeze 2.x |
| Database | MySQL 8.x |

### 1.2 Technology Stack

| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| PHP Runtime | PHP | 8.5+ |
| Framework | Laravel | 13.x |
| Frontend | Vue.js | 3.5.x |
| Inertia | @inertiajs/vue3 | 3.x |
| CSS Framework | Tailwind CSS | 4.x |
| UI Components | Headless UI | 2.x |
| Icons | Heroicons | 2.x |
| Auth | Laravel Breeze | 2.x |
| Media Library | Spatie Media Library | 11.x |
| Query Builder | Spatie Query Builder | 6.x |
| Filesystem | Flysystem | 3.x |
| S3 Driver | league/flysystem-aws-s3-v3 | 3.x |
| Dropbox Driver | league/flysystem-dropbox | 2.x |
| FTP Driver | league/flysystem-ftp | 3.x |
| SFTP Driver | league/flysystem-sftp | 3.x |
| GCS Driver | league/flysystem-google-cloud-storage | 3.x |
| Azure Driver | league/flysystem-azure-blob-storage | 3.x |
| Image Processing | Intervention Image | 3.x |
| Build Tool | Vite | 6.x |
| Package Manager | Composer | 2.x |
| Node Package Manager | npm | 10.x+ |

---

## 2. Visi Produk

### 2.1 Misi

Menjadi platform file hosting open-source yang fokus pada video sharing, dengan fitur enterprise (multi-storage, embed, link expiry) tapi gratis dan mudah digunakan.

### 2.2 Target Pengguna

- Content creator yang butuh host video
- Developer yang butuh embed video player
- Small business yang butuh file sharing internal
- Siapapun yang butuh file hosting dengan fitur lengkap

### 2.3 Pembeda (Differentiator)

| Fitur | FHV Stream | Google Drive | Dropbox |
|-------|-----------|--------------|---------|
| Video Embed Player | Ya | Tidak | Tidak |
| Link Expiry | Ya | Tidak | Ya |
| Multi-Storage Backend | Ya | Tidak | Tidak |
| Nested Folder Unlimited | Ya | Ya | Ya |
| Self-Hosted | Ya | Tidak | Tidak |
| Open Source | Ya | Tidak | Tidak |
| Download Counter | Ya | Tidak | Tidak |
| Custom Embed Code | Ya | Tidak | Tidak |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 3. Ruang Lingkup Produk

### 3.1 Phase 1 (MVP)

| No | Fitur |
|----|-------|
| 1 | User Authentication (Login, Register, 2FA) |
| 2 | Dashboard (statistik, recent files) |
| 3 | File Upload (drag and drop, progress bar) |
| 4 | Nested Folder (unlimited depth, CRUD) |
| 5 | File Manager (list, search, pagination) |
| 6 | Multi Storage Server (CRUD, config) |
| 7 | Public Download Page |
| 8 | Embed Video Player |
| 9 | Link Expiry (24h default) |
| 10 | File Statistics (views, downloads) |
| 11 | Video Streaming (HTML5 player) |
| 12 | Copy Embed Code |
| 13 | File Move/Rename |
| 14 | Breadcrumb Navigation |
| 15 | Storage Statistics |

### 3.2 Phase 2

| No | Fitur |
|----|-------|
| 1 | Auto Generate Thumbnail (video) |
| 2 | Upload Via URL |
| 3 | Upload Via Clipboard |
| 4 | Chunked Upload (large files) |
| 5 | Password-Protected Links |
| 6 | Download Limit |
| 7 | API Developer (REST) |
| 8 | Webhook Notifications |

### 3.3 Phase 3

| No | Fitur |
|----|-------|
| 1 | Auto Convert To MP4 (FFmpeg) |
| 2 | Video Transcoding (multiple quality) |
| 3 | Reward System |
| 4 | Affiliate System |
| 5 | Admin Panel |
| 6 | CDN Integration |
| 7 | Team/Workspace |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 4. User Persona

### 4.1 Content Creator

| Aspek | Detail |
|-------|--------|
| Nama | Budi (28 tahun) |
| Pekerjaan | YouTuber / Content Creator |
| Kebutuhan | Upload video, share ke subscriber, embed di website |
| Pain Point | Storage terbatas, link expired, susah embed |

### 4.2 Developer

| Aspek | Detail |
|-------|--------|
| Nama | Ani (32 tahun) |
| Pekerjaan | Full-Stack Developer |
| Kebutuhan | Host file untuk project, share ke client |
| Pain Point | Butuh API, butuh multi-storage |

### 4.3 Small Business

| Aspek | Detail |
|-------|--------|
| Nama | CV Maju Jaya |
| Pekerjaan | Perusahaan manufaktur |
| Kebutuhan | Share file internal, dokumentasi video training |
| Pain Point | File berserakan, susah cari |

### 4.4 Casual User

| Aspek | Detail |
|-------|--------|
| Nama | Rina (22 tahun) |
| Pekerjaan | Mahasiswa |
| Kebutuhan | Upload tugas, share ke teman |
| Pain Point | Link expired, susah share |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 5. User Journey

### 5.1 Upload and Share

```
Register/Login -> Dashboard -> My Files -> Upload File -> Copy Link/Embed -> Share
```

### 5.2 Public Download

```
Get Link -> Open /f/{code} -> Show Page -> Download
```

### 5.3 Embed Video

```
Get Embed Code -> Copy Code -> Paste in Website -> Video Plays
```

[↑ Kembali ke Daftar Isi](#daftar-isi)

---


---
[← Back to Main PRD](../PRD.md)
