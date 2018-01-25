<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateJournalFilesTable extends AbstractMigration
{
  public function up() {
    $this->table('journal_files', ['engine' => "InnoDB"])
      ->addColumn('ext', 'string', ['null' => false, 'limit' => 8])
      ->addColumn('uploaded_time', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
      ->addColumn('data', 'blob', ['null' => false, 'limit' => MysqlAdapter::BLOB_MEDIUM])
      ->save();
  }

  public function down() {
    $this->dropTable('journal_files');
  }
}
