<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateUsersLoveTable extends AbstractMigration
{
  public function up() {
    $this->table('users_love', ['id' => false, 'primary_key' => ["love_id"], 'engine' => "InnoDB"])
      ->addColumn('love_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'identity' => 'enable'])
      ->addColumn('from_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('to_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('message', 'string', ['null' => true, 'limit' => 255])
      ->addColumn('date_sent', 'datetime', ['null' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('users_love');
  }
}
