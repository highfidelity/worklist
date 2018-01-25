<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateEntryJobsTable extends AbstractMigration
{
  public function up() {
    $this->table('entry_jobs', ['id' => false, 'engine' => "InnoDB"])
      ->addColumn('entry_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('job_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addIndex(['entry_id'], ['name' => "entry_id", 'unique' => false])
      ->addIndex(['job_id'], ['name' => "job_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('entry_jobs');
  }
}
