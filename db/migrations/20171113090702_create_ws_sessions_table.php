<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateWsSessionsTable extends AbstractMigration
{
  public function up() {
    $this->table('ws_sessions', ['id' => false, 'primary_key' => ["session_id"], 'engine' => "InnoDB"])
      ->addColumn('session_id', 'string', ['null' => false, 'default' => '', 'limit' => 255])
      ->addColumn('session_expires', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('session_data', 'text', ['null' => true, 'limit' => 65535])
      ->addIndex(['session_expires'], ['name' => "session_expires", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('ws_sessions');
  }
}
