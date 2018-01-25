<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateProjectUsersTable extends AbstractMigration
{
  public function up() {
    $this->table('project_users', ['engine' => "InnoDB"])
      ->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'id'])
      ->addColumn('project_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('checked_out', 'boolean', ['null' => false, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addIndex(['user_id'], ['name' => "user_id", 'unique' => false])
      ->addIndex(['project_id'], ['name' => "project_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('project_users');
  }
}
