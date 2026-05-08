<?php
//this file is just example file copy from VBA.
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/ajax_controller.php');
require_once(__DIR__ . '/../classes/models/course_model.php');
require_once('../lib.php');

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
    public function get_modules(){
        $result = mt_module_model::get_modules();
        $this->result = $result;
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