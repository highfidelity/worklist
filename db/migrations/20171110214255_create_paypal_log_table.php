<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreatePaypalLogTable extends AbstractMigration
{
  public function up() {
    $this->table('paypal_log', ['engine' => "InnoDB"])
      ->addColumn('fee_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('payment_gross', 'decimal', ['null' => false, 'precision' => 10, 'scale' => 2])
      ->addColumn('payment_fee', 'decimal', ['null' => false, 'precision' => 10, 'scale' => 2])
      ->addColumn('status', 'string', ['null' => false, 'limit' => 50])
      ->addColumn('deny_reason', 'string', ['null' => true, 'limit' => 255])
      ->addColumn('masspay_txn_id', 'string', ['null' => false, 'limit' => 25])
      ->addColumn('currency', 'string', ['null' => true, 'limit' => 3])
      ->addColumn('payee_paypal_email', 'string', ['null' => true, 'limit' => 255])
      ->addColumn('date_created', 'datetime', ['null' => false])
      ->addColumn('date_updated', 'datetime', ['null' => false])
      ->addColumn('txn_verify', 'string', ['null' => false, 'limit' => 50])
      ->addColumn('masspay_run_status', 'string', ['null' => false, 'limit' => 50])
      ->addColumn('masspay_status_reason', 'string', ['null' => true, 'limit' => 255])
      ->addIndex(['fee_id'], ['name' => "fee_id", 'unique' => false])
      ->addIndex(['masspay_txn_id'], ['name' => "masspay_txn_id", 'unique' => false])
      ->addIndex(['status'], ['name' => "status", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('paypal_log');
  }
}
