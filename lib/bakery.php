<?php

namespace bakery;

/*

 */
class orm extends \PDO{

    /*
    
     */
    public function __construct($host, $db, $user, $pass, $type='mysql'){
        parent::__construct("{$type}:host={$host};dbname={$db}", $user, $pass);
        
        $this->config['db'] = $db;
        $this->config['type'] = $type;

        // Set Attributes
        $this->setAttrbitues();

        // Load Tables
        $this->getTables();

    }

    /*
    
     */
    private function getTables(){
        $tables = [];
        $raw_tables = $this->query("show tables")->fetchAll(\PDO::FETCH_ASSOC);
        foreach($raw_tables as $table){
            $tables[] = $table['Tables_in_'.$this->config['db']];
        }

        $this->tables = $tables;
    }

    /*
    
     */
    public function setAttrbitues(){
        //$this->setAttribute(\PDO::ATTR_AUTOCOMMIT,0);
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
    }

    private function getTableSchema($table){
        $stmt = $this->prepare("SHOW COLUMNS FROM `$table`");
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function createOrm($table, $schema){
        
        $orm = new record($table, $this);

        if(!empty($schema) && is_array($schema)){
            foreach($schema as $column){
                $orm->setColumn($column['Field'], 'value', $column['Default']);
                $orm->setColumn($column['Field'], 'type', $column['Type']);
                $orm->setColumn($column['Field'], 'null', ($column['Null'] == 'YES' ? true : false));
                $orm->setColumn($column['Field'], 'key', ($column['Key'] == 'PRI' ? true : false));
            }
        }

        return $orm;
    }

    public function create($table){
        
        if( in_array($table, $this->tables) === false ){
            return false;
        }

        $schema = $this->getTableSchema($table);
        $orm = $this->createOrm($table, $schema);

        return $orm;        
    }
    /*
    
     */
    public function findOrCreate($table, $column, $value){
        
        if( in_array($table, $this->tables) === false ){
            return false;
        }

        $schema = $this->getTableSchema($table);
        $orm = $this->createOrm($table, $schema);

        $stmt = $this->prepare("SELECT * FROM {$table} WHERE {$column} = :value");
        $stmt->setFetchMode(\PDO::FETCH_INTO, $orm);
        $stmt->execute(array(':value' => $value));
        $results = $stmt->fetch();

        if(empty($results)){
            return $orm;
        }

        return $results;

    }

    public function __call($a, $b){
        if(preg_match("`find_([a-z_-]+)_by_([a-z0-9-_]+)`i", $a, $matches)){
            $table = $matches[1];
            $column = $matches[2];
            $value = $b[0];

            return $this->findOrCreate($table, $column, $value);
        }
    }
}

class record {
    
    private $columns = [],
            $primary_key,
            $table,
            $pdo;

    public function __construct($table, $conn){
        
        $this->table = $table;
        $this->pdo = $conn;

        return $this;
    }

    public function setColumn($column, $attr, $value){
        if(!isset($this->columns[$column])){
            $this->columns[$column] = [];
        }

        if( $attr == 'key' && $value === true ){
            $this->primary_key = $column;
        }

        $this->columns[$column][$attr] = $value;
    }

    public function __set($column, $args){
        $this->columns[$column]['value'] = $args;
    }

    public function __toString(){
        return $this->{$this->primary_key};
    }

    /*public function __call($a, $b){
        if(substr($a, 0, 8) == 'find_by_'){
            print_r(func_get_args());
        }
    }*/

    public function __get($column) {
        return $this->columns[$column]['value'];
    }

    public function hasColumn($column){
        if(array_key_exists($column, $this->columns)){
            return true;
        }

        return false;
    }

    public function save(){
        
        $this->date_modified = time();

        $prepared_columns = [];
        $prepared_values = [];

        $primary = $this->columns[$this->primary_key];

        if(!empty($primary['value'])){
            $type = "UPDATE {$this->table} SET ";
            $where = " WHERE {$this->primary_key} = :{$this->primary_key}";
            $prepared_values[":{$this->primary_key}"] = $primary['value'];
        }
        else{
            $this->date_created = $this->date_modified;
            $type = "INSERT INTO {$this->table} SET ";   
        }

        foreach($this->columns as $column => $meta){
            if($meta['key'] != 1){
               $prepared_columns[] = "{$column}=:$column";
               $prepared_values[":{$column}"] = $meta['value'];
            }
        }       

        $stmt = $this->pdo->prepare("{$type}".implode(", ", $prepared_columns)."{$where}");
        $stmt->execute($prepared_values);
        
        return $stmt->rowCount();
    }

}
