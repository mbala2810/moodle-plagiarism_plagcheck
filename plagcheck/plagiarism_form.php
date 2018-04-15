<?php

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

