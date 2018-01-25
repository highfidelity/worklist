<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateImportOnlyTable extends AbstractMigration
{
  public function up() {
    $this->table('import_only', ['engine' => "InnoDB"])
      ->addColumn('summary', 'string', ['null' => false, 'limit' => 100])
      ->addColumn('creator_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('mechanic_id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('owner_id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('status', 'string', ['null' => false, 'default' => 'BIDDING', 'limit' => 32])
      ->addColumn('notes', 'text', ['null' => true, 'limit' => 65535])
      ->addColumn('created', 'datetime', ['null' => false])
      ->addColumn('project', 'string', ['null' => true, 'limit' => 64])
      ->addColumn('paid', 'string', ['null' => true, 'limit' => 8])
      ->addColumn('contractor', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('priority', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('creator_nick', 'string', ['null' => false, 'limit' => 90])
      ->addColumn('runner_nick', 'string', ['null' => false, 'limit' => 90])
      ->addColumn('mechanic_nic', 'string', ['null' => false, 'limit' => 90])
      ->addColumn('mgmt_fee', 'string', ['null' => false, 'limit' => 12])
      ->addColumn('value_self', 'string', ['null' => false, 'limit' => 12])
      ->addColumn('value_contract', 'string', ['null' => false, 'limit' => 12])
      ->addColumn('expense', 'string', ['null' => false, 'limit' => 12])
      ->addIndex(['priority'], ['name' => "priority", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('import_only');
  }
}
