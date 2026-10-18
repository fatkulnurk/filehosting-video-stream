# 05 Deployment

## 17. Deployment

### 16.1 Docker Compose

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: fhv-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - fhv-network

  nginx:
    image: nginx:alpine
    container_name: fhv-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/conf.d/:/etc/nginx/conf.d/
    networks:
      - fhv-network

  mysql:
    image: mysql:8.0
    container_name: fhv-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: fhv_stream
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_USER: fhv_user
      MYSQL_PASSWORD: secret
    ports:
      - "3306:3306"
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - fhv-network

networks:
  fhv-network:
    driver: bridge

volumes:
  mysql-data:
    driver: local
```

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 18. Skalabilitas

### 17.1 Horizontal Scaling

| Komponen | Metode |
|----------|--------|
| Web Server | Load balancer + multiple instances |
| Application | Stateless + shared session (Redis) |
| Database | Read replica + connection pooling |
| Storage | Cloud storage (S3/GCS) |
| Cache | Redis cluster |
| Queue | Redis/SQS workers |

### 17.2 Caching Strategy

| Level | Method | TTL |
|-------|--------|-----|
| Application | Redis Cache | 5 minutes |
| Query | Query caching | 10 minutes |
| Static | CDN caching | 24 hours |
| Browser | HTTP cache headers | 1 week |

[↑ Kembali ke Daftar Isi](#daftar-isi)

---

## 19. Keamanan

### 18.1 Security Layers

```
Layer 1: Network - Firewall, SSL/TLS, DDoS Protection
Layer 2: Application - CSRF, Rate Limiting, Input Validation
Layer 3: Authentication - Session Auth, 2FA, Password Hashing
Layer 4: Authorization - Policy Gates, Middleware, Ownership Checks
Layer 5: Data Protection - Encryption at Rest, Link Expiry
```

### 18.2 Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| Login | 5 attempts | 1 minute |
| Register | 3 attempts | 1 hour |
| File Upload | 10 requests | 1 minute |
| API (auth) | 60 requests | 1 minute |
| Public Access | 100 requests | 1 minute |

### 18.3 Security Checklist

- SSL/TLS enabled (HTTPS)
- CSRF protection on all forms
- XSS protection via Blade escaping
- SQL injection prevention (Eloquent)
- Rate limiting on sensitive endpoints
- Password hashing (Bcrypt/Argon2)
- File upload validation
- Directory traversal prevention
- Link expiry enforcement
- User isolation (ownership checks)

[↑ Kembali ke Daftar Isi](#daftar-isi)

---


---
[← Back to Main PRD](../PRD.md)
