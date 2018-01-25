<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateRelUsersFavoritesTable extends AbstractMigration
{
  public function up() {
    $this->table('rel_users_favorites', ['id' => false, 'engine' => "InnoDB"])
      ->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('favorite_user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10])
      ->addColumn('enabled', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addIndex(['user_id'], ['name' => "user_id", 'unique' => false])
      ->addIndex(['favorite_user_id'], ['name' => "favorite_user_id", 'unique' => false])
      ->save();
  }

  public function down() {
    $this->dropTable('rel_users_favorites');
  }
}
