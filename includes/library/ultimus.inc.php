<?php

require_once 'csv.inc.php';
require_once 'market_info.inc.php';
require_once 'strings.inc.php';

/**
 * Organizing information needed when reasoning about daily fund prices.
 */
class DailyFundPriceEntry {
    public $from;
    public $row;
}

/**
 * [Admin, Site] Helper method. Returns timestamp from # of days ago.
 */
function days_ago($start, $days) {
  return $start - $days * 60 * 60 * 24;
}

/**
 * [Admin, Site] Helper method. Returns Ultimate CSV path for day
 *   represented by a given timestamp, if available.
 */
function find_ultimus_path($dir, $timestamp) {
  $date = date('Ymd', $timestamp);
  $file = "{$dir}/AveMariaNAVs_{$date}.csv";
  return file_exists($file) ? $file : NULL;
}

/**
 * [Admin, Site] Helper method. Returns fund row from ultimus CSV file, if one.
 */
function find_ultimus_fund_row($csv, $fund) {
  foreach($csv as $row) {
    if (strtolower($row[7]) == strtolower($fund)) {
      return $row;
    }
  }
  return NULL;
}

/**
 * [Admin, Site] Helper method. Finds fund row for a given date, if exists.
 */
function read_ultimus_fund_row_on_date($dir, $timestamp, $fund) {
  $path = find_ultimus_path($dir, $timestamp);
  if (!isset($path)) {
    return NULL;
  }
  return find_ultimus_fund_row(read_csv($path), $fund);
}

/**
 * Given two dates (e.g., '2017/12/15' and '2017/12/13'), returns number of
 *   business days.
 *
 * Adapted from: https://stackoverflow.com/a/336175
 */
function count_business_days($start_date, $end_date){

    $end_date = strtotime($end_date);
    $start_date = strtotime($start_date);

    // The total number of days between the two dates.
    //We add one to inlude both dates in the interval.
    $days = ($end_date - $start_date) / 86400;

    $no_full_weeks = floor($days / 7);
    $no_remaining_days = fmod($days, 7);

    //It will return 1 if it's Monday,.. ,7 for Sunday
    $the_first_day_of_week = date("N", $start_date);
    $the_last_day_of_week = date("N", $end_date);

    //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
    //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
    if ($the_first_day_of_week <= $the_last_day_of_week) {
        if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) $no_remaining_days--;
        if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) $no_remaining_days--;
    }
    else {
        // (edit by Tokes to fix an edge case where the start day was a Sunday
        // and the end day was NOT a Saturday)

        // the day of the week for start is later than the day of the week for end
        if ($the_first_day_of_week == 7) {
            // if the start date is a Sunday, then we definitely subtract 1 day
            $no_remaining_days--;

            if ($the_last_day_of_week == 6) {
                // if the end date is a Saturday, then we subtract another day
                $no_remaining_days--;
            }
        }
        else {
            // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
            // so we skip an entire weekend and subtract 2 days
            $no_remaining_days -= 2;
        }
    }

    //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
//---->february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
   $workingDays = $no_full_weeks * 5;
    if ($no_remaining_days > 0 )
    {
      $workingDays += $no_remaining_days;
    }

    return $workingDays;
}


/**
 * [Admin, Site] Update a fund from ultimus.
 */
function get_market_price_from_ultimus($dir, $time, $fund) {

  $last_two_entries = array();

  // Loop over last week. Find rows.
  //   -3 because Ultimus dates Friday's filenames in future.
  foreach (range(-3, 7) as $days) {

    $row = read_ultimus_fund_row_on_date($dir, days_ago($time, $days), $fund);

    if (isset($row)) {
      $entry = new DailyFundPriceEntry();
      $entry->from = find_ultimus_path($dir, days_ago($time, $days));
      $entry->row = $row;
      array_push($last_two_entries, $entry);
    }

    if (count($last_two_entries) >= 2) {
      break;
    }
  }

  // Loop over last week, starting today. Find first CSV...
  if (count($last_two_entries) >= 2) {

    $row = $last_two_entries[0]->row;
    $prev_row = $last_two_entries[1]->row;

    $market = new MarketInfo();
    $market->from = $last_two_entries[0]->from;
    $market->fund = $fund;
    $market->price = $row[2];
    $market->last_trade_date = $row[1];
    $market->daily_change_price = $market->price - $prev_row[2];
    $market->daily_change_percentage = 100.0 * $market->daily_change_price / $prev_row[2];
    $market->warnings = array();

    if (abs($market->daily_change_percentage) >= 2.0) {
      $change = as_percent($market->daily_change_percentage);
      array_push($market->warnings, "$fund changed $change");
    }

    if (count_business_days($prev_row[1], $market->last_trade_date) > 1) {
      $msg = "For {$fund}, the most recent fund update since {$market->last_trade_date} is {$prev_row[1]}";
      array_push($market->warnings, $msg);
    }

    return $market;
  }

  return NULL; // didn't find
}

?>
