import { DataSource } from 'typeorm';
import { config } from 'dotenv';

config();

export default new DataSource({
  type: 'postgres',
  host: process.env.CRAWL_DB_HOST || 'localhost',
  port: parseInt(process.env.CRAWL_DB_PORT || '5432', 10),
  username: process.env.CRAWL_DB_USERNAME || 'postgres',
  password: process.env.CRAWL_DB_PASSWORD || 'postgres',
  database: process.env.CRAWL_DB_DATABASE || 'crawl_database',
  synchronize: false,
  logging: process.env.NODE_ENV === 'development',
  entities: [__dirname + '/entities/**/*.entity{.ts,.js}'],
  migrations: [__dirname + '/migrations/**/*{.ts,.js}'],
  migrationsTableName: 'typeorm_migrations',
});
