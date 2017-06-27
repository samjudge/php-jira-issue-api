<?php

use PHPUnit\Framework\TestCase;

include_once("src/Project.php");
include_once("src/Issue_Collection.php");
include_once("src/Issue.php");

class Project_Test extends TestCase{
    
    public $project;
    
    public function setUp(){
        $host = "https://dasdas.atlassian.net";
        $username = "admin";
        $password = "14mth3l4w";
        $project_key = "SOM";
        $this->project = new Project($host, $project_key ,$username, $password);
    }
    
    public function test_ping(){
        //connection
        $ping_ok = true;
        try {
            $this->project->ping();
        } catch(Exception $e) {
            $ping_ok = false;
        }
        $this->assertTrue($ping_ok);
        //custom_field_mapping
        $cf_map = $this->project->get_cfms();
        $this->assertTrue(is_array($cf_map));
    }

    public function test_create_issue(){
        //good insert
        $issue_data = array(
            "summary" => "test_create_issue"
        );
        //auto-set :
        //type (task)
        //project key
        //author
        $ok_insert = true;
        try {
            $this->project->create_issue($issue_data);
        } catch(Exception $ex){
            $ok_insert = false;
        }
        $this->assertTrue($ok_insert);
        //bad data exception
        $issue_data = array();
        //no summary, thus rejected
        $bad_insert = false;
        try{
            $this->project->create_issue($issue_data);
        } catch (Exception $ex) {
            $bad_insert = true;
        }
        $this->assertTrue($bad_insert);
    }
    
    public function test_issue_count(){
        $good_count = true;
        try{
            $issue_count = $this->project->issue_count();
        } catch(Exception $e){
            $good_count = false;
        }
        $this->assertTrue($good_count);
        $this->assertGreaterThanOrEqual(0,$issue_count);
    }
    
    public function test_create_multiple_issues(){
        $inital_issue_count = $this->project->issue_count();
        $issue_data = [
            array("summary" => "(1) test_create_multiple_issues"),
            array("summary" => "(2) test_create_multiple_issues")
        ];
        $good_insert = true;
        try{
            $this->project->create_multiple_issues($issue_data); //deafult isAtomic = true
        } catch(Exception $ex){
            $good_insert = false;
        }
        $this->assertTrue($good_insert);
        $this->assertEquals($this->project->issue_count(),$inital_issue_count+2);
        $bad_insert = false;
        $issue_data = [
            array("summary" => "(3) test_create_multiple_issues"),
            array()
        ];
        $isAtomic = true;
        try{
            $this->project->create_multiple_issues($issue_data,$isAtomic);
        } catch(Exception $ex){
            $bad_insert = true;
        }
        $this->assertTrue($bad_insert);
        $this->assertEquals($this->project->issue_count(),$inital_issue_count+2);
        //non-atomic multi-insert (non-strict)
        $isAtomic = false;
        $errors = $this->project->create_multiple_issues($issue_data,$isAtomic);
        $this->assertEquals($errors,1);
        $this->assertEquals($this->project->issue_count(),$inital_issue_count+3);
    }

    public function test_query(){
        $issue_data = array(
            "summary" => "test_query",
            "my_custom_text_field" => "test_query"
        );
        $this->project->create_issue($issue_data);
        //using custom field map
        $is_good = true;
        try {
            $issues = $this->project->query("project = SOM");
        } catch (Exception $ex){
            var_dump($ex);
            $is_good = false;
        }
        $this->assertTrue($is_good);
        $this->assertGreaterThanOrEqual(1,count($issues));
        //bad query
        $is_bad = false;
        try {
            $issues = $this->project->query("hhlhlkhulo");
        } catch (Exception $ex){
            $is_bad = true;
        }
        $this->assertTrue($is_bad);
        //no result query
        $is_empty = false;
        try {
            $issues = $this->project->query("project = HAHAIMNOTAPROJECT");
        } catch (Exception $ex){
            $is_empty = true;
        }
        $this->assertTrue($is_empty);
    }
    
}

?>