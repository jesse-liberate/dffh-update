<?php

//Todo: implement function
function dffh_list_training_session($payload)
{
  global $DB, $USER;
  $userid = $payload['userid'];
  $sql_str = "SELECT tb1.*, tb2.* FROM(
              SELECT S.id,F.id as facetofaceid, F.name, F.course as courseid, FSD1.data as location, FSD2.data as room, FSD3.data as venue, FSDE.sessionid, FSDE.timestart, FSDE.timefinish, S.duration
              FROM mdl_facetoface F 
              JOIN mdl_facetoface_sessions S
              ON F.id = S.facetoface
              LEFT JOIN mdl_facetoface_sessions_dates FSDE 
              ON S.id = FSDE.sessionid
              LEFT JOIN mdl_facetoface_session_data FSD1
              ON S.id = FSD1.sessionid AND FSD1.fieldid = 1
              LEFT JOIN mdl_facetoface_session_data FSD2
              ON S.id  = FSD2.sessionid  AND FSD2.fieldid = 2 
              LEFT JOIN mdl_facetoface_session_data FSD3
              ON S.id = FSD3.sessionid  AND FSD3.fieldid = 3
              WHERE F.course <> 0 AND S.capacity <> 2  ) tb1
              LEFT JOIN( SELECT FFS.sessionid, FFS.userid, FFSS.statuscode
                  FROM mdl_facetoface_signups FFS 
                  JOIN mdl_facetoface_signups_status FFSS 
                  ON FFS.id = FFSS.signupid ) tb2
              ON tb1.id = tb2.sessionid
              ";
  $data = array_values($DB->get_records_sql($sql_str));
  foreach ($data as &$item) {
    $date = date('d-m-Y', $item->timestart);
    $time = date('h:i', $item->timestart);
    $item->date = $date;
    $item->time = $time;
  }
  $isAdmin = is_siteadmin();
  if ($isAdmin) {
    return $data;
  } else {
    $data = array_filter($data, function ($item) use ($USER) {
      return $item->userid === $USER->id;
    });
    return array_values($data);
  }
}
//Todo: implement function
function dffh_list_coaching_session($payload)
{
  global $DB, $USER;
  $userid = $payload['userid'];
  $sql_str = "SELECT tb1.*, tb2.* FROM(
              SELECT S.id,F.id as facetofaceid, F.name, F.course as courseid, FSD1.data as location, FSD2.data as room, FSD3.data as venue, FSDE.sessionid, FSDE.timestart, FSDE.timefinish, S.duration,S.capacity
              FROM mdl_facetoface F 
              JOIN mdl_facetoface_sessions S
              ON F.id = S.facetoface
              LEFT JOIN mdl_facetoface_sessions_dates FSDE 
              ON S.id = FSDE.sessionid
              LEFT JOIN mdl_facetoface_session_data FSD1
              ON S.id = FSD1.sessionid AND FSD1.fieldid = 1
              LEFT JOIN mdl_facetoface_session_data FSD2
              ON S.id  = FSD2.sessionid  AND FSD2.fieldid = 2 
              LEFT JOIN mdl_facetoface_session_data FSD3
              ON S.id = FSD3.sessionid  AND FSD3.fieldid = 3
              WHERE F.course <> 0  AND S.capacity = 2) tb1
              LEFT JOIN( SELECT FFS.sessionid, FFS.userid, FFSS.statuscode
                  FROM mdl_facetoface_signups FFS 
                  JOIN mdl_facetoface_signups_status FFSS 
                  ON FFS.id = FFSS.signupid 
                  ) tb2
              ON tb1.id = tb2.sessionid
              ";
  $data = array_values($DB->get_records_sql($sql_str));
  foreach ($data as &$item) {
    $date = date('d-m-Y', $item->timestart);
    $time = date('h:i', $item->timestart);
    $item->date = $date;
    $item->time = $time;
  }
  $isAdmin = is_siteadmin();
  if ($isAdmin) {
    return $data;
  } else {
    $data = array_filter($data, function ($item) use ($USER) {
      return $item->userid === $USER->id;
    });
    return array_values($data);
  }
}
//Todo: implement function
function dffh_get_coach_booking_sessions($coach_userid)
{
}

//Todo: implement function
function dffh_place_booking_session($coach_userid, $userid, $date, $time)
{
}
//Todo: implement function
function dffh_cancel_booking_session($bookingid)
{
}

function dffh_check_coach($payload){
  global $DB, $USER;
  
  $hascoach = $DB->get_record('hierarchy_coach', array('user_id' => $payload['userid']));
  
  $node_user_record = $DB->get_record('hierarchy_user', array('user_id' => $payload['userid']));

  $node = $DB->get_record('hierarchy_node', array('id' => $node_user_record->node_id));
  $nodeparent = $DB->get_record('hierarchy_node', array('id' => $node->parent_node_id));
 
  $coachuser= '';
  if($nodeparent->id == 1){
  return true;
  }else{
  return false;
  }
}

function dffh_check_admin($payload){
  global $DB, $USER;
  
  if(is_siteadmin()){
    return true;
  }else{
    return false;
  }
}

function get_coach_node( $node_id) {
  global $DB;
  $node = $DB->get_record('hierarchy_node', array('id' => $node_id));
 
  if ($node->parent_node_id == 1) {
    return $node->id;
  } else {
    $parent_node = $DB->get_record('hierarchy_node', array('id' => $node->parent_node_id));
    if ($parent_node->parent_node_id == 1) {
      return $parent_node->id;
    } else {
      return get_coach_node($parent_node->id);
    }
  }
}

function dffh_has_coach($payload){
  global $DB;

  $node_user_record = $DB->get_record('hierarchy_user', array('user_id' => $payload['userid']));
  $node = $DB->get_record('hierarchy_node', array('id' => $node_user_record->node_id));
  $nodeparent = $DB->get_record('hierarchy_node', array('id' => $node->parent_node_id));
  $coachuser= '';
  $hascoach = $DB->get_record('hierarchy_coach', array('user_id' => $payload['userid']));
  if($hascoach){
    return true;
  }
  if($nodeparent->id != 1){
      $coachnodeid = get_coach_node( $nodeparent->id);

      $coachuser =  $DB->get_record('hierarchy_coach', array('node_id' => $coachnodeid));
  }else if($nodeparent->id == 1){
      $coachuser = new stdClass();
      $coachuser->user_id = $payload['userid'];
  }
  if(!$coachuser){
    return false;
  }else{
    return true;
  }

}

//Todo: implement function
function dffh_list_requested_training_session($payload)
{
  global $DB, $USER;
  $coach_id = $payload['coach_id'];
  if ($coach_id) {
    $sql_str = "SELECT request.id as request_id, request.userid as userid_request,request.coachid, user.firstname, user.lastname, request.startdate,
  F.id AS facetofaceid,
               F.name,
               request.courseid AS courseid,
               FSD1.data AS LOCATION,
               FSD2.data AS room,
               FSD3.data AS venue,
               FSDE.sessionid as sessionidold,
               FSDE.timestart,
               FSDE.timefinish,
               S.duration,
               S.capacity,
               FFSS.statuscode
        FROM mdl_coachmanagement_request request
        LEFT JOIN mdl_facetoface F
        ON F.course = request.courseid
        LEFT JOIN mdl_facetoface_sessions S
        ON S.facetoface = F.id
        JOIN mdl_user user
        ON request.userid = user.id
        
        LEFT JOIN mdl_facetoface_sessions_dates FSDE ON S.id = FSDE.sessionid
        LEFT JOIN mdl_facetoface_session_data FSD1 ON S.id = FSD1.sessionid
        AND FSD1.fieldid = 1
        LEFT JOIN mdl_facetoface_session_data FSD2 ON S.id = FSD2.sessionid
        AND FSD2.fieldid = 2
        LEFT JOIN mdl_facetoface_session_data FSD3 ON S.id = FSD3.sessionid
        AND FSD3.fieldid = 3
        LEFT JOIN mdl_facetoface_signups FFS ON FFS.sessionid = S.id
        LEFT JOIN mdl_facetoface_signups_status FFSS ON FFS.id = FFSS.signupid
        WHERE request.coachid = $coach_id";
  } else {
    $sql_str = "SELECT request.id as request_id, request.userid as userid_request,request.coachid, user.firstname, user.lastname, request.startdate,
    F.id AS facetofaceid,
                 F.name,
                 request.courseid AS courseid,
                 FSD1.data AS LOCATION,
                 FSD2.data AS room,
                 FSD3.data AS venue,
                 FSDE.sessionid as sessionidold,
                 FSDE.timestart,
                 FSDE.timefinish,
                 S.duration,
                 S.capacity,
                 FFSS.statuscode
          FROM mdl_coachmanagement_request request
          LEFT JOIN mdl_facetoface F
          ON F.course = request.courseid
          LEFT JOIN mdl_facetoface_sessions S
          ON S.facetoface = F.id
          JOIN mdl_user user
          ON request.userid = user.id
          
          LEFT JOIN mdl_facetoface_sessions_dates FSDE ON S.id = FSDE.sessionid
          LEFT JOIN mdl_facetoface_session_data FSD1 ON S.id = FSD1.sessionid
          AND FSD1.fieldid = 1
          LEFT JOIN mdl_facetoface_session_data FSD2 ON S.id = FSD2.sessionid
          AND FSD2.fieldid = 2
          LEFT JOIN mdl_facetoface_session_data FSD3 ON S.id = FSD3.sessionid
          AND FSD3.fieldid = 3
          LEFT JOIN mdl_facetoface_signups FFS ON FFS.sessionid = S.id
          LEFT JOIN mdl_facetoface_signups_status FFSS ON FFS.id = FFSS.signupid";
  }

  $data = array_values($DB->get_records_sql($sql_str));
  foreach ($data as &$item) {
    $date = date('d-m-Y', $item->startdate);
    $time = date('h:i A', $item->startdate);
    $item->date = $date;
    $item->time = $time;
  }
  $isAdmin = is_siteadmin();
  if ($isAdmin) {
    return $data;
  } else {
    return array_values($data);
  }
}

function dffh_detail_requested_training_session($payload)
{
  global $DB;
  $request_id = $payload['request_id'];

  $sql_str = "SELECT request.id as request_id, request.userid as userid_request,request.coachid, user.firstname, user.lastname, request.startdate, coach.firstname as coach_firstname, coach.lastname as coach_lastname,
  F.id AS facetofaceid,
               F.name,
               request.courseid AS courseid,
               FSD1.data AS LOCATION,
               FSD2.data AS room,
               FSD3.data AS venue,
               FSDE.sessionid as sessionidold,
               FSDE.timestart,
               FSDE.timefinish,
               S.duration,
               S.capacity
        FROM mdl_coachmanagement_request request
        LEFT JOIN mdl_facetoface_sessions S
        ON S.facetoface = request.sessionid
        JOIN mdl_user user
        ON request.userid = user.id
        JOIN mdl_user coach
        ON request.coachid = coach.id
        LEFT JOIN mdl_facetoface F
        ON F.id = S.facetoface
        LEFT JOIN mdl_facetoface_sessions_dates FSDE ON S.id = FSDE.sessionid
        LEFT JOIN mdl_facetoface_session_data FSD1 ON S.id = FSD1.sessionid
        AND FSD1.fieldid = 1
        LEFT JOIN mdl_facetoface_session_data FSD2 ON S.id = FSD2.sessionid
        AND FSD2.fieldid = 2
        LEFT JOIN mdl_facetoface_session_data FSD3 ON S.id = FSD3.sessionid
        AND FSD3.fieldid = 3
        WHERE request.id = $request_id";
  $data = array_values($DB->get_records_sql($sql_str));
  foreach ($data as &$item) {
    $date = date('Y-m-d', $item->startdate);
    $time = date('h:i A', $item->startdate);
    $item->date = $date;
    $item->time = $time;
    $item->isAdmin = is_siteadmin();
  }
  return $data;
}
function dffh_get_session_course($payload)
{
  global $DB;
  $request_id = $payload['request_id'];
  $course_id = $payload['course_id'];

  $sql_str = "select f.id as facetoface_id, f.name as facetoface_name, fs.id as session_id, GROUP_CONCAT(fsf.name, ' : ', fsd.data) as data_field
  from mdl_facetoface f
  JOIN mdl_facetoface_sessions fs
  on f.id = fs.facetoface
  JOIN mdl_facetoface_session_data fsd
  ON fs.id = fsd.sessionid
  JOIN mdl_facetoface_session_field fsf
  ON fsd.fieldid = fsf.id
  where f.course = $course_id
  GROUP BY fs.id";
  $data = array_values($DB->get_records_sql($sql_str));

  return $data;
}

function dffh_get_available_fields($payload){
  global $DB,$USER, $CFG;


  $all_fields = $DB->get_records_sql('SELECT * FROM {formbuilder_form_info_field} WHERE formid = ? AND id != ? AND datatype != "textarea" AND datatype != "text" ',array($payload['formid'],$payload['fieldid']));
  
  $arr_fields = [] ;
  $optiondata = new stdClass;
  $optiondata->value = '0';
  $optiondata->label = 'None';
  $arr_fields [] = $optiondata;
  foreach ($all_fields as $field) {
    $options = explode(',',$field->defaultdata);
   
      
    if(count($options) > 1){
      foreach($options as $option){

        $optiondata = new stdClass;
        $optiondata->value = $field->id.'-'.$option;
        $optiondata->label = $field->shortname.': '.$option;
        
        $arr_fields [] = $optiondata;
      }
    }else{
      $optiondata = new stdClass;
    $optiondata->value = $field->id.'-'.$field->defaultdata;
    $optiondata->label = $field->shortname.': '.$field->defaultdata;
    $arr_fields [] = $optiondata;
    }
    
  }

 
return $arr_fields;
}

function dffh_get_available_users($payload){
 
    global $DB,$USER, $CFG;
   
    require_once($CFG->dirroot.'/blocks/reporting/report/lib.php');
   
    $useragency = $DB->get_record('coachmanagement_request',array('id'=>$payload['request_id']));
    if($useragency->userid == $useragency->coachid){
      $hierarchy_coaches = $DB->get_records('hierarchy_coach', array('user_id' => $useragency->userid));
      $arr_userss = array();
      foreach($hierarchy_coaches as $coach){
        $coachnode = get_coach_node($coach->node_id);
        $node_user_record = $DB->get_record('hierarchy_user', array('user_id' => $USER->id));
         if($node_user_record==null){
             throw new Exception('The user must be in the hierarchy');
         };
         $node_user_record = $coachnode;
         $arrnodes = explode(',', $node_user_record);
       
         $is_admin =is_siteadmin($USER->id);
       
         // Check if the user belong to selected node list, then the current user has to be added into the list
         $currentUserNodeId = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$USER->id));
       
             // Add all users in selected nodes into the list
             $sql_user = "SELECT user_id from mdl_hierarchy_user where node_id in($node_user_record) group by user_id";
             $rs_user = $DB->get_records_sql($sql_user);
             if($rs_user){
                 foreach ($rs_user as $row_user) {
                     $arr_users [] = $row_user->user_id;
                 }
             }
         
         // Get all users under the selected nodes
         $nodes_queue = array();
         foreach ($arrnodes as $node_id) {
             // Find all children nodes of current node
             //if(!in_array($node_id, $nodes_queue)) $nodes_queue [] = $node_id;
             $all_children_node_ids = array();
             $children_node_ids_queue = array();
             $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $node_id));
             if ($lower_level_children_nodes_records != false) {
               foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
                 $all_children_node_ids[] = $lower_level_children_nodes_record->id;
                 $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
               }
             }
           
             while (count($children_node_ids_queue) != 0) {
               foreach ($children_node_ids_queue as $index => $child_node_id) {
                 $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $child_node_id));
                 if ($lower_level_children_nodes_records != false) {
                   foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
                     $all_children_node_ids[] = $lower_level_children_nodes_record->id;
                     $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
                   }
                 }
           
                 /* remove node from queue */
                 unset($children_node_ids_queue[$index]);
               }
             }
             
             if (!empty($all_children_node_ids)) {
                 foreach($all_children_node_ids as $child_node_id){
                     if(!in_array($child_node_id, $nodes_queue)) $nodes_queue [] = $child_node_id;
                 }
             }
             // Include the current nodes as well => This will other user can see each others in the same node
             // $nodes_queue [] = $node_id; // => Should be disabled
         }
         // Get all users in these nodes: nodes_queue.
         $all_users = array();
         if(!empty($nodes_queue)){
             $list = implode(',',$nodes_queue);
             $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
             $all_users = $DB->get_records_sql($sql,array());
         }
         foreach ($all_users as $r) {
           $userdata = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ? ',array($r->user_id));
           $user = new stdClass;
           $user->value = $userdata->id;
           $user->label = $userdata->firstname.' '.$userdata->lastname;
             $arr_userss [] = $user;
         }
      }
    }else{
      $coach_hierarchy = $DB->get_record('hierarchy_coach', array('user_id' => $useragency->userid));
      $coach_node = $DB->get_record('hierarchy_user', array('user_id' => $useragency->userid));
      
      $coachnode = get_coach_node($coach_node->node_id);
     $node_user_record = $DB->get_record('hierarchy_user', array('user_id' => $USER->id));
      if($node_user_record==null){
          throw new Exception('The user must be in the hierarchy');
      };
      $node_user_record = $coachnode;
      $arrnodes = explode(',', $node_user_record);
      $arr_userss = array();
      $is_admin =is_siteadmin($USER->id);
    
      // Check if the user belong to selected node list, then the current user has to be added into the list
      $currentUserNodeId = $DB->get_field('hierarchy_user','node_id',array('user_id'=>$USER->id));
    
          // Add all users in selected nodes into the list
          $sql_user = "SELECT user_id from mdl_hierarchy_user where node_id in($node_user_record) group by user_id";
          $rs_user = $DB->get_records_sql($sql_user);
          if($rs_user){
              foreach ($rs_user as $row_user) {
                  $arr_users [] = $row_user->user_id;
              }
          }
      
      // Get all users under the selected nodes
      $nodes_queue = array();
      foreach ($arrnodes as $node_id) {
          // Find all children nodes of current node
          //if(!in_array($node_id, $nodes_queue)) $nodes_queue [] = $node_id;
          $all_children_node_ids = array();
          $children_node_ids_queue = array();
          $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $node_id));
          if ($lower_level_children_nodes_records != false) {
            foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
              $all_children_node_ids[] = $lower_level_children_nodes_record->id;
              $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
            }
          }
        
          while (count($children_node_ids_queue) != 0) {
            foreach ($children_node_ids_queue as $index => $child_node_id) {
              $lower_level_children_nodes_records = $DB->get_records('hierarchy_node', array('parent_node_id' => $child_node_id));
              if ($lower_level_children_nodes_records != false) {
                foreach ($lower_level_children_nodes_records as $lower_level_children_nodes_record) {
                  $all_children_node_ids[] = $lower_level_children_nodes_record->id;
                  $children_node_ids_queue[] = $lower_level_children_nodes_record->id;
                }
              }
        
              /* remove node from queue */
              unset($children_node_ids_queue[$index]);
            }
          }
          
          if (!empty($all_children_node_ids)) {
              foreach($all_children_node_ids as $child_node_id){
                  if(!in_array($child_node_id, $nodes_queue)) $nodes_queue [] = $child_node_id;
              }
          }
          // Include the current nodes as well => This will other user can see each others in the same node
          // $nodes_queue [] = $node_id; // => Should be disabled
      }
      // Get all users in these nodes: nodes_queue.
      $all_users = array();
      if(!empty($nodes_queue)){
          $list = implode(',',$nodes_queue);
          $sql = "select user_id from mdl_hierarchy_user where node_id in($list)";
          $all_users = $DB->get_records_sql($sql,array());
      }
      foreach ($all_users as $r) {
        $userdata = $DB->get_record_sql('SELECT * FROM {user} WHERE id = ? ',array($r->user_id));
        $user = new stdClass;
        $user->value = $userdata->id;
        $user->label = $userdata->firstname.' '.$userdata->lastname;
          $arr_userss [] = $user;
      }
    }
     
   return $arr_userss;
}

function dffh_create_session($payload){
  global $DB, $CFG;
  $course_id = $payload['course_id'];
  die();
  $sql_str = "select f.id as facetoface_id, f.name as facetoface_name, fs.id as session_id, GROUP_CONCAT(fsf.name, ' : ', fsd.data) as data_field
  from mdl_facetoface f
  JOIN mdl_facetoface_sessions fs
  on f.id = fs.facetoface
  JOIN mdl_facetoface_session_data fsd
  ON fs.id = fsd.sessionid
  JOIN mdl_facetoface_session_field fsf
  ON fsd.fieldid = fsf.id
  where f.course = $course_id
  GROUP BY fs.id";
  $data = array_values($DB->get_records_sql($sql_str));
  
return $data;
}

function dffh_remove_request($payload)
{
  global $DB;
  $request_id = $payload['request_id'];
  $DB->delete_records('coachmanagement_request', array('id' => $request_id));
  return true;
}
