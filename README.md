PLAGCHECK

PLUGIN FOR PLAGIARISM CHECKING IN MOODLE

Assumption : This plugin is already installed in moodle before a student submits his/her assignment

The plugin is an event-triggered plugin. The plugin is triggered by the submission of assignments.

For checking plagiarism we calculate what we call the "simhash" of the file.
For this purpose, we use the following library: https://github.com/tgalopin/SimHashPhp.

Simhash is calculated for every file and then every file's simhash is compared with the simhash of other files belonging to the same assignment and course, accordingly the similarity score is generated.

Similarity score can be seen by :    
i. Opening grading summary for a particular assignment.    
ii. Clicking on "View All Submissions" button.     
iii. Similarity score will be below the file submitted by that particular user.   

One can also see the file from which it was copied, which is written just below the similarity score and below that is the name of the author from which this person copied it.

For installing PLAGCHECK :    

i. Download the zip file of this git repository (https://github.com/mbala2810/moodle-plagiarism_plagcheck.git)
ii. Extract this downloade zip folder.
iii. Now go into this folder to find a folder called "plagcheck"
iv. Compress this "plagcheck" folder into a zip file. Call it "moodle-plagiarism_plagcheck"
v. Login to moodle as site admin.
vi. Go to site administration -> Plugin -> Install plugins
vii. Open the above created zip file to intall Plagcheck.

Enabling PLAGCHECK:

i. Go to site administration -> Advanced Features and enable plagiarism.     
ii. Go to site administration -> Plugins -> Plagcheck and enable Plagcheck there.    
