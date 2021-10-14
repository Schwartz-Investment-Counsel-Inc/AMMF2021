<?php
  // Redirect a directory up
  $host  = $_SERVER['HTTP_HOST'];
  $path   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
  header("Location: http://$host$path/../");
  exit;
?>
