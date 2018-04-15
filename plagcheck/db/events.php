<?php

$observers = array(

    array(
        'eventname' => '\assignsubmission_file\event\assessable_uploaded',
        'callback' => 'plagiarism_plagcheck_observer::assignsubmission_file_uploaded'
    ),
    array(
        'eventname' => '\assignsubmission_onlinetext\event\assessable_uploaded',
        'callback' => 'plagiarism_plagcheck_observer::assignsubmission_onlinetext_uploaded'
    ),
    array(
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback' => 'plagiarism_plagcheck_observer::assignsubmission_submitted'
    ),
    array(
        'eventname' => '\mod_assign\event\grading_form_viewed',
        'callback' => 'plagiarism_plagcheck_observer::show_similarity'
    )
    /*
    array(
        'eventname' => '\mod_coursework\event\assessable_uploaded',
        'callback' => 'plagiarism_plagcheck_observer::assessable_uploaded'
    )*/
);
