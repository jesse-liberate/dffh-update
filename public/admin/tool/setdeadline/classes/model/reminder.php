<?php
namespace tool_setdeadline\model;

class reminder extends base {
    protected static $table = 'course_reminder';

    protected $fields = ['id', 'firstreminder', 'secondreminder', 'manager',
        'siteadmin', 'emailadminlist', 'repeated', 'timecreated','courseid','type','instanceid',
        'timemodified', 'modifiedby'];

    /**
     * @return \tool_setdeadline\relationship\has_many
     */
    public function reminder_assigns() {
        return $this->has_many(reminder_assign::class, 'reminder_id');
    }
    
    public function reminder_overrides() {
      return $this->has_many(reminder_override::class, 'reminder_id');
    }

    public function delete() {
        $this->reminder_assigns()->delete();
        parent::delete();
    }

    public function userids() {
        global $DB;

        $userids = [];

        foreach ($this->reminder_assigns as $assign) {
            if ($assign->type == 'user') {
                $userids[$assign->instanceid] = $assign->instanceid;
            } else if ($assign->type == 'course') {
                $enrolled_users = get_enrolled_users(
                    \context_course::instance($assign->instanceid),
                    '',
                    0,
                    'u.id'
                );
                foreach ($enrolled_users as $enrolled_user) {
                    $userids[$enrolled_user->id] = $enrolled_user->id;
                }
            } else if ($assign->type == 'cohort') {
                $users = $DB->get_records('cohort_members', [
                    'cohortid' => $assign->instanceid
                ], '', 'userid');
                foreach ($users as $user) {
                    $userids[$user->userid] = $user->userid;
                }
            }
        }

        return $userids;
    }

    public function courseids() {
        global $DB;

        $courseids = [];
        foreach ($this->reminder_assigns as $assign) {
            if ($assign->type == 'course') {
                $courseids[$assign->instanceid] = $assign->instanceid;
            } else if ($assign->type == 'user') {
                $courses = enrol_get_all_users_courses($assign->instanceid, true, 'id');
                foreach ($courses as $course) {
                    $courseids[$course->id] = $course->id;
                }
            } else if ($assign->type == 'cohort') {
                $users = $DB->get_records('cohort_members', [
                    'cohortid' => $assign->instanceid
                ], '', 'userid');
                foreach ($users as $user) {
                    $courses = enrol_get_all_users_courses($user->userid, true, 'id');
                    foreach ($courses as $course) {
                        $courseids[$course->id] = $course->id;
                    }
                }
            }
        }

        return $courseids;
    }
    
    public function get_attributes() {
      return $this->attributes;
    }
}
