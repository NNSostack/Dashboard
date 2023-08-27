<?php

require_once("klaviyo/klaviyo.php");
require_once("templates.php");
require_once("common.php");

?>

<style>
<?php


if(!Base::IsElementor()){ ?>

  .template, .dataTabs {
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
	$accounts = $klaviyo->getAccounts();
	$campaignsJson = json_encode(array_values($campaigns));
	$accountsJson = json_encode($accounts);
?>
<script>
<?php
	echo "campaigns = {$campaignsJson};";
	echo "accounts = {$accountsJson};";
?>	

	updateData();

	function updateData(accountId = '', timeout = 1000){
		jQuery('.dataTabs').hide();	
		jQuery('.spinner').fadeIn();
		jQuery('.template').hide();

		jQuery('.templateCampaignsAdded').remove();

		hasErrors = insertData(campaigns, ".tabError", function(metric, parent) {
				return metric["isError"] && (accountId == '' || accountId == parent["accountId"]);
			});

		hasWarnings = insertData(campaigns, ".tabWarning", function(metric, parent) {
				return metric["isWarning"] && (accountId == '' || accountId == parent["accountId"]);
			});

		insertData(campaigns, ".tabOK", function(metric, parent) {
			return !metric["isError"] && !metric["isWarning"] && (accountId == '' || accountId == parent["accountId"]);
		});

		clickButton = "#tabError";
		if(hasErrors == 0){
			jQuery('.templateNoErrors').show();
			if(hasWarnings == 0){
				jQuery('.templateNoWarnings').show();
				clickButton = "#tabOK";

			}
			else{
				clickButton = "#tabWarning";
			}
		}

		setTimeout(function(){ 
			if(clickButton != ''){
				jQuery(clickButton).click();
			}
			jQuery('.dataTabs').fadeIn();
			jQuery(".spinner").hide(); 
		}, timeout );
	}

	for(iA = 0; iA < accounts.length; iA++){
		jQuery("#ddAccounts").append(
			jQuery('<option>', { value: accounts[iA]["id"], text : accounts[iA]["name"] })
		);
	}

	

	jQuery('#ddAccounts').on('change', function(){
		accountId = jQuery(this).val();
		updateData(accountId, 500);

	});

	function insertData(campaigns, selector, filter){
		return updateAndInsertTemplateRows(".templateCampaigns", ".templateCampaign", campaigns,  
		[ "campaignName", "name", "accountName", "accountName", "campaignId", "id" ], "metrics", [ "metricName", "name", 
		"thisPeriod", "thisPeriod", "lastPeriod", "lastPeriod", "deviation", "deviation", "class", "class", "arrow", "arrow", "days", "days" ], jQuery(selector), filter);

	}



</script>