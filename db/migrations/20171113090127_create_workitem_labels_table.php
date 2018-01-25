<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateWorkitemLabelsTable extends AbstractMigration
{
  public function up() {
    $this->table('workitem_labels', ['id' => false, 'engine' => "InnoDB"])
      ->addColumn('workitem_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('label_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();
  }

  public function down() {
    $this->dropTable('workitem_labels');
  }
}
