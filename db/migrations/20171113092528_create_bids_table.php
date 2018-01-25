<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateBidsTable extends AbstractMigration
{
  public function up() {
    $this->table('bids', ['engine' => "InnoDB"])
      ->addColumn('bidder_id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('email', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('worklist_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('bid_amount', 'decimal', ['null' => false, 'precision' => 10, 'scale' => 2])
      ->addColumn('bid_created', 'datetime', ['null' => false])
      ->addColumn('bid_expires', 'datetime', ['null' => true])
      ->addColumn('bid_done_in', 'string', ['null' => true, 'limit' => 16])
      ->addColumn('bid_done', 'datetime', ['null' => true])
      ->addColumn('notes', 'text', ['null' => false, 'limit' => 65535])
      ->addColumn('accepted', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('withdrawn', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('old_bidder_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('expired_notify', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('past_notified', 'datetime', ['null' => true])
      ->addIndex(['worklist_id'], ['name' => "worklist_id", 'unique' => false])
      ->addIndex(['bidder_id'], ['name' => "bidder_id", 'unique' => false])
      ->addIndex(['bid_done'], ['name' => "bid_done", 'unique' => false])
      ->addIndex(['bid_created'], ['name' => "bid_created", 'unique' => false])
      ->addIndex(['accepted'], ['name' => "accepted", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('bids');
  }
}
