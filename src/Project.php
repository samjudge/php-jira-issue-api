<?php 

include_once("Issue_Collection.php");
include_once("Issue.php");

class Project {
    
    private $host;
    private $key;
    private $usr;
    private $pwd;
    private $bauth;
    
    //cfm = custom_field_map
    public $cfm_on = true;
    private $cfms;
    //ch = curl handler
    private $ch;
    
    public function __construct($host,$key,$usr,$pwd){
        $this->host = $host;
        $this->key = $key;
        $this->usr = $usr;
        $this->pwd = $pwd;
        $this->bauth = $usr.":".$pwd;
        $this->ch = curl_init();
        //create the cfm
        curl_setopt_array($this->ch, array(
            CURLOPT_URL => $this->host."/rest/api/2/issue/createmeta?expand=projects.issuetypes.fields&projectKeys=".$key,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_USERPWD => $this->bauth,
            CURLOPT_RETURNTRANSFER => true
        ));
        $result = curl_exec($this->ch);
        $status_code = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
        if($status_code != 200){
            throw new Exception(
                "Error : CreateMeta : Returned Status Code ".$status_code
            );
        }
        $result = json_decode($result,true);
        try{
            $this->cfms = $this->init_custom_fields_maps($result);
        } catch (Exception $ex){
            throw $ex;
        }
        curl_close($this->ch);
    }
    
    public function get_cfms(){
        return $this->cfms;
    }
    
    public function get_host(){
        return $this->host;
    }
    
    public function get_bauth(){
        return $this->bauth;
    }
    
    public function get_pwd(){
        return $this->pwd;
    }
    
    public function ping(){
        $this->ch = curl_init();
        curl_setopt_array($this->ch, array(
            CURLOPT_URL => $this->host."/rest/api/2/mypermissions",
            CURLOPT_USERPWD => $this->bauth,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true
        ));
        $result = curl_exec($this->ch);
        $status_code = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
        if($status_code != 200){
            throw new Exception(
                "Error : MyPermissions/Ping : Returned Status Code ".$status_code
            );
        }
        curl_close($this->ch);
    }
    
    private function init_custom_fields_maps($project_metadata){
        $cfms = array();
        if(isset($project_metadata["projects"][0]["issuetypes"])){
            foreach($project_metadata["projects"][0]["issuetypes"] as $issuetype){
                $custom_fields = array();
                foreach($issuetype["fields"] as $field_name=>$details){
                    if(substr( $field_name, 0, 12 ) == "customfield_"){
                        $field_human_name = $details["name"];
                        $custom_fields[$field_human_name] = $field_name;
                    }
                }
                $cfms[$issuetype["name"]] = $custom_fields;
            }
        } else {
            throw new Exception("Error : MapCustomFields : Unable to find any field data for project issues");
        }
        return $cfms;
    }
    
    private function map_custom_fields($data){
        $issuetype = $data["issuetype"]["name"];
        if(!isset($this->cfms[$issuetype])) throw new Exception("Error : DemapCustomFields : No cfm for provided issuetype");
        foreach($this->cfms[$issuetype] as $human_name=>$custom_name){
            foreach($data as $k=>$v){
                if($k == $custom_name){
                    $data[$human_name] = $v;
                    unset($data[$k]);
                }
            }
        }
        return $data;
    }

    private function demap_custom_fields($data){
        $issuetype = $data["issuetype"]["name"];
        if(!isset($this->cfms[$issuetype]))throw new Exception("Error : DemapCustomFields : No cfm for provided issuetype");
        foreach($this->cfms[$issuetype] as $human_name=>$custom_name){
            foreach($data as $k=>$v){
                if($k == $human_name){
                    $data[$custom_name] = $v;
                    unset($data[$k]);
                }
            }
        }
        return $data;
    }
    
    private function clean_issue_data($data){
        if(!is_array($data)) throw new Exception("ERROR : data is not an array");
        if(!isset($data["summary"])) throw new Exception("ERROR : `summary` must be set");
        if(!isset($data["project"])) $data["project"] = array("key"=>$this->key);
        if(!isset($data["issuetype"])) $data["issuetype"] = array("name"=>"Task");
        if($this->cfm_on){
            try {
                $data = $this->demap_custom_fields($data);
            } catch(Exception $ex){
                throw $ex;
            }
        }
        $data = array(
            "fields"=>$data
        );
        return $data;
    }
    
    public function create_issue($data){
        $this->ch = curl_init();
        $target = $this->host."/rest/api/2/issue/";
        $data = $this->clean_issue_data($data);
        $data = json_encode($data);
        curl_setopt_array($this->ch,
            array(
                CURLOPT_URL => $target,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_USERPWD => $this->bauth,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_SSL_VERIFYPEER=>false,
                CURLOPT_SSL_VERIFYHOST=>false,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Content-Length: '.strlen($data)
                )
            )
        );
        $result = curl_exec($this->ch);
        $status_code = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
        if($status_code != 201){
            throw new Exception(
                "Error : CreateIssue : Returned Status Code ".$status_code
            );
        }
        curl_close($this->ch);
        return $result;
    }
    
    public function create_multiple_issues($datas, $is_atomic = true){
        $status_code = 0;
        $target = $this->host."/rest/api/2/issue/bulk";
        $result_inf = array();
        if($is_atomic == true){
            $bulk_data = array(
                "issueUpdates"=>array()
            );
            foreach($datas as $data){
                try{
                    $data = $this->clean_issue_data($data);
                    array_push($bulk_data["issueUpdates"],$data);
                } catch (Exception $ex){
                    if($is_atomic){
                        throw $ex;
                    }
                }
            }
            $this->ch = curl_init();
            $bulk_data = json_encode($bulk_data);
            curl_setopt_array($this->ch,
                array(
                    CURLOPT_URL => $target,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_USERPWD => $this->bauth,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_POSTFIELDS => $bulk_data,
                    CURLOPT_SSL_VERIFYPEER=>false,
                    CURLOPT_SSL_VERIFYHOST=>false,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Content-Length: '.strlen($bulk_data)
                    )
                )
            );
            $result = curl_exec($this->ch);
            array_push($result_inf,json_decode($result));
            $status_code = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
            if($status_code != 201){
                throw new Exception(
                    "Error : CreateIssueBulk : Returned Status Code ".$status_code
                );
            }
            curl_close($this->ch);
        } else {
            $errors = 0;
            $passes = 0;
            foreach($datas as $issue){
                try{
                    $result = $this->create_issue($issue);
                    array_push($result_inf,json_decode($result));
                    $passes++;

                } catch(Exception $ex){
                    $errors++;
                }
            }
            if($errors > 0){
                return $errors;
            }
        }
        return $result_inf;
    }
    
    public function issue_count(){
        $target = $this->host."/rest/api/2/search?fields=*none&maxResults=100000000";
        $this->ch = curl_init();
        curl_setopt_array($this->ch,
            array(
                CURLOPT_URL => $target,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_USERPWD => $this->bauth,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true
            )
        );
        $result = curl_exec($this->ch);
        $status_code = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
        if($status_code != 200){
            throw new Exception(
                "Error : Search-CountIssues : Returned Status Code ".$status_code
            );
        }
        $result = json_decode($result,true);
        
        $count = 0;
        if(isset($result["issues"])){
            $count = count($result["issues"]);
        }
        curl_close($this->ch);
        return $count;
    }
    
    public function query($jql = false, $result_limit = -1, $fields = array()){
        $target = $this->host."/rest/api/2/search";
        $data = array(
                "jql"=>$jql
        );
        if($result_limit < 0){
            $data["maxResults"] = $this->issue_count();
        } else {
            $data["maxResults"] = $result_limit;
        }
        if(count($fields) > 0){
            $data["fields"] = array();
            foreach($fields as $field){
                array_push($data["fields"],$field);
            }
        }
        $data = json_encode($data);
        //curl stuff
        $this->ch = curl_init();
        curl_setopt_array($this->ch,
            array(
                CURLOPT_URL => $target,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_USERPWD => $this->bauth,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_SSL_VERIFYPEER=>false,
                CURLOPT_SSL_VERIFYHOST=>false,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Content-Length: '.strlen($data)
                )
            )
        );
        $result = curl_exec($this->ch);
        $status_code = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
        if($status_code != 200){
            throw new Exception(
                "Error : Query : Returned Status Code ".$status_code
            );
        }
        curl_close($this->ch);
        $result = json_decode($result,true);
        $result_issues_data = $result["issues"];
        if($this->cfm_on){
            try {
                foreach($result_issues_data as $i=>$issue_data){
                    $mapped_issue_data =
                        $this->map_custom_fields($issue_data["fields"]);
                    $result_issues_data[$i]["fields"] = $mapped_issue_data;
                }
            } catch(Exception $ex){
                throw $ex;
            }
        }
        $n_issue_arra = array();
        foreach($result_issues_data as $issue_data){
            $n_issue = new Issue($issue_data);
            array_push($n_issue_arra,$n_issue);
        }
        $issue_collection = new Issue_Collection($n_issue_arra, $this);
        return $issue_collection;
    }
}


 ?>