<?php
/**
 * Endpoint to create an account.
 */

 require_once '../../includes/api.inc.php';

 $isPost = isPostRequest();
 if ($isPost) {

   $payload = requestBody();

   // ............... Fields ...............
   $name             = obj_property($payload, 'name');
   $firm             = obj_property($payload, 'firm');
   $address          = obj_property($payload, 'address');
   $address2         = obj_property($payload, 'address2');
   $city             = obj_property($payload, 'city');
   $state            = obj_property($payload, 'state');
   $zip              = obj_property($payload, 'zip');
   $phone            = obj_property($payload, 'phone');
   $email            = obj_property($payload, 'email');
   $password         = obj_property($payload, 'password1');
   $passwordConfirm  = obj_property($payload, 'password2');

   // ............... Verify: required params ...............
   $required = array( 'name' => $name,
                      'email' => $email,
                      'password' => $password,
                      'confirm password' => $passwordConfirm);

   $okay = verifyRequiredParams($required);
   if (!$okay) return;

   // ............... Verify: matching pw ...............
   $okay = verifyMatchingPasswords($password, $passwordConfirm);
   if (!$okay) return;

   // ............... Verify: not exists ...............
   $exists = check_financial_advisor_email_exists($mysql, $email);
   if ($exists) {
     send422("An account with that email address already exists. Please log in.");
     return;
   }

   // ............... Create account ...............
   handle_financial_advisor_signup($mysql, $name, $firm, $address, $address2, $city, $state, $zip, $phone, $email, $password);
   $is_auth = handle_session_login($email, $password, $mysql);
   sendAsJson($is_auth);

 } else {
   send404();
 }

?>
