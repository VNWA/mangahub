# Setup Guide

## Prerequisites

- Node.js 18+ 
- PostgreSQL (for both crawl_database and vnwa_database)
- Redis (for BullMQ queue)

## Installation

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Configure environment:**
   ```bash
   cp env.example .env
   ```
   
   File `env.example` đã có sẵn tất cả các biến môi trường cần thiết. 
   Chỉ cần chỉnh sửa `.env` với thông tin database và Redis của bạn.

3. **Create crawl database:**
   ```sql
   CREATE DATABASE crawl_database;
   ```

4. **Run migrations:**
   ```bash
   npm run migration:run
   ```

5. **Kiểm tra Redis:**
   ```bash
   redis-cli ping
   # Nếu trả về PONG thì Redis đã chạy, không cần làm gì thêm
   # Nếu lỗi, thì cần start Redis (thường Redis đã tự động chạy như service)
   ```
   
   **Lưu ý:** NestJS/BullMQ chỉ cần **kết nối** đến Redis (giống Laravel), không cần chạy server riêng. 
   Nếu Redis đã chạy sẵn (như service/systemd) thì bỏ qua bước này.

## Running the Service

### Development
```bash
npm run start:dev
```

### Production
```bash
npm run build
npm run start:prod
```

## API Testing

Once the service is running, you can test the APIs:

```bash
# List crawl mangas
curl http://localhost:3000/api/crawl-mangas

# Create crawl manga
curl -X POST http://localhost:3000/api/crawl-mangas \
  -H "Content-Type: application/json" \
  -d '{
    "crawlUrl": "https://example.com/manga/123",
    "sourceId": 1
  }'

# Trigger crawl
curl -X POST http://localhost:3000/api/crawl-mangas/1/run

# Check crawl jobs
curl http://localhost:3000/api/crawl-jobs
```

## Database Migrations

```bash
# Generate new migration
npm run migration:generate src/crawl-database/migrations/MigrationName

# Run migrations
npm run migration:run

# Revert last migration
npm run migration:revert
```

## Project Structure Summary

- **crawl-database/**: Own database entities and migrations
- **vnwa-database/**: Laravel database access (read/write)
- **crawl/**: Main business logic, controllers, services
- **config/**: Configuration files

See [ARCHITECTURE.md](./ARCHITECTURE.md) for detailed architecture.
