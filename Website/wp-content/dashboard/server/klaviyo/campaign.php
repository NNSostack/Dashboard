<?php

require_once (__DIR__ . "/../base.php");
require_once("common.php");


class Campaign extends Base{

	public ?string $name = "";
	public ?string $id = null;
	public array $metrics = [];
	public ?string $accountName = null;
	public ?string $accountId = null;

	function __construct($name, $accountName, $accountId) {
		parent::__construct("campaign", $name);	
		$this->name = $name;
		$this->accountName = $accountName;
		$this->accountId = $accountId;

    }
}