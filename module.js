/**
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2012-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

/**
 * This function is initialized from PHP
 * 
 * @param {Object}
 *            Y YUI instance
 */

var bigbluebuttonbn_dataSource;
var bigbluebuttonbn_ping_interval_id;

M.mod_bigbluebuttonbn.view_init = function(Y) {
    bigbluebuttonbn_dataSource = new Y.DataSource.Get({
        source : M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
    });

    var status_bar = Y.DOM.byId('status_bar');
    var control_panel = Y.DOM.byId('control_panel');
    var join_button = Y.DOM.byId('join_button');
    var end_button = Y.DOM.byId('end_button');

    if (bigbluebuttonbn.action == 'before') {
    } else if (bigbluebuttonbn.action == 'after') {
    } else if (bigbluebuttonbn.action == 'join') {
        bigbluebuttonbn_dataSource.sendRequest({
            request : 'action=meeting_info&id=' + bigbluebuttonbn.meetingid + '&bigbluebuttonbn=' + bigbluebuttonbn.bigbluebuttonbnid,
            callback : {
                success : function(e) {
                    //if( e.data.infp.participantCount < bigbluebuttonbn.userlimit){}
                    Y.DOM.addHTML(status_bar, M.mod_bigbluebuttonbn.view_init_status_bar(e.data.status.message));
                    Y.DOM.addHTML(control_panel, M.mod_bigbluebuttonbn.view_init_control_panel(e.data));
                    if(typeof e.data.status.can_join != 'undefined' && e.data.status.can_join ) {
                        Y.DOM.addHTML(join_button, M.mod_bigbluebuttonbn.view_init_join_button(e.data.status));
                    }
                    if(typeof e.data.status.can_end != 'undefined' && e.data.status.can_end ) {
                        Y.DOM.addHTML(end_button, M.mod_bigbluebuttonbn.view_init_end_button(e.data.status));
                    }
                },
                failure : function(e) {
                    console.log(e);
                }
            }
        });
    }
};

M.mod_bigbluebuttonbn.view_init_status_bar = function(status_message) {
    var status_bar_span = Y.DOM.create('<span>');

    Y.DOM.setAttribute(status_bar_span, 'id', 'status_bar_span');
    Y.DOM.setText(status_bar_span, status_message);

    return(status_bar_span);
}

M.mod_bigbluebuttonbn.view_init_control_panel = function(data) {
    var control_panel_div = Y.DOM.create('<div>');

    Y.DOM.setAttribute(control_panel_div, 'id', 'control_panel_div');
    var control_panel_div_html = '';
    if (data.running) {
        control_panel_div_html = M.mod_bigbluebuttonbn.view_msg_started_at(data.info.startTime) + ' ' + M.mod_bigbluebuttonbn.view_msg_attendees_in(data.info.attendees);
    }
    Y.DOM.addHTML(control_panel_div, control_panel_div_html);

    return(control_panel_div);
}

M.mod_bigbluebuttonbn.view_msg_started_at = function (startTime) {
    var start_timestamp = (parseInt(startTime) -  parseInt(startTime) % 1000);
    var date = new Date(start_timestamp);
    //datevalues = [
    //    date.getFullYear(),
    //    date.getMonth()+1,
    //    date.getDate(),
    //    date.getHours(),
    //    date.getMinutes(),
    //    date.getSeconds(),
    // ];
    var hours = date.getHours();
    var minutes = date.getMinutes();

    return bigbluebuttonbn.locales.started_at + ' <b>' + hours + ':' + (minutes<10? '0': '') + minutes + '</b>.';
}

M.mod_bigbluebuttonbn.view_msg_users_joined = function (participantCount) {
    var participants = parseInt(participantCount);
    var msg_users_joined = '<b>' + participants + '</b> ';
    if( participants == 1 ) {
        msg_users_joined += bigbluebuttonbn.locales.user + ' ' + bigbluebuttonbn.locales.has_joined + '.'; 
    } else {
        msg_users_joined += bigbluebuttonbn.locales.users + ' ' + bigbluebuttonbn.locales.have_joined + '.';
    }
    return msg_users_joined;
}

M.mod_bigbluebuttonbn.view_msg_attendees_in = function (attendees) {
    var msg_attendees_in = '';

    if (typeof attendees.attendee != 'undefined') {
        if ( Array.isArray(attendees.attendee) ) {
            var counter_viewers = 0;
            var counter_moderators = 0;
            for( var i = 0; i < attendees.attendee.length; i++ ) {
                if( attendees.attendee[i].role == 'MODERATOR' )
                    counter_moderators++;
                else
                    counter_viewers++;
            }
            msg_attendees_in += bigbluebuttonbn.locales.session_has_users + ' <b>' + counter_moderators + '</b> ' + (counter_moderators == 1? bigbluebuttonbn.locales.moderator: bigbluebuttonbn.locales.moderators) + ' and ';
            msg_attendees_in += '<b>' + counter_viewers + '</b> ' + (counter_viewers == 1? bigbluebuttonbn.locales.viewer: bigbluebuttonbn.locales.viewers) + '.';
        } else {
            msg_attendees_in += bigbluebuttonbn.locales.session_has_user + ' <b>1</b> ' + (attendees.attendee.role == 'MODERATOR'? bigbluebuttonbn.locales.moderator: bigbluebuttonbn.locales.viewer) + '.';
        }

    } else {
        msg_attendees_in = bigbluebuttonbn.locales.session_no_users + '.';
    }

    return msg_attendees_in;
}


M.mod_bigbluebuttonbn.view_init_join_button = function (status) {
    var join_button_input = Y.DOM.create('<input>');

    Y.DOM.setAttribute(join_button_input, 'id', 'join_button_input');
    Y.DOM.setAttribute(join_button_input, 'type', 'button');
    Y.DOM.setAttribute(join_button_input, 'value', status.join_button_text);
    if (status.can_join) {
        Y.DOM.setAttribute(join_button_input, 'onclick', 'M.mod_bigbluebuttonbn.broker_joinNow(\'' + status.join_url + '\', \'' + bigbluebuttonbn.locales.in_progress + '\');');
    } else {
        Y.DOM.setAttribute(join_button_input, 'onclick', 'M.mod_bigbluebuttonbn.broker_waitModerator(\'' + status.join_url +'\');');
    }

    return join_button_input;
}

M.mod_bigbluebuttonbn.view_init_end_button = function (status) {
    var end_button_input = Y.DOM.create('<input>');

    Y.DOM.setAttribute(end_button_input, 'id', 'end_button_input');
    Y.DOM.setAttribute(end_button_input, 'type', 'button');
    Y.DOM.setAttribute(end_button_input, 'value', status.end_button_text);
    if (status.can_end) {
        Y.DOM.setAttribute(end_button_input, 'onclick', 'M.mod_bigbluebuttonbn.broker_endMeeting();');
    }

    return end_button_input;
}


M.mod_bigbluebuttonbn.view_clean_status_bar = function() {
    var status_bar_span = Y.DOM.byId('status_bar_span');
    status_bar_span.remove();
}

M.mod_bigbluebuttonbn.view_clean_control_panel = function() {
    var control_panel_div = Y.DOM.byId('control_panel_div');
    control_panel_div.remove();
}

M.mod_bigbluebuttonbn.view_hide_join_button = function() {
    var join_button = Y.DOM.byId('join_button');
    Y.DOM.setStyle(join_button, 'visibility', 'hidden');
}

M.mod_bigbluebuttonbn.view_hide_end_button = function() {
    var end_button = Y.DOM.byId('end_button');
    Y.DOM.setStyle(end_button, 'visibility', 'hidden');
}

M.mod_bigbluebuttonbn.broker_waitModerator = function(join_url) {
    /// Show the spinning wheel
    var control_panel = Y.DOM.byId('control_panel');
    //// clean the current content
    M.mod_bigbluebuttonbn.view_clean_control_panel();
    //// create a new div
    var control_panel_div = Y.DOM.create('<div>');
    Y.DOM.setAttribute(control_panel_div, 'id', 'control_panel_div');
    Y.DOM.setAttribute(control_panel_div, 'align', 'center');
    //// create a img element
    var spinning_wheel = Y.DOM.create('<img>');
    Y.DOM.setAttribute(spinning_wheel, 'id', 'spinning_wheel');
    Y.DOM.setAttribute(spinning_wheel, 'src', 'pix/processing64.gif');
    Y.DOM.setAttribute(spinning_wheel, 'align', 'center');
    //// add the spinning wheel
    Y.DOM.addHTML(control_panel_div, spinning_wheel);
    Y.DOM.addHTML(control_panel_div, '<br><br>');
    Y.DOM.addHTML(control_panel_div, bigbluebuttonbn.locales.wait_for_moderator);
    //// add the new div
    Y.DOM.addHTML(control_panel, control_panel_div);

    /// Hide the button
    M.mod_bigbluebuttonbn.view_hide_join_button();

    /// Start the ping
    bigbluebuttonbn_ping_interval_id = bigbluebuttonbn_dataSource.setInterval(bigbluebuttonbn.ping_interval, {
        request : "action=meeting_info&id=" + bigbluebuttonbn.meetingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
        callback : {
            success : function(e) {
                if (e.data.running) {
                    clearInterval(bigbluebuttonbn_ping_interval_id);
                    M.mod_bigbluebuttonbn.broker_joinNow(join_url, e.data.status.message);
                }
            },
            failure : function(e) {
                clearInterval(bigbluebuttonbn_ping_interval_id);
                console.log(e);
            }
        }
    });
}

M.mod_bigbluebuttonbn.broker_joinNow = function(join_url, status_message) {
    location.assign(join_url);
    // Update view
    M.mod_bigbluebuttonbn.view_clean_status_bar();
    M.mod_bigbluebuttonbn.view_clean_control_panel();
    M.mod_bigbluebuttonbn.view_hide_join_button();
    M.mod_bigbluebuttonbn.view_hide_end_button();
    Y.DOM.addHTML(Y.DOM.byId('status_bar'), M.mod_bigbluebuttonbn.view_init_status_bar(status_message));
}

M.mod_bigbluebuttonbn.broker_manageRecording = function(action, recordingid) {
    console.info('Action: ' + action);
    var id = bigbluebuttonbn_dataSource.sendRequest({
        request : "action=recording_" + action + "&id=" + recordingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
        callback : {
            success : function(e) {
                if( action == 'delete') {
                    var recording_td = Y.DOM.byId('recording-td-' + recordingid);
                    recording_td.remove();
                } else if( action == 'publish' || action == 'unpublish' ) {
                    var btn_action = Y.DOM.byId('recording-btn-' + action + '-' + recordingid);
                    btn_action.setAttribute('src', M.cfg.wwwroot + "/mod/bigbluebuttonbn/pix/processing16.gif");
                    btn_action.setAttribute('alt', (action == 'publish')? bigbluebuttonbn.locales.publishing: bigbluebuttonbn.locales.unpublishing);
                    btn_action.setAttribute('title', (action == 'publish')? bigbluebuttonbn.locales.publishing: bigbluebuttonbn.locales.unpublishing);
                    var link_action = Y.DOM.byId('recording-link-' + action + '-' + recordingid);
                    link_action.setAttribute('onclick', '');
                }
                console.log(e.data);
                if (e.data.status == 'true') {
                    console.info(action + " completed");
                }
            },
            failure : function(e) {
                console.log(e.error.message);
            },
            completed : function(e) {
                console.log(e);
            }
        }
    });
}

M.mod_bigbluebuttonbn.broker_endMeeting = function() {
    console.info('End Meeting');
    var id = bigbluebuttonbn_dataSource.sendRequest({
        request : 'action=meeting_end&id=' + bigbluebuttonbn.meetingid + '&bigbluebuttonbn=' + bigbluebuttonbn.bigbluebuttonbnid,
        callback : {
            success : function(e) {
                if (e.data.status) {
                    console.info("end meeting completed");
                    M.mod_bigbluebuttonbn.view_clean_control_panel();
                    M.mod_bigbluebuttonbn.view_hide_join_button();
                    M.mod_bigbluebuttonbn.view_hide_end_button();
                    location.reload();
                }
            },
            failure : function(e) {
                console.log(e.error.message);
            }
        }
    });
};

M.mod_bigbluebuttonbn.view_windowClose = function() {
    window.close();
};