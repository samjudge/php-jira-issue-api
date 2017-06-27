<?php 

include_once("Project.php");
include_once("Issue.php");

class Issue_Collection {
    
    public $fields;
    public $issues;
    
    public function __construct($data,$project){
        $this->issues = $data;
        $this->project = $project;
    }
    
    public function get_fields($fields){
        $got_fields = array();
        foreach($this->issues as $issue){
            foreach($fields as $field_name){
                if(isset($issue->fields[$field_name])){
                    $got_fields[$field_name] = $issue->fields[$field_name];
                } else {
                    throw new Exception("Error : No such field ". $field_name);
                }
            }
        }
        return $got_fields;
    }
    
    public function set_fields($fields){
        $updates = array();
        foreach($this->issues as $stored_issue){
            //var_dump($stored_issue);
            $update_issue = new Issue();
            $update_issue->key = $stored_issue->key;
            foreach($fields as $field_name=>$field_value){
                if(isset($stored_issue->fields[$field_name])){
                    $update_issue->fields[$field_name] = $field_value;
                }
            }
            if(count($update_issue) > 0){
                array_push($updates, $update_issue);
            }
        }
        //echo json_encode($updates[0]);
        //multi-cURL stuff
        $mh = curl_multi_init();
        $chs = array();
        foreach($updates as $issue){
            $ch = curl_init();
            $target = $this->project->get_host()."/rest/api/2/issue/".$issue->key;
            //echo $target;
            $update = array(
                "fields"=>$issue->fields
            );
            curl_setopt_array($ch,
                array(
                    CURLOPT_URL => $target,
                    CURLOPT_CUSTOMREQUEST => "PUT",
                    CURLOPT_USERPWD => $this->project->get_bauth(),
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_SSL_VERIFYPEER=>false,
                    CURLOPT_SSL_VERIFYHOST=>false,
                    CURLOPT_POSTFIELDS => json_encode($update),
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Content-Length: '.strlen(json_encode($update))
                    )
                )
            );
            curl_multi_add_handle($mh,$ch);
            array_push($chs,$ch);
        }
        
        $active = NULL;
        $multi_result = NULL;
        
        do {
            $multi_result = curl_multi_exec($mh, $active);
        } while ($multi_result == CURLM_CALL_MULTI_PERFORM);

        while ($active && $multi_result == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $multi_result = curl_multi_exec($mh, $active);
                } while ($multi_result == CURLM_CALL_MULTI_PERFORM);
            }
        }
        
        foreach($chs as $ch){
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $content = curl_multi_getcontent($ch);
            if($status_code != 204){
                //echo $content;
                throw new Exception(
                    "a request responded with status code : ".$status_code
                );
            }
        }
        //update the local values in this issue collection
        foreach($this->issues as $issue){
            foreach($fields as $field=>$value){
                if(isset($issue->fields[$field])){
                    $issue->fields[$field] = $value;
                }
            }
        }
        return true;
    }

    
    
}


 ?>