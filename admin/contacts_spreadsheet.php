<?php

  function norm_bool( $bool ) {
    if ( $bool && $bool == 1 ) {
      return 'y ';
    }
    return '- ';
  }

  // ---------------------------------------------------------------------------
  // Library includes
  // ---------------------------------------------------------------------------

  include_once '../includes/mysql.obj.inc.php';
  include_once '../includes/library.php';
  include_once '../includes/authentication.inc.php';

  // ---------------------------------------------------------------------------
  // Create connection module object and connect
  // ---------------------------------------------------------------------------

  $mysql = new mysql();
  $mysql->connectDB ();

  // ---------------------------------------------------------------------------
  // Authenticate user.
  // ---------------------------------------------------------------------------
  $authentication = authenticate_user_using_persistent_info($mysql);
  $flash_msg = $authentication->flash_msg;
  $is_authenticated = $authentication->is_authenticated;

  if ( ! $is_authenticated ) {
    die("You are not authenticated to view this page.");
  }

  header('Content-type: text/csv');
  header('Content-disposition: attachment;filename=contacts.csv');

  $fp = fopen('php://output', 'w');

  $headings = array(
    'date',
    'first_name',
    'last_name',
    'company',
    'addr_1',
    'addr_2',
    'city',
    'state',
    'zip',
    'zip_4',
    'email',
    'phone',
    'source_1',
    'source_2',
    'best_time_to_call',
    'call_notes',
    'kit_notes',
    'other_notes',
    'is_mail_me',
    'is_ira',
    'is_roth',
    'is_call_me',
    'is_fin_adv',
  );

  fputcsv($fp, $headings);

  $isContinue = true;
  $page = 25;
  $offset = 0;

  while ($isContinue) {

    $contacts = get_contacts( $mysql, array(), array( 'limit' => $page, 'offset' => $offset) );

    // Continue?
    $isContinue = count($contacts) > 0;
    $offset += $page;

    foreach ( $contacts as $row ) {

      // Remove index 0: id
      unset( $row[0] );

      // Just show mm/dd from 0000-00-00 00:00:00
      $pattern = '/^\d{4}\-(\d{2})\-(\d{2}) .*$/';
      preg_match($pattern, $row[1], $matches, PREG_OFFSET_CAPTURE);
      $row[1] = $matches[1][0] . '/' . $matches[2][0];

      // Zip with leading zeros
      $row[9] = sprintf( '%05d', intval($row[9]) );
      $row[9] .= ' '; // Force a string interpretation by spreadsheet
      if ( $row[10] ) {
        $row[10] = sprintf("%04s", intval($row[10]));
        $row[10] .= ' '; // Force a string interpretation by spreadsheet
      }

      // Normalize 0, 1
      $row[19] = norm_bool( $row[19] );
      $row[20] = norm_bool( $row[20] );
      $row[21] = norm_bool( $row[21] );
      $row[22] = norm_bool( $row[22] );
      $row[23] = norm_bool( $row[23] );

      fputcsv($fp, $row);
    }
  }



  fclose($fp);
?>
