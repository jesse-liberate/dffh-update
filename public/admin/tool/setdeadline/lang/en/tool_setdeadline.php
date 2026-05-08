<?php

$string['pluginname'] = 'Set Course Deadlines';
$string['setting_coursedeadline'] = 'Set course deadlines';
$string['setdeadline:set_deadline'] = "Set Course Deadlines";
$string['user_reminder_email:subject']='{$a}: Not completed courses';
$string['user_reminder_email:body']= '
Dear {$a->firstname},

You may recall that we assigned the following mandatory online modules to you in order for you to comply with business requirements, which need to be completed.
Our system indicates that you have been enrolled in{$a->courselist} have not yet completed it.
Please discuss this with your manager / team leader and set time aside to login and complete this training as soon as is practical to do so prior to the due date.

If you have further queries, please contact at {$a->support_email}.
Kind regards,
{$a->support_fullname},
{$a->support_email}
';
$string['user_reminder_email:body_old']= '
Dear {$a->firstname},

You may recall that we assigned the following mandatory online modules to you in order for you to comply with business requirements, which need to be completed.
Our system indicates that you have been enrolled in{$a->courselist} have not yet completed it.
Please discuss this with your manager / team leader and set time aside to login and complete this training as soon as is practical to do so prior to the due date.

If you have further queries, please contact at {$a->support_email}.
Kind regards,
{$a->support_fullname},
{$a->support_email}
';
$string['user_overdue_email:subject'] = '{$a}: Not completed courses';
$string['user_overdue_several_courses_email:body'] = 'Dear {$a->firstname},

Our system indicates that you have been enrolled in the following courses which are now overdue:{$a->courselist}

Please arrange some time to complete the above courses as a priority and contact your manager if you are having issues completing them.

To access the system, simply click <a href="{$a->siteurl}">{$a->siteurl}</a>.

Once you are logged in, you can view your assigned courses on your dashboard, or on your <a href="{$a->learning_path_url}">Courses</a> page.

If you have further questions, please contact at {$a->support_email}.
Kind regards,
{$a->support_fullname},
{$a->support_email}';
$string['user_overdue_several_courses_email:body_old'] = 'Dear {$a->firstname},

Our system indicates that you have been enrolled in the following courses which are now overdue:{$a->courselist}

Please arrange some time to complete the above courses as a priority and contact your manager if you are having issues completing them.

To access the system, simply click <a href="{$a->siteurl}">{$a->siteurl}</a>.

Once you are logged in, you can view your assigned courses on your dashboard, or on your <a href="{$a->learning_path_url}">Courses</a> page.

If you have further questions, please contact at {$a->support_email}.
Kind regards,
{$a->support_fullname},
{$a->support_email}
<p style="text-align:center">Your success is our goal!</p>';
$string['user_overdue_single_course_email:body'] = 'Dear {$a->firstname},

Our system indicates that you have been enrolled in the following course which is now overdue: {$a->courselist}.

Please arrange some time to complete the above courses as a priority and contact your manager if you are having issues completing them.

To access the system, simply click <a href="{$a->siteurl}">{$a->siteurl}</a>.

Once you are logged in, you can view your assigned courses on your dashboard, or on your <a href="{$a->learning_path_url}">Courses</a> page.

If you have further questions, please contact at {$a->support_email}.
Kind regards,
{$a->support_fullname},
{$a->support_email}';
$string['user_overdue_single_course_email:body_old'] = 'Dear {$a->firstname},

Our system indicates that you have been enrolled in the following course which is now overdue: {$a->courselist}.

Please arrange some time to complete the above courses as a priority and contact your manager if you are having issues completing them.

To access the system, simply click <a href="{$a->siteurl}">{$a->siteurl}</a>.

Once you are logged in, you can view your assigned courses on your dashboard, or on your <a href="{$a->learning_path_url}">Courses</a> page.

If you have further questions, please contact at {$a->support_email}.
Kind regards,
{$a->support_fullname},
{$a->support_email}';
$string['manager_reminder_email:subject']='{$a}: Not completed users';
$string['manager_reminder_email:body']= '
Dear {$a->firstname},

Please find below the list of your team members who have mandatory training modules that are overdue:
<ul>{$a->userlist}</ul>

Each individual has been emailed today, reminding them to login and complete their training. Can you please follow up or allow them time if necessary to ensure that the modules listed above are completed.
If you have further queries, please contact at {$a->support_email}.
Kind regards,
{$a->support_fullname},
{$a->support_email}
';
$string['manager_reminder_email:body_old']= '
Dear {$a->firstname},

Please find below the list of your team members who have mandatory training modules that are overdue:
<ul>{$a->userlist}</ul>

Each individual has been emailed today, reminding them to login and complete their training. Can you please follow up or allow them time if necessary to ensure that the modules listed above are completed.
If you have further queries, please contact at {$a->support_email}.
Kind regards,
{$a->support_fullname},
{$a->support_email}
';
$string['repeat_until_complete'] = 'Repeat the Period 2 notification indefinitely until the user completes the course';
$string['courses_with_deadlines'] = 'Courses with deadlines';
$string['coursename'] = 'Course name';
$string['assignment_name'] = 'Assignment name';
$string['deadline_type'] = 'Deadline type';
$string['period_one'] = 'Period 1';
$string['period_two'] = 'Period 2';
$string['days'] = 'Day(s)';
$string['after_period_one'] = 'after the Period 1';
$string['repeat_period_two_indefinitely'] = 'Repeat the Period 2 notification indefinitely until the user completes the course';
$string['also_send_to'] = 'Second email also send to';
$string['manager'] = 'Manager';
$string['site_admin'] = 'Site admin';
$string['pone_required'] = 'Period 1 is required';
$string['ptwo_required'] = 'Period 2 is required';
$string['set_deadline_success'] = 'Deadline has been created successfully';
$string['set_deadline:save:success'] = 'Course <strong>{$a->coursename}</strong> with Deadline type <strong>{$a->deadlinetype}</strong> has been saved successfully.';
$string['set_deadline'] = 'Set deadline';

$string['conflict:newexistingreminder']='<div class="alert alert-error">The reminder is failed to create. The course <strong>{$a->coursename}</strong> is conflicted with existing reminder in the same Deadline type <strong>{$a->type}</strong></div>';
$string['conflict:updateexistingreminder']='<div class="alert alert-error">The reminder is failed to update. The course <strong>{$a->coursename}</strong> is conflicted with existing reminder in the same Deadline type <strong>{$a->type}</strong></div>';
$string['cohort:removed']='At least one of below cohort(s) have not been assigned cohort sync to the course <strong>{$a}</strong>: ';
$string['user:removed']='User(s), who have not been enrolled to the course <strong>{$a}</strong>, will removed from the selection.';
$string['message:error']='<div class="alert alert-error">{$a}</div>';
$string['message:success']='<div class="alert alert-success">{$a}</div>';
$string['priority1']='Priority 1';
$string['priority2']='Priority 2';
$string['priority3']='Priority 3';

$string['override_deadline:save:success'] = 'New deadline has been saved successfully';
$string['override_deadline:firstreminder'] = 'First Reminder';
$string['override_deadline:secondreminder'] = 'Second Reminder';
$string['override_deadline:after_firstreminder'] = 'day(s) after first reminder';
$string['override_deadline:add_button'] = 'Add new user deadline';
$string['override_deadline:back_button'] = 'Back';
$string['override_deadline:confirmdeleteremindertitle'] = 'Confirm delete reminder';
$string['override_deadline:confirmdeleteremindermessage'] = 'Do you want to remove {$a} from the reminder?';
$string['override_deadline:deletedreminder'] = 'Reminder for {$a} has been successfully deleted.';
$string['override_deadline:update:success'] = 'Deadline for {$a} has been updated successfully.';

$string['deadline:remove'] = 'the deadline for course <strong>{$a}</strong> has been deleted successfully.';

$string['firstreminder'] = 'Period 1 (in days)';
$string['secondreminder'] = 'Period 2 (in days after period 1)';
$strig['reminder:repeat'] = 'Repeat the period 2 indefinitely';

$string['deadline:confirm:delete'] = 'The course deadline <strong>{$a}</strong> will be deleted. Do you want to continue?';
$string['accessdenied'] = 'Access denied';