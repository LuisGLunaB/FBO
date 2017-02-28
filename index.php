<?php
# Report all PHP Errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

# Include and declare FB Object
include_once("FBO.php");
$Face = new Facebook();

#Set default Page to mkti.mx if none given
$page = ( isset($_GET["page"]) ) ? $_GET["page"] : "mkti.mx";

# If I have a Token, use it...
if( $Face->hasToken() ){
  $Face->PAGE( $page );
  //$Face->ACCOUNTS( );
  $Face->json();
}else{
  # ...if not, request access.
  $loginURL = $Face->getLogin();
  echo '<a href="' .($loginURL). '">Entra con Facebook</a>';
}


?>
