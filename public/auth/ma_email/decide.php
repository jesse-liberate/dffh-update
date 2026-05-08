<?php

global $CFG, $PAGE;

// Include required libary files
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir.'/authlib.php');
require_once(__DIR__ . '/lib.php');


$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->fullname.': Signup approval');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_url($CFG->wwwroot. '/auth/ma_email/decide.php');
$PAGE->requires->js(new moodle_url('js/emailadmin.js'));

echo $OUTPUT->header();

$html = '<div id="decidepage">';

if (isset($_POST) && !empty($_POST)) {

	$username = $_POST['username'];
	$secret = $_POST['secret'];
	$decision = $_POST['confirmreject'];
	$emailuser = (isset($_POST['emailuser'])) ? true : false;
	$reason = trim($_POST['reason']);
	$user = $DB->get_record('user',array('username'=>$username));
	$admin_id = isset($_POST['adminid']) ? intval($_POST['adminid']) : null;
	// $userid = $DB->get_field('user','id',array('username'=>$username));

	if (!empty($user)) {
		if ($decision == 'confirm') {
			$html .= $OUTPUT->heading(get_string(
				'auth_emailadmin_confirmmessage',
				'auth_ma_email',
				(object) array('fullname' => fullname($user))
			));
			$url = $_SERVER['HTTP_REFERER'];
			$html .= '<a class="btn bg-color-primary text-white font-weight-bold d-inline-block mb-2 mr-2" href="'.$CFG->wwwroot .
				'/auth/ma_email/confirm.php?data='.$secret.'/'.$username.'&admin_id='.$admin_id.
				'">' . get_string('confirm') . '</a>&nbsp';
			$html .= '<a class="btn bg-color-primary text-white font-weight-bold d-inline-block mb-2 mr-2" href="'.$url.'">' . get_string('back') . '</a>';

		} else { // if decision == reject

			if ($user->confirmed) {
				$html .= '<h2>'.get_string('auth_emailadmin_rejectedfailed', 'auth_ma_email').'</h2>';
			} else {
				// Email rejection to user
				if ($emailuser) {

				    $site = get_site();
				    if (!isset($admin_id)) {
				    	$supportuser = core_user::get_support_user();
				    } else {
				    	$supportuser = $DB->get_record('user', array(
				    		'id' => $admin_id,
			    		));
				    }
				    // $user = $DB->get_record('user',array('id'=>$userid));

				    $data = new stdClass();
				    $data->firstname = $user->firstname;
				    $data->sitename  = format_string($site->fullname);
				    $data->admin     = generate_email_signoff();
				    $data->supportuser = fullname($supportuser);
				    $data->reason = $reason;

				    $subject = get_string('auth_emailadmin_userrejectionsubject', 'auth_ma_email', format_string($site->fullname));

				    $username = urlencode($user->username);
				    $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
				    $data->link  = $CFG->wwwroot;
				    $data->username = $username;

				    if ($reason != '') {
					    $message     = get_string('auth_emailadmin_userrejectionwithreason', 'auth_ma_email', $data);
					    $messagehtml = text_to_html($message, false, false, true);
				    } else {
				    	$message     = get_string('auth_emailadmin_userrejectionwithoutreason', 'auth_ma_email', $data);
					    $messagehtml = text_to_html(get_string('auth_emailadmin_userrejectionwithoutreason', 'auth_ma_email', $data), false, false, true);
				    }

				    $user->mailformat = 1;  // Always send HTML version as well

				    //directly email rather than using the messaging system to ensure its not routed to a popup or jabber
				    // Hacking to force Moodle to send using admin's email
				    if (isset($admin_id)) {
				    	// Store original values
				    	$original_maildisplay = $supportuser->maildisplay;
				    	$original_allowedemaildomains = $CFG->allowedemaildomains;

				    	// Start hacking, there are 2 conditions for Moodle to user From user to send email
				    	// Firstly, From user email address' domain must be in allowedemaildomains
				    	// Secondly, Moodle needs the From user to have maildisplay
				    	// set to either core_user::MAILDISPLAY_EVERYONE or MAILDISPLAY_COURSE_MEMBERS_ONLY
				    	// if they are in the same course to To user
				    	$CFG->allowedemaildomains .= PHP_EOL . substr($supportuser->email,
				    			strpos($supportuser->email, '@') + 1);
				    	$supportuser->maildisplay = core_user::MAILDISPLAY_EVERYONE;
				    }

				    // Save reject action and message to database
				    $action = new StdClass;
				    $action->userid = $user->id;
				    $action->adminid = $admin_id;
				    $action->action = 'reject';
				    $action->message = (!empty($reason)) ? $reason: null;
				    $action->timecreated = time();

				    insert_action($action);
				    email_to_user($user, $supportuser, $subject, $message, $messagehtml);

				    // Finish emailing now we revert back original values so that below code won't be
				    // affected by the wrong value in the hack
				    $supportuser->maildisplay = $original_maildisplay;
				    $CFG->allowedemaildomains = $original_allowedemaildomains;

				    $html .= '<h2>'.get_string('auth_emailadmin_rejectedwithemail', 'auth_ma_email').'</h2>';

				} else { // Reject without sending email
					// Delete user after email
					$DB->delete_records('user', array('username'=>$username)) && $DB->delete_records('user_info_data', array('userid'=>$user->id));
					$html .= '<h2>'.get_string('auth_emailadmin_rejected', 'auth_ma_email').'</h2>';
				}
			}

			$html .= '<a class="btn" href="'.$CFG->wwwroot.'"> Back to Home </a>&nbsp';
		}
	} else {
		$html .= '<h2>'.get_string('auth_emailadmin_nouserfound', 'auth_ma_email').'</h2>';
		$html .= '<a class="btn" href="'.$CFG->wwwroot.'"> Back to Home </a>&nbsp';
	}

}

if (isset($_GET['username'])) {

	$username = trim($_GET['username']);
	$user = $DB->get_record('user',array('username'=>$username));

	if (empty($user)) {
		$html .= '<h2>' . get_string('auth_emailadmin_nouserfound', 'auth_ma_email') . '</h2>';
	} else {
		$fullname = fullname($user);
		$email = $user->email;
		$secret = $_GET['secret'];
		$admin_id = isset($_GET['adminid']) ? intval($_GET['adminid']) : null;
		$previous_actions = get_actions($user->id);

		if (!$user->confirmed) {
			$html .= '<h2>'.get_string('auth_emailadmin_confirmorreject', 'auth_ma_email').'</h2>';
			$html .= '<table class="generaltable" style="width:600px;">';
			$html .= '<tr><td>' . get_string('fullname') . '</td><td>'. $fullname .'</td></tr>';
			$html .= '<tr><td>' . get_string('username') . '</td><td>'. $username .'</td></tr>';

			$html .= '<tr><td>' . get_string('email') . '</td><td>'. $email .'</td></tr>';
			$html .= '</table>';

			$html .= '<h4>'.get_string('auth_emailadmin_approveregistration', 'auth_ma_email').'</h4>';
			$html .= '<form action="decide.php" method="POST">';
			$html .= '<table class="generaltable" style="width:30%;">';
			$html .= '<tr><td><input type="radio" id="confirm" name="confirmreject" value="confirm" checked><label for="confirm">' . get_string('confirm') . '</label></td>';
			$html .= '<td><input type="radio" id="reject" name="confirmreject" value="reject"><label for="reject">' . get_string('reject') . '</label></td></tr>';
			$html .= '</table>';

			$html .= '<div class="reason">';
			$html .= '<h4>'.get_string('auth_emailadmin_reason', 'auth_ma_email').'</h4>';
			$html .= '<textarea rows="5" cols="90" name="reason" placeholder="'.get_string('auth_emailadmin_reason_placeholder', 'auth_ma_email').'"></textarea>';
			$html .= '</div>';

			$html .= '<table class="email">';
			$html .= '<tr><td><input type="checkbox" name="emailuser" id="emailuser" value="true" checked></td>';
			$html .= '<td><label for="emailuser"><h4>'.get_string('auth_emailadmin_emailuser', 'auth_ma_email').'</h4></label></td></tr>';
			$html .= '</table>';

			$html .= '<p class="email"><i>'.get_string('auth_emailadmin_emailinstruction1', 'auth_ma_email').'</i></p>';
			$html .= '<p class="email"><i>'.get_string('auth_emailadmin_emailinstruction2', 'auth_ma_email').'</i></p>';

			$html .= '</br>';
			$html .= '<input type="submit" style="margin-left: 0;" value="'.get_string('auth_emailadmin_updateregistration', 'auth_ma_email').'">';

			$html .= '<input type="hidden" name="username" value="'.$username.'">';
			$html .= '<input type="hidden" name="secret" value="'.$secret.'">';

			if (isset($admin_id)) {
				$html .= '<input type="hidden" name="adminid" value="'.$admin_id.'">';
			}

			$html .= '</form>';
		} else {
			// If this user is ap
			$latest_approve_action = null;

			foreach ($previous_actions as $previous_action) {
				if ($previous_action->action == 'approve') {
					$latest_approve_action = $previous_action;
					break;
				}
			}

			if (isset($latest_approve_action)) {
				$admin = new StdClass;
				$admin->firstname = $latest_approve_action->admin_firstname;
				$admin->lastname = $latest_approve_action->admin_lastname;

				$html .= '<h2>' . get_string('auth_emailadmin_userconfirmed', 'auth_ma_email',
					$admin) . '</h2>';
			} else {
				$html .= '<h2>' . get_string('auth_emailadmin_userconfirmed_noadmin',
						'auth_ma_email') .
					'</h2>';
			}
		}

		if (count($previous_actions)) {
			$html .= '<table class="generaltable">';
			$html .= '<h2>' . get_string('auth_emailadmin_actionsheader', 'auth_ma_email') . '</h2>';
			foreach ($previous_actions as $previous_action) {
				$html .= '<tr><td>';
				$html .= '<strong>' .
					$previous_action->admin_firstname . ' ' . $previous_action->admin_lastname
					. '</strong> - <span class="muted">' .
					date('d/m/Y h:i A', $previous_action->timecreated) . '</span>';
				if ($previous_action->action == 'approve') {
					$html .= '<p>' . get_string('auth_emailadmin_approveduser', 'auth_ma_email') .
						'</p>';
				} elseif ($previous_action->action == 'reject') {
					if (empty($previous_action->message)) {
						$html .= '<p>' . get_string('auth_emailadmin_rejected', 'auth_ma_email') .
							'</p>';
					} else {
						$html .= '<p>' . get_string('auth_emailadmin_rejectedwithreason',
								'auth_ma_email') .
							'</p>';
						$html .= '<p class="action_message">';
						$html .= htmlspecialchars($previous_action->message);
						$html .= '</p>';
					}
				}
				$html .= '</td></tr>';
			}
			$html .= '</table>';
		}
	}
}
$html .= '</div>';

echo $html;

echo $OUTPUT->footer();

?>