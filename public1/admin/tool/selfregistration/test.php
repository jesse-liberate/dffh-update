<?php
require_once(__DIR__ . '/../../../config.php');

use tool_selfregistration\task\self_registration_users_notification_email;

require_once(__DIR__ . "/classes/task/self_registration_users_notification_email.php");
$a = new self_registration_users_notification_email();
$a->execute();
