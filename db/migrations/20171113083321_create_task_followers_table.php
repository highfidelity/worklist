<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateTaskFollowersTable extends AbstractMigration
{
  public function up() {
    $this->table('task_followers', ['id' => false, 'engine' => "InnoDB"])
      ->addColumn('user_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('workitem_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false, 'after' => 'user_id'])
      ->addIndex(['user_id','workitem_id'], ['name' => "user_id", 'unique' => true])
      ->save();
  }

  public function down() {
    $this->dropTable('task_followers');
  }
}
