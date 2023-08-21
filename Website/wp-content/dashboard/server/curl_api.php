<?php

class Curl{
    
    var $isBasicAuthorization = false;
    var $token;
    var $curlObject = null;

    function __destruct() {
        $this->debugMsg("Destroying " . __CLASS__);
        if($this->curlObject){
            curl_close($this->curlObject);
        }
    }

    function debugMsg($msg){
        //echo $msg . '<br/>';
    }

    private function SetBasicAuthorization($username, $password){
        $this->isBasicAuthorization = true; 
        $this->token = $username . ":" . $password; 
    }    

	public function Init($url, $contentType = "application/json", $extraHeader = null){
    
        $this->debugMsg("<br/><strong>CurlInit: {$url}</strong>");
        //echo "<br/><br/>**************** " . $url . "**********************<br/>";
        $ch = curl_init();

        $headers = array ("Content-Type: {$contentType}");
        if($extraHeader != null){
            if(is_array($extraHeader)){
                foreach($extraHeader as $eh){
                    array_push($headers, $eh);
                }
            }
            else{
                array_push($headers, $extraHeader);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if($this->isBasicAuthorization){
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->token);  
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $this->curlObject = $ch;
    }
    
    public function Get(){
        curl_setopt($this->curlObject, CURLOPT_CUSTOMREQUEST, "GET");
        return $this->Exec();
    }

    public function Post($postFields = ''){
        curl_setopt($this->curlObject, CURLOPT_CUSTOMREQUEST, "POST");
        return $this->Exec($postFields);
    }

    private function Exec($postFields = ''){
        $result = $this->ExecAndReturnResult($postFields);
        //echo $result;
        $jsonObj = json_decode($result, true);
        return $jsonObj;
    }

    private function ExecAndReturnResult($postFields = ''){
        $ch = $this->curlObject;
        $postFields = str_replace("'", '"', $postFields);
        $this->debugMsg($postFields);
        if($postFields != ''){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }

        //curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);

        //$info = curl_getinfo($ch);
        //print_r($info['request_header']);

        $this->debugMsg("CurlExec: {$result}");

        return $result;
    }
}