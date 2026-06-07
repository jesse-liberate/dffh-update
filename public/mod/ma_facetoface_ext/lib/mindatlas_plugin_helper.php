<?php
defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/mindatlas_plugin_helper_base.php');

// Liberate 30/05 - Note: This appears to reference a block that is not installed

//use block_resources\category;
//use block_resources\resource;

class mindatlas_plugin_helper extends mindatlas_plugin_helper_base
{
    static $accessible_courses = null;

    // Liberate 30/05 - Assuming next 2 functions aren't used, as it depends on a 'resources' block that is not installed

    /*public function render_page_file_library($categoryid, $lmsonly, $keyword, $page)
    {
        global $CFG, $PAGE, $OUTPUT, $USER;

        $item_perpage = 10;

        // mustache need key to be sequenced
        $categories = array_values(resource::get_resources_categories($USER->id));

        $search_result = resource::search_resource_learning($lmsonly, $categoryid, $keyword, $page);
        $resources = array_values($search_result['results']);

        $data = (object) [
            'count' => count($resources),
            'total' => $search_result['total_number'],
        ];
        if ($search_result['total_number'] >= 2) {
            $count_msg = get_string('showing_number_plural', 'theme_mindatlas', $data);
        } else {
            $count_msg = get_string('showing_number_singular', 'theme_mindatlas', $data);
        }

        $no_result_msg_1 = '';
        $no_result_msg_2 = get_string('check_if_mispelt_search', 'theme_mindatlas');
        if (empty($search_result['total_number'])) {
            $no_result = true;
            if (empty($keyword)) {
                $no_result_msg_1 = get_string('no_files_found', 'theme_mindatlas');
                $no_result_msg_2 = '';
            } else {
                // message for searh
                $no_result_msg_1 = get_string('no_match', 'theme_mindatlas', $keyword);
            }
        } else {
            $no_result = false;
        }

        $context = [
            'wwwroot' => $CFG->wwwroot,
            'page_url' => $CFG->wwwroot . '/theme/mindatlas/pages/file_library.php',
            'lmsonly' => $lmsonly,
            'categories' => $this->render_flielib_category_selector($categoryid),
            'category_selected' => $categoryid,
            'item_each_page' => $item_perpage,
            'page' => $page,
            'keyword' => $keyword,
            'total_page' => $search_result['total_page'],
            'pagination' => $this->render_pagination($search_result['total_number'], $search_result['total_page'], $page),
            'count_msg' => $count_msg,
            'resources' => $resources,
            'no_result' => $no_result,
            'no_result_msg_1' => $no_result_msg_1,
            'no_result_msg_2' => $no_result_msg_2,
            'manage_url' => $CFG->wwwroot . '/blocks/resources/resources.php',
            'category_url' => $CFG->wwwroot . '/blocks/resources/category.php',
            'permissions_url' => $CFG->wwwroot . '/blocks/resources/permissions.php',
            'tags_url' => $CFG->wwwroot . '/blocks/resources/manage_tags.php',
            'isSiteAdmin' => is_siteadmin($USER->id),
        ];
        return $OUTPUT->render_from_template('theme_mindatlas/page_file_library', $context);
    } 

    public function render_flielib_category_selector($selected)
    {
        global $USER;

        $html = '';

        $categories = resource::get_resources_categories_by_levels($USER->id);

        foreach ($categories as $category) {
            $html .= $this->render_category_tree($category, $selected)['dom'];
        }

        return $html;
    }*/
    
    public function render_pagination($totalnumber, $totalpage, $page = 1)
    {
        if ($totalnumber == 0) {
            return '';
        }

        $html = '<div class="pagination-bar" data-totalpages="' . $totalpage . '">';
        $html .=    '<div id="page-prev" class="prev arrow"></div>';

        for ($i = 1; $i <= $totalpage; $i++) {
            $classes = 'page-number ';
            if ($i == $page) {
                $classes .= 'selected ';
            }
            $html .=    '<div class="' . $classes . '" data-page="' . $i . '">' . $i . '</div>';
        }

        $html .=    '<div id="page-next" class="next arrow"></div>';
        $html .= '</div>';
        return $html;
    }
    
    // Liberate 30/05 - Assuming next 2 functions not used, as it depends on a 'resources' block that is not installed
    

    /*public function render_category_tree($category, $selected)
    {

        $dom_subcats = '';
        if (!empty($category['subcategories'])) {
            foreach ($category['subcategories'] as $subcategory) {
                $dom_subcats .= $this->render_category_tree($subcategory, $selected)['dom'];
            }
        }

        $classes = 'category ';
        if ($category['parent'] == 0) {
            $classes .= 'top ';
        } else {
            $classes .= 'child pl-3 ';
        }
        if (!empty($category['subcategories'])) {
            $classes .= 'has-children ';
        } else {
            $classes .= 'no-children ';
        }

        $subcates = category::get_all_sub_categories($category['id']);

        $class_show = in_array($selected, $subcates) ? 'show' : 'notshow';
        $dom_arrow = '<span class="d-inline-block mr-1 arrow collapsed" data-toggle="collapse" data-target="#subcats_' . $category['id'] . '" aria-expanded="false" aria-controls="subcats_' . $category['id'] . '">  </span>';
        $dom_name = '<span class="name searchin" data-id="' . $category['id'] . '">' . $category['name'] . '</span>';
        $dom_name_wrapper = '<div class="name-wrapper" >' . $dom_arrow . $dom_name . '</div>';

        $dom_subcats_wrapper = '<div class="collapse ' . $class_show . '" id="subcats_' . $category['id'] . '">' . $dom_subcats . '</div>';

        $dom = '<div class="' . $classes . '">' . $dom_name_wrapper . $dom_subcats_wrapper . '</div>';
        $category['dom'] = $dom;
        return $category;
    }

    function render_view_resource($id, $categoryid, $lmsonly, $keyword, $page)
    {
        global $CFG, $PAGE, $OUTPUT;

        $resource = resource::get_resource_details_by_id($id);

        // var_dump($resource);

        $context = $resource;
        $context->wwwroot = $CFG->wwwroot;

        $preview = '';
        if (!empty($resource->attachment->fileid)) {
            $attachment = $resource->attachment;
            switch ($attachment->type) {
                case 'image':
                    $preview = '<img class="preview-img" src="' . $attachment->fileurl . '">';
                    break;
                case 'audio':
                    $preview  = '<audio controls>';
                    $preview .= '<source src="' . $attachment->fileurl . '" type="audio/mpeg">';
                    $preview .= '</audio>';
                    break;
                case 'video':
                    $preview  = '<video width="320" controls>';
                    $preview .=     '<source src="' . $attachment->fileurl . '">';
                    $preview .= '</video>';
                    break;
                case 'file':
                    if (str_endsWith($attachment->filename, '.pdf')) {
                        $preview = '<iframe src="' . $CFG->wwwroot . '/blocks/resources/pdf_preview.php?id=' . $attachment->fileid . '" frameborder="0" width="" scrolling="no"></iframe>';
                    }
                    break;
            }
        }

        $context->preview = $preview;

        return $OUTPUT->render_from_template('theme_mindatlas/view_resource', $context);
    }*/

    public function get_users_enrolled_courses($user = null, $options = array('onlyactive' => 'true'))
    {
        global $USER;
        // Make sure there is a real user specified.
        if ($user === null) {
            $userid = isset($USER->id) ? $USER->id : 0;
        } else {
            $userid = is_object($user) ? $user->id : $user;
        }

        $return = [];

        $enrolled_courses = enrol_get_users_courses($userid, $options['onlyactive']);
        foreach ($enrolled_courses as $course) {
            $return[] = $course->id;
        }

        return $return;
    }

    public function get_selfenrol_courses($options = array('onlyactive' => 'true'))
    {
        global $DB;
        // TODO: didn't consider enrol period here
        $self_course_ids = $DB->get_fieldset_select('enrol', 'courseid', 'enrol = ? AND status = ?', array('self', '0'));

        if ($options['onlyactive']) {
            // exclude hidden courses
            foreach ($self_course_ids as $key => $courseid) {
                // $course = new ma_course($courseid);
                $course = $DB->get_record('course', array('id' => $courseid)); // get course object
                if($course->visible == 0) {
                    unset($self_course_ids[$key]);
                }
            }
        }

        return $self_course_ids;
    }

    public function get_users_accessible_coursecats($user = null) {
        global $DB;

        $accessible_courses = $this->get_users_accessible_courses($user);
        if (empty($accessible_courses)) {
            return [];
        }
        list($in_courseids, $in_courseids_params) = $DB->get_in_or_equal($accessible_courses);
        return array_unique($DB->get_fieldset_select('course', 'category', "id $in_courseids", $in_courseids_params));
    }

    public function get_users_accessible_courses($user = null)
    {
        global $USER;

        // Liberate 30/05 - Note: can't find any definition for this class
        if (isset(static::$accessible_courses)) {
            return static::$accessible_courses;
        }

        // Make sure there is a real user specified.
        if ($user === null) {
            $userid = isset($USER->id) ? $USER->id : 0;
        } else {
            $userid = is_object($user) ? $user->id : $user;
        }

        // for current user
        if ($userid == $USER->id) {
            // use cache in $THEME first
            if (!empty($this->my_accessable_courses)) {
                return $this->my_accessable_courses;
            }

            // Check if the user has permission of view course without participation in system, we will
            // double check individual courses permission later
            $can_view_course = has_capability('moodle/course:view', context_system::instance());

            // admin and not switched to other roles can access all courses
            if ((is_siteadmin($userid) && empty($USER->access['rsw'])) || $can_view_course) {
                $allcourses = array_keys(get_courses());
                if (($root_course_key = array_search('1', $allcourses)) !== false) {
                    unset($allcourses[$root_course_key]);
                }
                foreach ($allcourses as $index => $courseid) {
                    $context_course = context_course::instance($courseid);
                    if (!has_capability('moodle/course:view', $context_course)) {
                        unset($allcourses[$index]);
                    }
                }

                $this->my_accessable_courses = $allcourses;
                return $this->my_accessable_courses;
            }
        }

        $return = [];

        // include enrolled courses
        $return = $this->get_users_enrolled_courses($userid);

        // include self enrol available courses
        $return = array_unique(array_merge($return, $this->get_selfenrol_courses()));

        // if it's current user,
        if ($userid == $USER->id) {
            // save to $THEME to cache
            $this->my_accessable_courses = $return;
        }

        static::$accessible_courses = $return;

        return $return;
    }

    public function get_user($userid = null)
    {
        global $USER, $OUTPUT, $DB, $PAGE;
        $user = new stdClass();

        $user->id = $userid ? $userid : $USER->id;
        if ($user->id < 1) {
            $user->sesskey = $USER->sesskey;
            return $user;
        }

        $record = $DB->get_record('user', array('id' => $user->id));

        $user->auth = $record->auth;
        $user->email = $record->email;
        $user->firstname = $record->firstname;
        $user->lastname = $record->lastname;

        try {
            $user->avatar = $OUTPUT->user_picture($record, array('size' => 100));
            $user->avatarL = $OUTPUT->user_picture($record, array('size' => 150));
        } catch (Exception $e) {
        }

        // Current user
        if ($user->id == $USER->id) {
            $user->profile = $USER->profile;
            $user->sesskey = $USER->sesskey;
            $user->isSiteAdmin = is_siteAdmin($USER);
        }else{ // not current user
            $user->isSiteAdmin = is_siteadmin($user);
            $user->profile = new stdClass();

            $customFields = $DB->get_records('user_info_data', ['userid' => $userid]);
            foreach ($customFields as $customField) {
                $field = $DB->get_record('user_info_field', ['id' => $customField->fieldid]);
                $shortName = $field->shortname;
                $user->profile->$shortName = $customField->data;
            }
        }

        return $user;
    }
}
