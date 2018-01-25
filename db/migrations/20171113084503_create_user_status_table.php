<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateUserStatusTable extends AbstractMigration
{
  public function up() {
    $this->table('user_status', ['id' => false, 'primary_key' => ["id", "timeplaced"], 'engine' => "InnoDB"])
      ->addColumn('id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('timeplaced', 'datetime', ['null' => false, 'default' => '1000-01-01 00:00:00'])
      ->addColumn('status', 'text', ['null' => true, 'limit' => 65535])
      ->addColumn('old_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();
  }

  public function down() {
    $this->dropTable('user_status');
  }
}
