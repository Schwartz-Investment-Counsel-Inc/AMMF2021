<?php

//
// TODO: break up.
//
//  [Admin] -> library-admin.inc.php
//  [Site] -> library-side.inc.php
//  [Admin,Site] -> library/common.inc.php
//

// MySQL file
require_once 'config.inc.php'; // TODO: better way to inject config values than globals?
require_once 'mysql.obj.inc.php';
require_once 'library/ultimus.inc.php';
require_once 'library/market_info.inc.php';
require_once 'library/html.inc.php';
require_once 'library/strings.inc.php';
require_once 'library/admin_config.inc.php';

define( 'VISIBLE', 'visible' );
define( 'HIDDEN', 'hidden' );
define( 'ADMIN_CONFIG_KEY_FUND_PRICE_UPDATES', 'fund_price_updates' );
define( 'ADMIN_CONFIG_KEY_LAST_FUND_PRICE_CHECK', 'last_fund_price_check' );
define( 'FUND_PRICE_MANUAL', 'manual' );
define( 'FUND_PRICE_ULTIMUS', 'ultimus' );
define( 'FUND_PRICE_CHECK_INTERVAL', 600); // 10 min

/**
 * [Admin, Site] Returns list of all funds.
 */
function get_funds() {
  return array('avewx', 'avegx', 'avemx', 'avedx', 'avefx', 'aveax');
}

/**
 * Whenever there's an error, write to log/stderr, and redirect user to error page.
 */
function custom_error_handler($errno, $errstr, $errfile, $errline) {

  // Check if error reporting turned OFF
  if ( error_reporting() === 0 ) return;

  // Warn on deprecated errors
  if ( $errno === E_DEPRECATED ) return;

  else {
    $msg = "[$errno] \"$errstr\" for $errfile at line #$errline";
    error_log( $msg);
    file_put_contents('php://stderr', $msg );
    if ( ! preg_match("/error\.php$/", $_SERVER["SCRIPT_NAME"]) ) {
      header('Location: https://' . $_SERVER["HTTP_HOST"] . '/error.php');
      exit(1);
    }
  }
}

//set error handler
set_error_handler("custom_error_handler");

//
// TODO: session/log in methods that are specific to financial advisors should
//       have names that make this obvious. (So not confused with admin login.)
//

/**
 * [Site] Checks whether financial advisor has valid session.
 */
function is_valid_session_login($email, $password, $mysql) {

    $select = "SELECT * FROM financial_advisor_account WHERE email=? AND password=?";

    $dbh = get_pdo_db( $mysql );
    $sth = $dbh->prepare( $select );
    $sth->execute( array( $email, hash_password($password) ) );

    return count($sth->fetchAll()) > 0;
}

/**
 * [Site] Login method for financial advisors.
 */
function handle_session_login($email, $password, $mysql) {
    $is_auth = is_valid_session_login($email, $password, $mysql);
    set_authenticated($is_auth);
    return is_authenticated();
}

/**
 * [Site] Log out method for financial advisors.
 */
function handle_financial_advisor_logout() {
  set_authenticated(false);
}

/**
 * [Site] Check whether financial advisor account exists with specific email.
 */
function check_financial_advisor_email_exists($mysql, $email) {
  $query = sprintf( "SELECT * FROM financial_advisor_account WHERE email= '%s'", mysql_real_escape_string($email) );
  return $mysql->resultsExist( $query );
}

/**
 * [Site] Submit financial advisor password reset request, starts reset workflow.
 */
function handle_financial_advisor_password_reset_request($mysql, $email) {

  // Add record to database
  $key = add_password_change_request_record( $email, $mysql );

  // Send email with instructions
  send_password_change_request_email( $email, $key );
}

/**
 * [Site] Verifies financial advisor password change request exists.
 */
function verify_password_change_request( $email, $key, $mysql ) {
  $sqlf = "SELECT * FROM `password_change_requests` WHERE `email` = '%s' AND `key` = '%s'";
  $query = sprintf($sqlf, mysql_real_escape_string($email), mysql_real_escape_string($key) );
  return $mysql->resultsExist( $query );
}

/**
 * [Site] Performs financial advisor password change reset.
 */
function handle_financial_advisor_password_reset_perform($mysql, $email, $password) {

  // Remove password change request
  remove_password_change_requests( $email, $mysql );

  // Update database
  $stmt = sprintf( "UPDATE `financial_advisor_account` SET `password` = '%s' WHERE `email` = '%s'", hash_password( $password ), mysql_real_escape_string($email) );

  $mysql->executeSQL($stmt) or die ( $mysql->showError() );

  // Send email
  $subject = 'Ave Maria Mutual Funds: Password Changed';
  $message = "Your password was recently changed. To log in, visit http://avemariafunds.com and select \"Login\".";
  send_email( $email, $subject, $message );
}

/**
 * [Site] Financial advisor signup method.
 */
function handle_financial_advisor_signup($mysql, $name, $firm, $address, $address2, $city, $state, $zip, $phone, $email, $password) {

  $query = sprintf( "INSERT INTO financial_advisor_account(`name`,`firm`,`address`, `address2`,`city`, `state`, `zip`, `phone`, `email`, `password`) VALUES ( '%s', '%s', '%s','%s', '%s', '%s', '%s', '%s', '%s', '%s' )",
    mysql_real_escape_string($name),
    mysql_real_escape_string($firm),
    mysql_real_escape_string($address),
    mysql_real_escape_string($address2),
    mysql_real_escape_string($city),
    mysql_real_escape_string($state),
    mysql_real_escape_string($zip),
    mysql_real_escape_string($phone),
    mysql_real_escape_string($email),
    mysql_real_escape_string(hash_password($password)));

  $mysql->executeSQL( $query ) or die( $mysql->showError() );

  // Send email to ave maria about new registration
  $message = <<<DONE
  REQUIRED
    Name:  $name
    Email: $email

  OPTIONAL
    Phone: $phone
    Firm:  $firm
    Address 1: $address
    Address 2: $address2
    City:  $city
    State: $state
    Zip:   $zip

DONE;

  $subject = 'New Financial Advisor Resources account registration';

  send_email( FINANCIAL_ADVISOR_EMAIL_ADDRESSES, $subject, $message );

  // Log user in
  handle_session_login($email, $password, $mysql);
}

/**
 * [Site] Helper method. Add a record for financial advisor password change
 *   request for future validation.
 */
function add_password_change_request_record( $email, $mysql ) {

  clear_expired_password_change_requests( $mysql );

  // Can only have one request per user - this is it!
  clear_password_change_requests_for_email( $mysql, $email );

  $key = generate_random_password_reset_key();

  $query = sprintf( "INSERT INTO password_change_requests(`email`,`created`,`key`) VALUES ( '%s', NOW(), '%s' )", mysql_real_escape_string($email), mysql_real_escape_string($key) );

  $mysql->executeSQL( $query ) or die( $mysql->showError );

  return $key;
}

/**
 * [Site] Helper method. Removes financial advisor password change requests for
 *   email address.
 */
function remove_password_change_requests( $email, $mysql ) {

  $stmt = sprintf( "DELETE FROM `password_change_requests` WHERE `email` = '%s'", mysql_real_escape_string($email) );

  $mysql->executeSQL($stmt) or die ( $mysql->showError() );

}

/**
 * [Site] Clears out all financial advisor password change requests that expired.
 */
function clear_expired_password_change_requests( $mysql ) {

  $stmt = "DELETE FROM `password_change_requests` WHERE `created` < now() - interval 1 day;";

  $mysql->executeSQL($stmt) or die ( $mysql->showError() );

}

/**
 * [Site] clear out all financial advisor password change requests for given
 *   email address.
 */
function clear_password_change_requests_for_email( $mysql, $email ) {

  $query = sprintf( "DELETE FROM `password_change_requests` WHERE `email` = '%s'", mysql_real_escape_string($email) );

  $mysql->executeSQL( $query ) or die( $mysql->showError );

}

/**
 * [Site] Helper method. Sends password change request email to financial advisor.
 */
function send_password_change_request_email( $email, $key ) {
  $subject = 'Ave Maria Mutual Funds: request to change password for Financial Advisor Resources';

  $message = 'You received this email because you submitted a request to change your password.' . "\n\n" . 'To change your password, please fill visit the form on the following page: ' . "\n\n" . get_password_change_form_url( $key );

  send_email( $email, $subject, $message );
}

/**
 * [Site] Helper method. Construct address for changing password.
 */
function get_password_change_form_url( $key ) {
  // Note: version 1 should be change-password.php
  return 'http://' . $_SERVER["HTTP_HOST"] . '/change-password.html?key=' . $key;
}

/**
 * [Site] Adds contact submission and sends email to default addresses.
 */
function handle_contact_form( $mysql, $page_type, $c_map, $message ) {

  add_contact( $mysql, $c_map );

  $subject = "Ave Maria \"$page_type\" submitted ".date('l jS \of F Y h:i:s A');

  send_email( CONTACT_SUBMITTED_EMAIL_ADDRESSES, $subject, $message );
}

/**
 * [Site] Helper method for sending email.
 */
function send_email( $email_addresses, $subject, $message ) {

  $headers = 'From: no-reply@avemariafunds.com' . "\n" .
         'Reply-To: no-reply@avemariafunds.com';

  // Prepend subject prefix, if exists
  if (EMAIL_SUBJECT_PREFIX) {
    $subject = sprintf("[%s] %s", EMAIL_SUBJECT_PREFIX, $subject);
  }

  // Only send email if $email_addresses non-blank
  if ($email_addresses) {
    if ( ! mail($email_addresses, $subject, $message, $headers) ) {
      die( "Email could not be sent to " . $email_addresses );
    }
    error_log(sprintf("Sent message '%s' to %s: %s", $subject, $email_addresses, $message));
  } else {
    error_log(sprintf("No email address specified, so did not send message '%s': %s", $subject, $message));
  }
}

/**
 * [Site] Helper method. Generates random password reset key for financial
 *   advisors.
 */
function generate_random_password_reset_key() {
  $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
  $len = 26;

  $string = '';
  for ($i = 0; $i < $len; $i++) {
    $string .= $characters[rand(0, strlen($characters) - 1)];
  }

  return $string;
}

$pdo_db = NULL;

/**
 * [Site] Helper method. Gets a PDO database, lazily initializing if necessary.
 */
function get_pdo_db( &$mysql ) {

  global $pdo_db;

  if ( !isset($pdo_db) ) {

    $dsn = "mysql:host=" . $mysql->ADDRESS . ";dbname=" . $mysql->DATABASE;

    $pdo_db = new PDO( $dsn, $mysql->USER, $mysql->PASSWORD);

    $pdo_db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

  }

  return $pdo_db;
}

/**
 * [Site] Salts and hashes password for end users.
 */
function hash_password( $password ) {
  $salt = sha1(md5($password));
  return sha1( $salt . $password );
}

/**
 * [Site] Helper method. Safely start session.
 */
function lazy_start_session() {
  if( !isset($_SESSION) ) {
    session_start();
  }
}

/**
 * [Site] Whether end user is authenticated.
 */
function set_authenticated( $authenticated ) {
  lazy_start_session();
  $_SESSION[ 'authenticated' ] = $authenticated;
}

/**
 * [Site] Checks whether end user is authenticated.
 */
function is_authenticated() {
  lazy_start_session();

  if ( ! isset( $_SESSION[ 'authenticated' ] ) ) {
    return false;
  }

  return $_SESSION[ 'authenticated' ];
}

/**
 * [Admin, Site] Extracts value from GET or POST globals, if available.
 */
function get_val_get_post_var($name) {
    if (isset( $_GET[$name] ) && $_GET[$name]) {
        return $_GET[$name];
    } else if (isset( $_POST[$name] ) && $_POST[$name]) {
        return $_POST[$name];
    }
    return NULL;
}

/**
 * [Admin] Helper method. For identifying my strange fund info POST parameters.
 */
function is_field_id($id) {
    return count(tokenize_id($id)) == 3; // TODO: too fragile
}

/**
 * [Admin] Helper method. For parsing table name from my strange fund info POST parameters.
 */
function parse_table_name($id) {
    $tokens = tokenize_id($id);
    return $tokens[0];
}

/**
 * [Admin] Helper method. For parsing field name from my strange fund info POST parameters.
 */
function parse_field_name($id) {
    $tokens = tokenize_id($id);
    return $tokens[1];
}

/**
 * [Admin] Helper method. For parsing pks from my strange fund info POST parameters.
 */
function parse_pk($id) {
    $tokens = tokenize_id($id);
    return $tokens[2];
}

/**
 * [Admin] Helper method. Tokenizes my strange fund info POST parameters.
 */
function tokenize_id($id) {
    return explode("-", $id);
}

/**
 * [Admin, Site] Helper method. Returns timestamp as number.
 */
function get_market_price_last_updated_timestamp($mysql, $fund) {
    $query = 'SELECT UNIX_TIMESTAMP(last_updated) FROM '.$fund.'_market_prices;';
    $result = $mysql->getResults ($query);

    return $result[0][0];
}

/**
 * [Admin, Site] Get the last trade date.
 */
function get_last_trade_date($mysql, $fund) {

    lazy_update_fund_prices($mysql);

    $query = 'SELECT last_trade_date FROM '.$fund.'_market_prices;';
    $result = $mysql->getResults ($query);

    return $result[0][0];
}

/**
 * [Admin, Site] Returns current price as float. Doesn't include string symbols.
 */
function get_current_price($mysql, $fund) {

    lazy_update_fund_prices($mysql);

    $query = 'SELECT current_price FROM '.$fund.'_market_prices;';
    $result = $mysql->getResults ($query);

    return $result[0][0];
}

/**
 * [Site] Returns current price as string with appropriate formatting for display.
 */
function get_current_price_str($mysql, $fund) {
    $price = get_current_price($mysql, $fund);
    return as_price($price);
}

/**
 * [Admin, Site] Returns daily change as float. Doesn't include string symbols.
 */
function get_daily_change_in_dollars($mysql, $fund) {

    lazy_update_fund_prices($mysql);

    $query = 'SELECT daily_change_in_dollars FROM '.$fund.'_market_prices;';
    $result = $mysql->getResults ($query);

    return $result[0][0];
}

/**
 * [Site] Returns daily change as string with appropriate formatting for display.
 */
function get_daily_change_in_dollars_str($mysql, $fund) {
    $daily_change_in_dollars = number_format(get_daily_change_in_dollars($mysql, $fund), 2, '.', '');
    return as_price($daily_change_in_dollars);
}

/**
 * [Site, Admin] Returns float percentage. Doesn't include string symbols.
 */
function get_daily_change_in_percentage($mysql, $fund) {

    lazy_update_fund_prices($mysql);

    $query = 'SELECT daily_change_in_percentage FROM '.$fund.'_market_prices;';
    $result = $mysql->getResults ($query);

    return $result[0][0];
}

/**
 * [Site] Returns float percentage as string with appropriate formatting for display.
 */
function get_daily_change_in_percentage_str($mysql, $fund) {
    $daily_change_in_percentage = number_format(get_daily_change_in_percentage($mysql, $fund), 2, '.', '');
    return as_percent($daily_change_in_percentage);
}

/**
 * [Admin] Returns true if fund price has dropped.
 */
function is_market_price_drop($mysql, $fund) {
    if (get_daily_change_in_dollars($mysql, $fund) >= 0) {
        return false;
    }
    return true;
}

/**
 * [Admin, Site] perform lazy update of fund's market price.
 */
function lazy_update_fund_prices($mysql) {

  // No lazy updates if manually configured
  $method = get_fund_price_update_method($mysql);
  if (FUND_PRICE_MANUAL == $method) {
    return;
  }

  // Don't check until min time ellapsed
  if (!is_time_to_check_fund_price($mysql) ) {
    return;
  }

  update_last_fund_price_check($mysql);

  error_log("Checking for price updates...");

  $warnings = array();

  // Check each fund for updates
  foreach (get_funds() as $fund) {

    $market_price;

    //
    // Get price info
    //
    switch ($method) {
      case FUND_PRICE_ULTIMUS:
        $dir = dirname(dirname(__FILE__));
        $market_price = get_market_price_from_ultimus( "{$dir}/ultimus", time(), $fund );
        break;
    }

    //
    // Save price info
    //
    if ($market_price) {
      // Only update if found something newer
      $last_trade = get_last_trade_date($mysql, $fund);
      $found = $market_price->last_trade_date;
      if (is_null($last_trade) || strtotime($found) > strtotime($last_trade)) {
        error_log("Found $fund price updates...");
        save_market_price( $mysql, $market_price );

        // Note: warnings only count if saved. Hence won't send duplicate warning
        //   email because won't save if same trade date.
        $warnings = array_merge($warnings, $market_price->warnings);
      }
    }

  } // check each fund for updates

  //
  // Email warnings
  //
  $warning_count = count($warnings);
  if ($warning_count > 0) {

    $subject = "Ave Maria Mutual Funds: $warning_count warnings from today's fund price update.";

    $message = 'Warning(s) were discovered while updating prices today:' . "\n\n" . join("\n\n", $warnings);

    send_email( TECH_SUPPORT_EMAIL_ADDRESSES, $subject, $message );
  }

} // lazy_update_fund_prices

/**
 * [Admin] Set how the fund prices are updated.
 */
function set_fund_price_update_method( $mysql, $value ) {
  set_admin_config( $mysql, ADMIN_CONFIG_KEY_FUND_PRICE_UPDATES, $value);
}

/**
 * [Admin, Site] Fetch how the fund prices are updated.
 */
function get_fund_price_update_method( $mysql ) {
  $value = get_admin_config( $mysql, ADMIN_CONFIG_KEY_FUND_PRICE_UPDATES );
  return is_null($value) ? FUND_PRICE_MANUAL : $value;
}

/**
 * [Admin, Site] Set last fund price check timestamp to now. (A slight abuse of
 *   admin name-value configuration table.)
 */
function update_last_fund_price_check( $mysql ) {
  set_admin_config( $mysql, ADMIN_CONFIG_KEY_LAST_FUND_PRICE_CHECK, time());
}

/**
 * [Admin, Site] Get last fund price check timestamp. (A slight abuse of
 *   admin name-value configuration table.)
 */
function get_last_fund_price_check( $mysql ) {
  return get_admin_config( $mysql, ADMIN_CONFIG_KEY_LAST_FUND_PRICE_CHECK );
}

/**
 * [Admin, Site] Returns true if time to update fund prices.
 */
function is_time_to_check_fund_price( $mysql ) {
  $prev = get_last_fund_price_check($mysql);
  return is_null($prev) ? true : time() - $prev >= FUND_PRICE_CHECK_INTERVAL;
}

/**
 * [Admin, Site] Save market price information.
 */
function save_market_price( $mysql, $market ) {
  $query = 'SELECT * FROM '.mysql_real_escape_string( $market->fund ).'_market_prices';

  if ($mysql->resultsExist ($query)) {

    $stmt = sprintf('UPDATE '.mysql_real_escape_string( $market->fund ).'_market_prices SET last_updated=FROM_UNIXTIME(%u), current_price=%f, daily_change_in_dollars=%f, daily_change_in_percentage=%f, last_trade_date="'.mysql_real_escape_string( $market->last_trade_date ).'"', time(), mysql_real_escape_string( $market->price ), mysql_real_escape_string( $market->daily_change_price ), mysql_real_escape_string( $market->daily_change_percentage ) );

    $mysql->executeSQL($stmt) or die ("save_market_price ($stmt): "+$mysql->showError());

  } else {

    $stmt = sprintf('INSERT INTO '.mysql_real_escape_string( $market->fund ).'_market_prices (last_updated, current_price, daily_change_in_dollars, daily_change_in_percentage, last_trade_date) VALUES (FROM_UNIXTIME(%u), %f, %f, %f, "'.mysql_real_escape_string( $market->last_trade_date ) .'")', time(), mysql_real_escape_string( $market->price ), mysql_real_escape_string( $market->daily_change_price ), mysql_real_escape_string( $market->daily_change_percentage ));

    $mysql->executeSQL($stmt) or die ("save_market_price ($stmt): "+$mysql->showError());

  }
}

/**
 * [Admin, Site] Updates the timestamp for fund's market price last update.
 */
function update_market_price_timestamp( $mysql, $fund ) {
  $stmt = sprintf('UPDATE '.$fund.'_market_prices SET last_updated=FROM_UNIXTIME(%u)', time() );
  $mysql->executeSQL($stmt) or die ("update_market_price_timestamp ($stmt): "+$mysql->showError());
}

/**
 * [Admin] Saves changes to fund.
 */
function save_admin_fund_changes($mysql, $fund) {

    // Only save fund prices changes if configuration set to 'manual'
    if (FUND_PRICE_MANUAL == get_fund_price_update_method($mysql)) {
      save_fund_price_values($mysql, $fund);
    }

    save_total_returns_changes($mysql, $fund);
    save_total_returns_footer($mysql, $fund);
    save_fund_info($mysql, $fund);

    return "Your changes were saved.";

} // save_admin_fund_changes

/**
 * [Admin] Helper method. Finds and extracts primary keys for specified table
 *   from instances of my strange "table-field-pk" fund info POST parameters.
 *
 * As of 2017/11/04, only valid table_name is 'fund_information'
 */
function extract_admin_fund_change_pks($table_name) {

  $pks = array();

  foreach($_POST as $key=>$value) {
      if (isset($key) && is_field_id($key)) {
          $next_table_name = parse_table_name($key);
          if (strcmp ($table_name, $next_table_name) == 0) {
            $pk = parse_pk($key);
            if (!in_array($pk, $pks)) {
                array_push($pks, $pk);
            }
          }
      }
  }

  return $pks;

} // extract_admin_fund_change_pks

/**
 * [Admin] Saves values of fund prices (e.g., price changes and related data) in admin site.
 */
function save_fund_price_values($mysql, $fund) {

  $market = new MarketInfo();
  $market->from = '/admin/' . $fund . '.php'; // TODO: shouldn't need this
  $market->fund = $fund;
  $market->price = $_POST['market_info_current_price'];
  $market->daily_change_price = $_POST['market_info_daily_change_dollar'];
  $market->daily_change_percentage = $_POST['market_info_daily_change_percent'];
  $market->last_trade_date = $_POST['market_info_last_trade_date'];

  save_market_price( $mysql, $market );

} // save_fund_price_values

/**
 * [Admin] Helper method. For removing rows from Fund Info table.
 */
function delete_from_table_if_not_contain_pk($table_name, $pks_arr, $mysql) {
    $query = "SELECT * FROM $table_name;";
    $result = $mysql->getResults ($query);

    for ($i = 0; $i < sizeof($result); $i++) {
        $row = $result[$i];
        $pk = $row[0];

        if (!in_array($pk, $pks_arr)) {
            $stmt = "DELETE FROM $table_name WHERE id=$pk;";

            $mysql->executeSQL($stmt) or die ("delete_from_table_if_not_contain_pk ($stmt): "+$mysql->showError());
        }
    }
}

/**
 * [Admin] Saves changes to Fund Information table updated in admin site.
 */
function save_fund_info($mysql, $fund) {

  $pks = extract_admin_fund_change_pks('fund_information');
  $table_type = 'fund_information';
  $table_name = $fund.'_'.$table_type;

  delete_from_table_if_not_contain_pk($fund.'_fund_information', $pks, $mysql);

  foreach ($pks as $ignore=>$pk) {

      $name_index = "$table_type-name-$pk";
      $name = $_POST[$name_index];

      if (!isset($name)) {
          die("Cannot update $table_name with $name_index -- not found. Please notify developer with date/time and this error message.");
      }

      $value_index = "$table_type-value-$pk";
      $value = $_POST[$value_index];

      if (!isset($value)) {
          die("Cannot update $table_name with $value_index -- not found. Please notify developer with date/time and this error message.");
      }

      $query = "SELECT * FROM $table_name WHERE id=$pk";

      if ($mysql->resultsExist ($query)) {

          $stmt = sprintf("UPDATE $table_name SET name='%s', value='%s' WHERE id=%u", $name, $value, $pk);

          $mysql->executeSQL($stmt) or die ("save_fund_info ($stmt): "+$mysql->showError());

      } else {

          $stmt = sprintf("INSERT INTO $table_name (name, value, id) VALUES ('%s', '%s', %u)", $name, $value, $pk);

          $mysql->executeSQL($stmt) or die ("save_fund_info ($stmt): "+$mysql->showError());

      }
  }
} // save_fund_info

/**
 * [Admin, Site]
 */
function get_total_returns_footer($mysql, $fund) {
    $query = "SELECT * FROM ${fund}_total_returns_footer;";
    if ($mysql->resultsExist ($query)) {
      $result = $mysql->getResults ($query);
      return $result[0][0];
  } else {
      return NULL;
  }
}

/**
 * [Admin] Save changes to the Total Returns footer made in the admin site.
 */
function save_total_returns_footer($mysql, $fund) {
  // TODO: all legacy admin functions should accept in explicit arguments
  //       instead of parsing $_POST
  $val = $_POST['total_returns_footer_value'];

  // Upserting. Note this table will always have exactly 0 or 1 row.

  // TODO: we should create a single total_returns_footers table with the fund
  //       name as the primary key; then we could do something like this:
  //    INSERT IGNORE INTO total_returns_footers SET value('aveax', 'whatever')
  if (get_total_returns_footer($mysql, $fund) != NULL) {
      $stmt = sprintf("UPDATE ".$fund."_total_returns_footer SET value='%s'", mysql_real_escape_string($val));
  } else {
      $stmt = sprintf("INSERT INTO ".$fund."_total_returns_footer VALUES ('%s')", mysql_real_escape_string($val));
  }

  $mysql->executeSQL($stmt) or die ("save_total_returns_footer ($stmt): "+$mysql->showError());
} // save_total_returns_footer

/**
 * [Admin] Save changes to the Total Returns table made in the admin site.
 */
function save_total_returns_changes($mysql, $fund_name) {

    $table_name = 'total_returns';

    $table = get_dynamic_table($mysql, $fund_name, $table_name);

    if (!isset($table)) {
        return;
    }

    $fund_table_id = $table->id;

    $max_row = 0;
    $max_col = 0;

    // Only receive annualized if checked. Gather values, then set afterwards based on what is present vs missing.
    $annualized = array();

    foreach($_POST as $key=>$value) {

        if (!isset($key)) {
          continue;
        }

        //
        // If data cell
        //
        else if (is_dynamic_table_data_value_name($table_name, $key)) {
            $row_index = parse_row_index_from_dynamic_table_value_name($table_name, $key);
            $col_index = parse_col_index_from_dynamic_table_value_name($table_name, $key);

            // If exists in database, update. Else add.
            $query_stmt = "SELECT * FROM fund_table_value WHERE row_index = $row_index AND col_index = $col_index AND fund_table_id = $fund_table_id;";

            if ($mysql->resultsExist ($query_stmt)) {
                $replace_stmt = "UPDATE fund_table_value SET value = '$value' WHERE row_index = $row_index AND col_index = $col_index AND fund_table_id = $fund_table_id;";
                $mysql->executeSQL($replace_stmt) or die ("In save_total_returns_changes ($replace_stmt): " + $mysql->showError());
            } else {
                $add_stmt = "INSERT INTO fund_table_value(`fund_table_id`,`row_index`,`col_index`,`value`) VALUES ($fund_table_id, $row_index, $col_index, '$value');";
                $mysql->executeSQL($add_stmt) or die ("In save_total_returns_changes($add_stmt): " + $mysql->showError());
            }

            // Update any max values
            if ($row_index > $max_row) {
                $max_row = $row_index;
            }
            if ($col_index > $max_col) {
                $max_col = $col_index;
            }
        }

        //
        // If header cell
        //
        else if (is_dynamic_table_header_value_name($table_name, $key)) {
            $col_index = parse_col_index_from_dynamic_table_header_value_name($table_name, $key);

            // If exists in database, update. Else add.
            $query_stmt = "SELECT * FROM fund_table_col_name WHERE `index` = $col_index AND fund_table_id = $fund_table_id;";

            if ($mysql->resultsExist ($query_stmt)) {
                $replace_stmt = "UPDATE fund_table_col_name SET value = '$value' WHERE `index` = $col_index AND fund_table_id = $fund_table_id;";
                $mysql->executeSQL($replace_stmt) or die ("In save_total_returns_changes($replace_stmt): " + $mysql->showError());
            } else {
                $add_stmt = "INSERT INTO fund_table_col_name(`fund_table_id`, `index`, `value`) VALUES ($fund_table_id, $col_index, '$value');";
                $mysql->executeSQL($add_stmt) or die ("In save_total_returns_changes($add_stmt): " + $mysql->showError());
            }

            // Update any max values
            if ($col_index > $max_col) {
                $max_col = $col_index;
            }
        }

        //
        // If annualized cell
        //
        else if (is_dynamic_table_annualized_value_name($table_name, $key)) {
          $col = parse_col_index_from_dynamic_table_annualized_value_name($table_name, $key);
          array_push($annualized, $col);
        }

    } // foreach posted value...

    $row_count = $max_row + 1;
    $col_count = $max_col + 1;

    //
    // Update annualized
    //
    for ($col = 1; $col < $col_count; $col++) {

      $stmt = get_total_returns_annualized_query($fund_name, $col);

      // Create MySQL BOOL value from presence or absence of column
      $value = in_array($col, $annualized) ? "TRUE" : "FALSE";

      if ($mysql->resultsExist ($stmt)) {
        $stmt = "UPDATE `annualized_total_returns_col` SET `annualized`=$value WHERE `fund_symbol`='$fund_name' AND `col`=$col";
        $mysql->executeSQL($stmt) or die ("In save_total_returns_changes($stmt): " + $mysql->showError());
      } else {
        $stmt = "INSERT INTO `annualized_total_returns_col` (`fund_symbol`, `col`, `annualized`) VALUES ('$fund_name', $col, $value)";
        $mysql->executeSQL($stmt) or die ("In save_total_returns_changes($stmt): " + $mysql->showError());
      }
    }

    //
    // Update fund_table to have correct row_count and table_name
    //
    $update_stmt = "UPDATE fund_table SET row_count = $row_count, col_count = $col_count WHERE id = $fund_table_id;";
    $mysql->executeSQL($update_stmt) or die ("In save_total_returns_changes($update_stmt): " + $mysql->showError());

    //
    // Clean up table
    //
    clean_dynamic_table($mysql, $fund_name, $table_name);
} // save_total_returns_changes

/**
 * [Admin] Clears out headers and data for table that are beyond row or column
 *   count according to fund_table entry.
 */
function clean_dynamic_table($mysql, $fund_name, $table_name) {
    $table = get_dynamic_table($mysql, $fund_name, $table_name);

    if (!isset($table)) {
        return;
    }

    $fund_table_id = $table->id;
    $max_row_index = $table->row_count - 1;
    $max_col_index = $table->col_count - 1;

    $data_stmt = "DELETE FROM fund_table_value WHERE fund_table_id = $fund_table_id AND (row_index > $max_row_index OR col_index > $max_col_index);";
    $mysql->executeSQL($data_stmt) or die ("In save_total_returns_changes($data_stmt): " + $mysql->showError());

    $header_stmt = "DELETE FROM fund_table_col_name WHERE fund_table_id = $fund_table_id AND `index` > $max_col_index;";
    $mysql->executeSQL($header_stmt) or die ("In save_total_returns_changes($header_stmt): " + $mysql->showError());
}

/**
 * [Admin] Is the HTTP variable a specific dynamic table's data id?
 */
function is_dynamic_table_data_value_name($table_name, $key) {
  return  _is_dynamic_table_type_value_name($key, $table_name, 4, 'value');
}

/**
 * [Admin] Is the HTTP variable a specific dynamic table's header id?
 */
function is_dynamic_table_header_value_name($table_name, $key) {
  return _is_dynamic_table_type_value_name($key, $table_name, 3, 'header');
}

/**
 * [Admin] Is the HTTP variable a specific dynamic table's header id?
 */
function is_dynamic_table_annualized_value_name($table_name, $key) {
  return _is_dynamic_table_type_value_name($key, $table_name, 3, 'annualized');
}

/**
 * [Admin] Helper function. Testing whether HTTP value is intended for a particular dynamic table type.
 * @param $key E.g., "total_returns-header-1"
 * @param $expected_table_name E.g., "total_returns"
 * @param $expected_token_count E.g., 3 for header ({total_returns, header, 1}); 4 for data
 * @param $expected_type E.g., "value", "header", "annualized"
 */
function _is_dynamic_table_type_value_name($key, $expected_table_name, $expected_token_count, $expected_type) {
  $tokens = tokenize_id($key);
  return (count($tokens) == $expected_token_count) &&
         (strcmp($tokens[0], $expected_table_name) == 0) &&
         (strcmp($tokens[1], $expected_type) == 0);
}

/**
 * [Admin] Helper function.
 */
function parse_row_index_from_dynamic_table_value_name($table_name, $key) {
    is_dynamic_table_data_value_name($table_name, $key) or die("Cannot parse row index from <$key>, not a dynamic table data cell name for <$table_name>.");
    $tokens = tokenize_id($key);
    return (int)$tokens[2];
}

/**
 * [Admin] Helper function.
 */
function parse_col_index_from_dynamic_table_value_name($table_name, $key) {
    is_dynamic_table_data_value_name($table_name, $key) or die("Cannot parse col index from <$key>, not a dynamic table data cell name for <$table_name>.");
    $tokens = tokenize_id($key);
    return (int)$tokens[3];
}

/**
 * [Admin] Helper function.
 */
function parse_col_index_from_dynamic_table_header_value_name($table_name, $key) {
    is_dynamic_table_header_value_name($table_name, $key) or die("Cannot parse col index from <$key>, not a dynamic table header cell name for <$table_name>.");
    $tokens = tokenize_id($key);
    return (int)$tokens[2];
}

/**
 * [Admin] Helper function.
 */
function parse_col_index_from_dynamic_table_annualized_value_name($table_name, $key) {
    is_dynamic_table_annualized_value_name($table_name, $key) or die("Cannot parse col index from <$key>, not a dynamic table annualized cell name for <$table_name>.");
    $tokens = tokenize_id($key);
    return (int)$tokens[2];
}

/**
 * [Admin,Site] Model representing Total Returns table, including all of its data.
 */
class DynamicTable {
    var $id = NULL;
    var $fund_name = NULL;
    var $table_name = NULL;
    var $row_count = NULL;
    var $col_count = NULL;
    var $headers = null;
    var $rows = null;
    var $annualized_flags = null;
}

/**
 * [Admin,Site] Fetch all necessary data for rendering Total Returns table,
 *   which is dynamic.
 */
function get_dynamic_table($mysql, $fund_name, $table_name) {

    $stmt = "SELECT `id`,`row_count`,`col_count` FROM fund_table WHERE fund_name='$fund_name' AND table_name='$table_name';";

    if ($mysql->resultsExist ($stmt)) {

        $result = $mysql->getResults($stmt) or die ("In get_dynamic_table($stmt): " + $mysql->showError());

        $table = new DynamicTable();

        $table->fund_name = $fund_name;
        $table->table_name = $table_name;
        $table->id = $result[0][0];
        $table->row_count = $result[0][1];
        $table->col_count = $result[0][2];

        get_dynamic_table_headers($mysql, $table);
        get_dynamic_table_content($mysql, $table);
        get_dynamic_table_annualized_flags($mysql, $table);

        return $table;

    } else {

        $table = new DynamicTable();

        $table->fund_name = $fund_name;
        $table->table_name = $table_name;
        $table->id = NULL;
        $table->row_count = 0;
        $table->col_count = 0;

        return $table;
    }
} // get_dynamic_table

/**
 * [Admin,Site] Fetch headers for Total Returns table.
 */
function get_dynamic_table_headers($mysql, DynamicTable &$table) {

    $stmt = "SELECT `index`, `value` FROM fund_table_col_name WHERE fund_table_id=$table->id;";
    //echo "<!-- $stmt -->\n";

    if ($mysql->resultsExist ($stmt)) {

        $results = $mysql->getResults($stmt) or die ("In get_dynamic_table_headers($stmt): ".$mysql->showError());

        $headers = array();

        for ($i = 0; $i < $table->col_count; $i++) {

            // Initialize to empty so defined for proper range
            $headers[$i] = "";

            // Find correct value for this header
            foreach ($results as $next_result) {
                if (strcmp ($next_result[0], $i) == 0) {
                    $headers[$i] = $next_result[1];
                    break;
                }
            }
        }

        $table->headers = $headers;
        return;
    }

    $table->headers = NULL;
} // get_dynamic_table_headers

/**
 * [Admin,Site] Fetch content for Total Returns table.
 */
function get_dynamic_table_content($mysql, DynamicTable &$table) {

    $stmt = "SELECT `row_index`, `col_index`, `value` FROM fund_table_value WHERE fund_table_id=$table->id;";
    //echo "<!-- $stmt -->\n";

    if ($mysql->resultsExist ($stmt)) {

        $results = $mysql->getResults($stmt) or die ("get_dynamic_table_content($stmt) : ".$mysql->showError());

        $rows = array();

        for ($i = 0; $i < $table->row_count; $i++) {

            $rows[$i] = array();
            for ($j = 0; $j < $table->col_count; $j++) {

                // Initialize to empty so defined for proper range
                $rows[$i][$j] = "";

                // Find correct value for this header
                foreach ($results as $next_result) {
                    if (strcmp ($next_result[0], $i) == 0 && strcmp ($next_result[1], $j) == 0) {
                        $rows[$i][$j] = $next_result[2];
                        break;
                    }
                }
            }
        }

        $table->rows =  $rows;
        return;
    }

    $table->rows = NULL;
} // get_dynamic_table_content

/**
 * [Admin,Site] Fetch headers for Total Returns table.
 */
function get_dynamic_table_annualized_flags($mysql, DynamicTable &$table) {

  $table->annualized_flags = array();

  //
  // For each column, fetch whether annualized flag is set
  //
  for ($col = 1; $col < $table->col_count; $col++) {
    $table->annualized_flags[$col] = get_total_returns_annualized_value($mysql, $table->fund_name, $col);
  }
}

/**
 * [Admin,Site] Helper method. Returns query for annualized flag.
 */
function get_total_returns_annualized_query($fund_name, $col) {
  return "SELECT `annualized` FROM `annualized_total_returns_col` WHERE `fund_symbol`=\"$fund_name\" AND `col`=$col;";
}

/**
 * [Admin,Site] Helper method, returns database value for annualized flag.
 */
function get_total_returns_annualized_value($mysql, $fund_name, $col) {

  $stmt = get_total_returns_annualized_query($fund_name, $col);

  if ($mysql->resultsExist ($stmt)) {

      $results = $mysql->getResults($stmt) or die ("get_dynamic_table_annualized_flags($stmt) : ".$mysql->showError());

      // Unique constraint, only one
      return $results[0][0] ? TRUE : FALSE;
  }

  return FALSE; // Not set yet
}

/**
 * [Admin] Delete row from total returns table, which is dynamic.
 */
function delete_row_from_dynamic_table($mysql, $fund_name, $table_name, $row_index) {

    $table = get_dynamic_table($mysql, $fund_name, $table_name);

    if (!isset($table)) {
        return "Error: table not found.";
    }

    $fund_table_id = $table->id;

    $exists_stmt = "SELECT * FROM fund_table_value WHERE row_index = $row_index AND fund_table_id = $fund_table_id;";

    if ($mysql->resultsExist ($exists_stmt)) {

        // Remove row from database
        $rm_stmt = "DELETE FROM fund_table_value WHERE row_index = $row_index AND fund_table_id = $fund_table_id;";

        $mysql->executeSQL($rm_stmt) or die ("delete_row_from_dynamic_table ($rm_stmt): "+$mysql->showError());

        // Decrement row count in fund_table
        $decr_stmt = "UPDATE fund_table SET row_count = row_count - 1 WHERE id = $fund_table_id;";

        $mysql->executeSQL($decr_stmt) or die ("delete_row_from_dynamic_table ($decr_stmt): "+$mysql->showError());

        // If other rows with index of greater value, decrement their index
        $decrement_stmt = "UPDATE fund_table_value SET row_index = row_index - 1 WHERE row_index > $row_index AND fund_table_id = $fund_table_id;";

        $mysql->executeSQL($decrement_stmt) or die ("delete_row_from_dynamic_table ($decrement_stmt): "+$mysql->showError());

        return "Column was removed.";
    } else {
        return "Matching row not found in database; nothing removed.";
    }

} // delete_row_from_dynamic_table

/**
 * [Admin] Delete column from total returns table, which is dynamic.
 */
function delete_column_from_dynamic_table($mysql, $fund_name, $table_name, $col_index) {

    $table = get_dynamic_table($mysql, $fund_name, $table_name);

    if (!isset($table)) {
        return "Error: table not found.";
    }

    $fund_table_id = $table->id;

    $exists_stmt = "SELECT * FROM fund_table_value WHERE col_index = $col_index AND fund_table_id = $fund_table_id;";

    if ($mysql->resultsExist ($exists_stmt)) {

        // Remove col from database
        $rm_stmt_1 = "DELETE FROM fund_table_value WHERE col_index = $col_index AND fund_table_id = $fund_table_id;";

        $mysql->executeSQL($rm_stmt_1) or die ("delete_column_from_dynamic_table ($rm_stmt_1): "+$mysql->showError());

        // Remove header from database
        $rm_stmt_2 = "DELETE FROM fund_table_col_name WHERE `index` = $col_index AND fund_table_id = $fund_table_id;";

        $mysql->executeSQL($rm_stmt_2) or die ("delete_column_from_dynamic_table ($rm_stmt_2): "+$mysql->showError());

        // Decrement col count in fund_table
        $decr_stmt = "UPDATE fund_table SET col_count = col_count - 1 WHERE id = $fund_table_id;";

        $mysql->executeSQL($decr_stmt) or die ("delete_column_from_dynamic_table ($decr_stmt): "+$mysql->showError());

        // If other cols with index of greater value, decrement their index
        $decrement_stmt = "UPDATE fund_table_value SET col_index = col_index - 1 WHERE col_index > $col_index AND fund_table_id = $fund_table_id;";

        $mysql->executeSQL($decrement_stmt) or die ("delete_column_from_dynamic_table ($decrement_stmt): "+$mysql->showError());

        $decrement_stmt_2 = "UPDATE fund_table_col_name SET `index` = `index` - 1 WHERE  `index` > $col_index AND fund_table_id = $fund_table_id;";

        $mysql->executeSQL($decrement_stmt_2) or die ("delete_column_from_dynamic_table ($decrement_stmt_2): "+$mysql->showError());

        return "Column was removed.";
    } else {
        return "Matching column not found in database; nothing removed.";
    }

} // delete_column_from_dynamic_table

/**
 * [Admin] Helper method. Given map & key, converts value to 1 if string
 *   evaluates to true, and any other string to 0.
 */
function to_mysql_bool( &$map, $key ) {
  $map[$key] = $map[$key] ? 1 : 0;
}

/**
 * [Admin] Helper method. Replaces all keys & vals w/ equivalent output from
 *   mysql_real_escape_string.
 */
function to_mysql_escape( &$map ) {
  foreach ($map as $key => $val) {
    $map[ mysql_real_escape_string( $key ) ] = mysql_real_escape_string( $val );
  }
}

/**
 * [Admin] Helper method. If key is for boolean value. (Starts w/ "is_")
 */
function is_bool_key( $key ) {
  return strpos($key, 'is_') === 0;
}

/**
 * [Admin] Helper method. Given a map and a table name, returns a query to
 *   insert items in table.
 *
 * Two requirements:
 *   1. Keys *must* match column names exactly
 *   2. Any boolean columns should start with "is_", e.g., "is_foo".
 *   3. Must be a column 'id', and the map must contain.
 */
function gen_update_stmt( $table_name, $map ) {

  // Assert 'id' set
  if ( ! array_key_exists( 'id', $map ) ) {
    die("Must prove value for 'id' in \$map for gen_update_stmt");
  }

  // Convert to mysql bools (0 or 1)
  foreach ($map as $key => $val) {
    if ( is_bool_key( $key ) ) {
      to_mysql_bool( $map, $key );
    }
  }

  // Escape table name, keys & vals (trust nothing)
  to_mysql_escape( $map );
  $table_name = mysql_real_escape_string( $table_name );

  // Build insert string for sprintf3:
  //  UPDATE `foo` SET `bar` = '%bar%', ... WHERE `id` = '%id%'

  $values = '';
  foreach ($map as $key => $val) {

    if ( $key === 'id' ) {
      continue; // Don't set; using for WHERE clause
    }

    $col = "`${key}`";
    if (is_bool_key( $key ) ) {
      $rec = "%${key}%";
    } else {
      $rec = "\"%${key}%\"";
    }

    if ( $values ) {
      $values .= ', ';
    }

    $values .= "${col} = ${rec}";
  }


  $q_str = "UPDATE `${table_name}` SET ${values} WHERE `id` = '%id%'";

  $query = sprintf3( $q_str, $map );

  return $query;
}

/**
 * [Admin] Helper method. Given a map and a table name, returns a query to
 *   insert items in table.
 *
 * Two requirements:
 *   1. Keys *must* match column names exactly
 *   2. Any boolean columns should start with "is_", e.g., "is_foo".
 */
function gen_insert_stmt( $table_name, $map ) {

  // Convert to mysql bools (0 or 1)
  foreach ($map as $key => $val) {
    if ( is_bool_key( $key ) ) {
      to_mysql_bool( $map, $key );
    }
  }

  // Escape table name, keys & vals (trust nothing)
  to_mysql_escape( $map );
  $table_name = mysql_real_escape_string( $table_name );

  // Build insert string for sprintf3:
  //  INSERT INTO `contacts` (`key_1`, `key_2` ) VALUES ( "%key_1%", "%key_2%" )
  $keys = array();
  $vals = array();

  foreach ($map as $key => $val) {
    $keys[] = "`${key}`";
    if (is_bool_key( $key ) ) {
      $vals[] = "%${key}%";
    } else {
      $vals[] = "\"%${key}%\"";
    }
  }

  $keys_str = implode( ',', $keys );
  $vals_str = implode( ',', $vals );

  $q_str = "INSERT INTO `${table_name}` (${keys_str}) VALUES (${vals_str})";

  $query = sprintf3( $q_str, $map );

  return $query;
}


/**
 * [Admin] Helper method. Get entries from table.
 *
 * Parameters:
 *   table_name: String.
 *   columns: array of columns to include. Leave empty for all. E.g., ['id', 'first_name', 'last_name', 'date' ]
 *   options: (optional) Any options. E.g.,
 *     - 'limit'       => 10
 *     - 'order_by'    => 'last_name'
 *     - 'id'          => n (where n is a number; will only return item matching id)
 *     - 'date_format' => "%c/%d/%Y" (12/25/2012. Only applied to columns titled "date")
 */
function get_entries_from_table( $mysql, $table_name, $columns, $options=array() ) {

  // Escape table name, columns & options (trust nothing)
  to_mysql_escape( $columns );
  to_mysql_escape( $options );
  $table_name = mysql_real_escape_string( $table_name );

  $q;

  if ( count( $columns ) == 0 ) {
    $q = "SELECT * FROM ${table_name}";
  } else {

    $date_format = NULL;
    if ( array_key_exists( 'date_format', $options ) ) {
      $date_format = $options['date_format'];
    }

    foreach ( $columns as $key => $col ) {
      if ( $col === 'date' && $date_format != NULL ) {
        $columns[$key] = "DATE_FORMAT(${col},'${date_format}')";
      }
    }

    $q = "SELECT " . implode( ', ', $columns ) . " FROM ${table_name}";
  }

  if ( array_key_exists( 'id', $options ) && $options['id'] != NULL ) {
    $q .= ' WHERE id = ' . $options['id'];
  }

  if ( array_key_exists( 'order_by', $options ) && $options['order_by'] != NULL ) {
    $q .= ' ORDER BY ' . $options['order_by'];
  }

  if ( array_key_exists( 'limit', $options ) && $options['limit'] != NULL ) {
    $q .= ' LIMIT ' . $options['limit'];
  }

  if ( array_key_exists( 'offset', $options ) && $options['offset'] != NULL ) {
    $q .= ' OFFSET ' . $options['offset'];
  }

  $q .= ';';

  return $mysql->getResults ($q);
}

/**
 * [Admin] Adds a contact to database.
 */
function add_contact( $mysql, $map ) {
  $query = gen_insert_stmt( 'contacts', $map );
  $mysql->executeSQL( $query ) or die( $mysql->showError );
  return mysql_insert_id();
}

/**
 * [Admin] Update a contact. Note that $map must contain id.
 */
function update_contact( $mysql, $map ) {
  $query = gen_update_stmt( 'contacts', $map );
  $mysql->executeSQL( $query ) or die( $mysql->showError );
}

/**
 * [Admin] Get contacts. See get_entries_from_table for doc.
 */
function get_contacts( $mysql, $columns, $options=array() ) {
  $original = get_entries_from_table( $mysql, 'contacts', $columns, $options );

  // Strip out strings, '0'
  $entries = array();
  foreach ($original as $ignore=>$entry) {
    if (!is_string($entry)) {
      array_push($entries, $entry);
    }
  }
  return $entries;
}

/**
 * [Admin] Get one contact. See get_entries_from_table for doc.
 */
function get_contact( $mysql, $columns, $id ) {
  $options = array( 'id' => $id );
  $result = get_entries_from_table( $mysql, 'contacts', $columns, $options );
  if ( count( $result ) == 1 ) {
    return $result[0];
  } else if ( count( $result ) > 1 ) {
    die("More than one match in get_contact");
  } else {
    return null;
  }
}

/**
 * [Admin] Delete one contact.
 */
function delete_contact( $mysql, $id ) {
  $stmt = sprintf( "DELETE FROM `contacts` WHERE `id` = '%s'", mysql_real_escape_string($id) );
  $mysql->executeSQL($stmt) or die ( $mysql->showError() );
}

/**
 * [Admin] Gets count of all contacts.
 */
function get_contacts_count( $mysql ) {
  $query = "SELECT count(*) FROM contacts;";

  if ($mysql->resultsExist ($query)) {

      $result = $mysql->getResults ($query);
      return $result[0][0];

  } else { return -1; }
}

/**
 * [Admin] Converts pagination to order_by value.
 */
function pagination_to_limit($page, $page_size) {
  $start = ( $page - 1 ) * $page_size;
  return "${start}, ${page_size}";
}

?>
