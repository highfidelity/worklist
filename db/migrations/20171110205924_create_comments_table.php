<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateCommentsTable extends AbstractMigration
{
  public function up() {
    $this->table('comments', ['engine' => "InnoDB"])
      ->addColumn('comment_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('worklist_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('date', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
      ->addColumn('comment', 'text', ['null' => false, 'limit' => 65535])
      ->addColumn('visible', 'integer', ['null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('old_user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addIndex(['worklist_id'], ['name' => "worklist_id", 'unique' => false])
      ->addIndex(['comment'], ['name' => "c_ft", 'unique' => false, 'type' => 'fulltext'])
      ->save();
  }

  public function down() {
    $this->dropTable('comments');
  }
}
