<?php

require_once(__DIR__ . '/../../../config.php');
$dir = $CFG->dataroot . '/reports';

require_login(0, false);
if (!is_siteadmin()) {
  redirect('/');
}

$type = required_param('type', PARAM_ALPHA);

switch($type){
  case 'general':
  case 'courseoverview':
    $dirPath = $dir .'/' .$type;
  break;
  default:
    $dirPath = '';
}

if($dirPath){
  if (file_exists($dirPath)) {
    $filenames = array_diff(scandir($dirPath, SCANDIR_SORT_DESCENDING), array('..', '.'));
    // get the latest file and output it
    if (isset($filenames[0])) {
      $file = $dirPath . '/' . $filenames[0];
      if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header("Content-type: application/csv");
        header("Content-Disposition: attachment; filename=$filenames[0]");
        header("Expires: 0");
        header("Pragma: public");
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
      }
    }
  }
  
  redirect(
    '/blocks/reporting/report/'.$type.'.php',
    'Cannot find any CSV File. Nothing to download at this moment.',
    null,
    \core\output\notification::NOTIFY_WARNING
  );
} else {
  redirect('/');
}



