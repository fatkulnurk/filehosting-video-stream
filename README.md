# File Hosting Video Stream

A file hosting & streaming platform built with **Laravel 13** + **Inertia.js** + **Vue 3**.

> ⚠️ **Heavily edited, not production-ready.**
> This project has been extensively customized during iterative development. It is **not
> production-ready**: it is under active development, may contain unfinished features, manual
> database changes, and undocumented behavior. Use at your own risk. A code audit and hardening
> pass (especially validation, authz, CSRF, queue workers, and storage configuration) is strongly
> recommended before any production use.

---

## Overview

FSH Stream lets users upload, organize, share, and stream files (including video). It includes:

- File & directory management (create, rename, move, delete, trash, versions, favorites)
- Public links, embeds, and streaming endpoints
- Sharing files & directories with other users (view / download permissions)
- Storage packages & subscriptions/quota per user
- API keys, webhooks, and activity logs
- **Cloud Downloads** — add files from a URL or a torrent (magnet / `.torrent`) directly into
  storage, with live status/progress tracking and **hash-based dedup** (the same torrent is reused
  instantly instead of re-downloading when the bytes already exist)
- Admin panel (users, storage servers, storage packages, downloader clients, logs) under `/admin`
- **System-managed storage pool**: storage servers are selected automatically by the system
  (least-utilized active server), not owned by individual users.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Vue 3, Inertia.js v3, Tailwind CSS, Heroicons |
| Media | Spatie Laravel MediaLibrary |
| Storage drivers | Local, S3, Wasabi, B2, Azure Blob, GCS, Dropbox, FTP, SFTP |
| Queue | Database driver (`QUEUE_CONNECTION=database`) |
| Auth | Laravel Fortify (2FA, sessions) |
| Torrent client | Transmission (via `fatkulnurk/torrent` SDK), `.torrent` parsing via `arokettu/bencode` |

### Supported storage drivers

| Driver | Storage backend | Required config fields |
|--------|-----------------|------------------------|
| `local` | Local filesystem | `root` |
| `s3` | Amazon S3 | `key`, `secret`, `region`, `bucket` |
| `wasabi` | Wasabi | `key`, `secret`, `region`, `bucket` |
| `b2` | Backblaze B2 | `key_id`, `application_key`, `bucket` |
| `azure` | Azure Blob Storage | `connection_string`, `container` |
| `gcs` | Google Cloud Storage | `key_file`, `bucket` |
| `dropbox` | Dropbox | `token` |
| `ftp` | FTP Server | `host`, `port`, `username`, `password`, `root` |
| `sftp` | SFTP (SSH) | `host`, `port`, `username`, `password`, `root` |
| `rackspace` | Rackspace Cloud Files | `username`, `api_key`, `container`, `region` |

> Storage servers are configured by an admin in the admin panel (`/admin/storage`). The app then
> auto-registers each server as a Laravel filesystem disk at boot.

> **Storage abstraction:** all storage is built on top of
> [PHPLeague Flysystem](https://flysystem.thephpleague.com/), the same filesystem abstraction Laravel
> uses. This means the app is **not limited to the drivers listed above** — any Flysystem adapter can
> be wired in as a disk, so dozens of backends are theoretically supported (Cloudflare R2, MinIO,
> DigitalOcean Spaces, Contabo, Vultr, GCS, Azure, Wasabi, B2, S3-compatible providers, SFTP, WebDAV,
> FTP, Dropbox, Rackspace, and more).

---

## Requirements

- PHP 8.3+
- Composer
- Node.js & npm
- A MySQL-compatible database (default config points to MySQL `fhv_stream`);
  SQLite is used for unit tests.

> Note: for the async **Cloud Downloads** feature (upload from URL / torrent), a queue worker must
> be running (`php artisan queue:work` / `queue:listen`) and, for torrents, a scheduler
> (`php artisan schedule:work`) — otherwise queued downloads never execute / are never imported.

---

## Installation

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
php artisan migrate --seed

# 4. Frontend (production build or dev server)
npm run build          # production assets
# or
npm run dev            # watch mode (Vite)

# 5. Serve
php artisan serve
# visit http://localhost:8000
```

Herd users can simply open the site through their local domain
(e.g. `https://filehostingvideo.test/`) after setting up a site for this directory.

### Queue worker (required for async URL downloads)

```bash
php artisan queue:work   # or queue:listen
```

### Torrent server & Cloud Downloads (optional)

Cloud Downloads supports both plain URLs (no external service needed) and **torrents**, which
require a running Transmission server and a scheduler for progress monitoring/import.

1. Configure Transmission (either via `docker compose` or your own instance):
   ```bash
   docker compose up -d transmission
   ```
2. Set environment variables (see `.env.example`):
   ```dotenv
   TRANSMISSION_HOST=http://127.0.0.1
   TRANSMISSION_PORT=9091
   TRANSMISSION_USER=admin
   TRANSMISSION_PASS=
   TORRENT_DOWNLOAD_DIR=
   TORRENT_CONTAINER_DOWNLOAD_DIR=/downloads
   ```
3. Register the downloader client in the admin panel (`/admin/downloaders`) and test the connection.
4. Run the scheduler so torrent progress is polled and completed files are imported:
   ```bash
   php artisan schedule:work
   ```

Torrent files are downloaded into a shared volume, imported into the storage pool (medialibrary),
then the temporary data is removed. When the same torrent info-hash is added again and its files
are still present, they are reused instantly without re-downloading.

---

## Default seeded credentials

> Generated by `DatabaseSeeder` — change these in production!

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@dibumi.com` | `password` |
| Member | `rudi@dibumi.com` | `password` |

The seeder also creates:

- A default storage package plan (Free / Basic / Pro).
- A single **global** storage server (`global-local`) used by the whole system.
- **Demo content for both admin and member** (not just the member): a rich nested folder tree
  (~18 folders, 3–4 levels deep) and ~130 files per user across `Videos`, `Images`, `Documents`,
  and `Music` — including subfolders such as `Events/Wedding`, `Tutorials/PHP`, `Reports/2025`,
  and `Music/Playlists`, so you can explore the file manager right away.

---

## Key Concepts

### System-managed storage (global pool)
Uploads do not belong to a per-user storage server. Instead, the app picks an **active** storage
server with the lowest utilization ratio (`storage_used / storage_limit`) at upload time. Manage
storage servers in the admin panel (`/admin/storage`).

### Storage packages & quota
Each user has a subscription to a storage package, giving them a storage limit and per-file size
limits. Quota is enforced per user; the physical storage is shared globally.

### Migrations
Migrations are consolidated **by domain** into six files under `database/migrations`:
`core`, `profile`, `storage`, `file`, `billing`, and `integration`.

---

## Available Routes (highlights)

### Guest / public
- `GET /` — landing page
- `GET /f/{id}` — public file page
- `GET /embed/{id}` — embeddable player
- `GET /stream/{id}` — stream endpoint

### Authenticated
- `GET /dashboard` — user dashboard
- `GET /files` — file manager (upload, folders, favorites, shares, trash)
- `GET /cloud-downloads` — Cloud Downloads (status/progress of URL & torrent downloads)
- Profile, API keys, webhooks, activity logs, subscriptions

### Admin (`/admin/*`)
- `GET /admin/dashboard` — admin dashboard
- `GET /admin/users` — manage users
- `GET /admin/packages` — manage storage packages
- `GET /admin/storage` — manage storage servers
- `GET /admin/downloaders` — manage downloader clients (URL / torrent / Transmission)

---

## Tests

```bash
php artisan test
```

Small but working: includes default unit + feature examples, migration success, basic page loads.

---

## Notes & Caveats

- **Not production ready.** No load-testing, hardening, or full E2E coverage.
- Storage drivers may require environment credentials (S3, Azure, etc.) not included in `.env.example`.
- The Cloud Downloads feature fetches remote content server-side (with SSRF guards on built-in
  private/blocked hosts) and downloads torrents via a Transmission client. Both should be reviewed
  carefully for abuse (queue load, SSRF edge cases, redirects, quota, legal use of torrents) before
  enabling widely.
- API/webhook signing and 2FA use standard Laravel mechanisms but have not been audited.

---

## Contributing

This is a private/experimental project. Contributions and suggestions are welcome but should be
coordinated with the repository owner.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
