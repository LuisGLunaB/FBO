<?php 
//Facebook Open Graph
$app_id = '632416283438924';
$app_secret = 'c478fbfb19e9f22f688b5db5144086c7';

//Iniciar sesión de Facebook
if (session_status() == PHP_SESSION_NONE) {session_start();}
//require_once __DIR__ . './facebook-php-sdk/src/Facebook/autoload.php';
define('FACEBOOK_SDK_V4_SRC_DIR', __DIR__ . '/facebook-sdk-v5/');
require_once __DIR__ . '/facebook-sdk-v5/autoload.php';

$fb = new Facebook\Facebook([
  'app_id' => $app_id,
  'app_secret' => $app_secret,
  'default_graph_version' => 'v2.7',
]);

function FB_login( &$fb , $URL = "register.php" , 
	$permissions = ['public_profile','email','user_likes','user_friends'] ){
		
	$helper = $fb->getRedirectLoginHelper();
	$permissions = ['public_profile','email','user_likes','user_friends','pages_show_list','read_page_mailboxes','read_insights','manage_pages','publish_pages','pages_messaging']; // optional
	$loginUrl = $helper->getLoginUrl("http://$_SERVER[HTTP_HOST]/$URL", $permissions);
	
	return htmlspecialchars($loginUrl);
}

function FB_catchToken(&$fb){
	$helper = $fb->getRedirectLoginHelper();
	try {
	  $Token = $helper->getAccessToken();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
	  // When Graph returns an error
	  echo 'Graph tuvo un error al recuperar Token: ' . $e->getMessage();
	  exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
	  // When validation fails or other local issues
	  echo 'Facebook SDK tuvo un error: ' . $e->getMessage();
	  exit;
	}
	
	if(isset($Token)){
		$Token = (string) FB_longToken($fb,$Token);
	}
	
	return $Token;
}

function FB_longToken(&$fb, $Token ){
	$oAuth2Client = $fb->getOAuth2Client();
	$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($Token);
	return (string) $longLivedAccessToken;
}

function FB_get($get,$Token = NULL){
	global $fb;
	$status = true;
	$message = "";
	if(!is_null($Token)){
		$fb->setDefaultAccessToken($Token);
	}
	//$fb->setDefaultAccessToken($Token);
	try {
	  $response = $fb->get($get);
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
	  $message = 'Graph tuvo un error: ' . $e->getMessage()."<br />";
	  $status = false;
	  //exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
	  $message = 'Facebook SDK tuvo un error: ' . $e->getMessage()."<br />";
	  $status = false;
	  //exit;
	}
	if($status){
		$datos = $response->getDecodedBody();
	}else{
		$datos['data'] = NULL;
	}
	
	$datos['status'] = $status;
	$datos['message'] = $message;
	
	return $datos;
}

function FB_save_token($id,$token){
	$men = true;
	try{
		$data["facebook_token"] = $token;
		$ins = Q_update("tUsuarios",$data,$id,array("facebook_token"));
		if(!$ins['status']){
			$men .= "* Error al actualizar Token: ".$ins['men']."<br />";
		}
	}catch(expection $e){
		$men .= "* Error al actualizar Token <br />
		Para corregir este error vuela a iniciar sesión con Facebook.: $e <br />";
	}
	return $men;
}

?>