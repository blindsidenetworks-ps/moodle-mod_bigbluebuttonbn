BigBlueButton Activity Module for Moodle 1.9
============================================

BigBlueButton is an open source web conferencing system that enables universities and colleges to deliver a high-quality learning experience to remote students.

These instructions describe how to install the BigBlueButton Activity Module for Moodle (version 1.9+).  

This module is developed and supported by Blindside Networks, the company that started the BigBlueButton project in 2007.

With this module you can
 - Create links in any class to a BigBlueButton virtual classroom
 - Restrict students from entering the virtual classroom until a teacher joins
 - Monitor the active classes and end any class (eject all users)

Prerequisites
=============
You need:

  1.  A server running Moodle 1.9+
  2.  A BigBlueButton server running on a separate server (not on the same server as your Moodle site)

Blindside Networks provides you a test BigBlueButton server for testing the Moodle integration.  To use this test server, just accept the default settings when configuring the activity module.  The default settings are

| url: http://test-install.blindsidenetworks.com/bigbluebutton/
| salt: 8cd8ef52e8e101574e400365b55e11a6

For information on how to setup your own BigBlueButton server see

   http://bigbluebutton.org/


Obtaining the source
====================
This GitHub repostiory at

  https://github.com/blindsidenetworks/moodle-mod_bigbluebutton/tree/master/moodle19/bigbluebuttonbn

contains the latest source.  If you want to use the latest packaged snapshot, you can download it from

  http://blindsidenetworks.com/downloads/moodle/bigbluebutton_blindsidenetworks_activity_module_19.zip

Installation
============

These instructions assume your Moodle 1.9 server is installed at /var/www/moodle and you've downloaded bigbluebutton_blindsidenetworks_activity_module_19.zip.

1.  Copy bigbluebutton_blindsidenetworks_activity_module_19.zip to /var/www/moodle
2.  Enter the following commands

        cd /var/www/moodle
        sudo unzip bigbluebutton_blindsidenetworks_activity_module_19.zip

    This will create the directory

        ./mod/bigbluebuttonbn

    and install the language file in

        ./lang/en_utf8/bigbluebuttonbn.php

3.  Login to your moodle site as administrator
4.  Click notifications.

        You'll see the database tables setup.

5.  Click the 'Continue' button.

        You'll be prompted to configure the activity module.

6.  Enter the URL and salt (security key) to your BigBlueButton server (or use the default values for test-install).
7.  Click the Save Changes button.

At this point, you can enter any course, turn editing on, and add a BigBlueButton activity link to the class.

When adding a link, you can specify:
 - The name of the virtual classroom
 - Whether students must wait until a teacher joins the virtual classroom before they can join

When a user clicks on the link, the activity module will use the BigBlueButton API to create a virtual classroom and join the user into that classroom.  Students join a viewers, other Moodle users (such as administrators and teachers) join as moderators.


If you have feedback, enhancement requests, or would like commercial support for hosting, integrating, customizing, branding, or scaling BigBlueButton, contact us at

        http://blindsidenetworks.com/

Regards,... Fred Dixon

ffdixon [at] blindsidenetworks [dt] com

