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
  $authentication = authenticate_user_using_persistent_info($mysql);
  $flash_msg = $authentication->flash_msg;
  $is_authenticated = $authentication->is_authenticated;

  if ( ! $is_authenticated ) {
    die("You are not authenticated to view this page.");
  }

  // ---------------------------------------------------------------------------
  // Items to delete
  // ---------------------------------------------------------------------------
  $ids = array();

  foreach ( $_POST as $key => $val ) {
    if ( startsWith( $key, 'del_' ) ) {
      $ids[] = $val;
    }
  }

  $count = count($ids);

  if ( $count == 0 ) {
    die("No contacts were selected to delete.");
  }

  $contacts = ( $count == 1 ? 'contact' : 'contacts' );
  $title    = "Delete $count $contacts";

  // ---------------------------------------------------------------------------
  // Perform delete
  // ---------------------------------------------------------------------------
  $confirm = get_val_get_post_var('confirm');
  if ( $confirm === 'confirm' ) {
    foreach ( $ids as $id ) {
      delete_contact( $mysql, $id );
    }
    header("Location: /admin/index.php?flash_msg=" . urlencode("<strong>Success</strong>: $count $contacts deleted.") );
    exit;
  }

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

   </head>

<body class="admin">
   <table id="content">
       <tr id="bottom">

          <!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --
            Left Navigation Bar
           -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
            <td class="left-content"><!-- php print_side_nav_bar(); --></td>

          <!-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --
            Content
           -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -->
          <td class="right-content">

            <h1><?= $title ?></h1>

            <p>Delete the following?:</p>

            <form action="/admin/delete_contacts.php" method="post" class="delete">

              <ul>
                <?php
                  foreach ( $ids as $id ) {
                    $contact = get_contact( $mysql, array('date', 'first_name', 'last_name'), $id );
                    $date    = $contact[0];
                    list( $date ) = preg_split('/\s/', $date);
                    $f_name  = $contact[1];
                    $l_name  = $contact[2];
                    print "<li><b>${date}</b>: ${f_name} ${l_name}</li>";
                    print "<input type=\"hidden\" name=\"del_${id}\" value=\"${id}\" />";
                  }
                ?>
              </ul>

              <input type="hidden" name="confirm" value="confirm"/>
              <input type="submit" value="Yes, delete"/>
            </form>


            <!-- Cross-browser -safe way to pad bottom -->
            <br /><br />

         </td>
      </tr>
   </table>
</body>
</html>
