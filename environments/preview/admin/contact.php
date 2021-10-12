<?php

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

   $flash_msg = NULL;
   $is_authenticated = FALSE;

   $authentication = authenticate_user_using_persistent_info($mysql);
   $is_authenticated = $authentication->is_authenticated;

   if ( ! $is_authenticated ) {
      die("You are not authorized to view this page.");
   }

   // Columns, with index
   $columns = array( 0 => 'id', 1 => 'date', 2 => 'first_name', 3 => 'last_name', 4 => 'company', 5 => 'addr_1', 6 => 'addr_2', 7 => 'city', 8 => 'state', 9 => 'zip', 10 => 'zip_4', 11 => 'email', 12 => 'phone', 13 => 'source_1', 14 => 'source_2', 15 => 'best_time_to_call', 16 => 'call_notes', 17 => 'kit_notes', 18 => 'other_notes', 19 => 'is_mail_me', 20 => 'is_ira',  21 => 'is_roth', 22 => 'is_call_me', 23 => 'is_fin_adv', 24 => 'is_holtz_dvd_2013' );

   // Create array of all variables to pre-declare
   $declare = array( 'year', 'month', 'day' );
   foreach ( $columns as $idx => $val ) {
     $declare[] = $val;
   }

   // Declare variables
   foreach ($declare as $var) {
     $$var = NULL;
   }

   /*
    * We basically want to submit the $_POST, except:
    *   - Remove 'year', 'month', 'day', 'for', 'submit'
    *   - Add 'date'
    *   - If boolean fields present, '1'; else, '0'
    */
   function get_contact_map_from_post() {

     global $columns;

     $map = $_POST; // In PHP, this is an array copy, so can modify

     if ( array_key_exists( 'year', $map ) ) {
       $yyyy = $map['year'];
     }

     if ( array_key_exists( 'month', $map ) ) {
       $mm = $map['month'];
     }

     if ( array_key_exists( 'day', $map ) ) {
       $dd = $map['day'];
     }

     $delete = array( 'year', 'month', 'day', 'for', 'submit' );
     foreach ( $delete as $key ) {
       unset($map[$key]);
     }

     if ( isset( $yyyy ) && isset( $mm ) && isset( $dd ) ) {
       $date = "${yyyy}-${mm}-${dd} 00:00:00";
       $map['date'] = $date;
     }

     foreach ( $columns as $idx => $key ) {
       if ( startsWith( $key, 'is_' ) ) {
         if ( array_key_exists( $key, $map ) ) {
           $map[$key] = 1;
         } else {
           $map[$key] = 0;
         }
       }
     }

     return $map;
   }

   // ---------------------------------------------------------------------------
   // What are we doing?
   // ---------------------------------------------------------------------------
   $for       = get_val_get_post_var('for');
   $submitted = get_val_get_post_var('submit') ? 1 : 0;
   $flash_msg = get_val_get_post_var('flash_msg');

   if ( ! $for ) {
     die("No objective was specified.");
   }

   // ---------------------------------------------------------------------------
   // Special case: if submitting an 'add contact' entry, add contact &
   //               switch 'for' to 'details'
   // ---------------------------------------------------------------------------
   if ( $for === 'add' && $submitted ) {
     $map = get_contact_map_from_post();

     if (array_key_exists('id', $map)) {
       unset($map['id']);
     }

     // After adding contact, redirect to page with details
     $id = add_contact( $mysql, $map );
     header("Location: /admin/contact.php?id=${id}&for=details&flash_msg=" . urlencode('Contact added.') );
     exit;
   }

   // ---------------------------------------------------------------------------
   // For: details
   // ---------------------------------------------------------------------------
   if ( $for === 'details' ) {

     $id = get_val_get_post_var('id');

     if ( ! $id ) {
       die("An id is required.");
     }

     // Retrive before update to make sure exists
     $contact = get_contact( $mysql, array(), $id );

     if ( ! $contact ) {
       die("Contact not found");
     }

     // Was there a form submission? If so, save.
     if ( $submitted ) {
       $map = get_contact_map_from_post();
       update_contact( $mysql, $map );
       $flash_msg = "Changes saved.";

       // Retrieve updated info
       $contact = get_contact( $mysql, array(), $id );
     }

     // DATA
     foreach ($columns as $idx => $var) {
       $$var = $contact[$idx];
     }

     if ( $date ) {
       list( $year, $month, $day ) = preg_split('/[-\s]/', $date);
     }

     // PAGE COMPONENTS
     $title        = "Details for $first_name $last_name";
     $submit_label = 'Save changes';
   }

   // ---------------------------------------------------------------------------
   // For: create
   // ---------------------------------------------------------------------------
   else if ( $for === 'add' ) {
     // PAGE COMPONENTS
     $title        = "Add contact";
     $submit_label = 'Add contact';
   }

   // ---------------------------------------------------------------------------
   // Unrecognized objective
   // ---------------------------------------------------------------------------
   else {
     die("Unrecognized objective, ${for}");
   }

   // ---------------------------------------------------------------------------
   // States (drop-down)
   // ---------------------------------------------------------------------------
   $states = array(
      'AL'=>"Alabama",
			'AK'=>"Alaska",
			'AZ'=>"Arizona",
			'AR'=>"Arkansas",
			'CA'=>"California",
			'CO'=>"Colorado",
			'CT'=>"Connecticut",
			'DE'=>"Delaware",
			'DC'=>"District Of Columbia",
			'FL'=>"Florida",
			'GA'=>"Georgia",
			'HI'=>"Hawaii",
			'ID'=>"Idaho",
			'IL'=>"Illinois",
			'IN'=>"Indiana",
			'IA'=>"Iowa",
			'KS'=>"Kansas",
			'KY'=>"Kentucky",
			'LA'=>"Louisiana",
			'ME'=>"Maine",
			'MD'=>"Maryland",
			'MA'=>"Massachusetts",
			'MI'=>"Michigan",
			'MN'=>"Minnesota",
			'MS'=>"Mississippi",
			'MO'=>"Missouri",
			'MT'=>"Montana",
			'NE'=>"Nebraska",
			'NV'=>"Nevada",
			'NH'=>"New Hampshire",
			'NJ'=>"New Jersey",
			'NM'=>"New Mexico",
			'NY'=>"New York",
			'NC'=>"North Carolina",
			'ND'=>"North Dakota",
			'OH'=>"Ohio",
			'OK'=>"Oklahoma",
			'OR'=>"Oregon",
			'PA'=>"Pennsylvania",
			'RI'=>"Rhode Island",
			'SC'=>"South Carolina",
			'SD'=>"South Dakota",
			'TN'=>"Tennessee",
			'TX'=>"Texas",
			'UT'=>"Utah",
			'VT'=>"Vermont",
			'VA'=>"Virginia",
			'WA'=>"Washington",
			'WV'=>"West Virginia",
			'WI'=>"Wisconsin",
			'WY'=>"Wyoming");


?><html>
  <head>

    <link rel="stylesheet" type="text/css" href="legacy/styles/popup.css" />
    <link rel="stylesheet" type="text/css" href="legacy/styles/contact.css" />
    <title><?= $title ?></title>

    <?php if ( isset($submitted) && $submitted ) { ?>
    <script type="text/javascript"><!--
      window.opener.location.reload(true);
    --></script>
    <?php } ?>

  </head>
  <body>
    <h1><?= $title ?></h1>
    <?php
      if (!is_null($flash_msg)) {
        echo "<p class=\"alert\">$flash_msg</p>\n";
      }
    ?>
    <form action="contact.php" method="POST">
      <table>

        <!-- .................. DATE .................. -->
        <tr>
          <th class="col_1">Date</td>
          <td class="col_2 col_3" colspan="2">

            <!-- / / / / / / / / / / / -->
            <div class="float_left">
              <input type="text" name="year" id="year" value="<?= $year ?>"/><label for="year" class="below">YYYY</label>
            </div>

            <!-- / / / / / / / / / / / -->
            <div class="float_left">
              <input type="text" name="month" id="month" value="<?= $month ?>"/><label for="month" class="below">MM</label>
            </div>

            <!-- / / / / / / / / / / / -->
            <div class="float_left">
              <input type="text" name="day" id="day" value="<?= $day ?>"/><label for="day" class="below">DD</label>
            </div>

            <!-- / / / / / / / / / / / -->
            <div class="clear"></div>

          </td>
        </tr>

        <!-- .................. NAME .................. -->
        <tr>
          <th class="col_1">Name</td>
          <td class="col_2"><input type="text" name="first_name" value="<?= $first_name ?>"/><label for="first_name" class="below">First name</label></td>
          <td class="col_3"><input type="text" name="last_name" value="<?= $last_name ?>"/><label for="last_name" class="below">Last name</label></td>
        </tr>

        <!-- .................. COMPANY .................. -->
        <tr>
          <th class="col_1">Company</td>
          <td class="col_2 col_3" colspan="2"><input type="text" name="company" value="<?= $company ?>"/></td>
        </tr>

        <!-- .................. ADDRESS .................. -->
        <tr>
          <th class="col_1" rowspan="4">Address</td>
          <td class="col_2 col_3" colspan="2"><input type="text" name="addr_1" value="<?= $addr_1 ?>"/></td>
        </tr>

        <tr>
          <!-- Minus one col -->
          <td class="col_2 col_3" colspan="2"><input type="text" name="addr_2" value="<?= $addr_2 ?>"/></td>
        </tr>

        <tr>
          <!-- Minus one col -->
          <td class="col_2"><input type="text" name="city" value="<?= $city ?>"/><label for="city" class="below">City</label></td>
          <td class="col_3">
            <select name="state" id="state" class="textual">
              <option></option>
              <?php
                foreach ($states as $code => $label) {
                  if ( $code === $state ) {
	                  print "<option value=\"$code\" selected=\"selected\">$label</option>";
                  } else {
	                  print "<option value=\"$code\">$label</option>";
                  }
               }
              ?>
            </select>
            <label for="state" class="below">State</label>
          </td>
        </tr>

        <tr>
          <!-- Minus one col -->
          <td class="col_2 col_3" colspan="2">

            <!-- / / / / / / / / / / / -->
            <div class="float_left">
              <input type="text" name="zip" id="zip" value="<?= $zip ?>"/><label for="zip" class="below">Zip</label>
            </div>

            <!-- / / / / / / / / / / / -->
            <div class="float_left">
              <input type="text" name="zip_4" id="zip_4" value="<?= $zip_4 ?>"/><label for="zip_4" class="below">Zip 4</label>
            </div>

            <!-- / / / / / / / / / / / -->
            <div class="clear"></div>

          </td>
        </tr>

        <!-- .................. CONTACT .................. -->
        <tr>
          <th class="col_1">Contact</td>
          <td class="col_2"><input type="text" name="email" value="<?= $email ?>"/><label for="email" class="below">Email</label></td>
          <td class="col_3"><input type="text" name="phone" value="<?= $phone ?>"/><label for="phone" class="below">Phone</label></td>
        </tr>

        <!-- .................. SOURCE .................. -->
        <tr>
          <th class="col_1">Source</td>
          <td class="col_2"><input type="text" name="source_1" value="<?= $source_1 ?>"/><label for="source_1" class="below">Source 1</label></td>
          <td class="col_3"><input type="text" name="source_2" value="<?= $source_2 ?>"/><label for="source_2" class="below">Source 2</label></td>
        </tr>

        <!-- .................. BEST TIME TO CALL .................. -->
        <tr>
          <th class="col_1">Best time to call</td>
          <td class="col_2 col_3" colspan="2"><textarea name="best_time_to_call"><?= $best_time_to_call ?></textarea></td>
        </tr>

        <!-- .................. CALL NOTES .................. -->
        <tr>
          <th class="col_1">Call notes</td>
          <td class="col_2 col_3" colspan="2"><textarea name="call_notes"><?= $call_notes ?></textarea></td>
        </tr>

        <!-- .................. KIT NOTES .................. -->
        <tr>
          <th class="col_1">Kit notes</td>
          <td class="col_2 col_3" colspan="2"><textarea name="kit_notes"><?= $kit_notes ?></textarea></td>
        </tr>

        <!-- .................. OTHER NOTES .................. -->
        <tr>
          <th class="col_1">Other notes</td>
          <td class="col_2 col_3" colspan="2"><textarea name="other_notes"><?= $other_notes ?></textarea></td>
        </tr>

        <!-- .................. REQUESTS .................. -->
        <tr>
          <th class="col_1" rowspan="6">Other notes</td>
          <td class="col_2 col_3" colspan="2"><input type="checkbox" name="is_mail_me" <?= $is_mail_me ? 'checked="checked"' : '' ?> /><label for="is_mail_me">Please mail me an investment kit</label></td>
        </tr>

        <tr>
          <!-- Minus one col -->
          <td class="col_2 col_3" colspan="2"><input type="checkbox" name="is_ira" <?= $is_ira ? 'checked="checked"' : '' ?> /><label for="is_ira">Include IRA Application and Transfer Form</label></td>
        </tr>

        <tr>
          <!-- Minus one col -->
          <td class="col_2 col_3" colspan="2"><input type="checkbox" name="is_roth" <?= $is_roth ? 'checked="checked"' : '' ?> /><label for="is_roth">Include Roth IRA Application and Transfer Form</label></td>
        </tr>

        <tr>
          <!-- Minus one col -->
          <td class="col_2 col_3" colspan="2"><input type="checkbox" name="is_call_me" <?= $is_call_me ? 'checked="checked"' : '' ?> /><label for="is_call_me">Please call me</label></td>
        </tr>

        <tr>
          <!-- Minus one col -->
          <td class="col_2 col_3" colspan="2"><input type="checkbox" name="is_fin_adv" <?= $is_fin_adv ? 'checked="checked"' : '' ?> /><label for="is_fin_adv">I am a financial professional. Please mail me a fund kit.</label></td>
        </tr>

        <tr>
          <!-- Minus one col -->
          <td class="col_2 col_3" colspan="2"><input type="checkbox" name="is_holtz_dvd_2013" <?= $is_holtz_dvd_2013 ? 'checked="checked"' : '' ?> /><label for="is_holtz_dvd_2013">Reserve a complimentary DVD copy of Coach Holtz's presentation from our June 25, 2013 celebration</label></td>
        </tr>

      </table>

      <input type="hidden" name="for" value="<?= $for ?>" />
      <input type="hidden" name="id" value="<?= $id ?>" />

      <input type="submit" name="submit" value="<?= $submit_label ?>" />

    </form>

    </table>
  </body>
</html>
