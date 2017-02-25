<?php

include_once("FBO.php");

$Face = new Facebook();

if( $Face->hasToken() ){
  $Face->PAGE("merkategia");
  $Face->json();
}else{
  $loginURL = $Face->getLogin();
  echo '<a href="' .($loginURL). '">Entra con Facebook!</a>';
}




?>
