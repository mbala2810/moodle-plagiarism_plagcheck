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

require_once($CFG->dirroot.'/plagiarism/plagcheck/vendor/autoload.php');
require_once($CFG->dirroot.'/plagiarism/lib.php');
use Tga\SimHash\Fingerprint;
global $plagcheckacceptedfiles;

//Given the content, generate the simhash and store it in table plagiarism_plagcheck
function generateSimhash($fileContent) {
	$simhash = new \Tga\SimHash\SimHash();
	$extractor = new \Tga\SimHash\Extractor\SimpleTextExtractor();
	$comparator = new Tga\SimHash\Comparator\GaussianComparator(3);
	$fileData = $simhash->hash($extractor->extract($fileContent), \Tga\SimHash\SimHash::SIMHASH_64);
	return $fileData->getDecimal();
}

/*After simhash has been generated, calculated simliarity scores for each file by
 *comparing them with every other file of same course and assignment ids
 */
function getSimilarity($records) {
	global $DB;
	$simhash = new \Tga\SimHash\SimHash();
	$extractor = new \Tga\SimHash\Extractor\SimpleTextExtractor();
	$comparator = new Tga\SimHash\Comparator\GaussianComparator(3);
	$i = 0;
	foreach($records as $record) {
		$max = 0;
		$index = 0;
		$hash1 = new Fingerprint(sizeof($record->simhash),$record->simhash);
		//If the submitter has deleted his existing submission and added a new submission, so we have to delete the existing one.
		$checkrecord = $DB->get_record('files', array('pathnamehash' => $record->identifier, 'component' => 'assignsubmission_file'));
		if(!$checkrecord) {
			$DB->delete_records('plagiarism_plagcheck', array('identifier' => $record->identifier));
			continue;
		}
		foreach($records as $recordinner){
			if($recordinner->id != $record->id && $recordinner->userid != $record->userid){
				$hash2 = new Fingerprint(sizeof($recordinner->simhash),$recordinner->simhash);
				$plag = $comparator->compare($hash1,$hash2);
				if($plag > $max){
					$max = $plag;
					$index = $recordinner->identifier;
				}
			}
		}
		$record->similarityscore = $max;
		$record->copyfilename = $index;
		insertIntoTable($record);
		$i++;
	}
	return;
}

//Given a record, enter it into the plagiarism_plagcheck table
function insertIntoTable($record) {
	global $DB, $CFG;
	$plagiarismfile = new stdClass();
	$plagiarismfile->id = $record->id;
	$plagiarismfile->cm = $record->cm;
	$plagiarismfile->userid = $record->userid;
	$plagiarismfile->identifier = $record->identifier;
	$plagiarismfile->itemid = $record->itemid;
	$plagiarismfile->statuscode = "graded";
	$plagiarismfile->similarityscore = $record->similarityscore;
	$plagiarismfile->simhash = $record->simhash;
	$plagiarismfile->submissiontype = 'file';
	$plagiarismfile->assignmentid = $record->assignmentid;
	$plagiarismfile->courseid = $record->courseid;
	$plagiarismfile->copyfilename = $record->copyfilename;
	$DB->update_record('plagiarism_plagcheck', $plagiarismfile, false);
}
class plagiarism_plugin_plagcheck extends plagiarism_plugin {
	public function update_status($course, $cm) {
		return '';
	}
	public function get_tutor_capability() {
		return 'mod/assign:grade';
	}
	public function get_links($linkarray) {
		global $DB;
        static $cm;
        if (empty($cm)) {
            $cm = get_coursemodule_from_id('', $linkarray["cmid"]);
        }
		static $context;
        if (empty($context)) {
            $context = context_course::instance($cm->course);
        }
		if(!has_capability($this->get_tutor_capability(), $context)) {
			return;
		}
		$file = $linkarray["file"];
		$submittinguser = $linkarray['userid'];
        if (!empty($linkarray["file"])) {
            $identifier = $file->get_pathnamehash();
            $itemid = $file->get_itemid();
            $submissiontype = 'file';
        }
		$plagiarismfiles = $DB->get_records('plagiarism_plagcheck', array('userid' => $linkarray["userid"],
                            'cm' => $linkarray["cmid"], 'identifier' => $identifier),
                            'id DESC', '*', 0, 1);
        $plagiarismfile = current($plagiarismfiles);
		if($plagiarismfile) {
			$transmatch = '';
			$fs = get_file_storage();
			$file = $fs->get_file_by_hash($plagiarismfile->copyfilename);
			if($file === false) {
				return;
			}
			$filename = $file->get_filename();
			$records = $DB->get_record('plagiarism_plagcheck', array('identifier' => $plagiarismfile->copyfilename));
			$copiedfrom = $DB->get_record('user', array('id' => $records->userid));
			$copyuser = $copiedfrom->firstname.' '.$copiedfrom->lastname;
			$score = ($plagiarismfile->similarityscore * 100).'%'.'<br>'.($filename).'<br>'.($copyuser);
	        $titlescore = ($plagiarismfile->similarityscore * 100).'% '.'<br>'.($filename).'<br>'.($copyuser).get_string('similarity', 'plagiarism_plagcheck');
	        $class = 'score_colour_'.round($plagiarismfile->similarityscore, -1);
			$orscorehtml = html_writer::tag('div', $score.$transmatch,
	                                                array('title' => $titlescore));
			return $orscorehtml;
		}
		return;
	}
	public function event_handler($eventdata) {
		global $DB, $CFG;
		$result = true;

		// Remove the event if the course module no longer exists.
		if (!$cm = get_coursemodule_from_id($eventdata['other']['modulename'], $eventdata['contextinstanceid'])) {
			return true;
		}

		// Set the author and submitter.
		$submitter = $eventdata['userid'];
		$author = (!empty($eventdata['relateduserid'])) ? $eventdata['relateduserid'] : $eventdata['userid'];

		if ($eventdata['other']['modulename'] == 'assign' && $eventdata['eventtype'] == "assessable_submitted") {
			// Get content.
			$moodlesubmission = $DB->get_record('assign_submission', array('id' => $eventdata['objectid']), 'id');
			if ($moodletextsubmission = $DB->get_record('assignsubmission_onlinetext',
				array('submission' => $moodlesubmission->id), 'onlinetext')) {
				$eventdata['other']['content'] = $moodletextsubmission->onlinetext;
			}

			// Get Files.
			$eventdata['other']['pathnamehashes'] = array();
			$filesconditions = array('component' => 'assignsubmission_file',
				'itemid' => $moodlesubmission->id, 'userid' => $author, 'filearea' => "submission_files");
			if ($moodlefiles = $DB->get_records('files', $filesconditions)) {
				//echo sizeof($moodlefiles);
				foreach ($moodlefiles as $moodlefile) {
					$eventdata['other']['pathnamehashes'][] = $moodlefile->pathnamehash;
				}
			}
		}

		/*if (in_array($eventdata['eventtype'], array("content_uploaded", "assessable_submitted"))
			&& !empty($eventdata['other']['content'])) {*/

		if (!empty($eventdata['other']['content'])) {

			$identifier = sha1($eventdata['other']['content']);
			// Check if text content has been submitted previously. Remove if so.
			list($insql, $inparams) = $DB->get_in_or_equal(array('success', 'queued'), SQL_PARAMS_QM, 'param', false);
			$typefield = ($CFG->dbtype == "oci") ? " to_char(statuscode) " : " statuscode ";
			$plagiarismfiles = $DB->get_records_select('plagiarism_plagcheck', " userid = ? AND cm = ? ".
				" AND identifier = ? AND ".$typefield. " " .$insql,
				array_merge(array($author, $cm->id, $identifier), $inparams));

			if ($plagiarismfiles) {
				return true;
			}
			else {
				$result = $this->queue_submission_to_plagcheck(
					$cm, $author, $submitter, $identifier, 'text_content', $eventdata['objectid']);
			}
		}
		$result = $result && true;
		if (!empty($eventdata['other']['pathnamehashes'])) {
			foreach ($eventdata['other']['pathnamehashes'] as $pathnamehash) {
				//echo "HI";
				$fs = get_file_storage();
				$file = $fs->get_file_by_hash($pathnamehash);
				if($file->get_filename() === '.') {
					continue;
				}
				$generatedSimhash = generateSimhash($file->get_content());
				$plagiarismfile = new stdClass();
		        $plagiarismfile->cm = $cm->id;
		        $plagiarismfile->userid = $author;
		        $plagiarismfile->identifier = $pathnamehash;
				$plagiarismfile->itemid = $eventdata['objectid'];
		        $plagiarismfile->statuscode = "queued";
		        $plagiarismfile->simhash = $generatedSimhash;
		        $plagiarismfile->submissiontype = 'file';
				$getid = $DB->get_record('assignsubmission_file', array('submission' => $eventdata['objectid']), 'assignment');
				$plagiarismfile->assignmentid = $getid->assignment;
				$getcourseid = $DB->get_record('assign', array('id' => $getid->assignment), 'course');
				$plagiarismfile->courseid = $getcourseid->course;
				$plagiarismfile->similarityscore = 0;
				$records = $DB->get_record('plagiarism_plagcheck', array('identifier' => $pathnamehash));
				if($records) {
					$plagiarismfile->id = $records->id;
					$fileid = $DB->update_record('plagiarism_plagcheck', $plagiarismfile);
				}
				else {
					$fileid = $DB->insert_record('plagiarism_plagcheck', $plagiarismfile);
				}
				$filesconditions = array('assignmentid' => $plagiarismfile->assignmentid, 'courseid' => $plagiarismfile->courseid);
				$records = $DB->get_records('plagiarism_plagcheck', $filesconditions);
				//echo $records[0]['simhash'];
				getSimilarity($records);

			}
		}

		return $result;
	}

}
