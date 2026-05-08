<?php
require_once('../../../config.php');
require_once('../../../lib/filelib.php');
// require_login(0, false);
if(isset($_GET['id'])){
	$id=$_GET['id'];
	$fs = get_file_storage();
	$file = $fs->get_file_by_id($id);
	send_stored_file($file);
}
?>