<?php
header('Access-Control-Allow-Origin: *');

require_once "func.php";

if (!(isset($_POST["tp"]))) exit();

$tp=$_POST["tp"];

$milliseconds = round(microtime(true) * 1000000);
$milliseconds = round(microtime(true) * 1000000)-$milliseconds;
	
if ($tp == 'link'){
	
	// LINK API
	$link_action = $_POST["link_action"];

	if ($link_action == 'short_link') {

		if (isset($_POST["us_id"])) $us_id = $_POST["us_id"]; else $us_id = '';

		if (isset($_POST["apiAppID"])) $apiAppID = $_POST["apiAppID"]; else $apiAppID = 0;
		if (isset($_POST["apiSecret"])) $apiSecret = $_POST["apiSecret"]; else $apiSecret = 0;

		if (!$apiAppID || $apiSecret) {
			response('errros','Vui lòng điền đầy đủ AppID và Secret');
		}

		$appDemo = 0;
		if ($apiAppID == 'demo' && $apiSecret == 'demo'){
			$appDemo = 1;
		}

		if (isset($_POST["url"])) $url = $_POST["url"]; else $url = 0;

		$subIds = [];
		if (isset($_POST["Sub_id1"]) && $_POST["Sub_id1"] != '') $subIds[] = $_POST["Sub_id1"];
		if (isset($_POST["Sub_id2"]) && $_POST["Sub_id2"] != '') $subIds[] = $_POST["Sub_id2"];
		if (isset($_POST["Sub_id3"]) && $_POST["Sub_id3"] != '') $subIds[] = $_POST["Sub_id3"];
		if (isset($_POST["Sub_id4"]) && $_POST["Sub_id4"] != '') $subIds[] = $_POST["Sub_id4"];
		if (isset($_POST["Sub_id5"]) && $_POST["Sub_id5"] != '') $subIds[] = $_POST["Sub_id5"];

		if ($appDemo){
			$apiAppID = ''; // change this, see at https://affiliate.shopee.vn/open_api 
			$apiSecret = ''; // change this, see at https://affiliate.shopee.vn/open_api
			$subIds[] = 'bccustomlink';
		}

		$link = short_link($us_id,$apiAppID,$apiSecret,$url,$subIds);

		echo $link;
		exit();
	}
}