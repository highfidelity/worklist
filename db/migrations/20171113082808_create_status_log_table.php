<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateStatusLogTable extends AbstractMigration
{
  public function up() {
    $this->table('status_log', ['id' => false, 'engine' => "InnoDB"])
      ->addColumn('worklist_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('status', 'string', ['null' => false, 'limit' => 32])
      ->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('change_date', 'datetime', ['null' => false])
      ->addIndex(['worklist_id'], ['name' => "worklist_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('status_log');
  }
}
