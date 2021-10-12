<?php
/**
 * Endpoints to reset password.
 */

 require_once '../../../includes/api.inc.php';

 $isPost = isPostRequest();
 $isPut  = isPutRequest();
 if ($isPost) {

   $payload = requestBody();

   // ............... Fields ...............
   $email = obj_property($payload, 'email');

   // ............... Verify: exists ...............
   $exists = check_financial_advisor_email_exists($mysql, $email);
   if (!$exists) {
     $msg = "An account with email address not found: ".$email;
     send404($msg);
     return;
   }

   // ............... Perform ...............
   handle_financial_advisor_password_reset_request($mysql, $email);
   sendAsJson(true);

 } else if ($isPut) {

   $payload = requestBody();

   // ............... Fields ...............
   $email     = obj_property($payload, 'email');
   $key       = obj_property($payload, 'key');
   $password1 = obj_property($payload, 'password1');
   $password2 = obj_property($payload, 'password2');

   // ............... Verify: required params ...............
   $required = array( 'email' => $email,
                      'password' => $password1,
                      'confirm password' => $password2);

   $okay = verifyRequiredParams($required);
   if (!$okay) return;

   // ............... Verify: matching pw ...............
   $okay = verifyMatchingPasswords($password1, $password2);
   if (!$okay) return;

   // ............... Verify: valid pw change request ...............
   $okay = verify_password_change_request( $email, $key, $mysql );
   if (!$okay) {
     // TODO: should this be a 403 to avoid leaking information about available requests?
     send404("Password reset request not found");
     return;
   }

   // ............... Perform ...............
   handle_financial_advisor_password_reset_perform($mysql, $email, $password1);
   sendAsJson(true);

 } else {
   send404();
 }

?>
