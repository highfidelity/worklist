<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateAllAssetsTable extends AbstractMigration
{

  public function up() {
    $this->table('all_assets', ['engine' => "InnoDB"])
      ->addColumn('app', 'string', ['null' => false, 'default' => 'worklist', 'limit' => 50])
      ->addColumn('content_type', 'string', ['null' => false, 'default' => '', 'limit' => 50])
      ->addColumn('content', 'blob', ['null' => false, 'limit' => MysqlAdapter::BLOB_LONG])
      ->addColumn('size', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('filename', 'string', ['null' => false, 'default' => '', 'limit' => 50])
      ->addColumn('width', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('height', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('created', 'datetime', ['null' => false, 'default' => '1000-01-01 00:00:00'])
      ->addColumn('updated', 'datetime', ['null' => false, 'default' => '1000-01-01 00:00:00'])
      ->addColumn('s3bucket', 'string', ['null' => true, 'limit' => 64])
      ->addIndex(['filename'], ['name' => "image_name", 'unique' => true])
      ->save();
  }

  public function down() {
    $this->dropTable('all_assets');
  }

}
