PLAGCHECK

PLUGIN FOR PLAGIARISM CHECKING IN MOODLE

Assumption : This plugin is already installed in moodle before a student submits his/her assignment

The plugin is an event-triggered plugin. The plugin is triggered by the submission of assignments. 

Simhash is calculated for every file and then every file's simhash is compared with the simhash of other files belonging to the same assignment and course, accordingly the similarity score is generated.

Similarity score can be seen by :
i. Opening grading summary for a particular assignment.
ii. Clicking on "View All Submissions" button.
iii. Similarity score will be below the file submitted by that particular user.

One can also see the file from which it was copied, which is written just below the similarity score and below that is the name of the author from which this person copied it.

For installing PLAGCHECK :

i. Copy the Plagcheck into moodle/plagiarism folder.
ii. Login as site admin into moodle, install the plugin.
iii. Go to site administration -> Advanced Features and enable plagiarism.
iv. Go to site administration -> Plugins -> Plagcheck and enable Plagcheck there.
