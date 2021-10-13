<?php

require_once 'config.inc.php'; // TODO: better way to inject config values than globals?
require_once 'mysql.obj.inc.php';

/**
 *
 */
class Authentication {
   var $flash_msg = NULL;
   var $is_authenticated = FALSE;
}

/**
 *
 *
 * RETURNS: Instance of Authentication class
 */
function authenticate_user_using_login($mysql, $adminuser, $adminpass) {

   $incorrect_penalty = 3;

   $authentication = new Authentication();

   if (is_null($adminuser) && !is_null($adminpass)) {

      $authentication->flash_msg = "You specified a password but not a user name. You must provide a user name and password.";

   } else if (!is_null($adminuser) && is_null($adminpass)) {

      $authentication->flash_msg = "You specified a user name but not a password. You must provide a user name and password.";

   } else if (!is_null($adminuser) && !is_null($adminpass)) {

      // If specified correct user name/password, then admit and store values
      if ($adminuser == AUTH_USER && $adminpass == AUTH_PASS) {

         $authentication->is_authenticated = true;

         // Store information
         set_persistent_login($mysql, $adminuser);

      } else {

         // Wrong information. Sleep for a bit to deter brute-force attacks.
         sleep($incorrect_penalty);

         $authentication->flash_msg = "Incorrect user name and password combination. (Note that there is a $incorrect_penalty second penalty for incorrect log in attempts.)";

      }
   }

   return $authentication;
} // authenticate_user

/**
 * Verifies that user is authenticatable from persistent information, but doesn't update persistent information.
 *
 * To authenticate user, using authenticate_user_using_persistent_info, which also updates cookies/database/etc. This is just a getter.
 *
 * RETURNS: TRUE if user is logged in persistently; FALSE otherwise
 */
function is_user_logged_in_persistently($mysql) {

   lazy_remove_all_expired_records($mysql);

   if (isset($_COOKIE['hash']) && isset($_COOKIE['username'])) {

     $username = $_COOKIE['username'];
     $hash = $_COOKIE['hash'];

      $query = "SELECT * FROM persistent_login WHERE username='$username' AND hash='$hash';";

      return $mysql->resultsExist ($query);
   }

   return FALSE;
}

/**
 *
 */
function lazy_remove_all_expired_records($mysql) {
   $timestamp = time();

   $query = "DELETE FROM persistent_login WHERE expires < FROM_UNIXTIME($timestamp);";

   $mysql->executeSQL($query) or die ($mysql->showError());
}

/**
 * Verifies that user is authenticated from persistent information and updates all related information so that the user is persistent.
 *
 * RETURNS: Instance of Authentication class
 */
function authenticate_user_using_persistent_info($mysql) {

   $authentication = new Authentication();

   if (is_user_logged_in_persistently($mysql)) {

      $authentication->is_authenticated = true;

      $username = $_COOKIE['username'];
      $hash = $_COOKIE['hash'];

      // Store information
      set_persistent_login($mysql, $username, $hash);
   }

   return $authentication;
}

/**
 *
 */
function set_persistent_login($mysql, $username, $old_hash_to_remove = NULL) {

   $hash = get_random_hash();
   $year = 60 * 60 * 24 * 365;
   $expires = time() + $year;

   // Set cookie
   setcookie('username', $username, $expires);
   setcookie('hash', $hash, $expires);

   // If replacing an old hash, remove it
   if (  !is_null($old_hash_to_remove) ) {

      // Remove any of user's persistent log in entries from db
      $delete = "DELETE FROM persistent_login WHERE username = '$username' AND hash = '$old_hash_to_remove';";

      $mysql->executeSQL($delete) or die ($mysql->showError());

   }

   // Add user's persistent log in entry
   $insert = "INSERT INTO persistent_login(expires, username, hash) VALUES (FROM_UNIXTIME($expires),'$username','$hash');";

   $mysql->executeSQL($insert) or die ($mysql->showError());
}

/**
 *
 */
function destroy_persistent_login($mysql) {

   $username = $_COOKIE['username'];

   // Negative time in cookie will delete
   $year = 60 * 60 * 24 * 365;
   $expires = time() - $year;

   // Set cookie
   setcookie('username', '', $expires);
   setcookie('hash', '', $expires);

   // Remove any of user's persistent log in entries from db
   $delete = "DELETE FROM persistent_login WHERE username = '$username';";

   $mysql->executeSQL($delete) or die ($mysql->showError());
}

/**
 * Generates a random hash of length of 12 using characters [0-9,a-z,A-Z]
 */
function get_random_hash() {
   $len = 12;
   $hash = '';

   // Digit ASCII character values
   $digit_start = 48;
   $digit_end = 57;
   $digit_len = $digit_end - $digit_start + 1;

   // Lower-case ASCII character values
   $lower_char_start = 97;
   $lower_char_end = 122;
   $lower_char_len = $lower_char_end - $lower_char_start + 1;

   // Upper-case ASCII character values
   $upper_char_start = 65;
   $upper_char_end = 90;
   $upper_char_len = $upper_char_end - $upper_char_start + 1;

   for ($i = 0; $i < $len; $i++) {

      // Note that rand is inclusive on lower and upper bounds (weird)
      $num = rand(0, $digit_len + $lower_char_len + $upper_char_len - 1);

      if ($num < $digit_len) {

         // Return digit
         $hash .= chr(rand($digit_start,$digit_end));

      } else if ($num < $digit_len + $lower_char_len) {

         // Return lower-case character
         $hash .= chr(rand($lower_char_start, $lower_char_end));

      } else {

         // Return upper-case character
         $hash .= chr(rand($upper_char_start, $upper_char_end));

      }
   }

   return $hash;
}

?>
