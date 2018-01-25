<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreatePseudoUsersTable extends AbstractMigration
{
  public function up() {
    $this->table('pseudo_users', ['engine' => "InnoDB"])
      ->addColumn('username', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('password', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('added', 'datetime', ['null' => false])
      ->addColumn('fb_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 19])
      ->addColumn('company_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('company_admin', 'boolean', ['null' => false, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('nickname', 'string', ['null' => false, 'limit' => 40])
      ->addColumn('confirm', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('confirm_string', 'string', ['null' => false, 'limit' => 10])
      ->addColumn('company_confirm', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('features', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('phone', 'string', ['null' => false, 'limit' => 16])
      ->addColumn('country', 'string', ['null' => false, 'limit' => 2])
      ->addColumn('provider', 'string', ['null' => false, 'limit' => 64])
      ->addColumn('confirm_phone', 'string', ['null' => false, 'limit' => 4])
      ->addColumn('skill', 'string', ['null' => true, 'limit' => 100])
      ->addColumn('team', 'string', ['null' => true, 'limit' => 100])
      ->addIndex(['company_id'], ['name' => "company_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('pseudo_users');
  }
}
