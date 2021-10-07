<?php
/**
 * Endpoint to submit contact us request.
 */

 require_once '../../includes/api.inc.php';

 $isPost = isPostRequest();
 if ($isPost) {

   $payload = requestBody();
   $c_map = array();

   // ............... Fields ...............
   $purposes = obj_property($payload, 'purpose');
   $c_map['first_name']    = $firstName              = obj_property($payload, 'firstName');  // required
   $c_map['last_name']     = $lastName               = obj_property($payload, 'lastName');   // required
   $c_map['is_mail_me']    = $mailMe                 = in_array('mail-me', $purposes);
   $c_map['is_ira']        = $ira                    = in_array('ira', $purposes);
   $c_map['is_roth']       = $rothIra                = in_array('roth-ira', $purposes);
   $c_map['is_call_me']    = $callMe                 = in_array('call', $purposes);
   //  $c_map['is_e_news']     = $eNews                  = in_array('e-newsletter', $purposes);
   $eNews                  = in_array('e-newsletter', $purposes);
   //  $c_map['is_f_matters']  = $faithMatters           = in_array('faith-matters', $purposes);
   $faithMatters           = in_array('faith-matters', $purposes);
   $c_map['is_fin_adv']    = $financialProfessional  = in_array('fund-kit', $purposes);

   $c_map['company']           = $company                = obj_property($payload, 'company');
   $c_map['addr_1']            = $address                = obj_property($payload, 'address');
   $c_map['addr_2']            = $address2             = obj_property($payload, 'address2');
   $c_map['city']              = $city                   = obj_property($payload, 'city');
   $c_map['state']             = $state                  = obj_property($payload, 'state');
   $c_map['zip']               = $zip                    = obj_property($payload, 'zip');
   $c_map['phone']             = $phone                  = obj_property($payload, 'phone');
   $c_map['best_time_to_call'] = $timeToCall             = obj_property($payload, 'timeToCall');
   $c_map['email']             = $email                  = obj_property($payload, 'email');

   $referrer = obj_property($payload, 'referrer');
   if (!$referrer) $referrer = obj_property($payload, 'referrerOther');

   $c_map['source_1'] = 'webform';
   $c_map['source_2'] = $referrer;

   // ............... Verify: required params and validations ...............
   $required_params = array( 'first name' => $firstName,
                             'last name' => $lastName);

   $validations = array( "In order for us to call you, please include your phone number." => $callMe && !$phone,
                         "In order for us to send you an investment kit, please include your address, city, state and zip." => ($mailMe || $financialProfessional) && !($address && $city && $state && $zip),
                         "Please include your phone number or email address." => !$phone && !$email);

   if ( !verifyParamsAndValidations( $required_params, $validations ) ) return;

   // ............... Captcha ...............
   // Make sure all validation completed prior to verifying captcha; else,
   //   when the user makes corrections to the form based on validation feedback,
   //   the captcha validation will fail because it can only be used once, and
   //   the user will not be able to submit form without refreshing.
   $recaptchaResponse = obj_property($payload, 'recaptchaResponse');
   $okay = verifyRecaptchaResponse($recaptchaResponse);
   if (!$okay) return;

   // ............... Compose message ...............
   $message = '';
   $message .= "Name: $firstName $lastName\n\n";

   if ($referrer) $message .= "Referrer: $referrer \n\n";

   $message .= "Contact information\n";
   $message .= "---------------------------------------------------\n";

   if ($company) $message .= "Company: $company\n";
   if ($address) $message .= "Address 1: $address\n";
   if ($address2) $message .= "Address 2: $address2\n";
   if ($city) $message .= "City: $city\n";
   if ($state) $message .= "State: $state\n";
   if ($zip) $message .= "Zip: $zip\n";
   if ($email) $message .= "Email: $email\n";
   $message .= "Phone: $phone\n";
   if ($timeToCall) $message .= "Time to call: $timeToCall\n";
   $message .= "\n";

   $message .= "Options\n";
   $message .= "---------------------------------------------------\n";
   if ($mailMe) $message .= "* Please mail me an investment kit\n";
   if ($ira) $message .= "* Include IRA application and transfer form\n";
   if ($rothIra) $message .= "* Include Roth IRA application and transfer form\n";
   if ($callMe) $message .= "* Call me\n";
   if ($eNews) $message .= "* Please send me the quarterly e-newsletter\n";
   if ($faithMatters) $message .= "* Please put me on the Faith Matters mailing list\n";
   if ($financialProfessional) $message .= "* I am a financial professional. Please mail me a fund kit.\n";

   // ............... Send email, save contact ...............
   handle_contact_form( $mysql, 'Contact Us', $c_map, $message );
   sendAsJson(true);

 } else {
   send404();
 }

?>
