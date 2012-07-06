function bigbluebuttonbn_joinURL() {
	window.location = joinurl;
}

function bigbluebuttonbn_callback() {
	// Not elegant, but works around a bug in IE8
	var isMeeting = ($("#HeartBeatDIV").text().search("true") > 0);
	if (isMeeting) {
		bigbluebuttonbn_joinURL();
	}
}

$(document).ready(function(){
    if ( joining == 'true' ){
        if (ismoderator == 'true' || waitformoderator == 'false'){
            bigbluebuttonbn_joinURL();
        } else {
            $.jheartbeat.set({
                url: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb-broker.php?action=ping&meetingID=" + meetingid,
                delay: 5000
                }, function() {
                    bigbluebuttonbn_callback();
            });
        }
    }
});
