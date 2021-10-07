<?php
/**
 * Endpoints to authenticate.
 */

 require_once '../../includes/api.inc.php';

 $isGet = isGetRequest();
 $isPut  = isPutRequest();
 $isDelete = isDeleteRequest();
 if ($isGet) {

   $is_auth = is_authenticated();
   sendAsJson($is_auth);

 } else if ($isPut) {

   $payload = requestBody();
   $is_auth = handle_session_login($payload->email, $payload->password, $mysql);

   if ($is_auth) {
     sendAsJson(true);
   } else {
     sleep(3);
     send404(false);
   }

 } else if ($isDelete) {

   handle_financial_advisor_logout();
   sendAsJson(false);

 } else {
   send404();
 }

?>
