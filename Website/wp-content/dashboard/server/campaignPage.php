<?php

require_once("klaviyo/klaviyo.php");
require_once("templates.php");
require_once("common.php");

?>

<style>
<?php


if(!Base::IsElementor()){ ?>

  .template {
		display:none;
  }

<?php
}
?>

	.errorText {
		color: red;
	}

	.okText {
		color: green;
	}

	.warningText {
		color: orange;
	}

	#tabError.e-active, #tabError-accordion.e-active {
		background-color: red;
	}

	#tabWarning.e-active, #tabWarning-accordion.e-active{
		background-color: orange;
	}

	#tabOK.e-activee, #tabOK-accordion.e-active{
		background-color: green;
	}

	#tabError:hover{
		background-color: red;
	}

	#tabWarning:hover{
		background-color: orange;
	}

	#tabOK:hover{
		background-color: green;
	}
</style>


<?php
	$config = json_decode(file_get_contents(getDataPath() . '/config.json'), true);
	$config_global = json_decode(file_get_contents(getDataPath() . '/config_global.json'), true);

    $klaviyo = new Klaviyo(getAccounts("klaviyo", $config["accounts"]), getAccounts("klaviyo", $config_global["accounts"]));
	$campaigns = $klaviyo->getCampaigns();//Campaign::getCampaigns([ "Test 1", "Test 2", "Test3", "Test4"], [ "2023-07-01T00:00:00+00:00" ], [ 120, 100, 50, 76 ], [ 80, 60, 19, 5 ], [ 12, 5, 1, 0 ], [3, 5, 3, 2]);
	$campaignsJson = json_encode(array_values($campaigns));

?>
<script>
<?php
	echo "campaigns = {$campaignsJson};";
?>


	insertData(campaigns, ".tabError", function(metric) {
			return metric["isError"];
		});

	insertData(campaigns, ".tabWarning", function(metric) {
			return metric["isWarning"];
		});

	insertData(campaigns, ".tabOK", function(metric) {
		return !metric["isError"] && !metric["isWarning"]; 
	});

	function insertData(campaigns, selector, filter){
		updateAndInsertTemplateRows(".templateCampaigns", ".templateCampaign", campaigns,  
		[ "campaignName", "name", "accountName", "accountName", "campaignId", "id" ], "metrics", [ "metricName", "name", 
		"thisPeriod", "thisPeriod", "lastPeriod", "lastPeriod", "deviation", "deviation", "class", "class", "arrow", "arrow", "days", "days" ], jQuery(selector), filter);

	}



</script>