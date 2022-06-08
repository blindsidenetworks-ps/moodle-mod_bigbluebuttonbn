[![Build Status](https://travis-ci.org/blindsidenetworks/moodle-mod_bigbluebuttonbn.svg?branch=master)](https://travis-ci.org/blindsidenetworks/moodle-mod_bigbluebuttonbn)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/blindsidenetworks/moodle-mod_bigbluebuttonbn/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/blindsidenetworks/moodle-mod_bigbluebuttonbn/?branch=master)

BigBlueButtonBN Activity Module for Moodle
==========================================
BigBlueButton is an open source web conferencing system that enables universities and colleges to deliver a high-quality learning experience to remote students.

These instructions describe how to install the BigBlueButtonBN Activity Module for Moodle.  This module is developed and supported by Blindside Networks, the company that started the BigBlueButton project in 2007.

With the latest version of this plugin you can

- Create links in any course that can be used to create rooms/sessions in a BigBlueButton server
- Specify join open/close dates that will appear in the Moodle calendar
- Create a custom welcome messages that appears in the chat window when users join the session
- Launch BigBlueButton in its own tab or window
- Assign the role uses will have in BigBlueButton (moderator, viewer) per user or role in Moodle
- Pre-upload presentations
- Monitor the active sessions for the course and end any session (eject all users)
- Record and playback your lectures
- Access and manage recorded lectures
- Import recording links from a different course, and more.


Note that on previous versions of Moodle you will need to use the specific version of this plugin.

| Moodle Version    |  Branch      | Version                 |
|-------------------|--------------|-------------------------|
| Moodle 2.0 - 2.5  | v1.1-stable  | v1.1.1  (2015062101)    |
| Moodle 2.6        | v2.0-stable  | v2.0.4  (2015080611)    |
| Moodle 2.7 - 2.9  | v2.1-stable  | v2.1.15 (2016051920)    |
| Moodle 3.0 - 3.1  | v2.2-stable  | v2.2.12 (2017101020)    |
| Moodle 3.2 - 3.9  | v2.3-stable  | v2.3.4  (2019042009)    |
| Moodle 3.4 - 3.11 | v2.4-stable  | v2.4.7  (2019101014)    |

Prerequisites
=============
You need:

1.  A server running Moodle
2.  A BigBlueButton 0.8 (or later) server running on a separate server (not on the same server as your Moodle site)

Blindside Networks provides you a test BigBlueButton server for testing this plugin.  To use this test server, just accept the default settings when configuring the activity module.  The default settings are

	url: http://test-install.blindsidenetworks.com/bigbluebutton/

	salt: 8cd8ef52e8e101574e400365b55e11a6

For information on how to setup your own BigBlueButton server see

http://bigbluebutton.org/

Obtaining the source
====================
This GitHub repository at

https://github.com/blindsidenetworks/moodle-mod_bigbluebuttonbn/

contains the latest source. We recommend to download the latest snapshot from the Moodle Plugin Directory.


Note: Since version 2.2 the use of the RecordingsBN activity module to access recordings is no longer needed. But when running an older version, it is requiered in order to gain access to the recordings.


Contact Us
==========
If you have feedback, enhancement requests, or would like commercial support for hosting, integrating, customizing, branding, or scaling BigBlueButton, contact us at

http://blindsidenetworks.com/
