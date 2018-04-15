<?php

$capabilities = array(
	'plagiarism/plagcheck:enable' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_COURSE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
		)
	),
	'plagiarism/plagcheck:viewreport' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_COURSE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
		)
	)
);
