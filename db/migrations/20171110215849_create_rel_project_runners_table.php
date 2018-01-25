<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateRelProjectRunnersTable extends AbstractMigration
{
  public function up() {
    $this->table('rel_project_runners', ['id' => false, 'engine' => "InnoDB"])
      ->addColumn('project_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('runner_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();
  }

  public function down() {
    $this->dropTable('rel_project_runners');
  }
}
