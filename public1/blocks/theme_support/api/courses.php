<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/ajax_controller.php');
require_once('../lib.php');
require_once($CFG->dirroot.'/blocks/theme_support/classes/mindatlas_theme_library.php');

// use sesskey() to create sesskey, it will be saved in $_SESSION['USER'], also can be get from $USER->sesskey
// require_sesskey(); // Gotta have the sesskey.
require_login(); // Gotta be logged in (of course).
$PAGE->set_context(context_system::instance());

new class() extends ajax_controller{
    function __construct()
    {
        parent::__construct();
        echo json_encode($this->process($this->action, $this->payload));
    }


    // add your extra action process functions, add your return to $this->result.
    // public function get_courses(){
    //     $result = theme_support_course_model::get_courses();
    //     $this->result = $result;
    // }

     //Get course info
    public function get_course_info($payload){
        global $DB;
        // $data =[];
        // $courseId = $payload["courseId"];
        // $course = $DB->get_record('course', array('id'=>$courseId));
        // if(!empty($course)){
        //     $data = $course;
        // }
        // // Please get course image url 
        // //if image not existed then use default-course.jpg under theme/generic/pix;
        // $data->image = '/generic/theme/generic/pix/default-course.jpg';
        $get_course_info = new mindatlas_theme_library();
        $data = $get_course_info->get_course_info($payload);
        $this->result = $data;
    }

    
};
exit;


// react & axios example:

// componentDidMount() {
//     axios.post(`http://vba.local/blocks/marking_tool/api/modules.php`, {
//         action: 'get_modules',
//         payload: {
//             userid: M.user.id,
//             sesskey: M.user.sesskey
//         },
//     }).then(res => {
//         console.log(res)
//     })
// }