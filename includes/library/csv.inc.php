<?php

/**
 * [Admin, Site] Helper method. Reads in a CSV as 2-dim array. If no file, returns NULL.
 */
function read_csv($file) {
  $arr = NULL;
  if (file_exists($file) && ($handle = fopen($file, 'r')) !== FALSE) {
    $arr = array();
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      array_push($arr, $data);
    }
    fclose($handle);
  }
  return $arr;
}

?>
