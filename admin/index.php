<?php
// ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ >

   $this_page = $_SERVER['PHP_SELF'];

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

   $flash_msg = get_val_get_post_var('flash_msg');
   $is_authenticated = FALSE;
   $just_logged_in = FALSE;

   // Check for log in
   if (get_val_get_post_var('login')) {

      $login_name = get_val_get_post_var('adminuser');
      $login_password = get_val_get_post_var('adminpass');

      $authentication = authenticate_user_using_login($mysql, $login_name, $login_password);
      $flash_msg = $authentication->flash_msg;
      $is_authenticated = $authentication->is_authenticated;

      if ($is_authenticated) {
         $just_logged_in = true;
      }

   } else if (get_val_get_post_var('logout')) {

      // Verify user was logged in; else, refreshes show incorrect flash msg to user
      $is_authenticated = is_user_logged_in_persistently($mysql);

      // Only perform log out if user was actually logged in
      if ($is_authenticated) {

         destroy_persistent_login($mysql);
         $is_authenticated = FALSE;
         $flash_msg = 'You were successfully logged out.';

      }

   } else {

      // Check for persistent log in
      $authentication = authenticate_user_using_persistent_info($mysql);
      //$flash_msg = $authentication->flash_msg;
      $is_authenticated = $authentication->is_authenticated;

   }

   // ---------------------------------------------------------------------------
   // Variables for page
   // ---------------------------------------------------------------------------

   $save_reminder = "Don't forget to save your changes regularly.";

   $just_logged_in_msg = $save_reminder.'\n\nAlso, don\'t forget to log out if you are on a shared computer; you will remain logged in unless you log out.';

   $header = "Administration home";

   if (!$is_authenticated) {
      $header = "Administration Log In";
   }

   $title = "$header - Ave Maria Mutual Funds";

   // ---------------------------------------------------------------------------
   // Save fund price updates changes
   // ---------------------------------------------------------------------------
   if (
     isset($_POST['save_fund_price_method']) &&
     isset($_POST['fund_price_method']) &&
     $is_authenticated
   ) {
      set_fund_price_update_method($mysql, $_POST['fund_price_method']);
      $flash_msg = 'Your changes were saved.';
   }

   $fund_price_method = get_fund_price_update_method($mysql);

// < ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~
?><!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

   <head>

    <title><?= $title ?></title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

    <link rel="stylesheet" type="text/css" href="legacy/styles/screen.css" />
    <script type="text/javascript" src="legacy/scripts/library.js"></script>
    <script src="legacy/scripts/jquery-1.6.3.min.js"></script>
    <script type="text/javascript" src="legacy/scripts/library.jquery.js"></script>


    <?php
    // ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ >

      // Just annoy user once with reminder to save regularly
      if ($just_logged_in) {
         print("    <script type=\"text/javascript\"><!--\n");
         print("      alert(\"$just_logged_in_msg\");\n");
         print("    --></script>\n");
      }

    // < ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~
    ?>
   </head>

<body class="admin">
   <table id="content">
       <tr id="bottom">
          <td class="right-content">

            <?php
               echo "<h1>$header ";

               if ($is_authenticated) {
                  echo '<a class="logout" href="index.php?logout=true" onclick="return confirm(\'Any changes since your last save will be lost. Log out?\')">log out</a>';
               }

               echo "</h1>\n";

               if (!is_null($flash_msg)) {

                  echo "<p class=\"alert info\">$flash_msg</p>\n";

               }

            /* =====================================================================================================
                START: If not authenticated
               ===================================================================================================== */

               if (!$is_authenticated) {

            ?>
               <form action="index.php" method="POST">
                  <label for="adminuser">User name</label>
                  <input type="text" name="adminuser" id="adminuser" />

                  <label for="adminpass">Password</label>
                  <input type="password" name="adminpass" id="adminpass" />

                  <input type="hidden" name="login" id="login" value="true" />

                  <input type="submit" value="Log in" class="submit" />

               </form>
            <?php

               }
            /* =====================================================================================================
                END: If not authenticated
               ===================================================================================================== */

            /* =====================================================================================================
                START: If authenticated
               ===================================================================================================== */

               else {

            ?>

            <!-- //////////////////////////////////[ SKIP TO ]////////////////////////////////// -->
            <ul class="nav">
              <li><a href="#contacts">Skip to <strong>Contacts</strong></a></li>
              <li><a href="#fund_price_updates">Skip to <strong>Fund Prices Updates</strong></a></li>
              <li><a href="#fund_mgmt">Skip to <strong>Fund Mgmt</strong></a></li>
            </ul>

            <!-- //////////////////////////////////[ CONTACTS ]////////////////////////////////// -->
            <h2><a name="contacts"></a>Contacts</h2>

            <?php

              $order_by = get_val_get_post_var('order_by');
              $order_by = $order_by ? $order_by : 'date DESC';

              $page = get_val_get_post_var('page');
              $page = $page ? $page : "1";

              $contacts_count = get_contacts_count( $mysql );
              $page_size = 50;

              $contacts = get_contacts( $mysql,
                                        array( 'date', 'id', 'first_name', 'last_name', 'phone' ),
                                        array( 'order_by' => $order_by, 'date_format' => "%c/%d/%Y", 'limit' => pagination_to_limit( $page, $page_size ) ));

            ?>

            <div class="close-to-next contacts-actions">

              <a href="/admin/contact.php?for=add" class="button popup" id="add_contact">+ Add contact</a>
              <a href="/admin/contacts_spreadsheet.php" class="button" id="download_spreadsheet">FULL spreadsheet</a>
              <a href="/admin/contacts_spreadsheet_partial.php" class="button" id="download_spreadsheet">RECENT ONLY</a>

              <form method="get" class="inline order_by">
                <label for="order_by">Sort by: </label>
                <select name="order_by" id="order_by">
                  <?php

                    $ob_options = array(
                      'date DESC'       => 'Newest first',
                      'date ASC'        => 'Oldest first',
                      'last_name ASC'   => 'Last name (A-Z)',
                      'last_name DESC'  => 'Last name (Z-A)',
                      'first_name ASC'  => 'First name (A-Z)',
                      'first_name DESC' => 'First name (Z-A)',
                    );

                    foreach ( $ob_options as $key => $val ) {
                      if ( $key === $order_by ) {
                        print "<option value=\"${key}\" selected=\"selected\">${val}</option>";
                      } else {
                        print "<option value=\"${key}\">${val}</option>";
                      }
                    }
                  ?>
                </select>

                <label for="page">Page: </label>

                <select id="page" name="page">
                <?php
                  $pages = ceil( $contacts_count / $page_size );
                  for ($i=1; $i<=$pages; $i++ ) {
                    $start = (($i - 1) * $page_size);
                    $end   = $start + $page_size;
                    $end   = $end > $contacts_count ? $contacts_count : $end;
                    $desc  = "${start}-${end}";
                    if ( $i == $page ) {
                      print "<option value=\"${i}\" selected=\"selected\">${i} (${start}-${end})</option>";
                    } else {
                      print "<option value=\"${i}\">${i} (${start}-${end})</option>";
                    }
                  }
                ?>
                </select>
                <input type="submit" value="sort" class="hide"/>
              </form>

            </div> <!-- .close-to-next -->

            <form action="delete_contacts.php" method="post">
              <table class="contacts">
                <tr>
                  <th class="select"><span class="hide">Select</span></th>
                  <th class="date">Date</th>
                  <th class="last">Last</th>
                  <th class="last">First</th>
                  <th class="phone">Phone</th>
                  <th class="actions">Actions</th>
                </tr>

                <?php
                  $even = false;
                  foreach ( $contacts as $c ) {

                    $date  = $c[0];
                    $id      = $c[1];
                    $f_n     = $c[2];
                    $l_n     = $c[3];
                    $phone   = $c[4] ? $c[4] : '&mdash;';
                    $even  = !$even;
                    $class = $even ? "row-even" : "row-odd";

                    print "<tr class=\"${class}\"> \n";
                    print "  <td><input type=\"checkbox\" class=\"selectable\" name=\"del_${id}\" id=\"selected\" value=\"${id}\"/></td> \n";

                    if ( startsWith( $order_by, 'date' ) ) {
                      print "  <td class=\"sorted\">${date}</td> \n";
                    } else {
                      print "  <td>${date}</td> \n";
                    }

                    if ( startsWith( $order_by, 'last_name' ) ) {
                      print "  <td class=\"sorted\">${l_n}</td> \n";
                    } else {
                      print "  <td>${l_n}</td> \n";
                    }

                    if ( startsWith( $order_by, 'first_name' ) ) {
                      print "  <td class=\"sorted\">${f_n}</td> \n";
                    } else {
                      print "  <td>${f_n}</td> \n";
                    }

                    print "  <td>${phone}</td> \n";
                    print "  <td><a href=\"/admin/contact.php?for=details&id=${id}\" class=\"details popup\">details</a></td> \n";
                    print "</tr> \n";
                  }
                ?>
              </table>

              <div id="selected_contacts_actions">

                <?php
                  $qry = '?';
                  foreach( $_GET as $name => $val ) {
                    if ( $name !== 'page' ) {
                      if ( ! $qry ) {
                        $qry = '&';
                      }
                      $qry .= $name . '=' . $val;
                    }
                  }
                ?>

                <?php if ( $page == 1 ) { ?>
                  <span class="disabled">&laquo;</span>
                  <span class="disabled">&lt;</span>
                <?php } else { ?>
                  <a href="/admin/<?= $qry ?>&page=1" class="link">&laquo;</a>
                  <a href="/admin/<?= $qry ?>&page=<?= $page - 1 ?>" class="link">&lt;</a>
                <?php } ?>

                <?php if ( $page == $pages ) { ?>
                  <span class="disabled">&gt;</span>
                  <span class="disabled">&raquo;</span>
                <?php } else { ?>
                  <a href="/admin/<?= $qry ?>&page=<?= $page + 1 ?>" class="link">&gt;</a>
                  <a href="/admin/<?= $qry ?>&page=<?= $pages ?>" class="link">&raquo;</a>
                <?php } ?>

                <a href="#" class="link" id="select_all">Select all</a>
                <a href="#" class="link" id="select_none">Select none</a>
                <input type="submit" value="Delete selected" id="delete-selected"/>
              </div>
            </form>

            <!-- //////////////////////////////////[ FUNDS MGMT ]////////////////////////////////// -->
            <h2 class="close-to-next"><a name="fund_price_updates"></a>Fund Price Updates</h2>

            <p>How would you like fund prices to be updated?</p>

            <form id="form_fund_price_method" method="POST">
              <label for="fund_price_method">Fund Price Updates</label>
              <select name="fund_price_method">
                <?php
                  echo "<!-- fund_price_method={$fund_price_method} -->";
                  echo html_option_ele(FUND_PRICE_MANUAL, 'Manual', FUND_PRICE_MANUAL == $fund_price_method);
                  echo html_option_ele(FUND_PRICE_ULTIMUS, 'Automated by Ultimus', FUND_PRICE_ULTIMUS == $fund_price_method);
                ?>
              </select>
              <input type="submit"
                     name="save_fund_price_method"
                     value="Save" />
            </form>

            <!-- //////////////////////////////////[ FUNDS MGMT ]////////////////////////////////// -->
            <h2 class="close-to-next"><a name="fund_mgmt"></a>Fund Mgmt</h2>

            <table>
               <tr>
                  <th>Fund symbol</th>
                  <th>Fund name</th>
                  <th>Actions</th>
               </tr>

               <tr class="row-odd">
                  <td class="abbr-large">AVEWX</td>
                  <td>Ave Maria World Equity Fund</td>
                  <td><a href="avewx.php" class="abbr-large">edit</a></td>
               </tr>

               <tr class="row-even">
                  <td class="abbr-large">AVEGX</td>
                  <td>Ave Maria Growth Fund</td>
                  <td><a href="avegx.php" class="abbr-large">edit</a></td>
               </tr>

               <tr class="row-odd">
                  <td class="abbr-large">AVEMX</td>
                  <td>Ave Maria Value Fund</td>
                  <td><a href="avemx.php" class="abbr-large">edit</a></td>
               </tr>

               <tr class="row-even">
                  <td class="abbr-large">AVEDX</td>
                  <td>Ave Maria Rising Dividend</td>
                  <td><a href="avedx.php" class="abbr-large">edit</a></td>
               </tr>

               <tr class="row-odd">
                  <td class="abbr-large">AVEFX</td>
                  <td>Ave Maria Bond Fund</td>
                  <td><a href="avefx.php" class="abbr-large">edit</a></td>
               </tr>
               
               
               
               <tr class="row-even">
                  <td class="abbr-large">AVEAX</td>
                  <td>Ave Maria Focused Fund</td>
                  <td><a href="aveax.php" class="abbr-large">edit</a></td>
                </tr>


            </table>

           <?php
              }
           /* =====================================================================================================
               END: If authenticated
              ===================================================================================================== */
           ?>

           <!-- Cross-browser -safe way to pad bottom -->
           <br /><br />

         </td>
      </tr>
   </table>
</body>
</html>
