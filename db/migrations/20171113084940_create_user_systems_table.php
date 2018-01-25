<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateUserSystemsTable extends AbstractMigration
{
  public function up() {
    $this->table('user_systems', ['engine' => "InnoDB"])
      ->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('operating_systems', 'string', ['null' => true, 'default' => '', 'limit' => 255])
      ->addColumn('hardware', 'text', ['null' => true, 'limit' => 65535])
      ->addColumn('index', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();
  }

  public function down() {
    $this->dropTable('user_systems');
  }
}
