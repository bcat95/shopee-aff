<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once "conn.php";
require_once "func.php";

if (!(isset($_POST["tp"]))) exit();

$tp = $_POST["tp"];

$milliseconds = round(microtime(true) * 1000000);
$milliseconds = round(microtime(true) * 1000000) - $milliseconds;

if ($tp == 'link') {

	// LINK API
	$link_action = $_POST["link_action"];

	if ($link_action == 'short_link') {

		if (isset($_POST["us_id"])) $us_id = trim($_POST["us_id"]);
		else $us_id = '';

		if (isset($_POST["apiAppID"])) $apiAppID = trim($_POST["apiAppID"]);
		else $apiAppID = '';
		if (isset($_POST["apiSecret"])) $apiSecret = trim($_POST["apiSecret"]);
		else $apiSecret = '';

		if (!$apiAppID || !$apiSecret) {
			echo response('errors', 'Vui lòng điền đầy đủ AppID và Secret');
			exit();
		}

		$appDemo = 0;
		if ($apiAppID == 'demo' && $apiSecret == 'demo') {
			$appDemo = 1;
		}

		if (isset($_POST["url"])) $url = trim($_POST["url"]);
		else $url = '';
		if ($url) {
			$url = removeParam($url, 'sp_atk');
			$url = removeParam($url, 'xptdk');
		}
		if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
			echo response('errors', 'Link không hợp lệ');
			exit();
		}
		$host = parse_url($url, PHP_URL_HOST);
		if (!$host || stripos($host, 'shopee.') === false) {
			echo response('errors', 'Chỉ hỗ trợ link Shopee');
			exit();
		}

		$subIds = [];
		if (isset($_POST["Sub_id1"]) && $_POST["Sub_id1"] != '') $subIds[] = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST["Sub_id1"]);
		if (isset($_POST["Sub_id2"]) && $_POST["Sub_id2"] != '') $subIds[] = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST["Sub_id2"]);
		if (isset($_POST["Sub_id3"]) && $_POST["Sub_id3"] != '') $subIds[] = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST["Sub_id3"]);
		if (isset($_POST["Sub_id4"]) && $_POST["Sub_id4"] != '') $subIds[] = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST["Sub_id4"]);
		if (isset($_POST["Sub_id5"]) && $_POST["Sub_id5"] != '') $subIds[] = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST["Sub_id5"]);
		$subIds = array_slice(array_values(array_filter($subIds)), 0, 5);

		if ($appDemo) {
			$apiAppID = ''; // change this, see at https://affiliate.shopee.vn/open_api 
			$apiSecret = ''; // change this, see at https://affiliate.shopee.vn/open_api
			// $subIds[] = 'bccustomlink';
		}

		echo short_link($us_id, $apiAppID, $apiSecret, $url, $subIds);
		exit();
	}
}
