<?php

function getDataPath(){
	return $_SERVER['DOCUMENT_ROOT']. "/dashboard_data";
}

function getHash($value){
	return hash("sha256", $value);
}


function getAccounts($accountType, $accounts){
    $new_array = [];
    
    for($i = 0; $i < count($accounts); $i++){
        $account = $accounts[$i];
        if($account["name"] == $accountType){
            array_push($new_array, $account);
        }
    }

	return $new_array;
}