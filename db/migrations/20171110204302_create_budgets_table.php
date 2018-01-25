<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateBudgetsTable extends AbstractMigration
{
  public function up() {
    $this->table('budgets', ['engine' => "InnoDB"])
      ->addColumn('giver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('receiver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('amount', 'decimal', ['null' => false, 'precision' => 10, 'scale' => 2])
      ->addColumn('remaining', 'decimal', ['null' => true, 'default' => '0.00', 'precision' => 10, 'scale' => 2])
      ->addColumn('original_amount', 'decimal', ['null' => true, 'default' => '0.00', 'precision' => 10, 'scale' => 2])
      ->addColumn('reason', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('notes', 'text', ['null' => true, 'limit' => 65535])
      ->addColumn('transfer_date', 'datetime', ['null' => false])
      ->addColumn('active', 'boolean', ['null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('source_data', 'string', ['null' => true, 'limit' => 255])
      ->addColumn('source_budget_id', 'integer', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('seed', 'boolean', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('old_giver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('old_receiver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addIndex(['id'], ['name' => "id", 'unique' => true])
      ->addIndex(['id'], ['name' => "id_2", 'unique' => false])
      ->addIndex(['giver_id'], ['name' => "giver_id", 'unique' => false])
      ->addIndex(['receiver_id'], ['name' => "receiver_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('budgets');
  }
}
