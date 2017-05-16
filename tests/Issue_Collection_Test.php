<?php

use PHPUnit\Framework\TestCase;

require_once("src/Project.php");
require_once("src/Issue_Collection.php");
require_once("src/Issue.php");

class Issue_Collection_Test extends TestCase{
    
    public $project;
    
    public function setUp(){
        $host = "http://10.10.0.46:8080";
        $username = "sam.judge";
        $password = "14mth3l4w";
        $project_key = "TPI";
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
        $contains_correct_text = false;
        if($data[0]["summary"] == "(2) test_set_fields") $contains_correct_text = true;
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
        if($data[0]["summary"] == "test_get_fields") $contains_correct_text = true;
        $this->assertTrue($contains_correct_text);
    }

    public function test_get_attachments_uris(){
        $data = array(
            "summary" => "test_get_attachments"
        );
        $this->project->create_issue($data);
        $issues_insert = $this->project->query("summary ~ \"test_get_attachments\"");
        $issues_insert->add_attachment("/home/bsystems/bamboo/xml-data/build-dir/JIAP-JIAP-JOB1/tests/", "testfile.txt");
        $issues_retrieved = $this->project->query("summary ~ \"test_get_attachments\"");
        $uri_data = $issues_retrieved->get_attachments_uris();
        $attachment_count = count($uri_data);
        $this->assertGreaterThan(0,$attachment_count);
    }

    public function test_add_attachment(){
       $data = array(
           "summary" => "test_add_attachments"
       );
       $this->project->create_issue($data);
       $to_add_attachment = $this->project->query("summary ~ \"test_add_attachments\"");
       $to_add_attachment->add_attachment("/home/bsystems/bamboo/xml-data/build-dir/JIAP-JIAP-JOB1/tests/", "testfile.txt");
       $with_attachment = $this->project->query("summary ~ \"test_add_attachments\"");
       $attachment_uris = $with_attachment->get_attachments_uris();
       $attachment_count = count($attachment_uris);
       $this->assertGreaterThan(0,$attachment_count);
    }

    public function test_remove_attachment(){
       //TODO
       return false;
    }
}

?>