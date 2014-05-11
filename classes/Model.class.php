<?php

class Model extends AppObject {
    protected $column = array();
    protected $table = '';

    public function __construct($values = array())
    {
        //parent::__construct();

        if (!$this->table) {
            // will use model name as default table name
            $table = preg_replace('/Model$/', '', get_class($this));
            $this->table = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $table));
        }

        $this->loadColumns();
        $this->setValues($values, false);
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->column)) {
            return $this->column[$name]->value;
        }
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->column)) {
            $col =& $this->column[$name];
            $col->value = $value;
            return $this;
        }
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __set(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __isset($name)
    {
        return isset($this->column[$name]);
    }

    public function __unset($name)
    {
        unset($this->column[$name]);
    }

    protected function setValues(array $values, $markAsTouched = true)
    {
        foreach($this->column as $name => $column) {
            if (isset($values[$name])) {
                $col =& $this->column[$name];
                $col->value = $values[$name];
                if (!$markAsTouched) {
                    $col->touched = false;
                }
            }
        }
        return $this;
    }

    protected function loadUnique($where)
    {
        $sql = 'SELECT * FROM `' . $this->table. '` WHERE ' . $where . ' LIMIT 1;';
        $result = mysql_query($sql);

        if ($result && (mysql_num_rows($result) == 1)) {
            $values = mysql_fetch_assoc($result);
            $this->setValues($values, false);
            return $this;
        }
        return false;
    }

    protected function loadMany($where, $order = '',  $limit = 0, $offset = 0) {
        $whereSql = $where ? ' WHERE ' . $where : '';
        $orderSql = $order ? ' ORDER BY ' . $order : '';
        $limitSql = ($limit > 0) ? ' LIMIT ' . $limit : '';
        $offsetSql = ($offset > 0) ? ' OFFSET ' . $offset : '';
        $sql = 'SELECT * FROM `' . $this->table. '`' . $whereSql . $orderSql . $limitSql . $offsetSql . ';';
        $result = mysql_query($sql);

        $ret = array();
        if ($result && (mysql_num_rows($result) > 0)) {
            $className = get_class($this);
            while($row = mysql_fetch_assoc($result)) {
                $ret[] = new $className($row);
            }
        }
        return $ret;

    }

    public function findById($id)
    {
        $where = sprintf('`id` = %d', (int)$id);
        return $this->loadUnique($where);
    }

    protected function getColumns()
    {
        $columns = array();
        $sql =  'SHOW COLUMNS FROM `' . $this->table . '`';
        $result = mysql_query($sql);
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $columns[] = $row;
            }
            return $columns;
        }
        return false;
    }

    protected function loadColumns() {
        $columns = $this->getColumns();
        foreach($columns as $column) {
            $name = $column['Field'];
            $type = $column['Type'];
            $this->column[$name] = new ModelColumn($name, $type);
        }
    }

    private function prepareData()
    {
        $columns = array();
        $values = array();
        foreach ($this->column as $name => $column) {
            if (!$column->touched) {
                continue;
            }
            if ($this->$name !== null) {
                $val = $this->$name;
                $columns[] = $name;
                $type = $column->type;
                if (preg_match('/(char|text|blob)/i', $type) === 1) {
                    $values[] =  "'" . mysql_real_escape_string($val) . "'";
                } else if (preg_match('/(date|time)/i', $type) === 1) {
                    if (preg_match('/CURRENT_(TIME|DATE)/', $val)) {
                        $values[] = $val;
                    } else {
                        $values[] =  "'" . mysql_real_escape_string($val) . "'";
                    }
                } else {
                    $values[] = $val;
                }
            }

        }

        return array(
            'columns' => $columns,
            'values' => $values
        );
    }

    public function insert()
    {
        $data = $this->prepareData();
        $sql = 'INSERT INTO `' . $this->table . '` (`' . implode('`,`', $data['columns']) . '`) VALUES (' . implode(',', $data['values']) . ')';
        $result = mysql_query($sql);
        if ($result) {
            $id_row = mysql_insert_id();
            return $id_row;
        }
        return false;
    }

    public function save()
    {
        $sql = "UPDATE `" . $this->table . "` SET ";
        $data = $this->prepareData();
        $columns = $data['columns'];
        $values = $data['values'];

        $id = (int) $this->id;
        $fields_sql = '';
        foreach ($columns as $index => $column) {
            $value = $values[$index];
            if ($value === NULL) {
                continue;
            }
            if (strlen($fields_sql) > 0) {
                $fields_sql .= ', ';
            }
            $fields_sql .= "`". $column . "` = {$value}";
        }
        if ($id == 0) {
            return null;
        }
        $sql .= $fields_sql . " WHERE `id` = `$id`;";
        return mysql_query($sql);
    }

    public function now() {
        $sql = 'SELECT NOW() AS ret';
        $result = mysql_query($sql);
        $row = mysql_fetch_assoc($result);
        return $row['ret'];
    }

    public function timezoneOffset() {
        $sql = 'SELECT TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW()) as ret;';
        $result = mysql_query($sql);
        $row = mysql_fetch_assoc($result);
        return (int) $row['ret'];
    }

}
