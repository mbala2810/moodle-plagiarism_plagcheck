<?php
require_once($CFG->dirroot.'/plagiarism/plagcheck/vendor/autoload.php');
require_once($CFG->dirroot.'/plagiarism/lib.php');
use Tga\SimHash\Fingerprint;
global $plagcheckacceptedfiles;

function generateSimhash($fileContent) {
	$simhash = new \Tga\SimHash\SimHash();
	$extractor = new \Tga\SimHash\Extractor\SimpleTextExtractor();
	$comparator = new Tga\SimHash\Comparator\GaussianComparator(3);
	$fileData = $simhash->hash($extractor->extract($fileContent), \Tga\SimHash\SimHash::SIMHASH_64);
	return $fileData->getDecimal();
}
function getSimilarity($records) {
	$simhash = new \Tga\SimHash\SimHash();
	$extractor = new \Tga\SimHash\Extractor\SimpleTextExtractor();
	$comparator = new Tga\SimHash\Comparator\GaussianComparator(3);
	//echo sizeof($record);
	$i = 0;
	foreach($records as $record) {
		$max = 0;
		$index = 0;
		$hash1 = new Fingerprint(sizeof($record->simhash),$record->simhash);
		//echo $record->simhash;
		//$similarity[$i]['itemid'] = $record[$i]['itemid'];
		foreach($records as $recordinner){
			if($recordinner->id != $record->id && $recordinner->userid != $record->userid){
				$hash2 = new Fingerprint(sizeof($recordinner->simhash),$recordinner->simhash);
				$plag = $comparator->compare($hash1,$hash2);
				if($plag > $max){
					$max = $plag;
					$index = $recordinner->itemid;
				}
			}
		}
		$record->similarityscore = $max;
		$record->copyfilename = (string)$index;
		insertIntoTable($record);
		$i++;
	}
	return;
}
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
	//echo $plagiarismfile->identifier;
	//$DB->delete_records('plagiarism_plagcheck', array('identifier' => $plagiarismfile->identifier));
	$DB->update_record('plagiarism_plagcheck', $plagiarismfile, false);
	//$fileid = $DB->insert_record('plagiarism_plagcheck', $plagiarismfile);
}
class plagiarism_plugin_plagcheck extends plagiarism_plugin {
	public function update_status($course, $cm) {
		return '';
	}
	public function get_tutor_capability() {
		return 'mod/assign:grade';
	}
	public function get_links($linkarray) {
		// Set static variables.
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
			$score = ($plagiarismfile->similarityscore * 100).'%';
	        $titlescore = ($plagiarismfile->similarityscore * 100).'% '.get_string('similarity', 'plagiarism_plagcheck');
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