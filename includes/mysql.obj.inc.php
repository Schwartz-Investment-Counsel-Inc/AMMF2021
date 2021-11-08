<?php

  require_once 'config.inc.php'; // TODO: better way to inject config values than globals?
  require_once 'fix_mysql.inc.php';
/***********************************************
*
* MODULE DESCRIPTION
*
* This module is used to encapsulate information
* about database connections. It gives the
* scripter easy-to-remember and hence use
* functions that can:
*   - connect to a database
*   - disconnect from a database
*   - create a database
*   - delete a database
*   - create a table
*   - delete a table
*   - access MySQL error (maybe)
*   - execute SQL statements, which requires
*     knowledge of SQL
*
* If your SQL is shaky, check out the following
* tutorial for a gentle introduction or
* refresher:
*
* http://www.w3schools.com/sql/default.asp
*
***********************************************/

/***********************************************
*
* VARIABLES
*
***********************************************/

class mysql {

	var $ADDRESS; // Modify
	var $DATABASE; // Modify
	var $USER; // Modify
	var $PASSWORD; // Modify

	var $CONNECTION; // Don't modify

	/***********************************************
	*
	* CONSTRUCTOR
	*
	* USE: INITIALIZES VARIABLES. DOES NOT CREATE
	*      CONNECTION. AUTOMATICALLY CALLED WHEN
	*      OBJECT IS CREATED.
	*
	* PARAMETERS: none
	*
	* RETURN: none
	*
	* EXAMPLES:
	*   - $obj = new mysql ();s
	*
	* NOTES:
	*   -
	*
	***********************************************/


	function mysql () {

		$this->ADDRESS  = MYSQL_HOST;
		$this->DATABASE = MYSQL_DATABASE;
		$this->USER     = MYSQL_USER;
		$this->PASSWORD = MYSQL_PASSWORD;

	}


	/***********************************************
	*
	* showErr ()
	*
	* USE: Show the MySQL error in the event of an error.
	*
	* PARAMETERS:
	*
	* RETURN: none
	*
	* EXAMPLES:
	*   - ... or trigger_error ($this->showError());
	*
	* NOTES:
	*   - Used by functions in this include file
	*   - Can use in user-defined functions, too
	*
	***********************************************/

	function showError () {
		return ("ERROR: " . mysql_errno() . " : " . mysql_error () );
	}


	/***********************************************
	*
	* connectDB ( ... )
	*
	* USE: Connect to database. Must be done before
	*      any other operations.
	*
	* PARAMETERS:
	*   - connectDB (): use default database
	*   - connectDB (String user, String password) :
	*       uses default database, but specify user
	*       and password. Overrides $USER, $PASSWORD
	*   - connectionDB (String database, String user,
	*       String password) : specify the database,
	*       user, and password. Overrides $DATABASE,
	*       $USER, $PASSWORD.
	*
	* RETURN: none (dies on unsuccessful attempts)
	*
	* EXAMPLES:
	*   - $obj->connectDB ();
	*   - $obj->connectDB ("george", "secretpassword");
	*   - $obj->connectDB ("databasename",
	*       "george", "secretpassword");
	*
	* NOTES:
	*   - Will die on unsuccessful attempts
	*   - I did not include an option to specify
	*       a connection to other machines or
	*       database management systems. This is
	*       an easy fix, but unnecessary for now.
	*
	***********************************************/


	// Emulates overloaded functions, can take arguments

	function connectDB () {

		// Determine number of arguments

		$numArgs = func_num_args();

		// Get array of args

		$args = func_get_args();

		// If no arguments, we want to connect to $DATABASE

		if ($numArgs == 0) {
			$this->connectDB0 ();
		}

		// If two arguments, specifies user and password

		elseif ($numArgs == 2) {
			$this->connectDB2 ($args[0], $args[1]);
		}

		// If three arguments, specify the user, password,
		//   and database

		elseif ($numArgs == 3) {
			$this->connectDB3 ($args[0], $args[1], $args[2]);
		}

		// If any other number of parameters, return false

		else {
			trigger_error ("<p><strong>Incorrect parameters for database connection.</strong></p>");
		}
	}


	// No arguments, use default database, user and
	//   and password

	function connectDB0 () {

		$this->CONNECTION = mysql_connect($this->ADDRESS, $this->USER, $this->PASSWORD)
		  or trigger_error ("<p><strong>Connection to database failed.<br />" . $this->showError ()."</strong></p>");

		mysql_select_db($this->DATABASE, $this->CONNECTION)
		  or trigger_error ("<p><strong>Select database failed.<br />" . $this->showError ()."</strong></p>");

	}


	// Two arguments, specifying user and password

	function connectDB2 ($user, $password) {

		$this->CONNECTION = mysql_connect($this->ADDRESS, $user, $password)
		  or trigger_error ("<p><strong>Connection to database failed.<br />" . $this->showError ()."</strong></p>");

		mysql_select_db($this->DATABASE, $this->CONNECTION)
		  or trigger_error ("<p><strong>Select database failed.<br />" . $this->showError ()."</strong></p>");

	}


	// Three arguments, specifying database, user and
	//   password

	function connectDB3 ($database, $user, $password) {

		$this->CONNECTION = mysql_connect($this->ADDRESS, $user, $password)
		  or trigger_error ("<p><strong>Connection to database failed.<br />" . $this->showError ()."</strong></p>");

		mysql_select_db($database, $this->CONNECTION)
		  or trigger_error ("<p><strong>Select database failed.<br />" . $this->showError ()."</strong></p>");

	}

	/***********************************************
	*
	* disconnectDB ()
	*
	* USE: Close database connection. Call when done
	*      with all database operations.
	*
	* PARAMETERS: none
	*
	* RETURN: none
	*
	* EXAMPLES:
	* - $obj->disconnectDB()
	*
	* NOTES:
	*   - none
	*
	***********************************************/


	function disconnectDB () {
		mysql_close ($this->CONNECTION);
	}


	/***********************************************
	*
	* createDatabase ( String database )
	*
	* USE: Create a database.
	*
	* PARAMETERS:
	*   - String database : name for new database
	*
	* RETURN: true if successful, otherwise dies
	*
	* EXAMPLES:
	*   - $obj->createDatabase ("database_name");
	*
	* NOTES:
	*   - This is best avoided. Use only for
	*     projects like content management systems.
	*
	***********************************************/


	function createDatabase ($database) {

		$query = "CREATE DATABASE " . $database . ";";
		mysql_query($query)
		  or trigger_error ("<p><strong>Create database failed.<br />" . $this->showError ()."</strong></p>");

		return true;

	}


	/***********************************************
	*
	* deleteDatabase ( String database )
	*
	* USE: Delete a database. (Warning: deleting is
	*   forever)
	*
	* PARAMETERS:
	*   - String database : name for database to be
	*     destroyed
	*
	* RETURN: true if successful, otherwise false
	*
	* EXAMPLES:
	*   - $obj->deleteDatabase ( "database_name" );
	*
	* NOTES:
	*   - This is best avoided. Use only for
	*     projects like content management systems.
	*
	***********************************************/


	function deleteDatabase ($database) {

		$query = "DROP DATABASE " . $database . ";";
		mysql_query($query)
		  or trigger_error ("<p><strong>Drop database failed.<br />" . $this->showError ()."</strong></p>");

		return true;

	}


	/***********************************************
	*
	* executeSQL ( String SQL )
	*
	* USE: Execute an arbitrary SQL command. Use
	*      for actions like INSERT, DELETE, UPDATE,
	*      MODIFY, etc.
	*
	* PARAMETERS:
	*      - String SQL: an arbitrary SQL command
	*
	* RETURN: generic variable storing result of
	*         mysql query
	*
	* EXAMPLES:
	*   - $obj->executeSQL ( "UPDATE users SET password = "
	*     . $new_password . " WHERE user = " .
	*     $username . ";" );
	*   - $obj->executeSQL ( "DELETE FROM user WHERE user = "
	*     . $user . ";" );
	*
	* NOTES:
	*   - Again, use this for the common database
	*     queries, such as SELECT, DELETE, UPDATE,
	*     INSERT
	*   - Can also use for CREATE and DROP, though
	*     the functions createTable ( table_name )
	*     and deleteTable ( table_name ) do the
	*     same thing
	*   - This is the only function that requires
	*     knowledge of SQL. Need a refresher? Easy
	*     read:
	*
	*     http://www.w3schools.com/sql/default.asp
	*
	***********************************************/


	function executeSQL ( $SQL ) {

		return mysql_query ( $SQL , $this->CONNECTION )
		  or trigger_error ("<p><strong>Execute SQL failed.<br />" . $this->showError ()."</strong></p>");

	}


	/***********************************************
	*
	* resultsExist (String SQL)
	*
	* USE: Determine whether results exists for a query
	*
	* PARAMETERS:
	*      - String SQL: an SQL SELECT statement
	*
	* RETURN: true if results exist, else false
	*
	* EXAMPLES:
	*   - See getResults () below for an example
	*
	* NOTES:
	*   - Useful before executing other queries
	*
	*     http://www.w3schools.com/sql/default.asp
	*
	***********************************************/


	function resultsExist ( $SELECT ) {

		$execute = mysql_query ( $SELECT , $this->CONNECTION )
		  or trigger_error ("<p><strong>Execute SQL failed.<br />" . $this->showError ()."</strong></p>");

		if(@mysql_num_rows($execute)) {

			return true;

		}

		else {

			return false;

		}

	}



	/***********************************************
	*
	* getResults ( String SQL )
	*
	* USE: Get an array of results from a SELECTion
	*
	* PARAMETERS:
	*      - String SQL: an SQL SELECT statement
	*
	* RETURN: array of results (an array of arrays)
	*
	* EXAMPLES:
	*
	* SLOWER, BUT EASIER TO READ
	*
	================================================
	=
	= $query = "SELECT * FROM test;";
	=
	= if ($obj->resultsExist ($query) ) {
	=
	=   $result = $obj->getResults ($query);
	=
	=   foreach ($result as $i)
	=     echo ("<p>&bull;&nbsp; <em> " . $i[0] . " ...</em></p>");
	=
	= }
	=
	= else {
	=
	=   echo("<p>&bull;&nbsp; <em>~ zero results ~</em></p>");
	=
	= }
	================================================
	*
	* FASTER, BUT MORE DIFFICULT TO READ
	*
	================================================
	=
	= $query = "SELECT * FROM test;";
	= $result = $obj->getResults ($query);
	=
	= if (strcmp ($result[0], "0") != 0) {
	=
	=   foreach ($result as $i)
	=     echo ("<p>&bull;&nbsp; <em> " . $i[0] . " ...</em></p>");
	=
	= }
	=
	= else {
	=
	=   echo("<p>&bull;&nbsp; <em>~ zero results ~</em></p>");
	=
	= }
	=
	================================================
	*
	* NOTES:
	*   - This is much cleaner than the typical query,
	*     but this is a slower technique. The reason is
	*     that calling resultsExist (...) will execute
	*     an additional query -- hence, two queries
	*     are executed!
	*   - resultsExist (...) is not necessary if you
	*     are confident that there are results.
	*   - You can even call this function without
	*     resultsExist (...), but you may print out
	*     a lone zero string unless you verify that
	*     the array
	*     is not storing a lone zero. You can do this
	*     using something like
	*     if ($returned_array[0] != "0") { ... }
	*
	*     http://www.w3schools.com/sql/default.asp
	*
	***********************************************/


	function getResults ( $SELECT ) {

		$execute = mysql_query ( $SELECT , $this->CONNECTION )
		  or trigger_error ("<p><strong>Execute SQL failed.<br />" . $this->showError ()."</strong></p>");

		if(@mysql_num_rows($execute)) {

			while($row = mysql_fetch_row ($execute))
				$result[]=$row;

		}

		else {

			$result[] = "0";

		}

		return $result;

	}

} // End of class mysql

?>
