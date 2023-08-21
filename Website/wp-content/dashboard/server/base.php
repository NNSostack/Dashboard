<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');

class Base{
	private ?string $type = null;
	private ?string $typeName = null;

	function __construct($type, $typeName) {
		$this->type = $type;
		$this->typeName = $typeName;
    }

	static function logMsg($msg){
		//echo $msg;
		//file_put_contents( $_SERVER["DOCUMENT_ROOT"] . "/wp-content/nns.log", $msg . "\r\n", FILE_APPEND);
	}

	public function ToJson(){
		return json_encode($this);
	}

	public static function ToObject($json){
		return json_decode($json);	
	}

	public function IsElementor(){
		return isset($_GET["post"]);
	}
	
}