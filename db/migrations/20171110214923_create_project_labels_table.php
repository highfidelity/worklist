<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateProjectLabelsTable extends AbstractMigration
{
  public function up() {
    $this->table('project_labels', ['id' => false, 'primary_key' => ["project_id", "label_id"], 'engine' => "InnoDB"])
      ->addColumn('project_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('label_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('active', 'integer', ['null' => false, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addIndex(['project_id','label_id'], ['name' => "pl_props", 'unique' => true])
      ->save();
  }

  public function down() {
    $this->dropTable('project_labels');
  }
}
