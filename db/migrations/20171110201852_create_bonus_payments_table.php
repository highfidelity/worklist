<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateBonusPaymentsTable extends AbstractMigration
{
  public function up() {
    $this->table('bonus_payments', ['engine' => "InnoDB"])
      ->addColumn('payer_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('receiver_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('amount', 'decimal', ['null' => false, 'precision' => 10, 'scale' => 2])
      ->addColumn('notes', 'text', ['null' => true, 'limit' => 65535])
      ->addColumn('date', 'datetime', ['null' => true])
      ->addColumn('paid', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('paid_date', 'datetime', ['null' => true])
      ->save();
  }

  public function down() {
    $this->dropTable('bonus_payments');
  }
}
