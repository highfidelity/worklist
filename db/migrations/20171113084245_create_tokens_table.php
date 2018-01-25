<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateTokensTable extends AbstractMigration
{
  public function up() {
    $this->table('tokens', ['engine' => "InnoDB"])
      ->addColumn('token', 'string', ['null' => false, 'limit' => 13])
      ->addColumn('completed', 'boolean', ['null' => false, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addIndex(['id'], ['name' => "id", 'unique' => true])
      ->addIndex(['id'], ['name' => "id_2", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('tokens');
  }
}
