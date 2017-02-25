<?php
class Facebook{
  public $FB = NULL;
  public $status = True;
  public $message = "";
  protected $app_id = '632416283438924';
  protected $app_secret = 'c478fbfb19e9f22f688b5db5144086c7';
  public $permissions = ['public_profile','email','user_likes','user_friends',
  'pages_show_list','read_page_mailboxes','read_insights',
  'manage_pages','publish_pages','pages_messaging'];

  public $Token = NULL;
  public $saveTokenWith = "COOKIE";
  public $datos = array();

  public function __construct($Token = NULL, $FB = NULL){
    # Start Framework and Session if needed
    if (session_status() == PHP_SESSION_NONE) {session_start();}
    define('FACEBOOK_SDK_V4_SRC_DIR', __DIR__ . '/facebook-sdk-v5/');
    require_once __DIR__ . '/facebook-sdk-v5/autoload.php';

    # Connect to Facebook is $FB is not given
    if(is_null($FB)){ $this->CONNECT(); }

    # Manage Token: Catch or load Token if Token is not given
    if( is_null($Token) ){
      if( isset($_GET['code']) ){
        $this->catchToken();
        $this->refreshToken();
        $this->saveToken();
      }
      $this->loadToken();
    }

  }

  public function CONNECT(){
    $this->FB = new Facebook\Facebook([
      'app_id' => $this->app_id,
      'app_secret' => $this->app_secret,
      'default_graph_version' => 'v2.8']);
  }
  public function catchToken(){
    $fb = &$this->FB;
  	$helper = $fb->getRedirectLoginHelper();
  	try {
  	  $Token = $helper->getAccessToken();
  	} catch(Facebook\Exceptions\FacebookResponseException $e) {
      $this->status = False;
  	  $this->message = 'Graph tuvo un error al recuperar Token: ' . $e->getMessage();
  	  exit;
  	} catch(Facebook\Exceptions\FacebookSDKException $e) {
      $this->status = False;
  	  $this->message = 'Facebook SDK tuvo un error: ' . $e->getMessage();
  	  exit;
  	}

  	if( isset($Token) ){
      $this->Token = $Token;
  	}
  	return $this->Token;
  }
  public function refreshToken(){
    $fb = &$this->FB;
    $Token = &$this->Token;

  	$oAuth2Client = $fb->getOAuth2Client();
  	$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($Token);
    $Token = $longLivedAccessToken;
  	return (string) $longLivedAccessToken;
  }
  public function saveToken( $with = NULL ){
    $with = ( is_null($with) ) ? $this->saveTokenWith : $with;
    $Token = $this->Token;

    # COOKIE
    $_COOKIE["FBTOKEN"] = $Token;
    setcookie("FBTOKEN", $Token, time() + 3600*24*30 ); //1 month

  }
  public function loadToken( $with=NULL){
    $with = ( is_null($with) ) ? $this->saveTokenWith : $with;

    # COOKIE
    $this->Token = ( isset($_COOKIE["FBTOKEN"]) ) ? $_COOKIE["FBTOKEN"] : NULL;
  }
  public function getLogin($URL = "", $relative = True ){
    $fb = &$this->FB;
    $permissions = &$this->permissions;

    $URL = ($relative) ? "http://$_SERVER[HTTP_HOST]/$URL" : "$URL";
  	$helper = $fb->getRedirectLoginHelper();
  	$loginUrl = $helper->getLoginUrl($URL, $permissions);

  	return htmlspecialchars($loginUrl);
  }
  public function hasToken(){
    return ( !is_null($this->Token) );
  }

  public static function parseNULL($key,&$parsed,&$datos){
    $parsed["$key"] = ( isset($datos["$key"]) ) ? $datos["$key"] : NULL;
  }
  public static function parseBoolean($key,&$parsed,&$datos){
    $x = ( isset($datos["$key"]) ) ? $datos["$key"] : NULL;
    if( $x=="true" or $x=="True" or $x=="TRUE"){ $x = 1; }
    if( $x=="false" or $x=="False" or $x=="FALSE" ){ $x = 0; }
    $parsed["$key"] = $x;
  }
  public static function parse2Keys($key1,$key2,&$parsed,&$datos){
    $parsed["$key1"."_$key2"] = ( isset($datos[$key1][$key2]) ) ? $datos[$key1][$key2] : NULL;
  }

  public static function parsePAGE( $datos ){
    $parsed = array();

    $byNULL = ["id","username","name","link","founded","category","products",
    "about","phone","website","place_type","fan_count","were_here_count",
    "checkins","rating_count","talking_about_count","overall_star_rating"];
    foreach($byNULL as $key){self::parseNULL($key,$parsed,$datos);}

    $byBoolean = ["can_checkin"];
    foreach($byBoolean as $key){self::parseBoolean($key,$parsed,$datos);}

    $by2Keys =[ ["cover","source"], ["location","city"],
    ["location","country"], ["location","latitude"], ["location","longitude"],
    ["location","street"], ["location","zip"] ];
    foreach($by2Keys as $keys){
      list($key1,$key2) = $keys;
      self::parse2Keys($key1,$key2,$parsed,$datos);
    }

    # Specific Parsings
    $parsed["emails"] = isset($datos["emails"]) ? implode(";",$datos["emails"]) : NULL;
    $parsed["picture_data_url"] = isset($datos["picture"]["data"]["url"]) ? $datos["picture"]["data"]["url"] : NULL;
    if( isset($datos["category_list"]) ){
      $categories = [];
      foreach($datos["category_list"] as $category){
        $categories[] = $category["name"];
      }
      $parsed["category_list"] = implode(";",$categories);
    }else{
      $parsed["category_list"] = NULL;
    }

    return $parsed;
  }
  
  public function GET($get){
  	$fb = &$this->FB;
    $Token = &$this->Token;
    $datos = &$this->datos;

  	if( !is_null($Token) ){ $fb->setDefaultAccessToken($Token); }
  	//$fb->setDefaultAccessToken($Token);
  	try {
  	  $response = $fb->get($get);
  	} catch(Facebook\Exceptions\FacebookResponseException $e) {
      $this->status = False;
  	  $this->message = 'Graph tuvo un error: ' . $e->getMessage();
  	  //exit;
  	} catch(Facebook\Exceptions\FacebookSDKException $e) {
      $this->status = False;
  	  $this->message = 'Facebook SDK tuvo un error: ' . $e->getMessage()."<br />";
  	  //exit;
  	}

  	if($this->status){$datos = $response->getDecodedBody();}

  	return $datos;
  }
  public function PAGE($page){
    $identification = ["id","username","name","link"];
    $image = ["cover","picture"];
    $info = ["founded","category","category_list","description","products","about"];
    $contact = ["emails","phone","website"];
    $location = ["location","place_type","can_checkin"];
    $insights = ["fan_count","were_here_count","checkins",
    "rating_count","talking_about_count","overall_star_rating"];

    $fields = array_merge($identification,$image,$info,$contact,$location,$insights);
    $fields = implode(",",$fields);

    $query = "$page?fields=$fields";
    $this->GET($query);
    $this->datos = $this->parsePAGE( $this->datos );
  }
  public function PAGETOKEN($page){
    $query = "$page?fields=access_token";
    return $this->GET($query);
  }

  public function json($show=True,$isJSON=True){
		$datos['status'] = $this->status;
		$datos['message'] = $this->message;
		$datos["data"] = $this->datos;
		if($isJSON){header('Content-Type: application/json');}
		$json = json_encode($datos, JSON_PRETTY_PRINT );
		if($show){echo $json;}
		return $json;
	}
  public function show(){
    print_r($this->datos);
    echo $this->status;
    echo $this->message;
  }
}

?>
