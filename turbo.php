<?php

/**
 * Opencart Turbo
 * Version: 0.2
 * Orignal Code by chrisatomix
 * Tweaks from Jay6390
 * Continued development by Alex 'Freedom' Haines
 * https://www.freedomitsolutions.co.uk
 *
 * This script will apply several changes to boost the performance of OpenCart, including:
 * 1) DONE: Converting the MySQL DB Storage Engine from MyISAM to InnoDB.
 * 2) DONE: Adding indexes to all foreign keys (columns ending with '_id') as well as those defined in the script index_list array.
 * 3) DONE: Deleting itself and log file from server on completion (no need to get back into FTP to do this).
 * 4) TODO: Replace config.php and admin/config.php with dynamic Git-friendly version.
 * 5) TODO: Accurately Detect and Remove Demo Data.
 * 6) TODO: Remove Unwanted Zones.
 * 7) BUGS: Script timing issues.
 *
 * NOTES:
 * 1) This script should be deleted immediately following use.
 * 2) This script should be run again following OpenCart upgrades (as the optimizations will be removed during upgrade).
 */

define('GITHUB_URL','https://github.com/AlexJamesHaines/opencart-turbo');
define('VERSION','0.2'); // incremented on change for easy changing of script
define('DEBUG_SCRIPT','0'); // 1 for yes any other value for no

// Set the time zone
date_default_timezone_set('GMT');

// Display some PHP errors whilst debugging
if(DEBUG_SCRIPT=="1"){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL | E_STRICT);
}

/**
 * List of Additional Columns that should be indexed (in the format tablename.columname)
 * NOTE: Exclude any columns that end with '_id' here
 */
$index_list   = array();
$index_list[] = 'product.model';
$index_list[] = 'url_alias.query';
$index_list[] = 'url_alias.keyword';

$action = (!empty($_REQUEST['action'])) ? $_REQUEST['action'] : '';

if(file_exists('./config.php')) {
  require_once './config.php';
  
  foreach($index_list as &$item) {
    $item = DB_PREFIX . $item;
  }
}
else {
  die("Aborting: File config.php not found!");
}

if(!$db = turbo_db_connect()) {
  die("Aborting: Unable to connect to DB! Please check your store settings within config.php");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Opencart Turbo v<?php echo VERSION; ?></title>
  <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <a href="<?php echo GITHUB_URL; ?>"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_red_aa0000.png" alt="Fork me on GitHub"></a>
  <br>
  <div class="container">
    <div class="well">
      <h2>Opencart Turbo v<?php echo VERSION; ?><br><small>Continued development by <a href="https://www.freedomitsolutions.co.uk">Alex 'Freedom' Haines</a></h2>
      <p>
        This script will apply several changes to boost the performance of OpenCart, including:<br>
        <ul>
          <li>Converting the MySQL DB Storage Engine from MyISAM to InnoDB.</li>
          <li>Adding indexes to all foreign keys (columns ending with '_id') as well as those defined in the script index_list array.</li>
        </ul>
        <strong>Notes:</strong><br>
        <ul>
          <li>This script should be deleted immediately following use.</li>
          <li>This script should be ran again following OpenCart upgrades.</li>
          <li>Updates can be found at GitHub: <a href="<?php echo GITHUB_URL; ?>" target="_blank"><?php echo GITHUB_URL; ?></a>.</li>
        </ul>
      </p>
    </div>
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h3 class="panel-title">Available Options</h3>
      </div>
      <div class="panel-body">
        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'].'?action=engine'); ?>" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to convert your OpenCart database tables from MyISAM to InnoDB?');">Convert DB to InnoDB Engine</a><br><br>
        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'].'?action=indexes'); ?>" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to add indexes to your OpenCart database tables?');">Add Indexes to the DB</a><br><br>
		<a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'].'?action=delete'); ?>" class="btn btn-danger btn-lg" onclick="return confirm('Are you sure you want to remove this script and it\'s associated log file from your server?');">Remove This Script from Server</a>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Output</h3>
      </div>
      <div class="panel-body">
        <p><?php
		  ob_start();
          switch($action) {
            case 'engine':
              turbo_switch_engine();
              break;
            case 'indexes':
              turbo_table_indexes();
              break;
            case 'delete':
              turbo_delete_self();
              break;
            default:
              // Nothing yet
              break;
          }
		  ob_end_flush();
        ?></p>
      </div>
    </div>
  </div>
  <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php


function turbo_switch_engine() {
  global $db;

  $time_mstart=microtime();
  $tables = turbo_get_tables();
  $time_mend=microtime();
  $time = ($time_mend - $time_mstart) * 1000;
  turbo_log("Execution time for fetching ".count($tables)." tables was ".$time." ms",'primary','DEBUG');

  if($tables && count($tables) > 0) {
    turbo_log("Switching database table engine now...",'info','START');
    foreach ($tables as $table_name => $table) {
      $time_mstart=microtime();
      if($table['engine'] != 'InnoDB') {
        $sql = "ALTER TABLE `{$table_name}` ENGINE = INNODB";
        if($rs = $db->query($sql)) {
          turbo_log("{$table_name} Converted from {$table['engine']} to InnoDB",'success','SUCCESS');
        }
        else {
          turbo_log("{$table_name} Engine switch failed - ".$db->error,'danger','ERROR');
        }
      }
      else {
        turbo_log("{$table_name} is already using the InnoDB engine",'info','SKIP');
      }
	  $time_mend=microtime();
	  $time = ($time_mend - $time_mstart) * 100000;
      turbo_log("Execution time for editing table ".$table_name." was ".round($time,1)." ms",'primary','DEBUG');
	  //wall_clock("{$table_name}");
    }
  }
  else {
    turbo_log("Aborting",'danger','ERROR');
	turbo_log("Table count was not greater than 0",'primary','DEBUG');
  }
  // Display execution time
  wall_clock('switch_engine');
}


function turbo_table_indexes() {
  global $db, $index_list;

  $time_mstart=microtime();
  $tables = turbo_get_tables(true);
  $time_mend=microtime();
  $time = ($time_mend - $time_mstart) * 1000;
  turbo_log("Execution time for fetching ".count($tables)." tables was ".round($time,1)." ms",'primary','DEBUG');
  
  if($tables && count($tables) > 0) {
    turbo_log("Adding indexes to tables now...",'info','START');
    // Loop through Tables
    foreach($tables as $table_name => $table) {
	$time_mstart=microtime();
	//turbo_log("Looping Tables - Line 189",'primary','DEBUG');
      // Loop through Columns
      foreach($table['columns'] as $column_name => $column) {
		//turbo_log("Looping Columns - Line 192",'primary','DEBUG');
        $has_index   = false;
        $needs_index = false;
        // Does this column need an index?
        if(substr($column_name, -3) == '_id') {
          // Column ends in '_id'
          $needs_index = true;
        }
        elseif(in_array($table_name.'.'.$column_name, $index_list)) {
          // This column exists in the manual index list
          $needs_index = true;
        }
        // Loop through the indexes for this column to determine if it has one already
        if($column['indexes'] && !empty($column['indexes'])) {
          foreach($column['indexes'] as $index) {
            if($index['position'] == 1) {
              // This column is in first position in an Index
              $has_index = true;
            }
          }
        }
        if(!$has_index && $needs_index) {
          // Has no Index and needs an Index
          $sql = "ALTER TABLE `{$table_name}` ADD INDEX (  `{$column_name}` )";
          if($output = $db->query($sql)) {
            turbo_log("{$table_name}.{$column_name} - Index added",'success','SUCCESS');
          }
          else {
            turbo_log("{$table_name}.{$column_name} - Index add failed - ".$db->error,'danger','ERROR');
          }
        }
        elseif($needs_index) {
          // Needs an Index but already has one
          turbo_log("{$table_name}.{$column_name} - Index already exists",'info','INFO');
        }
      }
	$time_mend=microtime();
    $time = ($time_mend - $time_mstart) * 100000;
    turbo_log("Execution time for working on ".$table_name." table was ".round($time,1)." ms",'primary','DEBUG');
    }
  }
  else {
    turbo_log("Aborting",'danger','ERROR');
	turbo_log("Table count was not greater than 0",'primary','DEBUG');
  }
  // Display execution time
  wall_clock('turbo_table_indexes');
}


function turbo_delete_self() {
  unlink(__FILE__);
  if (file_exists('turbo.log')) {
    unlink('turbo.log');
  }
  if (file_exists('turbo.log') or file_exists('turbo.php')) {
  }
  else {
  //turbo_log("Files cleaned up successfully...",'danger','DELETED'); //this made the script write to a new log file - doh!
  die("Files cleaned up successfully..."); //so we just die instead
  }
}


function turbo_get_tables($getindexes=false) {
  global $db;

  $tables = false;
  $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE '".DB_DATABASE."'";

  if($rs = $db->query($sql)) {
    if($rs->num_rows > 0) {
      // Table list loaded
      turbo_log("{$rs->num_rows} tables found...",'info','START');
      $tables = array();
      while ($row = $rs->fetch_assoc()) {
        $table               = array();
        $table['name']       = $row['TABLE_NAME'];
        $table['engine']     = $row['ENGINE'];
        $table['columns']    = false;
        if($getindexes) {
          // Get indexes first
          $sqli = "SELECT *
                  FROM INFORMATION_SCHEMA.STATISTICS
                  WHERE TABLE_SCHEMA LIKE '".DB_DATABASE."'
                  AND TABLE_NAME LIKE '".$table['name']."'";
          $table['indexes'] = array();
		  turbo_log("Running SQL - Line 265",'primary','DEBUG');
          if($rsi = $db->query($sqli)) {
            while($indexes = $rsi->fetch_assoc()) {
              $index             = array();
              $index['name']     = $indexes['COLUMN_NAME'];
              $index['key']      = $indexes['INDEX_NAME'];
              $index['unique']   = ($indexes['NON_UNIQUE'] == 1) ? false : true; // Invert logic
              $index['position'] = $indexes['SEQ_IN_INDEX'];
              if(!isset($table['indexes'][$index['name']])) {
                $table['indexes'][$index['name']] = array();
              }
              $table['indexes'][$index['name']][] = $index;
            }
          }
          // Get Columns
          $sqlc = "SELECT *
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA LIKE '".DB_DATABASE."'
                  AND TABLE_NAME LIKE '".$table['name']."'";
		  turbo_log("Running SQL - Line 284",'primary','DEBUG');
          if($rsc = $db->query($sqlc)) {
            $table['columns'] = array();
            while($columns = $rsc->fetch_assoc()) {
              $column            = array();
              $column['name']    = $columns['COLUMN_NAME'];
              $column['type']    = $columns['DATA_TYPE'];
              $column['indexes'] = false;
              if(isset($table['indexes'][$column['name']])) {
                // If there are any Indexes for this column, add to Array
                $column['indexes'] = $table['indexes'][$column['name']];
              }
              $table['columns'][$column['name']] = $column;
            }
          }
          else {
            turbo_log("No database columns found in table {$table['name']}",'danger','ERROR');
          }
        }
        $tables[$table['name']] = $table;
      }
    }
    else {
      // No tables found
      turbo_log("No database tables found",'danger','Error');
    }
  }
  else {
    turbo_log("Unable to retrieve database table list",'danger','ERROR');
  }
  return $tables;
}


function wall_clock($type='unknown') {
  // Work out execution time for debugging
  $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
  turbo_log("Execution time for module ".$type." was ".round($time,2)." seconds",'primary','TIMER');
}


function turbo_log($input,$type='default',$label='') {
  // Check to see if debug is enabled to output logging accordingly
  if(DEBUG_SCRIPT=="1") {
    if($label) {
      echo '<span class="label label-'.$type.'">'.$label.'</span> ';
    }
    echo $input."<br>";
  }
  else if($label!="DEBUG") {
	if($label) {
      echo '<span class="label label-'.$type.'">'.$label.'</span> ';
    }
    echo $input."<br>";
  }	
  // Write to file in case of timeout, crash etc
  $myfile = fopen("turbo.log", "a") or die("Unable to open log file (turbo.log)!");
  fwrite($myfile, $input.PHP_EOL);
  fclose($myfile);
  // Flush the buffers
  turbo_flush_buffers();
}


function turbo_flush_buffers() {
  ob_end_flush();
  //ob_flush();
  flush();
  ob_start();
}


function turbo_db_connect() {
  /**
  * Connect to Database using Config Settings
  * @return MySQLi Connection Object
  */
  $db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
  return $db;
}


/* End of File */
