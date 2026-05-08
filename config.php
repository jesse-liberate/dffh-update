<?php  // Moodle configuration file
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'dffhdev_mood863';
$CFG->dbuser    = 'dffhdev_mood863';
$CFG->dbpass    = '+lD&p6hYPlbR;uf2';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = 'https://dffh-development.com';
$CFG->dataroot  = '/home/dffhdev/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

@error_reporting(E_ALL); // NOT FOR PRODUCTION SERVERS!
@ini_set('display_errors', '1'); // NOT FOR PRODUCTION SERVERS!
$CFG->debug = (E_ALL); // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = 1; // NOT FOR PRODUCTION SERVERS!

$CFG->noemailever='true';
$CFG->theme = 'boost';
//$CFG->wwwrootendsinpublic = false;


require_once(__DIR__ . '/lib/setup.php');



// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!