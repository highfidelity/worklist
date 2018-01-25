<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreatePaypalAdminsTable extends AbstractMigration
{
  public function up() {
    $this->table('paypal_admins', ['engine' => "InnoDB"])
      ->addColumn('user', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('password', 'string', ['null' => false, 'limit' => 100])
      ->save();
  }

  public function down() {
    $this->dropTable('paypal_admins');
  }
}
