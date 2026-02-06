# Crawl Service Architecture

## Overview

This is an internal NestJS 11.0.16 service for crawling manga data. It provides REST APIs for Laravel (vnwa) to manage crawl operations.

## Folder Structure

```
crawl/
├── src/
│   ├── config/                    # Configuration files
│   │   ├── app.config.ts         # App configuration
│   │   ├── database.config.ts    # Database connections
│   │   └── redis.config.ts       # Redis configuration
│   │
│   ├── crawl-database/            # Crawl service's own database
│   │   ├── entities/             # TypeORM entities
│   │   │   ├── crawl-source.entity.ts
│   │   │   ├── crawl-manga.entity.ts
│   │   │   └── crawl-job.entity.ts
│   │   ├── migrations/            # TypeORM migrations
│   │   ├── data-source.ts        # Migration data source
│   │   └── crawl-database.module.ts
│   │
│   ├── vnwa-database/             # Laravel database access
│   │   ├── entities/              # Read-only entities
│   │   │   ├── manga.entity.ts
│   │   │   ├── manga-chapter.entity.ts
│   │   │   └── server-chapter-content.entity.ts
│   │   └── repositories/          # Custom repositories
│   │       ├── manga.repository.ts
│   │       ├── manga-chapter.repository.ts
│   │       └── server-chapter-content.repository.ts
│   │   └── vnwa-database.module.ts
│   │
│   ├── crawl/                     # Main crawl module
│   │   ├── controllers/           # REST API controllers
│   │   │   ├── crawl-manga.controller.ts
│   │   │   └── crawl-job.controller.ts
│   │   ├── services/              # Business logic
│   │   │   ├── crawl-manga.service.ts
│   │   │   ├── crawl-job.service.ts
│   │   │   ├── crawl-engine.service.ts (skeleton)
│   │   │   ├── puppeteer.service.ts (skeleton)
│   │   │   ├── html-parser.service.ts (skeleton)
│   │   │   └── vnwa-sync.service.ts
│   │   ├── dto/                   # Data Transfer Objects
│   │   │   ├── create-crawl-manga.dto.ts
│   │   │   └── update-crawl-manga.dto.ts
│   │   ├── queue/                 # BullMQ queue
│   │   │   ├── crawl.processor.ts
│   │   │   └── crawl-queue.service.ts
│   │   ├── scheduler/             # Cron jobs
│   │   │   └── crawl-scheduler.service.ts
│   │   └── crawl.module.ts
│   │
│   ├── app.module.ts              # Root module
│   └── main.ts                    # Application entry point
│
├── package.json
└── tsconfig.json
```

## Database Architecture

### Crawl Database (crawl_database)

Own database for managing crawl state:

- **crawl_sources**: Manga source websites
- **crawl_mangas**: Manga to crawl with status tracking
- **crawl_jobs**: Individual crawl job executions

### VNWA Database (vnwa_database)

Laravel database - READ/WRITE access only:

- **mangas**: Manga records (insert/update)
- **manga_chapters**: Chapter records (insert/update)
- **server_chapter_contents**: Chapter images (insert/update)

**Important Rules:**
- Always set `user_id = 1` (configurable via `VNWA_DEFAULT_USER_ID`)
- Use transactions for data integrity
- Prevent duplicate chapters by slug

## API Endpoints

### Crawl Manga Management

- `GET /api/crawl-mangas` - List all crawl mangas
- `GET /api/crawl-mangas/:id` - Get crawl manga by ID
- `POST /api/crawl-mangas` - Create new crawl manga
- `PUT /api/crawl-mangas/:id` - Update crawl manga
- `DELETE /api/crawl-mangas/:id` - Delete crawl manga

### Crawl Operations

- `POST /api/crawl-mangas/:id/run` - Trigger crawl for specific manga
- `POST /api/crawl-mangas/run-all` - Trigger crawl for all pending mangas

### Crawl Job Status

- `GET /api/crawl-jobs` - List crawl jobs (with filters)
- `GET /api/crawl-jobs/:id` - Get crawl job by ID

## Queue System

Uses BullMQ with Redis for background job processing:

- Queue name: `crawl`
- Job type: `crawl-manga`
- Retry: 3 attempts with exponential backoff

## Scheduler

Cron jobs using `@nestjs/schedule`:

- **Every hour**: Process pending crawl mangas (10 at a time)
- **Every 6 hours**: Update existing mangas (20 at a time)

## Environment Variables

See `.env.example` for required configuration:

- Database connections (crawl & vnwa)
- Redis connection
- Server port
- Default user ID for vnwa operations

## Migration Commands

```bash
# Generate migration
npm run migration:generate src/crawl-database/migrations/MigrationName

# Run migrations
npm run migration:run

# Revert last migration
npm run migration:revert
```

## Development

```bash
# Install dependencies
npm install

# Start development server
npm run start:dev

# Build for production
npm run build

# Start production server
npm run start:prod
```

## Service Skeletons

The following services are skeleton implementations that throw `NotImplementedException`:

- `CrawlEngineService.runMangaCrawl()`
- `CrawlEngineService.runAll()`
- `CrawlEngineService.syncToVnwa()`
- `PuppeteerService.init()`
- `PuppeteerService.close()`
- `HtmlParserService.parseManga()`
- `HtmlParserService.parseChapters()`
- `HtmlParserService.parseImages()`

These should be implemented when adding actual crawl logic.
