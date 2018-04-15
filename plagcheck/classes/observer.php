<?php

require_once($CFG->dirroot.'/plagiarism/plagcheck/lib.php');

class plagiarism_plagcheck_observer {

	public static function assignsubmission_file_uploaded(\assignsubmission_file\event\assessable_uploaded $event) {
		$eventdata = $event->get_data();
		$eventdata['eventtype'] = 'file_uploaded';
		$eventdata['other']['modulename'] = 'assign';

		$plugin = new plagiarism_plugin_plagcheck();
		$plugin->event_handler($eventdata);
	}

	public static function assignsubmission_onlinetext_uploaded(\assignsubmission_onlinetext\event\assessable_uploaded $event) {
		$eventdata = $event->get_data();
		$eventdata['eventtype'] = 'content_uploaded';
		$eventdata['other']['modulename'] = 'assign';

		$plugin = new plagiarism_plugin_plagcheck();
		$plugin->event_handler($eventdata);
	}

	public static function assignsubmission_submitted(\mod_assign\event\assessable_submitted $event) {
		$eventdata = $event->get_data();
		$eventdata['eventtype'] = 'assessable_submitted';
		$eventdata['other']['modulename'] = 'assign';

		$plugin = new plagiarism_plugin_plagcheck();
		$plugin->event_handler($eventdata);
	}
}
