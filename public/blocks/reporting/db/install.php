<?php

/**
 * Post installation procedure
 *
 * @see Initial blocks value()
 */
function xmldb_block_reporting_install() {
	global $DB;
	//INITIAL PDF RECORD
	$record = new stdClass();
	$record->timecreated = time();
	$record->name = 'report_pdf';
	$record->description = 'Turn off: 1; Turn on: 2';
	$record->setting = 1;
	$DB->insert_record('report_setting',$record);
}

/**
 * Post installation recovery procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_block_reporting_install_recovery() {
}
