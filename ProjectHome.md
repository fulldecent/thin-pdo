
## Project Overview ##
This project provides a minimal extension for PHP's PDO (PHP Data Objects) class designed for ease-of-use and reducing development time/effort. This is acheived by providing methods - delete, insert, select, and update - for quickly building common SQL statements, handling exceptions when SQL errors are produced, and automatically returning results/number of affected rows for the appropriate SQL statement types.

## System Requirements ##
  * PHP 5
  * PDO Extension
  * Appropriate PDO Driver(s) - PDO\_SQLITE, PDO\_MYSQL, PDO\_PGSQL
  * Only MySQL, SQLite, and PostgreSQL database types are currently supported.

## Code Samples / Documentation ##
Code samples and documentation for this project can be accessed at <a href='http://www.imavex.com/php-pdo-wrapper-class/'><a href='http://www.imavex.com/php-pdo-wrapper-class/'>http://www.imavex.com/php-pdo-wrapper-class/</a></a>.  Below, you will find a brief example of this class in action.

```
<?php
/*STEP 1: Include the project's required file, class.db.php.*/
include("class.db.php");

/*STEP 2: Connect to database using the project's constructor.*/
$db = new db("mysql:host=localhost;dbname=mydb", "dbuser", "dbpasswd");

/*STEP 3: Create a table for demoing functionality.  Because creating a table
isn't a common task, the catch-all run method is used.*/
$sql = <<<SQL
CREATE TABLE mytable (
    ID int(11) NOT NULL AUTO_INCREMENT,
    FName varchar(50) NOT NULL,
    LName varchar(50) NOT NULL,
    Age int(11) NOT NULL,
    Gender enum('male','female') NOT NULL,
    PRIMARY KEY (ID)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;
SQL;
$db->run($sql);

/*STEP 4: Insert a record into your newly created table by passing an associative
array to the project's insert method.  This method will automatically filter the array's
keys against the available field names in the database table.*/
$db->insert("mytable", array(
    "FName" => "John",
    "LName" => "Doe",
    "Age" => 26,
    "Gender" => "male"
));

/*STEP 5: View the table's records with the select method.*/
$results = $db->select("mytable");
print_r($results);

/*STEP 6: Update table records by using the update method.  Like insert,
this method will automatically filter the update array against the available
table fields.*/
$db->update("mytable", array(
    "FName" => "Jane",
    "Gender" => "female"
), "FName = John AND LName = Doe");

/*STEP 7: View the table's records again to verify the update.*/
$results = $db->select("mytable");
print_r($results);

/*STEP 8: Delete the newly updated record with the delete method.*/
$db->delete("mytable", "FName = Jane AND LName = Doe");

/*STEP 9: View the table's records again to verify the delete.*/
$results = $db->select("mytable");
print_r($results);
?>
```