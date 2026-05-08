<?php

defined('MOODLE_INTERNAL') || die();

class ma_coursecat_base
{
	// TODO: create constructor, can create base on $USER, initial some essential attributes
	// function __construct(argument)
	// {
	// 	# code...
	// }

	/** 
	* Find direct children courses under a category, return data of selected type
	* @static
	* @param $cat_id: category id
	* @param $return_element_type: 'db' - database records,'id' - course ids,'name' - course names
	* @return array
	*
	* @author Jacky-1.0
	* @version 1.0
	*/
	public static function children_courses($cat_id, $return_element_type='db') {
	    global $DB;

	    $cat_courses_records = $DB->get_records('course',array('category'=>$cat_id));
	    $cat_courses = array();

	    foreach ($cat_courses_records as $key => $course) {

	    	// EXTEND: apply filters here

	        switch ($return_element_type) {
	        	case 'db':
	        		$cat_courses[] = $course;
	        		break;

	        	case 'id':
					$cat_courses[] = $course->id;
	        		break;

	        	case 'name':
					$cat_courses[] = $course->fullname;
	        		break;

	        	default:
	        		$cat_courses[] = $course;
	        		break;
	        }
	    }

	    return $cat_courses;
	}


	/** 
	* Find all courses under a category recursively, return data of selected type
	* @static
	* @param $cat_id: category id
	* @param $return_element_type: 'db','id','name'
	* @param $courses: result feed back to the recursive function itself
	* @return array
	* 
	* @author Jacky-1.0, 
	* @version 1.0
	*/ 

	public static function descendant_courses($cat_id, $return_element_type='db', $courses = array() ){
	    global $DB;

		$children_courses = self::children_courses($cat_id, $return_element_type);

	    $courses = array_merge($courses,$children_courses);

	    if($subcategories = $DB->get_records('course_categories',array('parent'=>$cat_id))){
	        foreach($subcategories as $subcategory) {
	            $courses = self::descendant_courses($subcategory->id, $return_element_type ,$courses);
	        }
	    };

	    return $courses;
	    
	}

}


class ma_coursecat extends ma_coursecat_base
{
	
	// function __construct(argument)
	// {
	// 	# code...
	// }





}