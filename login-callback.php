<?php 
include_once("../facebook/FACEBOOK.php");
$Token = FB_catchToken($fb);

if (isset($Token)) {
	$fb->setDefaultAccessToken($Token);
	$fbuser = FB_get("/me?fields=id,first_name,last_name,email");
	include_once("../php/SQL.php");
	include_once("../php/AUTH.php");
	$contrasena = ($fbuser['id']*1808).$sal;
	
	$con = Q_connect();
	$otros['nombre'] = $fbuser['first_name'];
	$otros['apellido'] = $fbuser['last_name'];
	$otros['facebook_id'] = $fbuser['id'];
	$otros['facebook_token'] = $Token;
	$otros['verificado'] = 1;
	
	$URL = "http://$_SERVER[HTTP_HOST]/index.php";
	register( $user , $fbuser['email'] , $contrasena, $contrasena, $otros, $URL);
	if(!$user['status']){
		echo $user['men'];
	}
}else{
	echo "* Error al recuperar Token de Facebook.<br />";
}

?>