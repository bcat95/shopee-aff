<?php

function removeParam($url, $param) {
    $url = str_replace('&amp;','&',$url);
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*$/', '', $url);
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*&/', '$1', $url);
    return $url;
}

function log_shopee_affiliate_link($us_id,$apiAppID,$link='',$tracking_link='',$sub_id=''){
	global $connect;
	if (!$connect || $link == '' || $tracking_link == '') return false;
	if (is_array($sub_id)) $sub_id = json_encode($sub_id);
	$time_create = time();
	$ip = get_client_ip();
	$stmt = mysqli_prepare(
		$connect,
		"INSERT INTO shopee_affiliate_link(us_id,appid,link,tracking_link,sub_id,time_create,ip) VALUES (?, ?, ?, ?, ?, ?, ?)"
	);
	if (!$stmt) return false;
	mysqli_stmt_bind_param($stmt, 'sssssis', $us_id, $apiAppID, $link, $tracking_link, $sub_id, $time_create, $ip);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	return true;
}

function get_client_ip() {
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$candidate = trim($forwarded[0]);
		if (filter_var($candidate, FILTER_VALIDATE_IP)) return $candidate;
	}
	if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
		return $_SERVER['REMOTE_ADDR'];
	}
	return '';
}

function short_link($us_id,$apiAppID,$apiSecret,$url,$subIds=[]){
	$payload = array(
		'query' => 'mutation GenerateShortLink($originUrl: String!, $subIds: [String]) { generateShortLink(input: {originUrl: $originUrl, subIds: $subIds}) { shortLink } }',
		'variables' => array(
			'originUrl' => $url,
			'subIds' => $subIds
		)
	);
	$query = json_encode($payload, JSON_UNESCAPED_SLASHES);

	$data = shopee_aff_api($apiAppID,$apiSecret,$query);

	$message = 'Tạo link không thành công';

	if ($data){
		if (isset($data['errors']) && $data['errors']) {
			if (isset($data['errors'][0]['message'])) $message = $data['errors'][0]['message'];
			return response('errors',$message);
		} elseif (isset($data['data'])) {
			$data = $data['data'];
			if (isset($data['generateShortLink']) && isset($data['generateShortLink']['shortLink'])){
				$tracking_link = $data['generateShortLink']['shortLink'];
				// Log failures should not break API responses.
				log_shopee_affiliate_link($us_id,$apiAppID,$url,$tracking_link,$subIds);
				return response('success',$tracking_link);
			}
		}
	}
	return response('errors',$message);
}

function shopee_aff_api($AppID,$APIkey,$query){
	$Timestamp = time();

	$factor = $AppID.$Timestamp.$query.$APIkey;
	$Signature = hash('sha256', $factor);

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://open-api.affiliate.shopee.vn/graphql",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => $query,
		CURLOPT_HTTPHEADER => array(
		"Authorization: SHA256 Credential=".$AppID.", Timestamp=".$Timestamp.", Signature=".$Signature,
		"Content-Type: application/json"
		),
	));

	$response = curl_exec($curl);
	if ($response === false) {
		$curlError = curl_error($curl);
		curl_close($curl);
		return array('errors' => array(array('message' => 'CURL error: '.$curlError)));
	}
	$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);
	$response = json_decode($response, TRUE);
	if (!is_array($response)) {
		return array('errors' => array(array('message' => 'API response không hợp lệ')));
	}
	if ($httpCode >= 400) {
		$apiMessage = 'Shopee API error (HTTP '.$httpCode.')';
		if (isset($response['errors'][0]['message'])) $apiMessage = $response['errors'][0]['message'];
		return array('errors' => array(array('message' => $apiMessage)));
	}
	return $response;
}

function response($type,$message){
    $response = [];
    $response[$type]['message'] = $message;
    return json_encode($response);
}

function us_id(){
    if (isset($_SESSION["us_id"])) return $_SESSION["us_id"];
    else if (isset($_COOKIE["us_id"])) return $_COOKIE["us_id"];
    else {
        $us_id=md5(time().'_'.rand(1,1000));
		$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : '';
		$isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
		if (PHP_VERSION_ID >= 70300) {
			setcookie('us_id', $us_id, array(
				'expires' => time() + (86400 * 365),
				'path' => '/',
				'domain' => $host,
				'secure' => $isSecure,
				'httponly' => true,
				'samesite' => 'Lax'
			));
		} else {
			setcookie('us_id', $us_id, time() + (86400 * 365), '/; samesite=Lax', $host, $isSecure, true);
		}
        return $us_id;
    }
}

function new_us_id(){
    $us_id=md5(time().'_'.rand(1,1000));
	$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : '';
	$isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
	if (PHP_VERSION_ID >= 70300) {
		setcookie('us_id', $us_id, array(
			'expires' => time() + (86400 * 365),
			'path' => '/',
			'domain' => $host,
			'secure' => $isSecure,
			'httponly' => true,
			'samesite' => 'Lax'
		));
	} else {
		setcookie('us_id', $us_id, time() + (86400 * 365), '/; samesite=Lax', $host, $isSecure, true);
	}
    return $us_id;
}
?>