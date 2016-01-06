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
var bigbluebuttonbn_panel;

M.mod_bigbluebuttonbn.view_init = function(Y) {
    bigbluebuttonbn_dataSource = new Y.DataSource.Get({
        source : M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
    });

    if (bigbluebuttonbn.action == 'before') {
    } else if (bigbluebuttonbn.action == 'after') {
    } else if (bigbluebuttonbn.action == 'join') {
	    // Create the main modal form.
        bigbluebuttonbn_panel = new Y.Panel({
            srcNode      : '#panelContent',
            headerContent: bigbluebuttonbn.locales.modal_title,
            width        : 250,
            zIndex       : 5,
	        centered     : true,
	        modal        : true,
	        visible      : false,
	        render       : true,
	        plugins      : [Y.Plugin.Drag]
        });

        // Define the apply function -  this will be called when 'Apply' is pressed in the modal form.
        bigbluebuttonbn_panel.addButton({
            value  : bigbluebuttonbn.locales.modal_button,
            section: Y.WidgetStdMod.FOOTER,
            action : function (e) {
                e.preventDefault();
                bigbluebuttonbn_panel.hide();

                //var nameField = Y.DOM.byId('recording_name');
                var joinField = Y.one('#meeting_join_url');
                var messageField = Y.one('#meeting_message');
                var nameField = Y.one('#recording_name');
                var descriptionField  = Y.one('#recording_description');
                var tagsField = Y.one('#recording_tags');

                //Gatter the fields thay will be passed as metaparameters to the bbb server
                var name = nameField.get('value').replace(/</g, "&lt;").replace(/>/g, "&gt;");
                var description = descriptionField.get('value').replace(/</g, "&lt;").replace(/>/g, "&gt;");
                var tags = tagsField.get('value').replace(/</g, "&lt;").replace(/>/g, "&gt;");

                // Prepare the new join_url
                var join_url = joinField.get('value') + '&name=' + name + '&description=' + description + '&tags=' + tags;

                // Executes the join
                M.mod_bigbluebuttonbn.broker_executeJoin(join_url, messageField.get('value'));

                // Clean values in case the for is used again
                nameField.set('value', '');
                descriptionField.set('value', '');
                tagsField.set('value', '');
                joinField.set('value', '');
                messageField.set('value', '');
            }
        });

        M.mod_bigbluebuttonbn.view_update();
    }
};

M.mod_bigbluebuttonbn.view_update = function() {
    var status_bar = Y.one('#status_bar');
    var control_panel = Y.one('#control_panel');
    var join_button = Y.one('#join_button');
    var end_button = Y.one('#end_button');

    bigbluebuttonbn_dataSource.sendRequest({
        request : 'action=meeting_info&id=' + bigbluebuttonbn.meetingid + '&bigbluebuttonbn=' + bigbluebuttonbn.bigbluebuttonbnid,
        callback : {
            success : function(e) {
                //if( e.data.info.participantCount < bigbluebuttonbn.userlimit){}
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

M.mod_bigbluebuttonbn.view_clean = function() {
    M.mod_bigbluebuttonbn.view_clean_status_bar();
    M.mod_bigbluebuttonbn.view_clean_control_panel();
    M.mod_bigbluebuttonbn.view_clean_join_button();
    M.mod_bigbluebuttonbn.view_clean_end_button();
}

M.mod_bigbluebuttonbn.view_remote_update = function(delay) {
	setTimeout(function() {
		M.mod_bigbluebuttonbn.view_clean();
		M.mod_bigbluebuttonbn.view_update();
	}, delay);
}

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
        control_panel_div_html = M.mod_bigbluebuttonbn.view_msg_started_at(data.info.startTime) + ' ' + M.mod_bigbluebuttonbn.view_msg_attendees_in(data.info.moderatorCount, data.info.participantCount);
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

M.mod_bigbluebuttonbn.view_msg_attendees_in = function (moderators, participants) {
    var msg_attendees_in = '';

    if (typeof moderators != 'undefined' && typeof participants != 'undefined') {
        var viewers = participants - moderators;
        if( participants > 1 ) {
            var viewers = participants - moderators;
            msg_attendees_in += bigbluebuttonbn.locales.session_has_users + ' <b>' + moderators + '</b> ' + (moderators == 1? bigbluebuttonbn.locales.moderator: bigbluebuttonbn.locales.moderators) + ' and ';
            msg_attendees_in += '<b>' + viewers + '</b> ' + (viewers == 1? bigbluebuttonbn.locales.viewer: bigbluebuttonbn.locales.viewers) + '.';

        } else {
            if( viewers > 0 ) {
                msg_attendees_in += bigbluebuttonbn.locales.session_has_user + ' <b>1</b> ' + bigbluebuttonbn.locales.viewer + '.';	
            } else if ( moderators > 0 ) {
                msg_attendees_in += bigbluebuttonbn.locales.session_has_user + ' <b>1</b> ' + bigbluebuttonbn.locales.moderator + '.';	
            }
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
        Y.DOM.setAttribute(join_button_input, 'onclick', 'M.mod_bigbluebuttonbn.broker_joinNow(\'' + status.join_url + '\', \'' + bigbluebuttonbn.locales.in_progress + '\', ' + status.can_tag + ');');
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
    var status_bar_span = Y.one('#status_bar_span');
    status_bar_span.remove();
}

M.mod_bigbluebuttonbn.view_clean_control_panel = function() {
    var control_panel_div = Y.one('#control_panel_div');
    control_panel_div.remove();
}

M.mod_bigbluebuttonbn.view_clean_join_button = function() {
    var join_button = Y.one('#join_button');
    join_button.setContent('');
}

M.mod_bigbluebuttonbn.view_hide_join_button = function() {
    var join_button = Y.one('#join_button');
    Y.DOM.setStyle(join_button, 'visibility', 'hidden');
}

M.mod_bigbluebuttonbn.view_show_join_button = function() {
    var join_button = Y.one('#join_button');
    Y.DOM.setStyle(join_button, 'visibility', 'shown');
}

M.mod_bigbluebuttonbn.view_clean_end_button = function() {
    var end_button = Y.one('#end_button');
    end_button.setContent('');
}

M.mod_bigbluebuttonbn.view_hide_end_button = function() {
    var end_button = Y.one('#end_button');
    Y.DOM.setStyle(end_button, 'visibility', 'hidden');
}

M.mod_bigbluebuttonbn.view_show_end_button = function() {
    var end_button = Y.one('#end_button');
    Y.DOM.setStyle(end_button, 'visibility', 'shown');
}

M.mod_bigbluebuttonbn.broker_waitModerator = function(join_url) {
    /// Show the spinning wheel
    var control_panel = Y.one('#control_panel');
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
                    M.mod_bigbluebuttonbn.broker_joinNow(join_url, e.data.status.message, false);
                }
            },
            failure : function(e) {
                clearInterval(bigbluebuttonbn_ping_interval_id);
                console.log(e);
            }
        }
    });
}

M.mod_bigbluebuttonbn.broker_joinNow = function(join_url, status_message, can_tag) {
    if( can_tag ) {
        var panelContent = Y.one('#panelContent');
        panelContent.removeClass('hidden');

        bigbluebuttonbn_dataSource.sendRequest({
            request : 'action=meeting_info&id=' + bigbluebuttonbn.meetingid + '&bigbluebuttonbn=' + bigbluebuttonbn.bigbluebuttonbnid,
            callback : {
                success : function(e) {
                    if (e.data.running) {
                        M.mod_bigbluebuttonbn.broker_executeJoin(join_url, e.data.status.message);
                    } else {
                        Y.one('#meeting_join_url').set('value', join_url);
                        Y.one('#meeting_message').set('value', e.data.status.message);

                        YUI().use('panel', function (Y) {
                            bigbluebuttonbn_panel.show();
                        });
                    }
                },
                failure : function(e) {
                    console.log(e);
                }
            }
        });

    } else {
        M.mod_bigbluebuttonbn.broker_executeJoin(join_url, status_message);
    }
}

M.mod_bigbluebuttonbn.broker_executeJoin = function(join_url, status_message) {
    window.open(join_url);
    // Update view
    setTimeout(function() {
        M.mod_bigbluebuttonbn.view_clean();
        M.mod_bigbluebuttonbn.view_update();
    }, 15000);
}

M.mod_bigbluebuttonbn.broker_manageRecording = function(action, recordingid, meetingid) {
    console.info('Action: ' + action);
    var id = bigbluebuttonbn_dataSource.sendRequest({
        request : "action=recording_" + action + "&id=" + recordingid,
        callback : {
            success : function(e) {
                if( action == 'delete') {
                    var recording_td = Y.one('#recording-td-' + recordingid);
                    recording_td.remove();
                } else if( action == 'publish' || action == 'unpublish' ) {
                    var btn_action = Y.one('#recording-btn-' + action + '-' + recordingid);
                    var btn_action_src_current = btn_action.getAttribute('src');
                    var btn_action_src_url = btn_action_src_current.substring(0, btn_action_src_current.length - 4)
                    btn_action.setAttribute('src', M.cfg.wwwroot + "/mod/bigbluebuttonbn/pix/processing16.gif");
                    btn_action.setAttribute('alt', (action == 'publish')? bigbluebuttonbn.locales.publishing: bigbluebuttonbn.locales.unpublishing);
                    btn_action.setAttribute('title', (action == 'publish')? bigbluebuttonbn.locales.publishing: bigbluebuttonbn.locales.unpublishing);
                    var link_action = Y.one('#recording-link-' + action + '-' + recordingid);
                    var link_action_current_onclick = link_action.getAttribute('onclick');
                    link_action.setAttribute('onclick', '');

                    if (e.data.status == 'true') {
                        console.info(action + " requested");
                    }

                    //Start pooling until the action has been executed
                    bigbluebuttonbn_ping_interval_id = bigbluebuttonbn_dataSource.setInterval(bigbluebuttonbn.ping_interval, {
                        request : "action=recording_info&id=" + recordingid + "&idx=" + meetingid,
                        callback : {
                            success : function(e) {
                                if (e.data.status == 'true') {
                                    if (action == 'publish') {
                                        if (e.data.published == 'true') {
                                            clearInterval(bigbluebuttonbn_ping_interval_id);
                                            btn_action.setAttribute('id', 'recording-btn-unpublish-' + recordingid);
                                            link_action.setAttribute('id', 'recording-link-unpublish-' + recordingid);
                                            btn_action.setAttribute('src', btn_action_src_url+'hide');
                                            btn_action.setAttribute('alt', bigbluebuttonbn.locales.unpublish);
                                            btn_action.setAttribute('title', bigbluebuttonbn.locales.unpublish);
                                            link_action_current_onclick = link_action_current_onclick.replace('publish', 'unpublish');
                                            link_action.setAttribute('onclick', link_action_current_onclick);
                                            Y.one('#playbacks-' + recordingid).show();
                                            console.info(action + " completed");
                                        } else {
                                            console.info(action + " in process");
                                        }
                                    } else {
                                        if (e.data.published == 'false') {
                                            clearInterval(bigbluebuttonbn_ping_interval_id);
                                            btn_action.setAttribute('id', 'recording-btn-publish-' + recordingid);
                                            link_action.setAttribute('id', 'recording-link-publish-' + recordingid);
                                            btn_action.setAttribute('src', btn_action_src_url+'show');
                                            btn_action.setAttribute('alt', bigbluebuttonbn.locales.publish);
                                            btn_action.setAttribute('title', bigbluebuttonbn.locales.publish);
                                            link_action_current_onclick = link_action_current_onclick.replace('unpublish', 'publish');
                                            link_action.setAttribute('onclick', link_action_current_onclick);
                                            Y.one('#playbacks-' + recordingid).hide();
                                            console.info(action + " completed");
                                        } else {
                                            console.info(action + " in process");
                                        }
                                    }
                                } else {
                                    console.info('The status of the recoding could not be verified');
                                    clearInterval(bigbluebuttonbn_ping_interval_id);
                                }
                            },
                            failure : function(e) {
                                clearInterval(bigbluebuttonbn_ping_interval_id);
                                console.log(e);
                            }
                        }
                    });
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

M.mod_bigbluebuttonbn.broker_updateRecording = function(action, recordingid) {
}

M.mod_bigbluebuttonbn.broker_endMeeting = function() {
    console.info('End Meeting');
    var id = bigbluebuttonbn_dataSource.sendRequest({
        request : 'action=meeting_end&id=' + bigbluebuttonbn.meetingid + '&bigbluebuttonbn=' + bigbluebuttonbn.bigbluebuttonbnid,
        callback : {
            success : function(e) {
                if (e.data.status) {
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
    window.onunload = function (e) {
        opener.M.mod_bigbluebuttonbn.view_remote_update(5000);
    };
    window.close();
};

M.mod_bigbluebuttonbn.recordingsbn_init = function(Y) {
    bigbluebuttonbn_dataSource = new Y.DataSource.Get({
        source : M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
    });
};