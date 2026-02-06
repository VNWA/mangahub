import { MigrationInterface, QueryRunner, Table, TableForeignKey } from 'typeorm';

export class CreateCrawlJobsTable1738000000002
  implements MigrationInterface
{
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.createTable(
      new Table({
        name: 'crawl_jobs',
        columns: [
          {
            name: 'id',
            type: 'int',
            isPrimary: true,
            isGenerated: true,
            generationStrategy: 'increment',
          },
          {
            name: 'crawlMangaId',
            type: 'int',
          },
          {
            name: 'type',
            type: 'enum',
            enum: ['full', 'update'],
            default: "'full'",
          },
          {
            name: 'status',
            type: 'enum',
            enum: ['pending', 'running', 'success', 'failed'],
            default: "'pending'",
          },
          {
            name: 'startedAt',
            type: 'timestamp',
            isNullable: true,
          },
          {
            name: 'finishedAt',
            type: 'timestamp',
            isNullable: true,
          },
          {
            name: 'errorMessage',
            type: 'text',
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
      'crawl_jobs',
      new TableForeignKey({
        columnNames: ['crawlMangaId'],
        referencedColumnNames: ['id'],
        referencedTableName: 'crawl_mangas',
        onDelete: 'CASCADE',
      }),
    );
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    const table = await queryRunner.getTable('crawl_jobs');
    if (table) {
      const foreignKey = table.foreignKeys.find(
        (fk) => fk.columnNames.indexOf('crawlMangaId') !== -1,
      );
      if (foreignKey) {
        await queryRunner.dropForeignKey('crawl_jobs', foreignKey);
      }
    }
    await queryRunner.dropTable('crawl_jobs');
  }
}
