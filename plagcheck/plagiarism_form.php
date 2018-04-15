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

require_once($CFG->dirroot.'/lib/formslib.php');

class plagiarism_setup_form extends moodleform {

/// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        $choices = array('No','Yes');
        $mform->addElement('html', get_string('plagcheckexplain', 'plagiarism_plagcheck'));
        $mform->addElement('checkbox', 'plagcheck_use', get_string('useplagcheck', 'plagiarism_plagcheck'));

        $mform->addElement('textarea', 'plagcheck_student_disclosure', get_string('studentdisclosure','plagiarism_plagcheck'),'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('plagcheck_student_disclosure', 'studentdisclosure', 'plagiarism_plagcheck');
        $mform->setDefault('plagcheck_student_disclosure', get_string('studentdisclosuredefault','plagiarism_plagcheck'));

        $this->add_action_buttons(true);
    }
}
