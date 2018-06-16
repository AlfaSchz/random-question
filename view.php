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
 * Prints a particular instance of randomquestion
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_randomquestion
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace randomquestion with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$r  = optional_param('r', 0, PARAM_INT);  // ... randomquestion instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('randomquestion', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $randomquestion  = $DB->get_record('randomquestion', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($r) {
    $randomquestion  = $DB->get_record('randomquestion', array('id' => $r), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $randomquestion->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('randomquestion', $randomquestion->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_randomquestion\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $randomquestion);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/randomquestion/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($randomquestion->name));
$PAGE->set_heading(format_string($course->fullname));

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('randomquestion-'.$somevar);
 */

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($randomquestion->intro) {
    echo '<h3>'.$OUTPUT->box(format_module_intro('randomquestion', $randomquestion, $cm->id), 'generalbox mod_introbox', 'randomquestionintro').'</h3>';
}

// Replace the following lines with you own code.

$admins = get_admins();
$isadmin = false;
foreach($admins as $admin) {
    if ($USER->id == $admin->id) {
        $isadmin = true;
        break;
    }
}

if (!$isadmin) {
    
    global $USER;

    $context =  context_module::instance($cm->id);

    $roles = get_user_roles($context, $USER->id, true);

    $role = end($roles)->shortname;

    $themes = $DB->get_record('randomquestion', array('course'=>$course->id));
    $mandatory = explode(',', $themes->randomquestionmandatory);
    
    if($role == 'student'){

        $usertheme = $DB->get_record('randomquestion_user_answer', array('courseid'=>$course->id, 'userid'=>$USER->id));

        if(!$usertheme){

            
            $mandatoryalreadyin = $DB->get_records('randomquestion_user_answer', array('courseid'=>$course->id));
            $mandatoryalreadyinlength = count($mandatoryalreadyin);
            
            $mandatoryalreadyinarray = array();
            
            foreach ($mandatoryalreadyin as $imandatoryalreadyin){
                if(in_array($imandatoryalreadyin->themeassigned, $mandatory)){
                    array_push($mandatoryalreadyinarray,$imandatoryalreadyin->themeassigned);
                }
            }            

            if($mandatoryalreadyinlength >= count($mandatory)){
                
                $optional = explode(',', $themes->randomquestionoptional);

                shuffle($optional);

                $record = new stdClass();
                $record->userid = $USER->id;
                $record->courseid = $course->id;
                $record->themeassigned = $optional[0];
                $record->answer = null;
                $DB->insert_record('randomquestion_user_answer', $record, false);
                
                
            }else{

                shuffle($mandatory);

                foreach ($mandatory as $imandatory) {
                    if (!in_array($imandatory,$mandatoryalreadyinarray)){
                        
                        $record = new stdClass();
                        $record->userid = $USER->id;
                        $record->courseid = $course->id;
                        $record->themeassigned = $imandatory;
                        $record->answer = null;
                        $DB->insert_record('randomquestion_user_answer', $record, false);
                        
                        break;
                    }
                } 
            
            }
        
        }
          
        $usertheme = $DB->get_record('randomquestion_user_answer', array('courseid'=>$course->id, 'userid'=>$USER->id));

        echo '<h3>' . $themes->randomquestionstatement . ' <strong>'. $usertheme->themeassigned . '</strong></h3>';
        
        $useranswer = $usertheme->answer;
            
        if($useranswer == null or $useranswer == ''){
            
            //include locallib.php
            require_once('locallib.php');
             
            //Instantiate randomquestion_user_form 
            $posturl = new moodle_url('/mod/randomquestion/view.php', array('id' => $cm->id));
            $mform = new randomquestion_user_form((string)$posturl);
                
            //Form processing and displaying is done here
            if ($mform->is_cancelled()) {
                //Handle form cancel operation, if cancel button is present on form
                    
                //redirect($posturl);
            } else if ($fromform = $mform->get_data()) {
                //In this case you process validated data. $mform->get_data() returns data posted in form.
                $answertext = $fromform->answer['text'];
                  
                $DB->set_field('randomquestion_user_answer', 'answer', $answertext, array('courseid'=>$course->id, 'userid'=>$USER->id));
                                    
                //redirect($posturl);
                echo '<div class="box generalbox p-y-1">' . get_string('youranswerwas', 'randomquestion') . '</div>';
                echo '<div class="alert alert-info alert-block fade in">'.$answertext.'</div>'; 
                                
            } else {
                // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
                // or on the first display of the form.

                //displays the form
                $mform->display();
            }
                
        }else{
            
            echo '<div class="box generalbox p-y-1">' . get_string('youranswerwas', 'randomquestion') . '</div>';
            echo '<div class="alert alert-info alert-block fade in">'.$useranswer.'</div>';            
            
        }
        
        
    }elseif($role == 'editingteacher'){
        
        $useranswers = $DB->get_records('randomquestion_user_answer', array('courseid'=>$course->id));
        
        
        echo '<table class="generaltable"><thead><tr><th class="header c0" style="" scope="col"><h4>'.get_string('randomquestioncomplex', 'randomquestion').'</h4></th><th class="header c1 lastcol" style="" scope="col"></th></tr></thead><tbody>';
        foreach ($useranswers as $useranswer) {
            if (in_array($useranswer->themeassigned,$mandatory)){
                echo '<tr class=""><td class="cell c0" style=""><strong>'.$useranswer->themeassigned.'</strong></td>';
                if($useranswer->answer == null or $useranswer->answer == ''){
                    echo '<td class="cell c1 lastcol" style="">' . get_string('randomquestionnotresponded', 'randomquestion').'</td></tr>';
                }else{
                    echo '<td class="cell c1 lastcol" style="">'.$useranswer->answer.'</td></tr>';
                }
            
            }
        }
        echo '</tbody></table><br><br>';
        
        echo '<table class="generaltable"><thead><tr><th class="header c0" style="" scope="col"><h4>'.get_string('randomquestionsimple', 'randomquestion').'</h4></th><th class="header c1 lastcol" style="" scope="col"></th></tr></thead><tbody>';
        foreach ($useranswers as $useranswer) {
            if (!in_array($useranswer->themeassigned,$mandatory)){
                echo '<tr class=""><td class="cell c0" style=""><strong>'.$useranswer->themeassigned.'</strong></td>';
                if($useranswer->answer == null or $useranswer->answer == ''){
                    echo '<td class="cell c1 lastcol" style="">'. get_string('randomquestionnotresponded', 'randomquestion').'</td></tr>';
                }else{
                    echo '<td class="cell c1 lastcol" style="">'.$useranswer->answer.'</td></tr>';
                }
            
            }
        }
        echo '</tbody></table>';

    }
}

            


// Finish the page.
echo $OUTPUT->footer();
