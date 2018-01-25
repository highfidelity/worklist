<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateCountriesTable extends AbstractMigration
{
  public function up() {
    $this->table('countries', ['id' => false, 'primary_key' => ["country_id"], 'engine' => "InnoDB"])
      ->addColumn('country_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'identity' => 'enable'])
      ->addColumn('country_code', 'string', ['null' => true, 'limit' => 2, 'collation' => "latin1_swedish_ci", 'encoding' => "latin1"])
      ->addColumn('country_url', 'string', ['null' => true, 'limit' => 2, 'collation' => "latin1_swedish_ci", 'encoding' => "latin1"])
      ->addColumn('country_name', 'string', ['null' => true, 'limit' => 100, 'collation' => "latin1_swedish_ci", 'encoding' => "latin1"])
      ->addColumn('country_phone_prefix', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_SMALL, 'precision' => 5, 'signed' => false])
      ->addColumn('country_twilio_enabled', 'boolean', ['null' => true, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 3])
      ->addIndex(['country_code'], ['name' => "UK_countries_country_code", 'unique' => true])
      ->save();
  }

  public function down() {
    $this->dropTable('countries');
  }
}
