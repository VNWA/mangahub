# Manga Project - Docker Development Setup

Dự án này bao gồm 2 phần chính:
- **vnwa**: Laravel admin + API (sử dụng Laravel Octane với FrankenPHP, Redis, PostgreSQL)
- **nuxt**: Frontend Nuxt.js (sử dụng Bun)

## 📋 Yêu cầu hệ thống

- Docker & Docker Compose
- Git

## 🚀 Hướng dẫn Setup (Sau khi git clone)

### 1. Clone repository và vào thư mục

```bash
git clone <repository-url>
cd manga
```

### 2. Setup môi trường Laravel

```bash
# Copy file .env.example thành .env (nếu có)
cd vnwa
cp .env.example .env  # hoặc tạo file .env mới

# Chỉnh sửa file .env với thông tin database:
# DB_CONNECTION=pgsql
# DB_HOST=postgres
# DB_PORT=5432
# DB_DATABASE=vnwa
# DB_USERNAME=vnwa
# DB_PASSWORD=vnwa
# REDIS_HOST=redis
# REDIS_PORT=6379
```

### 3. Build và khởi động Docker containers

```bash
# Quay về thư mục gốc
cd ..

# Build và khởi động tất cả services
docker-compose up -d --build
```

### 4. Chạy migrations và setup Laravel

```bash
# Generate application key (nếu chưa có)
docker-compose exec vnwa-app php artisan key:generate

# Chạy migrations
docker-compose exec vnwa-app php artisan migrate

# (Tùy chọn) Chạy seeders
docker-compose exec vnwa-app php artisan db:seed

# Tạo storage link
docker-compose exec vnwa-app php artisan storage:link
```

### 5. Kiểm tra services đã chạy

```bash
docker-compose ps
```

Bạn sẽ thấy các services:
- `nginx` - Reverse proxy
- `vnwa-app` - Laravel Octane với FrankenPHP
- `vnwa-worker` - Queue workers và schedulers
- `nuxt` - Nuxt.js frontend
- `postgres` - PostgreSQL database
- `redis` - Redis cache

## 💻 Chạy Development

### Khởi động tất cả services

```bash
docker-compose up
```

Hoặc chạy ở background:

```bash
docker-compose up -d
```

### Xem logs

```bash
# Xem logs tất cả services
docker-compose logs -f

# Xem logs của service cụ thể
docker-compose logs -f vnwa-app
docker-compose logs -f nuxt
docker-compose logs -f vnwa-worker
```

### Dừng services

```bash
docker-compose down
```

### Dừng và xóa volumes (reset database)

```bash
docker-compose down -v
```

## 🌐 Truy cập ứng dụng

Sau khi khởi động, bạn có thể truy cập:

- **Admin/API Laravel**: http://admin.localhost hoặc http://api.localhost
- **Nuxt Frontend**: http://site.localhost
- **PostgreSQL**: localhost:5432 (user: vnwa, password: vnwa, database: vnwa)
- **Redis**: localhost:6379

> **Lưu ý**: Nếu không truy cập được qua domain, bạn có thể thêm vào file `hosts`:
> - Windows: `C:\Windows\System32\drivers\etc\hosts`
> - Linux/Mac: `/etc/hosts`
>
> Thêm các dòng:
> ```
> 127.0.0.1 admin.localhost
> 127.0.0.1 api.localhost
> 127.0.0.1 site.localhost
> ```

## 🔧 Các lệnh hữu ích

### Laravel commands

```bash
# Chạy artisan commands
docker-compose exec vnwa-app php artisan <command>

# Ví dụ:
docker-compose exec vnwa-app php artisan tinker
docker-compose exec vnwa-app php artisan route:list
docker-compose exec vnwa-app php artisan cache:clear
```

### Database commands

```bash
# Kết nối PostgreSQL
docker-compose exec postgres psql -U vnwa -d vnwa

# Backup database
docker-compose exec postgres pg_dump -U vnwa vnwa > backup.sql
```

### Redis commands

```bash
# Kết nối Redis CLI
docker-compose exec redis redis-cli
```

### Nuxt commands

```bash
# Chạy commands trong Nuxt container
docker-compose exec nuxt bun <command>

# Ví dụ:
docker-compose exec nuxt bun install
docker-compose exec nuxt bun run build
```

## 📝 Những gì đã được cấu hình

### Docker Setup

1. **vnwa/Dockerfile**:
   - Base image: `dunglas/frankenphp:latest` (đã có FrankenPHP)
   - Cài đặt PHP extensions: pdo, pdo_pgsql, zip, redis
   - Cài đặt Composer và Supervisor
   - Copy supervisor config cho queue workers

2. **nuxt/Dockerfile**:
   - Base image: `oven/bun:latest` (Bun runtime)
   - Cài đặt dependencies bằng Bun
   - Expose port 3000

3. **docker-compose.yml**:
   - **nginx**: Reverse proxy với config cho admin/api và nuxt
   - **vnwa-app**: Laravel Octane với FrankenPHP server
   - **vnwa-worker**: Supervisor chạy queue workers và schedulers
   - **nuxt**: Nuxt.js dev server với Bun
   - **postgres**: PostgreSQL 16 với health checks
   - **redis**: Redis với health checks
   - Tất cả services có health checks và dependencies được cấu hình đúng

4. **Nginx configs**:
   - `docker/nginx/vnwa.conf`: Proxy cho admin.localhost và api.localhost
   - `docker/nginx/nuxt.conf`: Proxy cho site.localhost với WebSocket support

### Các cải tiến đã thực hiện

✅ Sửa Dockerfile để sử dụng FrankenPHP thay vì Swoole  
✅ Cấu hình đúng supervisor config path  
✅ Thêm ports cho PostgreSQL và Redis để dev tools kết nối  
✅ Thêm health checks cho tất cả services  
✅ Cấu hình environment variables cho database và Redis  
✅ Tối ưu Dockerfile với layer caching  
✅ Sử dụng Bun thay vì npm cho Nuxt  
✅ Volume mounts được cấu hình đúng cho development  

## 🐛 Troubleshooting

### Services không start được

```bash
# Kiểm tra logs
docker-compose logs

# Rebuild containers
docker-compose up -d --build --force-recreate
```

### Database connection errors

- Kiểm tra PostgreSQL đã healthy: `docker-compose ps postgres`
- Kiểm tra environment variables trong docker-compose.yml
- Đảm bảo `.env` file có đúng DB_HOST=postgres

### Permission errors

```bash
# Fix permissions cho Laravel storage
docker-compose exec vnwa-app chmod -R 775 storage bootstrap/cache
docker-compose exec vnwa-app chown -R www-data:www-data storage bootstrap/cache
```

### Port conflicts

Nếu ports 80, 3000, 5432, 6379 đã được sử dụng, bạn có thể thay đổi trong `docker-compose.yml`

## 📚 Tài liệu tham khảo

- [Laravel Octane](https://laravel.com/docs/octane)
- [FrankenPHP](https://frankenphp.dev/)
- [Nuxt.js](https://nuxt.com/)
- [Bun](https://bun.sh/)
