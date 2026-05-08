<?php

namespace tool_enrolmentemail\forms;

require_once(__DIR__ . '/../../locallib.php');
require_once(__DIR__ . '/../../constants.php');
require_once($CFG->libdir . '/formslib.php');

use moodleform;

class settings_form extends moodleform {

	private function get_editor_options() {
		return array(
			'maxfiles' => -1,
			'maxbytes' => 0,
			'noclean' => true,
			'trusttext' => false,
			'subdirs' => false,
			'context' => \context_system::instance(),
			'return_types' => 7,
		);
	}

	public function definition() {
		$mform = $this->_form;

		$mform->addElement('header', 'hdr01', get_string('basicconfiguration', ENROLMENTEMAIL_PLUGINNAME));
		$mform->setExpanded('hdr01');

		// - radio button (on/off)
// 		$toggleswitch = <<< EOT
// <div class="custom-control custom-switch">
//   <input type="checkbox" class="custom-control-input" id="initnotyconf">
//   <label class="custom-control-label" for="initnotyconf"></label>
// </div>
// EOT;

		// Initial Course Notification
		$elementname = 'initialcoursenotification';
		$groupname = 'initialnotificationgroup';
		$radioarray = array();
		$radioarray[] = $mform->createElement('radio', $elementname, '', get_string('on', ENROLMENTEMAIL_PLUGINNAME), 1);
		$radioarray[] = $mform->createElement('radio', $elementname, '', get_string('off', ENROLMENTEMAIL_PLUGINNAME), 0);
		$mform->addGroup($radioarray, $groupname, get_string('initialcoursenotification', ENROLMENTEMAIL_PLUGINNAME), array(' '), false);
		$mform->addHelpButton($groupname, 'initialcoursenotification', ENROLMENTEMAIL_PLUGINNAME);

		// Max attempts allowed
		$elementname = 'maxattemptsallowed';
		$elementlabel = get_string($elementname, ENROLMENTEMAIL_PLUGINNAME);
		$mform->addElement('text', $elementname, $elementlabel, array('size' => 5, 'maxlength' => 3));
		$mform->setType($elementname, PARAM_INT);
		$mform->addHelpButton($elementname, $elementname, ENROLMENTEMAIL_PLUGINNAME);
		$mform->addRule($elementname, 'Please specify a number', 'numeric', null, 'client');

		// Table course list (use dataTable)
		// - course name (url to open course page)
		// - course id number
		// - on/off switch
		$mform->addElement('header', 'hdr02', get_string('courselist', ENROLMENTEMAIL_PLUGINNAME));
		$mform->setExpanded('hdr02');
		
 		$lbl_course = get_string('course');
		$lbl_idnumber = get_string('idnumber');	
		$lbl_enable = get_string('enable', ENROLMENTEMAIL_PLUGINNAME);
		$tbody = '<tbody>';
		$courses = $this->get_existing_courselist();
		if ($courses) {
			foreach ($courses as $courseid => $course) {
				$courselink = \html_writer::link(new \moodle_url('/course/view.php', array('id' => $courseid)), $course->fullname, array('target' => '_blank'));
				$idnumber = $course->idnumber;
				$notificationid = $course->notificationid;
				$checked = '';
				if ($course->enabled == 1) {
					$checked = 'checked';
				}
				$toggleswitch = <<< EOT
	<div class="custom-control custom-switch">
		<input type="checkbox" class="custom-control-input" id="coursenotification[$notificationid]" name="coursenotification[$notificationid]" $checked>
		<label class="custom-control-label" for="coursenotification[$notificationid]"></label>
	</div>
EOT;
				$tbody .= '<tr>';
				$tbody .= '  <td>' . $courselink . '</td>';
				$tbody .= '  <td>' . $idnumber . '</td>';
				$tbody .= '  <td>' . $toggleswitch . '</td>';
				$tbody .= '</tr>';
			}
		}
		$tbody .= '</tbody>';
		$table = <<< EOT
		<table id='courselist'>
			<thead>
				<tr>
					<th>$lbl_course</th>
					<th>$lbl_idnumber</th>
					<th>$lbl_enable</th>
				</tr>
			</thead>
			$tbody
		</table>
		<br/>
EOT;
		$mform->addElement('html', $table);

		// Email template
		$mform->addElement('header', 'hdr03', get_string('emailtemplate', ENROLMENTEMAIL_PLUGINNAME));
		$mform->setExpanded('hdr03');

		// - Signature Line
		$elementname = 'signaturename';
		$elementlabel = get_string($elementname, ENROLMENTEMAIL_PLUGINNAME);
		$mform->addElement('text', $elementname, $elementlabel, array('size' => 80));
		$mform->setType($elementname, PARAM_RAW);
		$mform->addHelpButton($elementname, $elementname, ENROLMENTEMAIL_PLUGINNAME);

		// - Contact Line
		$elementname = 'contact';
		$elementlabel = get_string($elementname, ENROLMENTEMAIL_PLUGINNAME);
		$mform->addElement('text', $elementname, $elementlabel, array('size' => 80));
		$mform->setType($elementname, PARAM_RAW);
		$mform->addHelpButton($elementname, $elementname, ENROLMENTEMAIL_PLUGINNAME);

		// - Subject Line
		$elementname = 'subjectline';
		$elementlabel = get_string($elementname, ENROLMENTEMAIL_PLUGINNAME);
		$mform->addElement('text', $elementname, $elementlabel, array('size' => 80));
		$mform->setType($elementname, PARAM_RAW);
		$mform->addHelpButton($elementname, $elementname, ENROLMENTEMAIL_PLUGINNAME);

		// - Content (editor?)
		$elementname = 'content';
		$elementlabel = get_string($elementname, ENROLMENTEMAIL_PLUGINNAME);
		//$mform->addElement('editor', $elementname, $elementlabel, null, $this->get_editor_options());
		$mform->addElement('textarea', $elementname, $elementlabel, array('rows' => 15, 'cols' => 80));
		$mform->setType($elementname, PARAM_RAW);
		$mform->addHelpButton($elementname, $elementname, ENROLMENTEMAIL_PLUGINNAME);

		$elementname = 'defaulttemplate';
		$elementlabel = get_string($elementname, ENROLMENTEMAIL_PLUGINNAME);
		// $mform->addElement('static', $elementname, $elementlabel, get_string('defaulttemplatecontent', ENROLMENTEMAIL_PLUGINNAME));
		$mform->addElement('textarea', $elementname, $elementlabel, array('rows' => 15, 'cols' => 80));
		$mform->setDefault($elementname, get_string('defaulttemplatecontent', ENROLMENTEMAIL_PLUGINNAME));
		$mform->addHelpButton($elementname, $elementname, ENROLMENTEMAIL_PLUGINNAME);		
		$mform->freeze(array($elementname));

		// submit buttons
		// - save and cancel
		$groupname = 'submitbuttons';
		$buttonarray = array(
			$mform->createElement('submit', 'save', 'Save'),
			$mform->createElement('cancel')
		);
		$mform->addGroup($buttonarray, $groupname, '', array(' '), false);	

		$mform->closeHeaderBefore($groupname);
	}

	/**
	 * Get current existing enrolment email course list
	 */
	public function get_existing_courselist() {
		global $DB;
		$sql = <<< EOT
	SELECT 
		c.id,
		c.idnumber,
		c.fullname,
		ec.id as notificationid,
		ec.enabled
	FROM
		mdl_enrolmentemail_courses ec
		JOIN mdl_course c ON ec.courseid = c.id
EOT;
		return $DB->get_records_sql($sql);
	}

	public function validation($data, $files) {
		return parent::validation($data, $files);
	}

	public function get_data() {
		$data = parent::get_data();
		return $data;
	}

	public function set_data($data) {
		$defaults = clone($data);

		return parent::set_data($defaults);
	}

	public function save($data) {
		if (isset($data->initialcoursenotification)) {
			set_local_config(ENROLMENTEMAIL_INITIALCOURSENOTIFICATION, $data->initialcoursenotification);
		}
		if (isset($data->maxattemptsallowed)) {
			set_local_config(ENROLMENTEMAIL_MAXATTEMPTSALLOWED, $data->maxattemptsallowed);
		}
		if (isset($data->subjectline)) {
			set_local_config(ENROLMENTEMAIL_EMAILSUBJECTLINE, trim($data->subjectline));
		}
		if (isset($data->signaturename)) {
			set_local_config(ENROLMENTEMAIL_EMAILSIGNATURENAME, trim($data->signaturename));
		}
		if (isset($data->contact)) {
			set_local_config(ENROLMENTEMAIL_EMAILCONTACT, trim($data->contact));
		}
		if (isset($data->content)) {
			set_local_config(ENROLMENTEMAIL_EMAILCONTENT, trim($data->content));
		}

		$enabled_notificationids = $data->coursenotification;
		global $DB;
		$records = $DB->get_records('enrolmentemail_courses');
		if ($records) {
			$existing_notificationids = array_keys($records);
		}
		$disabled_notificationids = array_diff($existing_notificationids, $enabled_notificationids);
		if (!empty($disabled_notificationids)) {
			global $DB;
			$transaction = $DB->start_delegated_transaction();
			foreach ($records as $id => $record) {
				if (in_array($id, $enabled_notificationids) && $record->enabled == 0) {
					$record->enabled = 1;
					$DB->update_record('enrolmentemail_courses', $record);
				}
				else if (in_array($id, $disabled_notificationids) && $record->enabled == 1) {
					$record->enabled = 0;
					$DB->update_record('enrolmentemail_courses', $record);
				}
			}
			$transaction->allow_commit();
		}

	}

//  private function get_fileid($data) {
//    
//    // Check if thumbnail image is uploaded
//    $fs = get_file_storage();
//    $context = \context_user::instance($data->userid);
//    $component = 'block_intranetmanagement';
//    $filearea = 'news_thumbnail';
//    $uploadedfile = null; 
//    if ($files = $fs->get_area_files($context->id, 'user', 'draft', $data->thumbnail, 'id DESC', false)) {
//      $uploadedfile = reset($files);
//    }
//    
//    // if uploaded a new thumbnail, replace existing thumbnail with new one
//    // return the new file record id
//    if ($uploadedfile) {
//      if (isset($data->id)) {
//        $this->delete_existing_thumbnail($data->id);
//      }
//      return $this->create_filerecord(
//        $context->id, 
//        $component, 
//        $filearea, 
//        $data->thumbnail, 
//        $uploadedfile, 
//        $data->userid
//      );      
//    }
//
//    return $data->thumbnail;
//  }
//  private function create_filerecord($contextid, $component, $filearea, $itemid, $file, $userid) {
//    $fs = get_file_storage();
//    $file_record = array(
//      'contextid' => $contextid,
//      'component' => $component,
//      'filearea' => $filearea,
//      'itemid' => $itemid,
//      'filepath' => '/',
//      'filename' => $file->get_filename(),
//      'userid' => $userid
//    );
//    if ($instance = $fs->create_file_from_storedfile($file_record, $file)) {
//      return $instance->get_id();
//    }
//    return null;
//  }
//  protected function delete_file($fileid) {
//    if (isset($fileid) && !empty($fileid)) {
//      $fs = get_file_storage();
//      if ($oldfile = $fs->get_file_by_id($fileid)) {
//        $oldfile->delete();
//      }
//    }
//  }
//  private function content_conversion($data) {
//    $field = 'content';
//    $options = $this->get_editor_options();
//    $context = \context_system::instance();
//    $component = 'block_intranetmanagement';
//    $filearea = 'content';
//    $itemid = $data->content['itemid'];
//    $data = file_postupdate_standard_editor($data, $field, $options, $context, $component, $filearea, $itemid);
//    return file_rewrite_pluginfile_urls($data->content, 'pluginfile.php', $context->id, $component, $filearea, $itemid);
//  }
}
