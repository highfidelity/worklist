<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateWorkitemSkillsTable extends AbstractMigration
{
  public function up() {
    $this->table('workitem_skills', ['id' => false, 'engine' => "InnoDB"])
      ->addColumn('workitem_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('skill_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();
  }

  public function down() {
    $this->dropTable('workitem_skills');
  }
}
