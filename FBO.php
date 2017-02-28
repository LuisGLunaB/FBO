<?php
class Facebook{
  /* THIS OBJECT FACILITATES THE USE OF FACEBOOK'S GRAPH API'S MAIN FUNCTIONS
    MOST OF THE CODE DEALS WITH EXCEPTIONS, TOKEN REFRESHING AND DATA PARSING
    EVERY MAIN QUERY HAS DEFAULT FIELDS TO BE PASSED TO FACEBOOK
    EVERY QUERY IS PARSED IN ORDER TO HAVE AN STRUCTURED ASSOCIATIVE ARRAY
    WITH NO MISSING VALUES AT ALL (BUT WITH NULLS VALUES).*/
  protected $app_id = '632416283438924';
  protected $app_secret = 'c478fbfb19e9f22f688b5db5144086c7';

  # Default attribute values
  public $FB = NULL;
  public $status = True;
  public $message = "";
  public $Token = NULL;
  public $datos = array();
  public $saveTokenWith = "COOKIE"; // Where to save FB's Token

  # Permissions to be requested to the user
  public $permissions = ['public_profile','email','user_likes','user_friends',
  'pages_show_list','read_page_mailboxes','read_insights',
  'manage_pages','publish_pages','pages_messaging'];

  # Platform methods
  public function __construct($Token = NULL, $FB = NULL){
    # Start Framework and Session if needed
    if (session_status() == PHP_SESSION_NONE) {session_start();}
    define('FACEBOOK_SDK_V4_SRC_DIR', __DIR__ . '/facebook-sdk-v5/');
    require_once __DIR__ . '/facebook-sdk-v5/autoload.php';

    # Connect to Facebook is $FB is not given
    if(is_null($FB)){
      $this->CONNECT();
    }

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
  public function processError($message, $e = NULL, $exit = False){
    # Every error in this Object leads here.

    # Concatenate message and set status to False
    $emessage = ( is_null($e) ) ? "" : $e->getMessage();
    $this->status = False;
    $this->message = $message . " " . $emessage;

    if($exit){exit;} # exit execution or not
  }
  public function CONNECT(){
    # Connect to FB with the credentials
    try{
      $this->FB = new Facebook\Facebook([
        'app_id' => $this->app_id,
        'app_secret' => $this->app_secret,
        'default_graph_version' => 'v2.8']);
    }catch(Exception $e){
      $this->processError("Error en CONNECT.",$e);
    }
  }

  # Token handling methods
  public function catchToken(){
    $fb = &$this->FB;
  	$helper = $fb->getRedirectLoginHelper();

    # Try to get a new Token
  	try {
  	  $Token = $helper->getAccessToken();
  	} catch(Facebook\Exceptions\FacebookResponseException $e) {
      $this->processError("Error al recuperar el Token en catchToken.", $e);
  	} catch(Facebook\Exceptions\FacebookSDKException $e) {
      $this->processError("Error del SDK en catchToken.", $e);
  	}

    # If you fail, set Token to NULL
  	if( isset($Token) ){
      $this->Token = $Token;
  	}else{
      $this->Token = NULL;
    }

  	return $this->Token;
  }
  public function refreshToken(){
    $fb = &$this->FB;
    $Token = &$this->Token;
    $longLivedAccessToken = NULL;

    # Try to enlongate Token
    if( !is_null($Token) ){
        try{
        	$oAuth2Client = $fb->getOAuth2Client();
        	$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($Token);
        }catch(Exception $e){
          $this->processError("Error en refreshToken.",$e);
        }
        $Token = $longLivedAccessToken;
    }else{
        $this->processError("No hay Token previo en refreshToken.");
    }

  	return $longLivedAccessToken;
  }
  public function saveToken( $with = NULL ){
    # Save Token depending on the method
    $Token = $this->Token;

    // Get saving method
    $with = ( is_null($with) ) ? $this->saveTokenWith : $with;

    # If saving with a COOKIE then:
    if($with=="COOKIE"){
      $_COOKIE["FBTOKEN"] = $Token;
      setcookie("FBTOKEN", $Token, time() + 3600*24*30 ); //1 month
    }

  }
  public function loadToken( $with=NULL){
    # Load Token depending on the method
    // Get saving method
    $with = ( is_null($with) ) ? $this->saveTokenWith : $with;

    # Loading from a COOKIE
    if($with=="COOKIE"){
      $this->Token = ( isset($_COOKIE["FBTOKEN"]) ) ? $_COOKIE["FBTOKEN"] : NULL;
    }
  }
  public function hasToken(){
    # Return if Object has a Token
    return ( !is_null($this->Token) );
  }

  public function getLogin($URL = "", $relative = True ){
    # Get Login URL for the user to grant access to Facebook
    $fb = &$this->FB;
    $permissions = &$this->permissions;

    # Build Callback URL
    $URL = ($relative) ? "http://$_SERVER[HTTP_HOST]/$URL" : "$URL";

    # Try to request Login URL
    try{
    	$helper = $fb->getRedirectLoginHelper();
    	$loginUrl = $helper->getLoginUrl($URL, $permissions);
    }catch(Exception $e){
      $this->processError("Error en getLogin.",$e);
    }

  	return htmlspecialchars($loginUrl);
  }

  # Main Parsing methods
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
  public static function parseACCOUNT( $datos ){
    //$fields = ["instagram_accounts"];
    $parsed = array();
    $c = 0;
    foreach($datos as $dato){
      $byNULL = ["id","name","access_token","username"];
      foreach($byNULL as $key){
        $parsed[$c][$key] = self::issetKey($dato, $key);
      }

      $parsed[$c]["perms"] = ( isset($dato["perms"]) ) ?
      implode( ";", $dato["perms"] ) : NULL;

      $parsed[$c]["picture"] = ( isset($dato["picture"]["data"]["url"]) ) ?
      $dato["picture"]["data"]["url"] : NULL;

      $parsed[$c]["cover"] = ( isset($dato["cover"]["source"]) ) ?
      $dato["cover"]["source"] : NULL;

      $parsed[$c]["instagram_accounts"] = NULL;
      if( isset($dato["instagram_accounts"]) ){
        $Instas = [];
        foreach($dato["instagram_accounts"]["data"] as $Ins){
          $Instas[] = (int) $Ins["id"];
        }
        $parsed[$c]["instagram_accounts"] = implode(";", $Instas);
      }

      $c++;
    }
    return $parsed;
  }

  # Secondary Parsing methods
  public static function parseNULL($key,&$parsed,&$datos){
    # Check if a key exists: return its Value or NULL
    # Add the key back to $parsed (by reference)
    $parsed["$key"] = ( isset($datos["$key"]) ) ? $datos["$key"] : NULL;
  }
  public static function parseBoolean($key,&$parsed,&$datos){
    # Check if key exists: return NULL or Boolean to $parsed (by reference)
    $x = ( isset($datos["$key"]) ) ? $datos["$key"] : NULL;
    if( $x=="true" or $x=="True" or $x=="TRUE"){ $x = 1; }
    if( $x=="false" or $x=="False" or $x=="FALSE" ){ $x = 0; }
    $parsed["$key"] = $x;
  }
  public static function parse2Keys($key1,$key2,&$parsed,&$datos){
    # Check if array with 2 given keys exists: return its Value or NULL (by reference)
    $parsed["$key1"."_$key2"] = ( isset($datos[$key1][$key2]) ) ? $datos[$key1][$key2] : NULL;
  }
  public static function issetKey($var,$key){
    # Check if an associative array has a key declared and return its Value.
    if( isset($var[$key]) ){
      return $var[$key];
    }else{
      return NULL;
    }
  }

  # Data retriving methods
  public function GET($get){
  	$fb = &$this->FB;
    $Token = &$this->Token;
    $datos = &$this->datos;

    try{
      $fb->setDefaultAccessToken($Token);
    }catch(Exception $e){
      $this->processError("Error al dar Token en GET.",$e);
    }

  	try {
  	  $response = $fb->get($get);
  	} catch(Facebook\Exceptions\FacebookResponseException $e) {
      $this->processError("Error de Respuesta en GET.",$e);
  	} catch(Facebook\Exceptions\FacebookSDKException $e) {
      $this->processError("Error al SDK en GET.",$e);
  	}

    try{
      $datos = $response->getDecodedBody();
    }catch(Exception $e){
      $this->processError("Error al crear array en GET.",$e);
    }

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

    try{
      $this->GET($query);
      $this->datos = $this->parsePAGE( $this->datos );
    }catch(Exception $e){
      $this->processError("Error en PAGE.",$e);
    }

  }
  public function PAGETOKEN($page){
    $query = "$page?fields=access_token";

    try{
      $this->GET($query);
    }catch(Exception $e){
      $this->processError("Error en PAGETOKEN.",$e);
    }

    return $this->datos;
  }
  public function ACCOUNTS( $access_token = False ){
    $fields = ["id","name","perms","username","instagram_accounts","picture","cover"];
    if($access_token){ $fields[] = "access_token"; }
    $fields = implode(",",$fields);

    $query = "me/accounts?fields=$fields";

    try{
      $this->GET($query);
      $this->datos =$this->datos["data"];
      $this->datos = $this->parseACCOUNT( $this->datos );
    }catch(Exception $e){
      $this->processError("Error en ACCOUNTS.",$e);
    }

  }

  # Data displaying methods
  public function json($show=True,$isJSON=True){
		$datos['status'] = $this->status;
		$datos['message'] = $this->message;
		$datos["data"] = $this->datos;
		if($isJSON){header('Content-Type: application/json');}
		$json = json_encode($datos, JSON_PRETTY_PRINT );
		if($show){echo $json;}
		return $json;
	}
  public function flush(){
    $status = ($this->status) ? "True" : "False";
    echo "status: ". $status."</br>";
    echo "message: ". $this->message."</br>";
    print_r($this->datos);
  }

}

?>
