<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateRelProjectCodeReviewersTable extends AbstractMigration
{
  public function up() {
    $this->table('rel_project_code_reviewers', ['engine' => "InnoDB"])
      ->addColumn('project_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('code_reviewer_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->save();
  }

  public function down() {
    $this->dropTable('rel_project_code_reviewers');
  }
}
