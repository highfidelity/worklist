<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateNotesTable extends AbstractMigration
{
  public function up() {
    $this->table('notes', ['engine' => "InnoDB"])
      ->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('author_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('note', 'text', ['null' => false, 'limit' => 65535])
      ->addIndex(['user_id'], ['name' => "user_id", 'unique' => false])
      ->addIndex(['author_id'], ['name' => "author_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('notes');
  }
}
