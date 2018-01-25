<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateLabelsTable extends AbstractMigration
{
  public function up() {
    $this->table('labels', ['engine' => "InnoDB"])
      ->addColumn('label', 'string', ['null' => true, 'limit' => 45, 'collation' => "latin1_swedish_ci", 'encoding' => "latin1"])
      ->addIndex(['label'], ['name' => "skill", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('labels');
  }
}
