<?php
/**
 * @package    block_reportwizard
 * @copyright  Mindatlas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

// namespace block_reportwizard;
defined('MOODLE_INTERNAL') || die();

class block_reportwizard_report_base {

	const REPORT_TYPE_GENERAL = 1;
	const REPORT_TYPE_COURSEOVERVIEW = 2;
	const REPORT_TYPE_ACTIVITY = 3;
    const REPORT_TYPE_MANDATORY_ONLINE = 4;

	const COMPLETION_ALL = 2;
	const COMPLETION_COMPLETED = 1;
	const COMPLETION_INCOMPLETE = 0;

	const ACCESS_PRIVATE = 0;
	const ACCESS_PUBLIC = 1;

    const OBJECT_CATEGORY = 1;
    const OBJECT_COURSE = 2;

    const PERIOD_CONDITION_BEFORE = 0;
    const PERIOD_CONDITION_AFTER = 1;

    const PERIOD_UNIT_DAY = 1;
    const PERIOD_UNIT_WEEK = 2;
    const PERIOD_UNIT_MONTH = 3;
    const PERIOD_UNIT_YEAR = 4;


    public $id;
    public $name;
    public $type;

    public $object_type;
    public $object_id;
    public $hierarchy_nodes;

    public $enrol_date_from;
    public $enrol_date_to;
    public $enrol_period_condition;
    public $enrol_period_number;
    public $enrol_period_unit;

    public $complete_date_from;
    public $complete_date_to;
    public $complete_period_condition;
    public $complete_period_number;
    public $complete_period_unit;

    public $completion_status;

    public $creator_type;
    public $access_type;
    public $share_to;

    public $creator_id;
    public $timecreated;

    /**
     * Constructor.
     *
     * @param 
     */
    public function __construct(stdClass $report_record) {
    	$this->id = $report_record->id;
    	$this->name = $report_record->name;
    	$this->type = $report_record->type;

    	$this->object_type = $report_record->object_type;
    	$this->object_id = $report_record->object_id;
    	$this->hierarchy_nodes = $report_record->hierarchy_nodes;

    	$this->enrol_date_from = $report_record->enrol_date_from;
    	$this->enrol_date_to = $report_record->enrol_date_to;
    	$this->complete_date_from = $report_record->complete_date_from;
    	$this->complete_date_to = $report_record->complete_date_to;

        $this->enrol_period_condition = $report_record->enrol_period_condition;
        $this->enrol_period_number = $report_record->enrol_period_number;
        $this->enrol_period_unit = $report_record->enrol_period_unit;
        $this->complete_period_condition = $report_record->complete_period_condition;
        $this->complete_period_number = $report_record->complete_period_number;
        $this->complete_period_unit = $report_record->complete_period_unit;

    	$this->completion_status = $report_record->completion_status;

    	$this->creator_type = $report_record->creator_type;
    	$this->access_type = $report_record->access_type;
    	$this->share_to = $report_record->share_to;

    	$this->creator_id = $report_record->creator_id;
    	$this->timecreated = $report_record->timecreated;

    }

    public static function db_insert(stdClass $new_record) {
    	global $DB;
		return $DB->insert_record('report_wzd_report', $new_record);
    }


    public function db_update() {
		global $DB;
    	return $DB->update_record('report_wzd_report', $this);
    }

    public function db_delete() {
    	global $DB;

        $DB->delete_records('report_wzd_shareto', array('report_id' => $this->id));
        $DB->delete_records('report_wzd_infofield_filter', array('report_id' => $this->id));
        $DB->delete_records('report_wzd_columns', array('report_id' => $this->id));
        $DB->delete_records('report_wzd_report', array('id' => $this->id));

        #TODO: add error handling

    	return true;
    }


    // turn constant values to readable string
    public function str_report_type() {
    	switch ($this->type) {
    		case self::REPORT_TYPE_GENERAL :
    			return 'General report';
    			break;
    		case self::REPORT_TYPE_COURSEOVERVIEW :
    			return 'Course overview report';
    			break;
    		case self::REPORT_TYPE_ACTIVITY :
    			return 'Activity report';
    			break;
            case self::REPORT_TYPE_MANDATORY_ONLINE :
                return 'Mandatory online training report';
                break;
    		default:
    			return 'Report type is not defined';
    			break;
    	}
    }

    public function str_completion_status() {
    	switch ($this->completion_status) {
    		case self::COMPLETION_ALL :
    			return 'All';
    			break;
    		case self::COMPLETION_COMPLETED :
    			return get_string('completed', 'block_reportwizard');
    			break;
    		case self::COMPLETION_INCOMPLETE :
    			return get_string('incomplete', 'block_reportwizard');
    			break;
    		default:
    			return 'Completion status is not defined';
    			break;
    	}
    }

    public function str_access_type() {
    	switch ($this->access_type) {
    		case self::ACCESS_PRIVATE :
    			return 'Private';
    			break;
    		case self::ACCESS_PUBLIC :
    			return 'Public';
    			break;
    		default:
    			return 'Access type is not defined';
    			break;
    	}
    }

    public function str_enrol_period_conition() {

        if (empty($this->enrol_period_number)) {
            return 'No condition';
        }

        $str = 'is ';

        switch ($this->enrol_period_condition) {
            case self::PERIOD_CONDITION_BEFORE :
                $str .= 'before ';
                break;
            case self::PERIOD_CONDITION_AFTER :
                $str .= 'after ';
                break;
        }

        $str .= $this->enrol_period_number.' ';

        switch ($this->enrol_period_unit) {
            case self::PERIOD_UNIT_DAY :
                if ($this->enrol_period_number > 1) {
                    $str .= 'days ';
                }else{
                    $str .= 'day ';
                }
                break;
            case self::PERIOD_UNIT_WEEK :
                if ($this->enrol_period_number > 1) {
                    $str .= 'weeks ';
                }else{
                    $str .= 'week ';
                }
                break;
            case self::PERIOD_UNIT_MONTH :
                if ($this->enrol_period_number > 1) {
                    $str .= 'months ';
                }else{
                    $str .= 'month ';
                }
                break;
            case self::PERIOD_UNIT_YEAR :
                if ($this->enrol_period_number > 1) {
                    $str .= 'years ';
                }else{
                    $str .= 'year ';
                }
                break;
        }

        return $str;

    }

    // @30/07/2018 enhancement
    public function str_enrol_conition() {
        $html = null;
        if (!empty($this->enrol_date_from)){
            $html .= 'From - '.date('d/m/Y', $this->enrol_date_from);
        }
        if (!empty($this->enrol_date_to)){
            $html .= ' To - '.date('d/m/Y', $this->enrol_date_to);
        }
        return $html;
    }

    public function str_complete_period_conition() {

        if (empty($this->complete_period_number)) {
            return 'No condition';
        }

        $str = 'is ';

        switch ($this->complete_period_condition) {
            case self::PERIOD_CONDITION_BEFORE :
                $str .= 'before ';
                break;
            case self::PERIOD_CONDITION_AFTER :
                $str .= 'after ';
                break;
        }

        $str .= $this->complete_period_number.' ';

        switch ($this->complete_period_unit) {
            case self::PERIOD_UNIT_DAY :
                if ($this->complete_period_number > 1) {
                    $str .= 'days ';
                }else{
                    $str .= 'day ';
                }
                break;
            case self::PERIOD_UNIT_WEEK :
                if ($this->complete_period_number > 1) {
                    $str .= 'weeks ';
                }else{
                    $str .= 'week ';
                }
                break;
            case self::PERIOD_UNIT_MONTH :
                if ($this->complete_period_number > 1) {
                    $str .= 'months ';
                }else{
                    $str .= 'month ';
                }
                break;
            case self::PERIOD_UNIT_YEAR :
                if ($this->complete_period_number > 1) {
                    $str .= 'years ';
                }else{
                    $str .= 'year ';
                }
                break;
        }

        return $str;

    }

    // @30/07/2018 enhancement
    public function str_complete_conition()
    {
        $html = null;
        if (!empty($this->complete_date_from)) {
            $html .= 'From - ' . date('d/m/Y', $this->complete_date_from);
        }
        if (!empty($this->complete_date_to)) {
            $html .= ' To - '.date('d/m/Y', $this->complete_date_to);
        }
        return $html;
    }

    // return the name of category or course criteria of the report
    public function get_report_object_name() {
        global $DB;
        // @30/07/2018 enhancement
        $ids = [];
        if (!empty($this->object_id)){
            $ids = explode(',' ,$this->object_id);
        }
        $return = [];
        if (!empty($ids)){
            foreach ($ids as $key => $value) {
                if (strpos($value, '{category}') !== false){
                    $pattern = '/{.*}(\d*)/';
                    $cat_id = preg_replace($pattern, '$1', $value);
                    $return[] = 'Category - '. $DB->get_field('course_categories', 'name', ['id' => $cat_id]); 
                }else{
                    $return[] = 'Course - '. $DB->get_field('course', 'fullname', ['id' => $value]);
                }
            }
        }
        return implode(', ', $return);
        // switch ($this->object_type) {
        //     case self::OBJECT_CATEGORY :
        //         return '[ '.$DB->get_field('course_categories', 'name', array('id'=>$this->object_id)).' ]';
        //         break;
        //     case self::OBJECT_COURSE :
        //         if ($this->object_id == '1') {
        //             return 'ALL';
        //         }
        //         return $DB->get_field('course', 'fullname', array('id'=>$this->object_id));
        //         break;
        //     default:
        //         return '';
        //         break;
        // }
    }

    public static function report_get_schedule_details($report){
        global $DB;
        if($report->type == self::REPORT_TYPE_MANDATORY_ONLINE){
            $schedule = $DB->get_record('report_wzd_schedule',array('report_id'=>$report->id),'frequency,startdate,lastrun,nextrun');
            if(!empty($schedule)){
                $days_diff = (time() - abs(intval($schedule->startdate)))/86400;
                $schedule->conds_from_date = ($schedule->lastrun==0 || $schedule->lastrun==NULL) ? 0 : $schedule->lastrun;
                return $schedule;
            }
        }
    }

}


class block_reportwizard_report extends  block_reportwizard_report_base {

}

?>