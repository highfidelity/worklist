<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateBackupBudgetLogTable extends AbstractMigration
{
  public function up() {
    $this->table('backup_budget_log', ['engine' => "InnoDB"])
      ->addColumn('giver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false, 'after' => 'id'])
      ->addColumn('receiver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('amount', 'decimal', ['null' => false, 'precision' => 10, 'scale' => 2])
      ->addColumn('reason', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('transfer_date', 'datetime', ['null' => false])
      ->addColumn('old_giver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('old_receiver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addIndex(['id'], ['name' => "id", 'unique' => true])
      ->addIndex(['id'], ['name' => "id_2", 'unique' => false])
      ->addIndex(['receiver_id'], ['name' => "receiver_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('backup_budget_log');
  }
}
