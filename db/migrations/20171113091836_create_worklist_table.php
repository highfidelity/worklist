<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateWorklistTable extends AbstractMigration
{
  public function up() {
    $this->table('worklist', ['engine' => "InnoDB"])
      ->addColumn('summary', 'string', ['null' => false, 'limit' => 100])
      ->addColumn('budget_id', 'integer', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('creator_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('mechanic_id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('runner_id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('status', 'string', ['null' => false, 'default' => 'BIDDING', 'limit' => 32])
      ->addColumn('funded', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('notes', 'text', ['null' => true, 'limit' => 65535])
      ->addColumn('created', 'datetime', ['null' => false])
      ->addColumn('paid', 'string', ['null' => true, 'limit' => 8])
      ->addColumn('contractor', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('priority', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('old_creator_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('old_mechanic_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('old_runner_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('sandbox', 'string', ['null' => true, 'limit' => 255])
      ->addColumn('project_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('bug_job_id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('isprivate', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('is_bug', 'boolean', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('status_changed', 'datetime', ['null' => false])
      ->addColumn('test_status', 'string', ['null' => false, 'default' => 'test', 'limit' => 32])
      ->addColumn('code_reviewer_id', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('code_review_started', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('code_review_completed', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('is_internal', 'boolean', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('assigned_id', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addIndex(['priority'], ['name' => "priority", 'unique' => false])
      ->addIndex(['mechanic_id'], ['name' => "mechanic_id", 'unique' => false])
      ->addIndex(['status'], ['name' => "status", 'unique' => false])
      ->addIndex(['runner_id'], ['name' => "owner_id", 'unique' => false])
      ->addIndex(['project_id'], ['name' => "project_id", 'unique' => false])
      ->addIndex(['id'], ['name' => "id", 'unique' => false])
      ->addIndex(['summary','notes'], ['name' => "summary", 'unique' => false, 'type' => 'fulltext'])
      ->save();
  }

  public function down() {
    $this->dropTable('worklist');
  }
}
