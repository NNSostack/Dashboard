<?php

require_once("curl_api_cached.php");
require_once(__DIR__ . "/common.php");

class Repositories{
	public static function GetHttpClient($apiKey, $cacheTimeoutInHours){
		return new Curl_Cached(getHash($apiKey), $cacheTimeoutInHours);
	}
}