<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateFilesTable extends AbstractMigration
{
  public function up() {
    $this->table('files', ['engine' => "InnoDB"])
      ->addColumn('userid', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('workitem', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('projectid', 'integer', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('mime', 'string', ['null' => false, 'limit' => 60])
      ->addColumn('title', 'string', ['null' => true, 'limit' => 60])
      ->addColumn('description', 'string', ['null' => true, 'limit' => 255])
      ->addColumn('url', 'string', ['null' => false, 'limit' => 120])
      ->addColumn('status', 'boolean', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('old_userid', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('is_scanned', 'boolean', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('scan_result', 'boolean', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addIndex(['userid'], ['name' => "workitem", 'unique' => false])
      ->addIndex(['is_scanned'], ['name' => "is_scanned", 'unique' => false])
      ->addIndex(['scan_result'], ['name' => "scan_result", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('files');
  }
}
