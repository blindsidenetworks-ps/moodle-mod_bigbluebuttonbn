RecordingsBN Activity Module for Moodle 2.x
===========================================
BigBlueButton is an open source web conferencing system that enables universities and colleges to deliver a high-quality learning experience to remote students.  

These instructions describe how to install the RecordingsBN Activity Module for Moodle 2.x.  This module is developed and supported by Blindside Networks, the company that started the BigBlueButton project in 2007.

With this plugin you can
	- Create resource links to recordings made with BigBlueButton (requires bigbluebuttonbn plugin to be installed)
	- Publish/unpublish and delete recordings

Prerequisites
=============
You need:

        1.  A server running Moodle 2.0+
        2.  A BigBlueButton 0.8-beta-4 (or later) server running on a separate server (not on the same server as your Moodle site)

Blindside Networks provides you a test BigBlueButton server for testing this plugin.  To use this test server, just accept the default settings when configuring the activity module.  The default settings are

	url: http://test-install.blindsidenetworks.com/bigbluebutton/

	salt: 8cd8ef52e8e101574e400365b55e11a6

For information on how to setup your own BigBlueButton server see

Obtaining the source
====================
This GitHub repostiory at

  https://github.com/blindsidenetworks/moodle-mod_bigbluebutton/tree/master/moodle2x/recordingsbn

contains the latest source.  If you want to use the latest packaged snapshot, you can download it from

  http://blindsidenetworks.com/downloads/moodle/recordingsbn.zip


Installation
============

These instructions assume your Moodle server is installed at /var/www/moodle.

1.  Copy recordingsbn.zip to /var/www/moodle/mod
2.  Enter the following commands

	cd /var/www/moodle/mod
    	sudo unzip recordingsbn.zip

    This will create the directory
 
        ./recordingsbn
        
3.  Login to your moodle site as administrator

	Moodle will detect the new module and prompt you to Upgrade.
	
4.  Click the 'Upgrade' button.  

	The activity module will install mod_recordingsbn.
	
5.  Click the 'Continue' button. 

At this point, you can enter any course, turn editing on, and add a recordingsbn resource link to the class.

For a video overview of installing and using this plugin,

	http://blindsidenetworks.com/integration


Contact Us
==========
If you have feedback, enhancement requests, or would like commercial support for hosting, integrating, customizing, branding, or scaling BigBlueButt
on, contact us at

	http://blindsidenetworks.com/

Regards,... Fred Dixon
ffdixon [at] blindsidenetworks [dt] com

