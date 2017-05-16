<?php 

require_once("Project.php");
require_once("Issue.php");

class Issue_Collection {
    
    public $issues;
    public $project;
    
    public function __construct($data,$project){
        $this->issues = $data;
        $this->project = $project;
    }
    
    public function get_fields($fields){
        $issue_list = array();
        foreach($this->issues as $issue){
            $got_fields = array();
            foreach($fields as $field_name){
                if(array_key_exists($field_name,$issue->fields)){
                    $got_fields[$field_name] = $issue->fields[$field_name];
                } else {
                    throw new Exception("Error : No such field ". $field_name);
                }
            }
            array_push($issue_list,$got_fields);
        }
        return $issue_list;
    }
    
    public function set_fields($fields){
        $updates = array();
        foreach($this->issues as $stored_issue){
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
    
    /**
    * get_attachments_uris
    * returns data in the format array(array("id"=>val,"uri"=>val,"filename"=>val,"size"=>val),...)
    * @args $filename get all the attachments that are associated with the issues, [] for none [attachment1,attachment2]
    */
    public function get_attachments_uris(){
        $ch = curl_init();
        $results = array();
        foreach($this->issues as $issue){
            $target =  $this->project->get_host()."/rest/api/2/issue/".$issue->key;
            curl_setopt_array($ch,
                array(
                    CURLOPT_URL => $target,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_USERPWD => $this->project->get_bauth(),
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_SSL_VERIFYPEER=>false,
                    CURLOPT_SSL_VERIFYHOST=>false,
                )
            );
            $data = curl_exec($ch);
            $error = curl_error($ch);
            if($error){
                echo "ERROR : UNABLE TO GET ATTACHED FILE : $error";
                return false;
            }
            $data = json_decode($data, true);
            $result = array(
                "uri"=>$data["fields"]["attachment"],
                "id"=>$data["id"]
            );
            $results[] = $result;
        }
        return $results;
    }

    /**
    * get_attachments
    * @args $filename get all the attachments that are associated with the issues, [] for none [attachment1,attachment2]
    */
    public function get_attachments(){
        //get attachment
        //download attachment to local memory
        //TODO
        return [];
    }

    /**
    * remove_attachment
    * @args $filename the filename of the attachment to remove
    */
    public function remove_attachment($filename){
        //TODO
    }

    /**
    * add_attachment
    * @args $path_to_file Add a new attachment to the non-simple "attachments" issue field. The path to the location of the file (without filename or final '/') from the webroot.
    * @args $filename The file's name+extension
    */
    public function add_attachment($path_to_file, $filename){
        $curl = curl_init();
        $headers = array( //TODO: #thinking
            'X-Atlassian-Token:nocheck',
            'Content-Type: multipart/form-data'
        );
        $file = new CURLFile($path_to_file.$filename);
        $file->setPostFilename($filename);
        $data["file"] = $file;
        foreach($this->issues as $issue){
            $target = $this->project->get_host()."/rest/api/2/issue/".$issue->id ."/attachments";
            curl_setopt_array($curl,
                array(
                    CURLOPT_URL=>$target,
                    CURLOPT_POST =>true,
                    CURLOPT_VERBOSE=>true,
                    CURLOPT_POSTFIELDS=>$data,
                    CURLOPT_SSL_VERIFYHOST=>false,
                    CURLOPT_SSL_VERIFYHOST=>false,
                    CURLOPT_RETURNTRANSFER=>true,
                    CURLOPT_HEADER=>false,
                    CURLOPT_HTTPHEADER=>$headers,
                    CURLOPT_USERPWD=>$this->project->get_bauth()
                )
            );
            $result = curl_exec($curl);
            $error = curl_error($curl);
            if($error){
                echo "ERROR : UNABLE TO ATTACH FILE : $error";
                return false;
            }
        }
        curl_close($curl);
        return true;
    }
    
}


 ?>