<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateFeesTable extends AbstractMigration
{
  public function up() {
    $this->table('fees', ['engine' => "InnoDB"])
      ->addColumn('worklist_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('budget_id', 'integer', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('amount', 'decimal', ['null' => false, 'precision' => 10, 'scale' => 2])
      ->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('desc', 'text', ['null' => false, 'limit' => 65535])
      ->addColumn('date', 'datetime', ['null' => true])
      ->addColumn('paid', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('paid_date', 'datetime', ['null' => true])
      ->addColumn('bid_id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('user_paid', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('notes', 'text', ['null' => false, 'limit' => 65535])
      ->addColumn('category', 'string', ['null' => false, 'limit' => 32])
      ->addColumn('withdrawn', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('expense', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('rewarder', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('was_migrated', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3, 'comment' => "migrated from google spreadsheet - desc changed to Accepted Bid"])
      ->addColumn('old_user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('old_user_paid', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('bonus', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('payer_id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('fund_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('bid_notes', 'text', ['null' => true, 'limit' => 65535])
      ->addIndex(['worklist_id'], ['name' => "worklist_id", 'unique' => false])
      ->addIndex(['user_id'], ['name' => "user_id", 'unique' => false])
      ->addIndex(['date'], ['name' => "date", 'unique' => false])
      ->addIndex(['withdrawn'], ['name' => "withdrawn", 'unique' => false])
      ->addIndex(['rewarder'], ['name' => "rewarder", 'unique' => false])
      ->addIndex(['bonus'], ['name' => "bonus", 'unique' => false])
      ->addIndex(['paid','expense'], ['name' => "paid", 'unique' => false])
      ->addIndex(['notes'], ['name' => "notes", 'unique' => false, 'type' => 'fulltext'])
      ->save();
  }

  public function down() {
    $this->dropTable('fees');
  }
}
