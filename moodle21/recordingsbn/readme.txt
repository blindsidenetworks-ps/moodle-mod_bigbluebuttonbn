
BigBlueButton is an open source web conferencing system that enables universities and colleges to deliver a high-quality learning experience to remote students.  

These instructions describe how to install the BigBlueButton Recording and Playback Module for Moodle (version 2.0+). 
This module is developed and supported by Blindside Networks, the company that started the BigBlueButton project in 2007.

With this module you can
	- Create links in any class to a BigBlueButton Media Libary
	- Browse the recordingsbn related to the virtual classroom linked to the different courses
	- Monitor and administrate the recordingsbn stored in a BigBlueButton server
	
Prerequisites:
============
You need a:

	1.  A server running Moodle 2.0+
	2.  A BigBlueButton server (usually running on a separate, dedicated server).
	3.  BigBlueButton Activity Module for Moodle
	
Blindside Networks provides you a test BigBlueButton server for free use.  To use this test server, just accept the default settings when configuring  the activity module.

For information on how to setup your own BigBlueButton server see

   http://bigbluebutton.org/
   

Installation
============

These instructions assume your Moodle server is installed at /var/www/moodle.

1.  Copy bbb_recordingsbn_module_moodle20.zip to /var/www/moodle
2.  Enter the following commands

	cd /var/www/moodle
    	sudo unzip bbb_recordingsbn_module_moodle20.zip

    This will create the directory
 
        ./mod/recordingsbn
        
3.  Login to your moodle site as administrator

	Go to Notifications in the Site administration menu. Moodle will detect the new module and prompt you to Upgrade.
	
4.  Click the 'Upgrade' button.  

	The activity module will install mod_recordingsbn.
	
5.  Click the 'Continue' button. 

	You'll be prompted to configure the activity module.
	
6.  Enter the URL and salt (security key) to your BigBlueButton server (or use the default values).
7.  Click the ‘Save Changes’ button.

At this point, you can enter any course, turn editing on, and add a Recordings activity link to the class.

When adding a link, you can specify:
	- The name of the recordingsbn library

When a user clicks on the link, the activity module will display in a table, a list of the recordingsbn that the user is authorized to see or administrate.


If you have feedback, enhancement requests, or would like commercial support for hosting, integrating, customizing, branding, or scaling BigBlueButton, contact us at

	http://blindsidenetworks.com/

Regards,... Fred Dixon
ffdixon [at] blindsidenetworks [dt] com

