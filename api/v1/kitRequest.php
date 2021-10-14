<?php
/**
 * Endpoint to submit kit request.
 */

 require_once '../../includes/api.inc.php';

 $isPost = isPostRequest();
 if ($isPost) {

   $payload = requestBody();
   $c_map = array();

   $title = obj_property($payload, 'title');

   // ............... Fields ...............
   $c_map['first_name']    = $firstName              = obj_property($payload, 'firstName');  // required
   $c_map['last_name']     = $lastName               = obj_property($payload, 'lastName');   // required

   $c_map['addr_1']            = $address                = obj_property($payload, 'address');
   $c_map['addr_2']            = $address2             = obj_property($payload, 'address2');
   $c_map['city']              = $city                   = obj_property($payload, 'city');
   $c_map['state']             = $state                  = obj_property($payload, 'state');
   $c_map['zip']               = $zip                    = obj_property($payload, 'zip');
   $c_map['phone']             = $phone                  = obj_property($payload, 'phone');
   $c_map['email']             = $email                  = obj_property($payload, 'email');

   $c_map['source_1'] = $title;
   $c_map['source_2'] = null;


   $purposes = obj_property($payload, 'purpose');
   $is_mail_me = in_array('mail-me', $purposes);
   $is_ira = in_array('ira', $purposes);
   $is_roth_ira = in_array('roth-ira', $purposes);
   $is_call_me = in_array('call-me', $purposes);
   $is_faith_matters = in_array('faith-matters', $purposes);
   $is_complimentary_in_God_we_trust = in_array('complimentary-in-God-we-trust', $purposes);
   $is_quarterly_email = in_array('quarterly-email', $purposes);


   // ............... Verify: required params and validations ...............
   $required_params = array( 'first name' => $firstName,
                             'last name' => $lastName);

   $validations = array(); // None

   if ( !verifyParamsAndValidations( $required_params, $validations ) ) return;

   // ............... Compose message ...............
   $message = '';
   $message .= "Name: $firstName $lastName\n\n";

   $message .= "Contact information\n";
   $message .= "---------------------------------------------------\n";
   if ($address) $message .= "Address 1: $address\n";
   if ($address2) $message .= "Address 2: $address2\n";
   if ($city) $message .= "City: $city\n";
   if ($state) $message .= "State: $state\n";
   if ($zip) $message .= "Zip: $zip\n";
   if ($email) $message .= "Email: $email\n";
   if ($phone) $message .= "Phone: $phone\n";
   $message .= "\n";

   $message .= "Options\n";
   $message .= "---------------------------------------------------\n";
  //  if ($purpose) $message .= "* $purpose\n";
  if ($is_mail_me) $message .= "• Please mail me an INVESTMENT KIT\n";
  if ($is_ira) $message .= "• Please include an IRA Application and Transfer Form\n";
  if ($is_roth_ira) $message .= "• Please include a Roth IRA Application and Transfer Form\n";
  if ($is_call_me) $message .= "• Please CALL me\n";
  if ($is_faith_matters) $message .= "• Please subscribe me to FAITH MATTERS\n";
  if ($is_complimentary_in_God_we_trust) $message .= "• Please send me a complimentary copy of the book, \"In God We Trust\"\n";
  if ($is_quarterly_email) $message .= "• Please subscribe me to your QUARTERLY EMAIL\n";


   // ............... Send email, save contact ...............
   handle_contact_form( $mysql, $title, $c_map, $message );
   sendAsJson(true);

 } else {
   send404();
 }

?>
