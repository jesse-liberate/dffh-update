<?php
require_once($CFG->dirroot.'/blocks/reporting/report/lib.php');

function block_reportwizard_getCourses_Category() {
  global $DB,$USER;
  // $sql = "select co.fullname,ca.name,ca.path,ca.id as categoryid from mdl_course co, mdl_course_categories ca where co.category=ca.id order by path";
  //$rs = $DB->get_records_sql($sql,array());
  $data = array();

  /////////////////////////////////////////////
  //////// reportwizard add All courses option
  // $data['1'] = 'ALL';
  /////////////////////////////////////////////

  // Check if the user want to see is Vendor or not
  if (\reporting\lib\is_vendor($USER->id)) {
    $arr_course_ids = \reporting\lib\get_vendor_course_ids($USER->id);
    $list = implode(",",$arr_course_ids);
    $sql = "SELECT * from mdl_course_categories 
     where visible=1 AND id in(select category from mdl_course where id in(".$list.")) order by path ASC";
    $rs = $DB->get_records_sql($sql,array());

    foreach ($rs as $row) {
        if($row->name!=""){
           // Find all course in this category
          $num_space = substr_count($row->path,"/");
          $space = add_space($num_space);
          $course_space = " . ";
          $sql_course = "select * from mdl_course where visible=1 AND category=".$row->id." and id in(".$list.") order by fullname ASC";
          $courses = $DB->get_records_sql($sql_course,array());
          $data ["{category}".$row->id] = $space.strtoupper("[ ".$row->name)." ]";
          foreach ($courses as $course_record) $data [$course_record->id] = $space.$course_space.$course_record->fullname;
        }
    }
  }else{
    $rs = $DB->get_records('course_categories',array('visible'=>1),'path ASC');
    foreach ($rs as $row) {
        if($row->name!=""){
           // Find all course in this category
          $num_space = substr_count($row->path,"/");
        $space = \reporting\lib\add_space($num_space);
          $course_space = " . ";
          $courses = $DB->get_records('course',array('visible'=>1,'category'=>$row->id), 'fullname ASC');
          //echo "<pre>".print_r($courses,true)."</pre>";
          // if(empty($courses)) $data["{category}".$row->name] = $space.strtoupper("[ ".$row->name)." ]"." - [empty]";
          if(empty($courses)) $data["{category}".$row->id] = $space.strtoupper("[ ".$row->name)." ]"." - [empty]";
          else{
              $data ["{category}".$row->id] = $space.strtoupper("[ ".$row->name)." ]";
              foreach ($courses as $course_record) {
                if($course_record->category==0) continue;
                 $data [$course_record->id] = $space.$course_space.$course_record->fullname;
              }
          }
        }
    }
  }
  return $data;
}


?>