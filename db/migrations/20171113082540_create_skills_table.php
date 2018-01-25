<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateSkillsTable extends AbstractMigration
{
  public function up() {
    $this->table('skills', ['engine' => "InnoDB"])
      ->addColumn('skill', 'string', ['null' => false, 'limit' => 45])
      ->addIndex(['skill'], ['name' => "skill", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('skills');
  }
}
