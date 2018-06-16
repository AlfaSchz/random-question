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
 * Internal library of functions for module randomquestion
 *
 * All the randomquestion specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_randomquestion
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 *function randomquestion_do_something_useful(array $things) {
 *    return new stdClass();
 *}
 */

 //moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class randomquestion_user_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('editor', 'answer', get_string('writehere', 'randomquestion')/*get_string('labeltext', 'langfile')*/);
        $mform->setType('answer', PARAM_RAW);
        
        $this->add_action_buttons();

    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
    
    
}