import { MigrationInterface, QueryRunner, Table } from 'typeorm';

export class CreateCrawlMangasTable1738000000001
  implements MigrationInterface
{
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.createTable(
      new Table({
        name: 'crawl_mangas',
        columns: [
          {
            name: 'id',
            type: 'int',
            isPrimary: true,
            isGenerated: true,
            generationStrategy: 'increment',
          },
          {
            name: 'sourceName',
            type: 'varchar',
            length: '255',
            comment: 'Domain name of the crawl source (e.g., manga18.me)',
          },
          {
            name: 'crawlUrl',
            type: 'varchar',
            length: '1000',
          },
          {
            name: 'title',
            type: 'varchar',
            length: '500',
            isNullable: true,
          },
          {
            name: 'slug',
            type: 'varchar',
            length: '500',
            isNullable: true,
          },
          {
            name: 'avatar',
            type: 'varchar',
            length: '500',
            isNullable: true,
            comment: 'Path to avatar image in MinIO',
          },
          {
            name: 'vnwaMangaId',
            type: 'int',
            isNullable: true,
          },
          {
            name: 'latestChapter',
            type: 'varchar',
            length: '255',
            isNullable: true,
          },
          {
            name: 'status',
            type: 'enum',
            enum: ['pending', 'crawling', 'done', 'error'],
            default: "'pending'",
          },
          {
            name: 'lastCrawledAt',
            type: 'timestamp',
            isNullable: true,
          },
          {
            name: 'createdAt',
            type: 'timestamp',
            default: 'CURRENT_TIMESTAMP',
          },
          {
            name: 'updatedAt',
            type: 'timestamp',
            default: 'CURRENT_TIMESTAMP',
            onUpdate: 'CURRENT_TIMESTAMP',
          },
        ],
      }),
      true,
    );
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.dropTable('crawl_mangas');
  }
}
