<?php
require(realpath(dirname(__FILE__)) . '/../../../../config.php');
require_once($CFG->dirroot.'/admin/tool/hierarchy/lib.php');
global $DB;

//Call customisation function to update Position code and Report To field in here.
update_PositionCode_ReportTo_field();

rebuild_hierarchy();

echo "<br><br>";
echo "<a href='../visualization.php' class='btn'>Back</a>";
