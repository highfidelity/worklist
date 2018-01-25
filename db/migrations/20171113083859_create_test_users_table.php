<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateTestUsersTable extends AbstractMigration
{
  public function up() {
    $this->table('test_users', ['engine' => "InnoDB"])
      ->addColumn('username', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('password', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('added', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('nickname', 'string', ['null' => false, 'limit' => 40])
      ->addColumn('confirm', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('confirm_string', 'string', ['null' => false, 'limit' => 10])
      ->addColumn('about', 'text', ['null' => false, 'limit' => 65535])
      ->addColumn('contactway', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('payway', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('skills', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('timezone', 'string', ['null' => false, 'limit' => 10])
      ->addColumn('is_runner', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('is_payer', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('journal_nick', 'string', ['null' => true, 'limit' => 60])
      ->addColumn('phone', 'string', ['null' => true, 'limit' => 15])
      ->addColumn('smsaddr', 'string', ['null' => true, 'limit' => 255])
      ->addColumn('country', 'char', ['null' => true, 'limit' => 2])
      ->addColumn('provider', 'string', ['null' => true, 'limit' => 255])
      ->save();
  }

  public function down() {
    $this->dropTable('test_users');
  }
}
