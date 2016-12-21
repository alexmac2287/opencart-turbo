Opencart Turbo
==============

Version: 0.2  
Orignal Code by chrisatomix  
Tweaks from Jay6390  
Continued development by Alex 'Freedom' Haines  
https://www.freedomitsolutions.co.uk  

This script will apply several changes to boost the performance of OpenCart, including:
*   DONE: Converting the MySQL DB Storage Engine from MyISAM to InnoDB.
*   DONE: Adding indexes to all foreign keys (columns ending with '_id') as well as those defined in the script index_list array.
*   DONE: Deleting itself and log file from server on completion (no need to get back into FTP to do this).
*   TODO: Replace config.php and admin/config.php with dynamic Git-friendly version.
*   TODO: Accurately Detect and Remove Demo Data.
*   TODO: Remove Unwanted Zones.
*   BUGS: Script timing issues.

###Notes###
*   This script should be deleted immediately following use.
*   This script should be run again following OpenCart upgrades (as the optimizations will be removed during upgrade).

###Installation###
1.  Upload **turbo.php** to your OpenCart root directory (next to config.php).
2.  Load **https://yoursite/turbo.php** in your browser and follow the instructions on screen.
