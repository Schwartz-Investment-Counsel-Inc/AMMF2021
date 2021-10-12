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
$just_logged_in = FALSE;

// Check for persistent log in
$authentication = authenticate_user_using_persistent_info($mysql);

if (!$authentication->is_authenticated) {

    // Redirect to admin home
    $host  = $_SERVER['HTTP_HOST'];
    $path   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header("Location: http://$host$path/");
    exit;
}

$fund_price_method = get_fund_price_update_method($mysql);

// ---------------------------------------------------------------------------
// Check whether anything to save and perform save if there is
// ---------------------------------------------------------------------------

if (isset($_GET['save'])) {

    $flash_msg = "Do not try to save changes using HTTP get. If you are not trying something fancy and you think this is an error, then this message is probably due to a bug, and you should contact the developer with time/date and a description of what you were doing.";

}

// Did user save changes?
if (isset($_POST['save'])) {

    $flash_msg = save_admin_fund_changes($mysql, $this_fund_symbol);

}

// ---------------------------------------------------------------------------
// Look for row or column to remove
// ---------------------------------------------------------------------------
if (get_val_get_post_var('remove_row')) {
    $row_index = (int)get_val_get_post_var('remove_row');
    $table_name = get_val_get_post_var('table');
    $flash_msg = delete_row_from_dynamic_table($mysql, $this_fund_symbol, $table_name, $row_index);
}

if (get_val_get_post_var('remove_col')) {
    $col_index = (int)get_val_get_post_var('remove_col');
    $table_name = get_val_get_post_var('table');
    $flash_msg = delete_column_from_dynamic_table($mysql, $this_fund_symbol, $table_name, $col_index);
}

// ---------------------------------------------------------------------------
// Current market value information
// ---------------------------------------------------------------------------

$current_price = get_current_price($mysql, $this_fund_symbol);
$last_trade_date = get_last_trade_date($mysql, $this_fund_symbol);
$daily_change_in_dollars_str = get_daily_change_in_dollars($mysql, $this_fund_symbol);
$daily_change_in_percentage_str = get_daily_change_in_percentage($mysql, $this_fund_symbol);

// For styling prices based on market going up or down
$change_class = "up";
if (is_market_price_drop($mysql, $this_fund_symbol)) {
    $change_class = "down";
}

// ---------------------------------------------------------------------------
// Variables for page
// ---------------------------------------------------------------------------

$save_reminder = "Don't forget to save your changes regularly.";

$just_logged_in_msg = $save_reminder.'\n\nAlso, don\'t forget to log out if you are on a shared computer; you will remain logged in unless you log out.';

$header = "Administration for $this_fund_name";
$title = "$header - Ave Maria Mutual Funds";

// < ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~
?><!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

    <head>

        <title><?= $title ?></title>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

        <link rel="stylesheet" type="text/css" href="legacy/styles/screen.css" />
        <script type="text/javascript" src="legacy/scripts/library.js"></script>

    </head>

    <body>
        <table id="content">
            <tr class="bottom">
                <td class="right-content" id="right-content">

                    <?php

                    echo '<h1>'.$header.' <span class="abbr">&nbsp;('.$this_fund_symbol.')</span> <a class="logout" href="index.php" onclick="return confirm(\'Any changes since your last save will be lost. Return home?\')">home</a> <a class="logout" href="index.php?logout=true" onclick="return confirm(\'Any changes since your last save will be lost. Log out?\')">log out</a></h1>'."\n";

                    if (!is_null($flash_msg)) {

                        echo "<p class=\"alert info\">$flash_msg</p>\n";

                    }

                    ?>

                    <form action="<?= $this_page ?>" method="POST">

                        <!-- *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** -->

                        <h2>Fund price information</h2>

                        <table id="fund_price" style="width: 80%">

                            <tr>
                                <th>Name</th>
                                <th>Value</th>
                            </tr>

                            <tr class="row-odd">
                                <td>Current Price ($)</td>
                                <td>
                                  <input type="text"
                                         name="market_info_current_price"
                                         value="<?= $current_price ?>"
                                         <?= html_disabled_attr(FUND_PRICE_MANUAL != $fund_price_method) ?> />
                                </td>
                            </tr>

                            <tr class="row-even">
                                <td>As of (MM/DD/YYYY)</td>
                                <td>
                                  <input type="text"
                                         name="market_info_last_trade_date"
                                         value="<?= $last_trade_date ?>"
                                         <?= html_disabled_attr(FUND_PRICE_MANUAL != $fund_price_method) ?> />
                                </td>
                            </tr>

                            <tr class="row-odd">
                                <td>Daily Change ($)</td>
                                <td>
                                  <input type="text"
                                         name="market_info_daily_change_dollar"
                                         value="<?= $daily_change_in_dollars_str ?>"
                                         <?= html_disabled_attr(FUND_PRICE_MANUAL != $fund_price_method) ?> />
                                </td>
                            </tr>

                            <tr class="row-even">
                                <td>Daily Change (%)</td>
                                <td>
                                  <input type="text"
                                         name="market_info_daily_change_percent"
                                         value="<?= $daily_change_in_percentage_str ?>"
                                         <?= html_disabled_attr(FUND_PRICE_MANUAL != $fund_price_method) ?> />
                                </td>
                            </tr>

                        </table>

                        <hr />

                        <!-- *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** -->

                        <?php
                        $table_name = 'total_returns';

                        // Type: DynamicTable
                        $total_returns = get_dynamic_table($mysql, $this_fund_symbol, $table_name);

                        $cols = $total_returns->col_count;
                        if ($cols < 3 || $cols > 12) {
                          echo "<p class=\"alert error\"><b>Error</b>: Found <b>$cols</b> columns, but there should be between 3-12 columns.</p>\n";
                        }

                        if ($total_returns->row_count < 2 || $total_returns->row_count > 4) {
                          echo "<p class=\"alert error\"><b>Error</b>: Found <b>$total_returns->row_count</b> rows, but there should be either <b>2</b>, <b>3</b>, or <b>4</b>  rows.</p>\n";
                        }
                        ?>

                        <p class="alert warning"><strong>Warning</strong>: follow these guidelines to ensure that the Total Returns table displays correctly.</p>

                        <h2>Total Returns</h2>

                        <?php

                        // Print out tracers if need to debug
                        echo "<!-- id:$total_returns->id, fund_name: $total_returns->fund_name, table_name: $total_returns->table_name, row_count: $total_returns->row_count, col_count: $total_returns->col_count -->";

                        ?>

                        <p>Please make sure there are:</p>
                        <ul>
                          <li>Between 3-12 columns (inclusive)</li>
                          <li>Between 2-4 rows (inclusive)</li>
                        </ul>

                        <?php if (isset ($total_returns)) { ?>

                          <table id="total_returns" style="width: 100%;">
                            <!-- Annualized -->
                            <tr id="total_returns_annualized">
                              <td>&nbsp;</td>
                              <td><strong>Annualized:</strong></td>
                            <?php
                              for ($col = 1; $col < $total_returns->col_count; $col++) {
                                $name = "total_returns-annualized-$col";
                                if ($total_returns->annualized_flags[$col]) {
                                  print "<td><input type=\"checkbox\" name=\"$name\" id=\"$name\" checked=\"checked\"></td>";
                                } else {
                                  print "<td><input type=\"checkbox\" name=\"$name\" id=\"$name\" ></td>";
                                }
                              }
                            ?>
                            </tr>

                            <!-- Headers -->
                            <tr id="total_returns_headers">
                              <th>&nbsp;</th>

                        <?php
                            for ($col = 0; $col < $total_returns->col_count; $col++) {

                                $header = $total_returns->headers[$col];

                                $class_stmt = '';
                                if ($col != 0) {
                                    $class_stmt = 'class="short""';
                                }

                                $name = "total_returns-header-$col";

                                echo "<th><input type=\"text\" name=\"$name\" id=\"$name\" value=\"$header\" $class_stmt /></th>\n";
                            }

                            echo '</tr>' . "\n";

                            echo '<tr id="total_returns_col_actions">' . "\n";

                            echo '<td>&nbsp;</td>'."\n";

                            // Add rows with column "remove" links
                            for ($col = 0; $col < $total_returns->col_count; $col++) {

                                $rm_link = "<a href=\"$this_page?remove_col=$col&table=total_returns\" class=\"abbr-large\" onclick=\"return confirm('If you made any changes, save them before removing a row or column from a table, as changes will be lost.\\n\\nReally remove column?');\">Remove</a>";

                                echo "<td>$rm_link</td>\n";

                            }

                            echo '</tr>' . "\n";

                            $is_even = false;


                            for ($row = 0; $row < $total_returns->row_count; $row++) {

                                $className = "row-even";

                                if (!$is_even) {
                                    $className = "row-odd";
                                }

                                $is_even = !$is_even;

                                print("<tr name=\"total_returns-$row\" id=\"total_returns-$row\" class=\"$className\">\n");
                                print("<td><a href=\"$this_page?remove_row=$row&table=total_returns\" class=\"abbr-large\" onclick=\"return confirm('If you made any changes, save them before removing a row or column from a table, as changes will be lost.\\n\\nReally remove row?');\">Remove</a></td>\n");

                                for ($col = 0; $col < $total_returns->col_count; $col++) {
                                    $value = $total_returns->rows[$row][$col];

                                    $class_stmt = '';
                                    if ($col != 0) {
                                        $class_stmt = 'class="short"';
                                    }

                                    $td_name="total_returns-cell-$row-$col";
                                    $input_name="total_returns-value-$row-$col";

                                    print ("<td id=\"$td_name\"><input type=\"text\" value=\"$value\" name=\"$input_name\" id=\"$input_name\" $class_stmt /></td>\n");
                                }

                                print("</tr>\n");
                            }

                            print("</table>");

                            print("<input type=\"button\" class=\"submit-float\" onclick=\"return add_row('total_returns','$this_fund_symbol');\" value=\"Add row\" />\n");
                            print("<input type=\"button\" class=\"submit-float\" onclick=\"return add_col_to_total_returns(); \" value=\"Add column\" />\n");

                        } else {
                            // die ("Could not find table \"$table_name\" for fund \"$this_fund_symbol\"");
                        }
                        ?>

                        <hr />

                        <!-- *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** -->

                        <h2>Total Returns footer</h2>

                        <table>
                            <tr class="row-even">
                                <td>
                                    <?php
                                    $total_returns_footer = get_total_returns_footer($mysql, $this_fund_symbol);

                                    // Singleton row doesn't exist yet
                                    if ($total_returns_footer == NULL) {
                                        $total_returns_footer = '';
                                    }

                                    print("<input type=\"text\" name=\"total_returns_footer_value\" id=\"total_returns_footer_value\" value=\"$total_returns_footer\" class=\"long\" /></td>\n");
                                    ?>
                                </td>
                            </tr>
                        </table>

                        <hr />

                        <!-- *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** -->

                        <h2>Fund information</h2>

                        <table id="fund_information" style="width: 80%">

                            <tr>
                                <th>Name</th>
                                <th>Value</th>
                                <th>Actions</th>
                            </tr>

                            <?php
                            // ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ >

                            $query = "SELECT * FROM ".$this_fund_symbol."_fund_information;";

                            // Array of entries already fetched above in stats area, now print!
                            $result = $mysql->getResults ($query);

                            $is_even = false;

                            for ($i = 0; $i < sizeof($result); $i++) {
                                $row = $result[$i];

                                $className = "row-even";

                                if (!$is_even) {
                                    $className = "row-odd";
                                }

                                $is_even = !$is_even;

                                print("            <tr name=\"fund_information-$row[0]\" id=\"fund_information-$row[0]\" class=\"$className\">\n");
                                print("               <td><input type=\"text\" name=\"fund_information-name-$row[0]\" id=\"fund_information-name-$row[0]\" value=\"$row[1]\" /></td>\n");
                                print("               <td><input type=\"text\" name=\"fund_information-value-$row[0]\" id=\"fund_information-value-$row[0]\" value=\"$row[2]\" /></td>\n");
                                print("               <td><a href=\"admin.php\" class=\"abbr-large\" onclick=\"return remove_row('fund_information-$row[0]');\">remove</a></td>\n");
                                print("            </tr>\n");
                            }
                            // < ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~
                            ?>

                        </table>

                        <input type="button" class="button" onclick="return add_row('fund_information','avemx');" value="Add row" />

                        <hr />

                        <h2>Save changes</h2>
                        <p><?= $save_reminder ?></p>
                        <div>

                            <input type="submit" name="save" id="save" value="Save changes" class="submit-float" onclick="return confirm('This will save all changes on the page. Continue?')" /> &nbsp; <input type="button" value="Clear changes" class="submit-float" onclick="confirm_redirect('Any changes since your last save will be lost. Continue?','./index.php');" />

                        </div>

                    </form>

                    <!-- Cross-browser -safe way to pad bottom -->
                    <br /><br />

                </td>
            </tr>
        </table>
    </body>
</html>
