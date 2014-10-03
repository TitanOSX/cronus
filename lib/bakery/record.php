<?php

namespace bakery;

class record implements \arrayaccess{
    
    private $columns = [],
            $primary_key,
            $table,
            $pdo,
            $new_row = true;

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

    public function exists(){
        if(!is_null($this->columns['id']['value'])){
            return true;
        }

        return false;
    }

    public function isNew(){
        return $this->new_row;
    }

    public function __set($column, $args){
        $this->columns[$column]['value'] = $args;
    }

    public function __toString(){
        return (string)$this->{$this->primary_key};
    }

    public function __call($a, $b){
        return $this->columns[$a]['value'];
    }

    public function __get($column) {
        return $this->columns[$column]['value'];
    }

    public function fetchAll(){
        $fetch = [];
        foreach($this->columns as $field => $meta){
            $fetch[$field] = $meta['value'];
        }

        return $fetch;
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

        // Create Query
        foreach($this->columns as $column => $meta){
            if($meta['key'] != 1){
                
                if(!$meta['null']){
                    //throw new MissingRequiredField($column);
                }
                
                $prepared_columns[] = "{$column}=:$column";
                $prepared_values[":{$column}"] = $meta['value'];
            }
        }       

        // Execute Statement
        $stmt = $this->pdo->prepare("{$type}".implode(", ", $prepared_columns)."{$where}");
        $stmt->execute($prepared_values);

        // New Row, return new ID
        if(empty($primary['value'])){
            $this->new_row = true;
            echo $this->pdo->lastIsnsertId();
            $this->columns[$this->primary_key]['value'] = $this->pdo->lastIsnsertId();

            return $this->columns[$this->primary_key]['value'];
        }

        // Return affected rows
        return $stmt->rowCount();
    }

    public function delete() {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$this->primary_key} = ?");
        $stmt->execute([$this->columns[$this->primary_key]['value']]);

        return true;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->columns[] = $value;
        } else {
            $this->columns[$offset]['value'] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->columns[$offset]['value']);
    }
    
    public function offsetUnset($offset) {
        unset($this->columns[$offset]['value']);
    }
    
    public function offsetGet($offset) {
        return isset($this->columns[$offset]['value']) ? $this->columns[$offset]['value'] : null;
    }
}