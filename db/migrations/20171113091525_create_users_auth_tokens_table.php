<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateUsersAuthTokensTable extends AbstractMigration
{
  public function up() {
    $this->table('users_auth_tokens', ['id' => false, 'primary_key' => ["user_id", "github_id"], 'engine' => "InnoDB"])
      ->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'signed' => false])
      ->addColumn('github_id', 'string', ['null' => false, 'default' => '', 'limit' => 50, 'collation' => "utf8_general_ci", 'encoding' => "utf8"])
      ->addColumn('auth_token', 'string', ['null' => true, 'limit' => 50, 'collation' => "utf8_general_ci", 'encoding' => "utf8"])
      ->save();
  }

  public function down() {
    $this->dropTable('users_auth_tokens');
  }
}
