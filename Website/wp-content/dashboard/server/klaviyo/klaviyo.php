<?php

require_once("common.php");
require_once("campaign.php");
require_once(__DIR__ . "/../repositories.php");
require_once(__DIR__ . "/../common.php");

class Klaviyo {
	private ?string $apiKey = null;
	private string $revision = "2023-06-15";
    private array $metrics = [];
    private array $campaignIds = [];
    private ?array $accounts = null;  
    private ?array $accountsGlobal = null;    
    private ?string $accountName = null;

	private string $campaignBodyTemplate = '{ 
    "data": {
        "type": "metric-aggregate",
        "attributes": {
            "metric_id": "[metric_id]",
            "measurements": [ "unique" ],
            "interval": "day",
            "page_size": 5000,
            "by": [ "Campaign Name" ],
            "filter": [
                "greater-or-equal(datetime,[start_date])",
                "less-than(datetime,[end_date])"
            ],
            "timezone": "UTC"
        }
    }
}';

	function __construct($accounts, $accountsGlobal) {
        $this->accounts = $accounts;
        $this->accountsGlobal = $accountsGlobal;
    }

    private function getMetricId($name){
        $name = strtolower($name);
        if(!array_key_exists($name, $this->metrics)){
            return -1;
        }

        return $this->metrics[$name];
    }

    private function getCampaignId($name){
        if(!array_key_exists($name, $this->campaignIds)){
            //throw new Exception("Campaign '{$name}' NOT found");
            return "NOT FOUND";
        }

        return $this->campaignIds[$name];
    }
    
    private function setMetrics(){
        $this->metrics = [];
        $httpClient = Self::getHttpClient("https://a.klaviyo.com/api/metrics/", 24);
        $metrics = $httpClient->Get();
        
        foreach($metrics["data"] as $metric){
            $this->metrics[strtolower($metric["attributes"]["name"])] = $metric["id"];
        }
    } 

    private function setAccountInfo(){
        $httpClient = Self::getHttpClient("https://a.klaviyo.com/api/accounts/", 24);
        $accounts = $httpClient->Get();
        foreach($accounts["data"] as $account){
            $this->accountName = $account["attributes"]["contact_information"]["organization_name"];
        }
        
    }

    private function setCampaigns(){
        $httpClient = Self::getHttpClient("https://a.klaviyo.com/api/campaigns/", 1);
        $campaigns = $httpClient->Get();
        $this->campaignIds = [];

        foreach($campaigns["data"] as $c){
            $this->campaignIds[$c["attributes"]["name"]] = $c["id"];
        }
    }

	public function getMetricData($startDate, $endDate, $metricsForAccount){
        $this->setMetrics();
        $this->setCampaigns();
        $this->setAccountInfo();

        $httpClient = $this->getHttpClient("https://a.klaviyo.com/api/metric-aggregates/", 10);

        $metrics = [];
        foreach($metricsForAccount as $metric){
            $m = new Metric($metric);
            
            if($this->setDataByMetric($m->name, $httpClient, $m->data, $startDate, $endDate)){
                /*if($m->config["ParentMetric"] != null){
                    $this->setDataByMetric($m->config["ParentMetric"], $httpClient, $m->parentData, $startDate, $endDate);
                }*/

                $metrics[$m->name] = $m;
            }
        }

        return $metrics;
	}

    public function getCampaigns(){
        $campaignsAll = [];

        for($iA = 0; $iA < count($this->accounts); $iA++){
            $account = $this->accounts[$iA];

            $this->apiKey = $account["apiKey"];
            $metrics = $this->accountsGlobal[0]["metrics"];
            $campaigns = [];

            $now = date("Y-m-d");
            $oneMonthBack = date("Y-m-d", strtotime("-1 month", strtotime($now)));;
    
            $startDate =  substr($oneMonthBack, 0, 10) . 'T00:00:00';
            $endDate = substr($now, 0, 10) . 'T00:00:00';

            $metricsData = $this->getMetricData($startDate, $endDate, $metrics);

            foreach($metricsData as $key => $value){
                $this->setCampaingData($campaigns, $value);
            }

            $campaignsAll = array_merge($campaignsAll, array_values($campaigns));
        }

        return $campaignsAll;
    }

    private function setCampaingData(&$campaigns, $metricObj){
        foreach ($metricObj->data as $cname => $metric){
            $campaign = null;
            if(!array_key_exists($cname, $campaigns)){
                $campaigns[$cname] = new Campaign($cname, $this->accountName);
            }

            $campaign = $campaigns[$cname];

            $index = count($campaign->metrics);

            $campaign->metrics[$index] = [];
            $campaign->metrics[$index]["name"] = $metricObj->name;
            $campaign->metrics[$index]["data"] = $metric;
            $campaign->metrics[$index]["config"] = $metricObj->config;

            if($campaign->id == null){
                $campaign->id = $this->getCampaignId($cname);
            }
        }

        if($metricObj->parentData != null){
            foreach ($metricObj->parentData as $cname => $metric){
                $campaign = null;
                if(!array_key_exists($cname, $campaigns)){
                    $campaigns[$cname] = new Campaign($cname);
                }
                $campaign = $campaigns[$cname];
            
                $campaign->metrics[$index]["parentData"] = $metric;

                if($campaign->id == null){
                    $campaign->id = $this->getCampaignId($cname);
                }
            }
        }

        if($metricObj->config["Threshold"] != null){
            $warning = $metricObj->config["Threshold"]["Warning"];
            $error = $metricObj->config["Threshold"]["Error"];
            $timespanSize = $metricObj->config["Threshold"]["TimespanSize"];
            $data = $campaign->metrics[$index]["data"];

            if($this->parentData == null){
                $campaign->metrics[$index]["lastPeriod"] = array_sum(array_slice($data, count($data) - $timespanSize - 1, $timespanSize));
                $campaign->metrics[$index]["thisPeriod"] = array_sum(array_slice($data, count($data) - $timespanSize, $timespanSize));

                $campaign->metrics[$index]["deviation"] = 0;
                if($campaign->metrics[$index]["lastPeriod"] > 0){
                    $campaign->metrics[$index]["deviation"] = round($campaign->metrics[$index]["thisPeriod"] / $campaign->metrics[$index]["lastPeriod"] * 100 - 100, 2);
                }
                $dev = $campaign->metrics[$index]["deviation"];

                $campaign->metrics[$index]["isError"] = false;
                $campaign->metrics[$index]["isWarning"] = false;

                if($warning > $error){
                    if( $dev < $warning){
                        if($dev < $error){
                            $campaign->metrics[$index]["isError"] = true;
                        }
                        else{
                            $campaign->metrics[$index]["isWarning"] = true;
                        }
                    }
                }
                else{
                    if( $dev > $warning){
                        if($dev > $error){
                            $campaign->metrics[$index]["isError"] = true;
                        }
                        else{
                            $campaign->metrics[$index]["isWarning"] = true;
                        }
                    }
                }
                
                $campaign->metrics[$index]["arrow"] = $dev > 0 ? '↑' : ($dev < 0 ? '↓' : "");
                $campaign->metrics[$index]["class"] = $campaign->metrics[$index]["isError"] ? "errorText" : ($campaign->metrics[$index]["isWarning"] ? "warningText" : "okText");
                $campaign->metrics[$index]["days"] = $timespanSize;
            }
        }
    }

    //  Returns an array [ date, campaingName] = value;
    private function setDataByMetric($metricName, $httpClient, &$metricData, $startDate, $endDate){
        $metricId = $this->getMetricId($metricName);
        if($metricId == -1){
            return false;
        }

        $data = str_replace("[metric_id]", $metricId, $this->campaignBodyTemplate);
        $data = str_replace("[start_date]", $startDate, $data);
        $data = str_replace("[end_date]", $endDate, $data);

        $postData = $httpClient->Post($data);

        $dates = $postData["data"]["attributes"]["dates"];

        foreach($postData["data"]["attributes"]["data"] as $d){
            $campaignName = $d["dimensions"][0];
            $metricData[$campaignName] = $d["measurements"]["unique"];
        }

        return true;
    }

    private function getHttpClient($url, $cachTimeout){
        $httpClient = Repositories::GetHttpClient($this->apiKey, $cachTimeout);
		$httpClient->Init($url, "application/json", [ "Authorization: Klaviyo-API-Key {$this->apiKey}", "revision: {$this->revision}"]);
        return $httpClient;
    }
}

if(isset($_GET["apiKey"])){
    //$klaviyo = new Klaviyo($_GET["apiKey"]);
    $dataPath = $_SERVER['DOCUMENT_ROOT']. "/dashboard_data";
    $config = json_decode(file_get_contents($dataPath . '/config.json'), true);
	$config_global = json_decode(file_get_contents($dataPath . '/config_global.json'), true);
    
    $klaviyo = new Klaviyo(getAccounts("klaviyo", $config["accounts"]), getAccounts("klaviyo", $config_global["accounts"]));

    //$klaviyo->getCampaignData($emailsSent, $emailsOpened, $emailsClicked, $ordersPlaced, $startDate, $endDate);
    $campaigns = $klaviyo->getCampaigns();
    echo json_encode($campaigns);
    //print_r($emailsOpened);
}
