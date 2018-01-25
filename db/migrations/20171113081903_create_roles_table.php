<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateRolesTable extends AbstractMigration
{
  public function up() {
    $this->table('roles', ['engine' => "InnoDB"])
      ->addColumn('project_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('role_title', 'string', ['null' => false, 'limit' => 65])
      ->addColumn('percentage', 'decimal', ['null' => false, 'precision' => 5, 'scale' => 2])
      ->addColumn('min_amount', 'decimal', ['null' => false, 'precision' => 10, 'scale' => 2])
      ->save();
  }

  public function down() {
    $this->dropTable('roles');
  }
}
