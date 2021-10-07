<?php
/**
 * Endpoint to fetch summary of all fund.
 */

 require_once '../../includes/api.inc.php';

 $isGet = isGetRequest();
 if ($isGet) {

   $fund_names = get_funds();
   $funds = array();
   foreach ($fund_names as $fund) {
     array_push($funds, summary_for_fund($fund));
   }

   $any_fund = $fund_names[0];
   $updated = get_last_trade_date($mysql, $any_fund);
   $res = array( "objects" => $funds, "updated" => $updated );

   sendAsJson($res);

 } else {
   send404();
 }

?>
