<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateProjectsTable extends AbstractMigration
{
  public function up() {
    $this->table('projects', ['id' => false, 'primary_key' => ["project_id"], 'engine' => "InnoDB"])
      ->addColumn('project_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'identity' => 'enable'])
      ->addColumn('name', 'string', ['null' => false, 'limit' => 100])
      ->addColumn('description', 'string', ['null' => false, 'limit' => 1000])
      ->addColumn('website', 'text', ['null' => true, 'limit' => 65535])
      ->addColumn('budget', 'float', ['null' => false, 'precision' => 12])
      ->addColumn('repository', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('repo_type', 'enum', ['null' => false, 'default' => 'svn', 'limit' => 3, 'values' => ['svn','git']])
      ->addColumn('github_id', 'string', ['null' => true, 'limit' => 50])
      ->addColumn('github_secret', 'string', ['null' => true, 'limit' => 50])
      ->addColumn('contact_info', 'string', ['null' => false, 'limit' => 255])
      ->addColumn('last_commit', 'datetime', ['null' => false])
      ->addColumn('active', 'boolean', ['null' => false, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('owner_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('fund_id', 'string', ['null' => false, 'default' => '3', 'limit' => 16])
      ->addColumn('testflight_team_token', 'string', ['null' => true, 'limit' => 50])
      ->addColumn('testflight_enabled', 'boolean', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('logo', 'string', ['null' => true, 'limit' => 30])
      ->addColumn('cr_anyone', 'boolean', ['null' => true, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('cr_3_favorites', 'boolean', ['null' => true, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('cr_project_admin', 'boolean', ['null' => true, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('cr_job_runner', 'boolean', ['null' => true, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('cr_users_specified', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('internal', 'integer', ['null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('hipchat_notification_token', 'string', ['null' => true, 'limit' => 40])
      ->addColumn('hipchat_room', 'string', ['null' => true, 'limit' => 40])
      ->addColumn('hipchat_color', 'string', ['null' => true, 'limit' => 20])
      ->addColumn('hipchat_enabled', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('slack_enabled', 'boolean', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addColumn('slack_token', 'string', ['null' => true, 'limit' => 40])
      ->addColumn('slack_room', 'string', ['null' => true, 'limit' => 40])
      ->addColumn('creation_date', 'datetime', ['null' => true])
      ->addColumn('short_description', 'string', ['null' => true, 'limit' => 100])
      ->addColumn('require_sandbox', 'integer', ['null' => false, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addIndex(['project_id'], ['name' => "project_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('projects');
  }
}
