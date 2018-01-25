<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateLocationLatlongTable extends AbstractMigration
{
  public function up() {
    $this->table('location_latlong', ['engine' => "InnoDB"])
      ->addColumn('location', 'string', ['null' => false, 'limit' => 200, 'collation' => "latin1_swedish_ci", 'encoding' => "latin1"])
      ->addColumn('latlong', 'string', ['null' => false, 'limit' => 200, 'collation' => "latin1_swedish_ci", 'encoding' => "latin1"])
      ->save();
  }

  public function down() {
    $this->dropTable('location_latlong');
  }
}
