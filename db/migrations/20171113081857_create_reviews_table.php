<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateReviewsTable extends AbstractMigration
{
  public function up() {
    $this->table('reviews', ['engine' => "InnoDB"])
      ->addColumn('reviewer_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('reviewee_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('review', 'string', ['null' => true, 'limit' => 1024])
      ->addColumn('journal_notified', 'boolean', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addIndex(['reviewer_id'], ['name' => "reviewer_id", 'unique' => false])
      ->addIndex(['reviewee_id'], ['name' => "reviewee_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('reviews');
  }
}
