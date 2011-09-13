
BigBlueButton is an open source web conferencing system that enables universities and colleges to deliver a high-quality learning experience to remote students.  

These instructions describe how to install the BigBlueButton Activity Module for Moodle (version 1.9+). 
This module is developed and supported by Blindside Networks, the company that started the BigBlueButton project in 2007.

With this module you can
	- Create links in any class to a BigBlueButton virtual classroom
	- Restrict students from entering the virtual classroom until a teacher joins
	- Monitor the active classes and end any class (eject all users)
	
Prerequisites:
============
You need a:

	1.  A server running Moodle 2.0+
	2.  A BigBlueButton server (usually running on a separate, dedicated server).
	
Blindside Networks provides you a test BigBlueButton server for free use.  To use this test server, just accept the default settings when configuring  the activity module.

For information on how to setup your own BigBlueButton server see

   http://bigbluebuttonbn.org/
   

Installation
============

These instructions assume your Moodle server is installed at /var/www/moodle.

1.  Copy bbb_activity_module_moodle19.zip to /var/www/moodle
2.  Enter the following commands

	cd /var/www/moodle
    	sudo unzip bbb_activity_module_moodle20.zip

    This will create the directory
 
        ./mod/bigbluebuttonbn
        
3.  Login to your moodle site as administrator

	Moodle will detect the new module and prompt you to Upgrade.
	
4.  Click the 'Upgrade' button.  

	The activity module will install mod_bigbluebuttonbn.
	
5.  Click the 'Continue' button. 

	You'll be prompted to configure the activity module.
	
6.  Enter the URL and salt (security key) to your BigBlueButton server (or use the default values).
7.  Click the ‘Save Changes’ button.

At this point, you can enter any course, turn editing on, and add a BigBlueButton activity link to the class.

When adding a link, you can specify:
	- The name of the virtual classroom
	- Whether students must wait until a teacher joins the virtual classroom before they can join

When a user clicks on the link, the activity module will use the BigBlueButton API to create a virtual classroom and join the user into that classroom.  Students join a viewers, other Moodle users (such as administrators and teachers) join as moderators.


If you have feedback, enhancement requests, or would like commercial support for hosting, integrating, customizing, branding, or scaling BigBlueButton, contact us at

	http://blindsidenetworks.com/

Regards,... Fred Dixon
ffdixon [at] blindsidenetworks [dt] com
