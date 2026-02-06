import { registerAs } from '@nestjs/config';
import { join } from 'path';

export default registerAs('database', () => ({
  crawl: {
    type: 'postgres',
    host: process.env.CRAWL_DB_HOST || 'localhost',
    port: parseInt(process.env.CRAWL_DB_PORT || '5432', 10),
    username: process.env.CRAWL_DB_USERNAME || 'postgres',
    password: process.env.CRAWL_DB_PASSWORD || 'postgres',
    database: process.env.CRAWL_DB_DATABASE || 'crawl_database',
    synchronize: false,
    logging: process.env.NODE_ENV === 'development',
    entities: [
      join(__dirname, '../crawl-database/entities/**/*.entity{.ts,.js}'),
    ],
    migrations: [
      join(__dirname, '../crawl-database/migrations/**/*{.ts,.js}'),
    ],
    migrationsRun: false,
    migrationsTableName: 'typeorm_migrations',
  },
  vnwa: {
    type: 'postgres',
    host: process.env.VNWA_DB_HOST || 'localhost',
    port: parseInt(process.env.VNWA_DB_PORT || '5432', 10),
    username: process.env.VNWA_DB_USERNAME || 'postgres',
    password: process.env.VNWA_DB_PASSWORD || 'postgres',
    database: process.env.VNWA_DB_DATABASE || 'vnwa_database',
    synchronize: false,
    logging: process.env.NODE_ENV === 'development',
    entities: [
      join(__dirname, '../vnwa-database/entities/**/*.entity{.ts,.js}'),
    ],
  },
}));
