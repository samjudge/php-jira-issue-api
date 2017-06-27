<?php

use PHPUnit\Framework\TestCase;

include_once("src/Project.php");
include_once("src/Issue_Collection.php");
include_once("src/Issue.php");

class Issue_Collection_Test extends TestCase{
    
    public $project;
    
    public function setUp(){
        $host = "https://dasdas.atlassian.net";
        $username = "admin";
        $password = "14mth3l4w";
        $project_key = "SOM";
        $this->project = new Project($host, $project_key ,$username, $password);
    }
    
    public function test_set_fields(){
        $data = array(
            "summary" => "(1) test_set_fields"
        );
        $this->project->create_issue($data);
        $issues = $this->project->query("summary ~ \"(1) test_set_fields\"");
        $data = array(
            "summary" => "(2) test_set_fields"
        );
        $good_set = true;
        try{
            $issues->set_fields($data);
        } catch(Exception $ex){
            throw $ex;
        }
        $this->assertTrue($good_set);
        $data = array("summary");
        try{
            $data = $issues->get_fields($data);
        } catch (Exception $ex){
            throw $ex;
        }
        var_dump($data);
        $contains_correct_text = false;
        if($data["summary"] == "(2) test_set_fields") $contains_correct_text = true;
        $this->assertTrue($contains_correct_text);
    }
    
    public function test_get_fields(){
        $data = array(
            "summary" => "test_get_fields"
        );
        $this->project->create_issue($data);
        $issues = $this->project->query("summary ~ \"test_get_fields\"");
        $data = array("summary");
        try{
            $data = $issues->get_fields($data);
        } catch (Exception $ex){
            throw $ex;
        }
        $contains_correct_text = false;
        if($data["summary"] == "test_get_fields") $contains_correct_text = true;
        $this->assertTrue($contains_correct_text);
    }
    
}

?>