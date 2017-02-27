<?php

include_once("FBO.php");

$Face = new Facebook();

$page = ( isset($_GET["page"]) ) ? $_GET["page"] : "mkti.mx";

if( $Face->hasToken() ){
  $Face->PAGE( $page );
  //$Face->ACCOUNTS();
  $Face->json();
}else{
  $loginURL = $Face->getLogin();
  echo '<a href="' .($loginURL). '">Entra con Facebook!</a>';
}




?>
