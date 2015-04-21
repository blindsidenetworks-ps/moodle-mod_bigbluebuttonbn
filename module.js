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

    if (bigbluebuttonbn.action == 'before') {
    } else if (bigbluebuttonbn.action == 'after') {
    } else if (bigbluebuttonbn.action == 'join') {
        bigbluebuttonbn_dataSource.sendRequest({
            request : "action=info&id=" + bigbluebuttonbn.meetingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
            callback : {
                success : function(e) {
                    Y.DOM.addHTML(status_bar, M.mod_bigbluebuttonbn.view_init_status_bar(e.data.status.message));
                    Y.DOM.addHTML(control_panel, M.mod_bigbluebuttonbn.view_init_control_panel(e.data.status));
                    Y.DOM.addHTML(join_button, M.mod_bigbluebuttonbn.view_init_join_button(e.data.status));
                },
                failure : function(e) {
                    console.log(e);
                }
            }
        });
    }
};

M.mod_bigbluebuttonbn.view_update = function() {
    var status_bar = Y.DOM.byId('status_bar');
    var control_panel = Y.DOM.byId('control_panel');
    var join_button = Y.DOM.byId('join_button');

    bigbluebuttonbn_dataSource.sendRequest({
        request : "action=info&id=" + bigbluebuttonbn.meetingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
        callback : {
            success : function(e) {
                console.log(e.data);
                //M.mod_bigbluebuttonbn.view_set_status(e.data.status.message);
                //Y.DOM.addHTML(control_panel, M.mod_bigbluebuttonbn.view_init_control_panel(e.data.status));
                //Y.DOM.addHTML(join_button, M.mod_bigbluebuttonbn.view_init_join_button(e.data.status));
            },
            failure : function(e) {
                console.log(e);
            }
        }
    });
}

M.mod_bigbluebuttonbn.view_init_status_bar = function(status_message) {
    var status_bar_span = Y.DOM.create('<span>');

    Y.DOM.setAttribute(status_bar_span, 'id', 'status_bar_span');
    Y.DOM.setText(status_bar_span, status_message);

    return(status_bar_span);
}

M.mod_bigbluebuttonbn.view_init_control_panel = function(status) {
    var control_panel_div = Y.DOM.create('<div>');

    Y.DOM.setAttribute(control_panel_div, 'id', 'control_panel_div');
    var control_panel_div_text = '';
    //if (e.data.running) {
    //    control_panel_div_text = 'it is running';
    //} else {
    //    control_panel_div_text = 'it is NOT running';
    //}
    Y.DOM.setText(control_panel_div, control_panel_div_text);

    return(control_panel_div);
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
    Y.DOM.setAttribute(spinning_wheel, 'src', 'pix/polling.gif');
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
        request : "action=info&id=" + bigbluebuttonbn.meetingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
        callback : {
            success : function(e) {
                console.log(e.data);
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
    window.open(join_url);
    // Update view
    M.mod_bigbluebuttonbn.view_clean_status_bar();
    M.mod_bigbluebuttonbn.view_clean_control_panel();
    M.mod_bigbluebuttonbn.view_hide_join_button();
    Y.DOM.addHTML(Y.DOM.byId('status_bar'), M.mod_bigbluebuttonbn.view_init_status_bar(status_message));
}

M.mod_bigbluebuttonbn.broker_publishRecording = function(action, recordingid) {
    console.info('Publish: ' + action);
    var request = {
            request : "action=" + ((action == 'hide')? 'unpublish': 'publish') + "&id=" + recordingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
            callback : {
                success : function(e) {
                    if (e.data.status == 'true') {
                        console.info("publish/unpublish completed");
                    }
                },
                failure : function(e) {
                    console.log(e.error.message);
                }
            }
        };

    var id = dataSource.sendRequest( request );
};

M.mod_bigbluebuttonbn.broker_deleteRecording = function(recordingid) {
    console.info('Delete');
    var request = {
            request : "action=delete&id=" + recordingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
            callback : {
                success : function(e) {
                    if (e.data.status == 'true') {
                        console.info("publish/unpublish completed");
                    }
                },
                failure : function(e) {
                    console.log(e.error.message);
                }
            }
        };

    var id = dataSource.sendRequest( request );
};

M.mod_bigbluebuttonbn.view_locationReload = function() {
    location.reload();
};

M.mod_bigbluebuttonbn.view_windowClose = function() {
    window.close();
};
