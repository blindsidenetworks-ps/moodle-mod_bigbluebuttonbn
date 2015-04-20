/**
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2012-2014 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

/**
 * This function is initialized from PHP
 * 
 * @param {Object}
 *            Y YUI instance
 */

var dataSource;

M.mod_bigbluebuttonbn.init_view = function(Y) {
    console.info('Init');
    console.log(bigbluebuttonbn.action);
    dataSource = new Y.DataSource.Get({
        source : M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
    });

    if (bigbluebuttonbn.action == 'join') {
        var request = {
                request : "action=ping&id=" + bigbluebuttonbn.meetingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
                callback : {
                    success : function(e) {
                        if (e.data.running) {
                            location.reload();
                        }
                    },
                    failure : function(e) {
                        console.log(e.error.message);
                    }
                }
            };

        var id = dataSource.setInterval(bigbluebuttonbn.ping_interval, request);
    } else if (bigbluebuttonbn.action == 'view') {
        var status_bar = Y.DOM.byId('status_bar');
        var control_panel = Y.DOM.byId('control_panel');
        var join_button = Y.DOM.byId('join_button');

        var request = {
                request : "action=info&id=" + bigbluebuttonbn.meetingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
                callback : {
                    success : function(e) {
                        console.log("Here");
                        Y.DOM.setText(status_bar, e.data.status.message);
                        var control_panel_div_text;
                        var control_panel_div = Y.DOM.create('<div>');
                        if (e.data.running) {
                            control_panel_div_text = 'it is running';
                        } else {
                            control_panel_div_text = 'it is NOT running';
                        }
                        console.log(control_panel_div_text);
                        Y.DOM.setText(control_panel_div, control_panel_div_text);
                        Y.DOM.addHTML(control_panel, control_panel_div);

                        var join_button_input = Y.DOM.create('<input>');
                        Y.DOM.setAttribute(join_button_input, 'type', 'button');
                        Y.DOM.setAttribute(join_button_input, 'value', e.data.status.join_button_text);
                        if (e.data.status.can_join) {
                            Y.DOM.setAttribute(join_button_input, 'onclick', 'window.open(\'' + e.data.status.join_url +'\');');
                            console.log('it can join');
                            //join_button_html = Y.DOM.create('<input type="button" onClick="" value="' + e.data.status.join_button_text + '">');  
                        } else {
                            console.log('it can NOT join');
                            //join_button_html = Y.DOM.create();
                        }
                        Y.DOM.addHTML(join_button, join_button_input);

                        console.log(e.data.status.message);
                        console.log(e.data);
                    },
                    failure : function(e) {
                        console.log(e);
                    }
                }
            };

        var id = dataSource.sendRequest(request);
    }
};

M.mod_bigbluebuttonbn.broker_waitModerator = function(action, bigbluebuttonbnid) {
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
