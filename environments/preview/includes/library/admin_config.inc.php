<?php

/**
 * [Admin, Site] Helper method. Query for generic name-value configuration for admins.
 */
function query_select_admin_config( $nameSafe ) {
  return "SELECT value FROM admin_config WHERE name = '{$nameSafe}'";
}

/**
 * [Admin] Set generic name-value configuration for admins.
 */
function set_admin_config( $mysql, $name, $value ) {

  $nameSafe = mysql_real_escape_string($name);
  $valueSafe = mysql_real_escape_string($value);
  $query = query_select_admin_config($nameSafe);

  if ($mysql->resultsExist ($query)) {
    $stmt = "UPDATE admin_config SET value = '{$valueSafe}' WHERE name = '{$nameSafe}'";
    $mysql->executeSQL($stmt) or die ("set_admin_config ($stmt): "+$mysql->showError());
  } else {
    $stmt = "INSERT INTO admin_config(name, value) VALUES ('{$nameSafe}', '{$valueSafe}')";
    $mysql->executeSQL($stmt) or die ("set_admin_config ($stmt): "+$mysql->showError());
  }
} // set_admin_config

/**
 * [Site, Admin] Fetch generic name-value configuration for admins.
 */
function get_admin_config( $mysql, $name ) {

  $nameSafe = mysql_real_escape_string($name);
  $query = query_select_admin_config($nameSafe);

  if ($mysql->resultsExist ($query)) {
    $result = $mysql->getResults($query);
    return $result[0][0];
  } else {
    return NULL;
  }
} // get_admin_config

?>
