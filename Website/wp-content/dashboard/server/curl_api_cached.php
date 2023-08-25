<?php

require_once("curl_api.php");
require_once("common.php");

class Curl_Cached extends Curl{
    private string $hash;
    private string $url;
    private int $cachTimeout;

	function __construct($hash, $cachTimeout) {
		$this->hash = $hash;
        $this->cachTimeout = $cachTimeout;
    }

	public function Init($url, $contentType = "application/json", $extraHeader = null){
        $this->url = $url;
        parent::Init($url, $contentType, $extraHeader);    
    }
    
    public function Get(){
        
        $key = $this->url;
        $ret = $this->getCacheFileContent($key);

        if($ret == null){
            $ret = parent::Get();
            $this->setCacheFileContent($key, $ret);
        }

        return $ret;
    }

    public function Post($postFields = ''){
        $key = $this->url . "_" . $postFields;
        $ret = $this->getCacheFileContent($key);

        if($ret == null){
            $ret = parent::Post($postFields);
            $this->setCacheFileContent($key, $ret);
        }

        return $ret;
    }

    public function getCacheFileContent($key){
        if(isset($_GET["debug"])){
            echo "<br/>HASH: " . $this->hash . "<br/>";
        }

        $cacheFile = $this->getCacheFile($key);

        $ret = null;

        if(file_exists($cacheFile)){
            if(time()-filemtime($cacheFile) > $this->cacheTimeout * 3600){
                $ret = json_decode(file_get_contents($cacheFile), true);
                return $ret;
            }
        }

        return null;

    }

    public function setCacheFileContent($key, $obj){
        
        if($obj["errors"] != null){
            return;
        }
    
        $cacheFile = $this->getCacheFile($key);

        file_put_contents($cacheFile, json_encode($obj));
    }

    function getCacheFile($key){
        $path = getDataPath() . "/cache/";
        
        if(!is_dir($path)){
            mkdir($path);
        }

        $path .= $this->hash;

        if(!is_dir($path)){
            mkdir($path);
        }

        $cacheFile = $path . "/" . getHash($key) . ".json";
        return $cacheFile;
    }
}