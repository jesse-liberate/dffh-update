<?php
require(realpath(dirname(__FILE__)) . '/../../../config.php');
include("lib.php");
echo "Testing to send email to admin";
$support_user = core_user::get_support_user();
if(email_to_user_delay($support_user,$support_user,'subject','test text','test html')) echo "Sending email delay to admin";
?>