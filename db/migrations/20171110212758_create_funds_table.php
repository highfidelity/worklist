<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateFundsTable extends AbstractMigration
{
  public function up() {
    $this->table('funds', ['engine' => "InnoDB"])
      ->addColumn('name', 'string', ['null' => false, 'limit' => 100])
      ->addColumn('pp_enabled', 'boolean', ['null' => false, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('pp_login_email', 'string', ['null' => true, 'limit' => 255])
      ->addColumn('pp_API_key', 'string', ['null' => true, 'limit' => 255])
      ->save();
  }

  public function down() {
    $this->dropTable('funds');
  }
}
