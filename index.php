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
  //$Face->Token = "EAAIZCLePsr0wBAG1aJppgsbBDaDfpDTKAbwIOuid5FZC7bfGgIkrZColyMAcV04cd2o7xTK5ElhtA1YVsAykin8QFuLw67awMKw0UI7MBhPLZAu630ZCAj0rGc3ZAILr4PF7aEwcirTx5vrgi8gJiPrSHtikZAgare3y7DDoPV2CQZDZD";
  //$Face->getACCOUNTS();
  //$Face->getCONVERSATIONS( 346211398819387 );
  $Face->json( $Face->getAllCONVERSATIONS() );
}else{
  # ...if not, request access.
  $loginURL = $Face->getLogin();
  echo '<a href="' .($loginURL). '">Entra con Facebook</a>';
}


?>
