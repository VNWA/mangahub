import { MigrationInterface, QueryRunner, Table, TableForeignKey } from 'typeorm';

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
            name: 'sourceId',
            type: 'int',
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

    await queryRunner.createForeignKey(
      'crawl_mangas',
      new TableForeignKey({
        columnNames: ['sourceId'],
        referencedColumnNames: ['id'],
        referencedTableName: 'crawl_sources',
        onDelete: 'CASCADE',
      }),
    );
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    const table = await queryRunner.getTable('crawl_mangas');
    if (table) {
      const foreignKey = table.foreignKeys.find(
        (fk) => fk.columnNames.indexOf('sourceId') !== -1,
      );
      if (foreignKey) {
        await queryRunner.dropForeignKey('crawl_mangas', foreignKey);
      }
    }
    await queryRunner.dropTable('crawl_mangas');
  }
}
