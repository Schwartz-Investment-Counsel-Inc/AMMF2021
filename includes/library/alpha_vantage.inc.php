<?php

require_once 'market_info.inc.php';

/**
 * [Admin, Site] Helper method. Cleanup string number and convert to float.
 */
 function clean_to_float($str) {

   $str = trim($str);

   // Remove characters
   $str = str_replace('$', '', $str);
   $str = str_replace('%', '', $str);

   return (float) $str;
}

/**
 * [Admin, Site] Extract market info for specified fund from Alpha Vantage API.
 */
function get_market_price_from_alpha_vantage( $fund ) {

  $url = "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol={$fund}&apikey=".ALPHA_VANTAGE_KEY;

  $res = @file_get_contents($url);

  if ($res === FALSE) {
    return FALSE;
  }

  $json = json_decode($res, TRUE);
  $key_time_series = 'Time Series (Daily)';
  $key_closing_price = '4. close';

  // Verify shape of response
  if (
    $json === FALSE || !array_key_exists($key_time_series, $json)
  ) {
    return FALSE;
  }

  $time_series = $json[$key_time_series];

  // Service not highly reliable; some more validation
  if (
    count( $time_series ) < 2
  ) {
    return FALSE;
  }

  // Ensure sorted since we depend on order to get previous two trade dates
  krsort( $time_series, SORT_STRING );
  $days = array_keys(array_slice($time_series, 0, 2, TRUE));

  $first_closing = clean_to_float($time_series[$days[0]][$key_closing_price]);
  $second_closing = clean_to_float($time_series[$days[1]][$key_closing_price]);

  $market = new MarketInfo();
  $market->from = $url;
  $market->fund = $fund;
  $market->price = $first_closing;
  $market->daily_change_price = $market->price - $second_closing;
  $market->daily_change_percentage = 100.0 * $market->daily_change_price / $second_closing;
  $market->last_trade_date = str_replace('-', '/', $days[0]);

  return $market;

} // get_market_price_from_alpha_vantage

?>
