<?php

namespace bakery;

/*

 */
class orm extends \PDO{

    /*
    
     */
    public function __construct($host, $db, $user, $pass, $type='mysql'){
        try{
            parent::__construct("{$type}:host={$host};dbname={$db}", $user, $pass);

            $this->config['db'] = $db;
            $this->config['type'] = $type;

            // Set Attributes
            $this->setAttrbitues();

            // Load Tables
            $this->getTables();
        }
        catch(\Exception $e){
            throw new \Exception($e->getMessage());   
        }
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
            $orm->{$column} = $value;
            return $orm;
        }

        return $results;

    }

    public function findAll($table, $column = NULL, $value = NULL){
        
        // -- audit
        if(!is_null($column) && !is_null($value)){
            $constraint = " WHERE {$column} = :value";
            $values = array(':value' => $value);
        }
    
        $schema = $this->getTableSchema($table);
        $orm = $this->createOrm($table, $schema);

        $stmt = $this->prepare("SELECT * FROM {$table}{$constraint}");
        $stmt->setFetchMode(\PDO::FETCH_INTO, $orm);
        $stmt->execute($values);
        
        $results = $stmt->fetchAll();

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

class MissingRequiredField extends \Exception{
    public function __construct($message){
        parent::__construct($message);
    }
}
