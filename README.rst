BigBlueButtonBN Activity Module for Moodle 2.x
==============================================
BigBlueButton is an open source web conferencing system that enables universities and colleges to deliver a high-quality learning experience to remote students.  

These instructions describe how to install the BigBlueButtonBN Activity Module for Moodle 2.x.  This module is developed and supported by Blindside Networks, the company that started the BigBlueButton project in 2007.

With this plugin you can
	- Create links in any class to a BigBlueButton session 
	- Specify join open/close dates that will appear in the Moodle calendar
	- Create a custom welcome messages that appears in the chat window when users join the session
	- Launch BigBlueButton in its own window
	- Restrict students from entering the session until a teacher joins
	- Monitor the active sessions for the course and end any session (eject all users)
	- Record and playback your lectures (requires BigBlueButton 0.8-beta)
	- Access and manage recorded lectures (requires recordingsbn plugin to be installed)

Prerequisites
=============
You need:

	1.  A server running Moodle 2.0+
	2.  A BigBlueButton 0.8-beta-4 (or later) server running on a separate server (not on the same server as your Moodle site)
	
Blindside Networks provides you a test BigBlueButton server for testing this plugin.  To use this test server, just accept the default settings when configuring the activity module.  The default settings are

	url: http://test-install.blindsidenetworks.com/bigbluebutton/

	salt: 8cd8ef52e8e101574e400365b55e11a6

For information on how to setup your own BigBlueButton server see

   http://bigbluebutton.org/
   
Obtaining the source
====================
This GitHub repostiory at

  https://github.com/blindsidenetworks/moodle-mod_bigbluebutton/tree/master/moodle2x/bigbluebuttonbn

contains the latest source.  If you want to use the latest packaged snapshot, you can download it from

  http://blindsidenetworks.com/downloads/moodle/bigbluebuttonbn.zip


Installation
============

These instructions assume your Moodle server is installed at /var/www/moodle.

1.  Copy bigbluebuttonbn.zip  to /var/www/moodle/mod
2.  Enter the following commands

	cd /var/www/moodle/mod
    	sudo unzip bigbluebuttonbn.zip

    This will create the directory
 
        ./bigbluebuttonbn
        
3.  Login to your moodle site as administrator

	Moodle will detect the new module and prompt you to Upgrade.
	
4.  Click the 'Upgrade' button.  

	The activity module will install mod_bigbluebuttonbn.
	
5.  Click the 'Continue' button. 

	You'll be prompted to configure the activity module.
	
6.  Enter the URL and salt (security key) to your BigBlueButton server (or use the default values for testing).
7.  Click the 'Save Changes' button.

At this point, you can enter any course, turn editing on, and add a BigBlueButtonBN activity link to the class.

Note: You will want to install the RecordingsBN activity module to access recordings as well.

For a video overview of installing and using this plugin,

	http://blindsidenetworks.com/integration


Contact Us
==========
If you have feedback, enhancement requests, or would like commercial support for hosting, integrating, customizing, branding, or scaling BigBlueButton, contact us at

	http://blindsidenetworks.com/

Regards,... Fred Dixon

ffdixon [at] blindsidenetworks [dt] com
