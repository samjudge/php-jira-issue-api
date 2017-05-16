<?php

class Issue {
    
    /**
    * @args $issue_map  issuemap is the result from a request to JIRA at `/rest/2/api/search`
    *                   containing at least 
    */
    public function __construct($issue_map = false){
        if($issue_map != false){
            $this->fields = $issue_map["fields"];
            $this->key = $issue_map["key"];
            $this->id = $issue_map["id"];
        }
    }
    
    public $id;
    public $key;
    public $fields = array();
}

?>