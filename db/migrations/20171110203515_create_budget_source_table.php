<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateBudgetSourceTable extends AbstractMigration
{
  public function up() {
    $this->table('budget_source', ['engine' => "InnoDB"])
      ->addColumn('giver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('budget_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('source_budget_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('amount_granted', 'decimal', ['null' => true, 'default' => '0.00', 'precision' => 10, 'scale' => 2])
      ->addColumn('original_amount', 'decimal', ['null' => true, 'default' => '0.00', 'precision' => 10, 'scale' => 2])
      ->addColumn('source_data', 'string', ['null' => true, 'limit' => 255])
      ->addColumn('transfer_date', 'datetime', ['null' => true])
      ->addIndex(['budget_id'], ['name' => "budget_id", 'unique' => false])
      ->addIndex(['source_budget_id'], ['name' => "source_budget_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('budget_source');
  }
}
