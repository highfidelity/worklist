<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateEntriesTable extends AbstractMigration
{
  public function up() {
    $this->table('entries', ['engine' => "InnoDB"])
      ->addColumn('user_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('entry', 'text', ['null' => false, 'limit' => 65535])
      ->addColumn('author', 'string', ['null' => false, 'limit' => 50])
      ->addColumn('ip', 'string', ['null' => false, 'limit' => 50])
      ->addColumn('date', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('room', 'string', ['null' => false, 'default' => '', 'limit' => 45])
      ->addColumn('sampled', 'boolean', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('visible', 'integer', ['null' => false, 'default' => '1', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('old_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addIndex(['date'], ['name' => "date", 'unique' => false])
      ->addIndex(['id'], ['name' => "id", 'unique' => false])
      ->addIndex(['user_id'], ['name' => "user_id", 'unique' => false])
      ->addIndex(['visible'], ['name' => "visible", 'unique' => false])
      ->addIndex(['old_id'], ['name' => "old_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('entries');
  }
}
