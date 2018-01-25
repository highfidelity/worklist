<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateSchemaInfoTable extends AbstractMigration
{
  public function up() {
    $this->table('schema_info', ['engine' => "InnoDB"])
      ->addColumn('version', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();
  }

  public function down() {
    $this->dropTable('schema_info');
  }
}
