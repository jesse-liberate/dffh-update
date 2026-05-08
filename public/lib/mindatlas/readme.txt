***************************
Any changes of this folder MUST be updated to demo-new
Always get thi folder fom demo-new
Consider compatibility when edit 

Don’t put version number on folder name or file names, so the path keeps static 

Every plugin has a readme_mindatlas.txt file. Please remember to update it when update the plugin.
It should include:
•	Version
•	Download url
•	API doc url
•	Any notes you think is useful







========================
HOW TO INCLUDE
========================
require JS|CSS:

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true); 
$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true); 
$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');

"$PAGE->requires" will only require same flie once. Most of the JQuery plugin issue is caused by re-declearing JQuery.
The second parameter of $PAGE->requires->js decides whether include js in header.

==========================
require php lib

require_once($CFG->libdir.'/mindatlas/malib.php');

========================
ma_coursecat.php is a class containing functions to manipulate course & category.