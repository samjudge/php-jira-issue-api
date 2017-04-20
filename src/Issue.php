<?php

class Issue {
    
    public function __construct($issue_map = false){
        if($issue_map != false){
            $this->fields = $issue_map["fields"];
            $this->key = $issue_map["key"];
        }
    }    
    
    public $key;
    public $fields = array();
}

?>