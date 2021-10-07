<?php

  /**
   * Endpoint to fetch a single fund's details.
   */

  require_once '../../../includes/api.inc.php';

  $isGet = isGetRequest();
  if ($isGet) {

    $symbol = strtolower( $_GET['symbol'] );

    // Sanitize input (Little Bobby Tables)
    $valid = get_funds();
    if (!in_array($symbol, $valid)) {
      send404();
    } else {
      $fund = details_for_fund($symbol);
      sendAsJson($fund);
    }

  } else {
    send404();
  }

?>
