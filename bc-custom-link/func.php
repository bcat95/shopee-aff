<?php

function short_link($us_id='',$apiAppID,$apiSecret,$url,$subIds=[]){
	$subIds = json_encode($subIds);
	$query = '
	{
		"query":"
			mutation{
				generateShortLink(
					input:{
						originUrl:\"'.$url.'\",
						subIds:'.addslashes($subIds).'
					}
				),{
					shortLink
				}
			}
		"
	}';

	$data = shopee_aff_api($apiAppID,$apiSecret,$query);

	$message = 'Tạo link không thành công';

	if ($data){
		if (isset($data['errors']) && $data['errors']) {
			if (isset($data['errors'][0]['message'])) $message = $data['errors'][0]['message'];
			echo response('errors',$message);
			exit();
		} elseif (isset($data['data'])) {
			$data = $data['data'];
			if (isset($data['generateShortLink']) && isset($data['generateShortLink']['shortLink'])){
				$tracking_link = $data['generateShortLink']['shortLink'];
			}
			echo response('success',$tracking_link);
			exit();
		}
	}
	echo response('errors',$message);
	exit();
}

function shopee_aff_api($AppID,$APIkey,$query){
	$Timestamp = time();
	$now=strtotime(date('Y-m-d',time()-60*60*24*1));
	$past=strtotime(date('Y-m-d',time()-60*60*24*10));

	$scrollId='';

	$query=str_replace("\t", '', $query);
	$query=str_replace("\n", '', $query);
	$query=str_replace("\r", '', $query);

	$factor = $AppID.$Timestamp.$query.$APIkey;
	$Signature = hash('sha256', $factor);

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://open-api.affiliate.shopee.vn/graphql",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
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
	curl_close($curl);
	$response = json_decode($response, TRUE);
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
        setcookie('us_id',$us_id, time() + (86400 * 365), "/",".youdomain.com"); // change this
        return $us_id;
    }
}

function new_us_id(){
    $us_id=md5(time().'_'.rand(1,1000));
    setcookie('us_id',$us_id, time() + (86400 * 365), "/",".youdomain.com"); // change this
    return $us_id;
}
?>