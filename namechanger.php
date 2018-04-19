<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is a one page wonder name changing page
 * Created by Justin Hunt for an earlier version of this course
 * Modified by Richard Jones
 *
 * @package    mod_multipage
 * @copyright  2015 Flash Gordon http://www.flashgordon.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir . '/formslib.php');


/**
 * Define a form that acts on just one field, "name", in an existing table "mdl_multipage"
 */
class multipage_namechanger_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('multipagename', 'mod_multipage'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'multipagename', 'mod_multipage');
        
        $mform->addElement('hidden','courseid');
        $mform->setType('courseid',PARAM_INT);
        $mform->addElement('hidden','id');
        $mform->setType('id',PARAM_INT);
        $this->add_action_buttons();
        
    }
}

//fetch URL parameters
$courseid = required_param('courseid', PARAM_INT);   // course
$action = optional_param('action','list',PARAM_TEXT);
$actionitem = optional_param('actionitem',0,PARAM_INT);

//Set course related variables
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_course_login($course);
$coursecontext = context_course::instance($course->id);

//set up the page
$PAGE->set_url('/mod/multipage/namechanger.php', array('courseid' => $courseid));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');

//=========================================
//Form processing begins here
//=========================================

//get the name_changer form
$mform = new multipage_namechanger_form();

//if the cancel button was pressed, we are out of here
if ($mform->is_cancelled()) {
    redirect($PAGE->url,get_string('cancelled'),2);
    exit;
}

//if we have data, then our job here is to save it and return
if ($data = $mform->get_data()) {
        
        // $DB->update_record('multipage',$data);
        
        // Replace update with call to ad_hoc task
        $updatetask = new \mod_multipage\task\multipage_adhoc();
        $updatetask->set_custom_data($data);
        \core\task\manager::queue_adhoc_task($updatetask);

        redirect($PAGE->url,get_string('updated','core',$data->name),2);
}

//if the action is specified as "edit" then we show the edit form
if($action =="edit"){
    //create some data for our form
    $data = new stdClass();
    $data->courseid=$courseid;
    $multipage = $DB->get_record('multipage',array('id'=>$actionitem));
    if(!$multipage){redirect($PAGE->url,'nodata',2);}
    $data->id=$multipage->id;
    $data->name=$multipage->name;
    
    //set data to form
    $mform->set_data($data);
    
    //output page + form
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('modulenameplural', 'multipage'), 2);
    $mform->display();
    echo $OUTPUT->footer();
    return;
}
//=========================================
//Form processing ends here
//=========================================


//=========================================
//List of multipage activities begins here
//=========================================
echo $OUTPUT->header();

if (!$multipages = get_all_instances_in_course('multipage', $course)) {
    notice(get_string('nomultipages', 'multipage'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
if ($course->format == 'weeks') {
    $table->head  = array(get_string('week'), get_string('name'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array(get_string('topic'), get_string('name'));
    $table->align = array('center', 'left', 'left', 'left');
} else {
    $table->head  = array(get_string('name'));
    $table->align = array('left', 'left', 'left');
}



foreach ($multipages as $multipage) {
    if (!$multipage->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/multipage/view.php', array('id' => $multipage->coursemodule)),
            format_string($multipage->name, true),
            array('class' => 'dimmed'));            
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/multipage/view.php', array('id' => $multipage->coursemodule)),
            format_string($multipage->name, true));
    }
    
    $editurl = $PAGE->url;
    $editurl->params(array('action'=>'edit','actionitem'=>$multipage->id));
    $editbutton = $OUTPUT->single_button($editurl,get_string('edit'));
    
    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($multipage->section, $link, $editbutton);
    } else {
        $table->data[] = array($link, $editbutton);
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', 'multipage'), 2);
echo html_writer::table($table);
echo $OUTPUT->footer();