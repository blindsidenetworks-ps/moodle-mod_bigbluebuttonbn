/**
 * JavaScript library for the bigbluebuttonbn module.
 *
 * @package    mod
 * @subpackage bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright  2012-2016 Blindside Networks Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** global: M */
/** global: Y */
/** global: bigbluebuttonbn */

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.broker_waitModerator = function() {
    /* global bigbluebuttonbn */

    // Show the spinning wheel.
    var status_bar_span = Y.one('#status_bar_span');
    // Create a img element.
    var spinning_wheel = Y.DOM.create('<img>');
    Y.DOM.setAttribute(spinning_wheel, 'id', 'spinning_wheel');
    Y.DOM.setAttribute(spinning_wheel, 'src', 'pix/processing16.gif');
    // Add the spinning wheel.
    Y.DOM.addHTML(status_bar_span, '&nbsp;');
    Y.DOM.addHTML(status_bar_span, spinning_wheel);

    // Start the ping.
    bigbluebuttonbn_ping_interval_id = bigbluebuttonbn_dataSource.setInterval(bigbluebuttonbn.ping_interval, {
        request: "action=meeting_info&id=" + bigbluebuttonbn.meetingid + "&bigbluebuttonbn=" + bigbluebuttonbn.bigbluebuttonbnid,
        callback: {
            success: function(e) {
                if (e.data.running) {
                    clearInterval(bigbluebuttonbn_ping_interval_id);
                    M.mod_bigbluebuttonbn.view_clean();
                    M.mod_bigbluebuttonbn.view_update();
                }
            },
            failure: function() {
                clearInterval(bigbluebuttonbn_ping_interval_id);
            }
        }
    });
};

M.mod_bigbluebuttonbn.broker_joinNow = function(join_url, status_message, can_tag) {
    /* global bigbluebuttonbn */
    var qs = '';

    if (can_tag) {
        Y.one('#panelContent').removeClass('hidden');
        qs += 'action=meeting_info';
        qs += '&id=' + bigbluebuttonbn.meetingid;
        qs += '&bigbluebuttonbn=' + bigbluebuttonbn.bigbluebuttonbnid;
        bigbluebuttonbn_dataSource.sendRequest({
            request: qs,
            callback: {
                success: function(e) {
                    if (e.data.running) {
                        M.mod_bigbluebuttonbn.broker_executeJoin(join_url, e.data.status.message);
                    } else {
                        Y.one('#meeting_join_url').set('value', join_url);
                        Y.one('#meeting_message').set('value', e.data.status.message);

                        YUI({
                            lang: bigbluebuttonbn.locale
                        }).use('panel', function() {
                            bigbluebuttonbn_panel.show();
                        });
                    }
                }
            }
        });

    } else {
        M.mod_bigbluebuttonbn.broker_executeJoin(join_url);
    }
};

M.mod_bigbluebuttonbn.broker_executeJoin = function(join_url) {
    window.open(join_url);
    // Update view.
    setTimeout(function() {
        M.mod_bigbluebuttonbn.view_clean();
        M.mod_bigbluebuttonbn.view_update();
    }, 15000);
};

M.mod_bigbluebuttonbn.broker_actionVerification = function(action, recordingid, meetingid, callback) {
    /* global bigbluebuttonbn */

    var is_imported_link = Y.one('#playbacks-' + recordingid).get('dataset').imported === 'true';
    var data = {
        'id': recordingid
    };
    var confirm;

    if (!is_imported_link && (action === 'unpublish' || action === 'delete')) {
        bigbluebuttonbn_dataSource.sendRequest({
            request: 'action=recording_links&id=' + recordingid,
            callback: {
                success: function(e) {
                    if (e.data.status) {
                        data.links = e.data.links;
                        if (e.data.links === 0) {
                            data.confirmed = true;
                        } else {
                            var confirmation_warning = bigbluebuttonbn.locales[action + "_confirmation_warning_p"];
                            if (e.data.links == 1) {
                                confirmation_warning = bigbluebuttonbn.locales[action + "_confirmation_warning_s"];
                            }
                            confirmation_warning = confirmation_warning.replace("{$a}", e.data.links) + '. ';
                            var recording_type = bigbluebuttonbn.locales.recording;
                            if (is_imported_link) {
                                recording_type = bigbluebuttonbn.locales.recording_link;
                            }
                            var confirmation = bigbluebuttonbn.locales[action + "_confirmation"].replace("{$a}", recording_type);

                            // Create the confirmation dialogue.
                            confirm = new M.core.confirm({
                                modal: true,
                                centered: true,
                                question: confirmation_warning + '\n\n' + confirmation
                            });

                            // If it is confirmed.
                            confirm.on('complete-yes', function(data, callback) {
                                data.confirmed = true;
                                callback(data);
                            }, this);
                        }
                    } else {
                        data.error = 'Big failiure';
                    }
                    callback(data);
                },
                failure: function(e) {
                    data.error = e.error.message;
                    callback(data);
                }
            }
        });
    } else if (action === 'import') {
        // Create the confirmation dialogue.
        confirm = new M.core.confirm({
            modal: true,
            centered: true,
            question: bigbluebuttonbn.locales.import_confirmation
        });

        // If it is confirmed.
        confirm.on('complete-yes', function(data, callback) {
            data.confirmed = true;
            callback(data);
        }, this);
    } else {
        data.confirmed = true;
        callback(data);
    }
};

M.mod_bigbluebuttonbn.broker_manageRecording = function(action, recordingid, meetingid) {
    /* global bigbluebuttonbn */

    // Before sending the request, let's process a verification.
    M.mod_bigbluebuttonbn.broker_actionVerification(action, recordingid, meetingid, function(data) {
        if (data.confirmed) {
            bigbluebuttonbn_dataSource.sendRequest({
                request: "action=recording_" + action + "&id=" + recordingid,
                callback: {
                    success: function(e) {
                        if (action == 'delete') {
                            Y.one('#recording-td-' + recordingid).remove();

                        } else if (action == 'import') {
                            Y.one('#recording-td-' + recordingid).remove();

                        } else if (action == 'publish' || action == 'unpublish') {
                            if (e.data.status == 'true') {
                                var btn_action = Y.one('#recording-btn-' + action + '-' + recordingid);
                                var btn_action_src_current = btn_action.getAttribute('src');
                                var btn_action_src_url = btn_action_src_current.substring(0, btn_action_src_current.length - 4);
                                btn_action.setAttribute('src', M.cfg.wwwroot + "/mod/bigbluebuttonbn/pix/processing16.gif");
                                if (action == 'publish') {
                                    btn_action.setAttribute('alt', bigbluebuttonbn.locales.publishing);
                                    btn_action.setAttribute('title', bigbluebuttonbn.locales.publishing);
                                } else {
                                    btn_action.setAttribute('alt', bigbluebuttonbn.locales.unpublishing);
                                    btn_action.setAttribute('title', bigbluebuttonbn.locales.unpublishing);
                                }
                                var link_action = Y.one('#recording-link-' + action + '-' + recordingid);
                                var link_action_current_onclick = link_action.getAttribute('onclick');
                                link_action.setAttribute('onclick', '');

                                // Start pooling until the action has been executed.
                                bigbluebuttonbn_ping_interval_id = bigbluebuttonbn_dataSource.setInterval(bigbluebuttonbn.ping_interval, {
                                    request: "action=recording_info&id=" + recordingid + "&idx=" + meetingid,
                                    callback: {
                                        success: function(e) {
                                            if (e.data.status == 'true') {
                                                if (action == 'publish') {
                                                    if (e.data.published == 'true') {
                                                        clearInterval(bigbluebuttonbn_ping_interval_id);
                                                        btn_action.setAttribute('id', 'recording-btn-unpublish-' + recordingid);
                                                        link_action.setAttribute('id', 'recording-link-unpublish-' + recordingid);
                                                        btn_action.setAttribute('src', btn_action_src_url + 'hide');
                                                        btn_action.setAttribute('alt', bigbluebuttonbn.locales.unpublish);
                                                        btn_action.setAttribute('title', bigbluebuttonbn.locales.unpublish);
                                                        link_action.setAttribute('onclick',
                                                            link_action_current_onclick.replace('publish', 'unpublish'));
                                                        Y.one('#playbacks-' + recordingid).show();
                                                    }
                                                } else {
                                                    if (e.data.published == 'false') {
                                                        clearInterval(bigbluebuttonbn_ping_interval_id);
                                                        btn_action.setAttribute('id', 'recording-btn-publish-' + recordingid);
                                                        link_action.setAttribute('id', 'recording-link-publish-' + recordingid);
                                                        btn_action.setAttribute('src', btn_action_src_url + 'show');
                                                        btn_action.setAttribute('alt', bigbluebuttonbn.locales.publish);
                                                        btn_action.setAttribute('title', bigbluebuttonbn.locales.publish);
                                                        link_action.setAttribute('onclick',
                                                            link_action_current_onclick.replace('unpublish', 'publish'));
                                                        Y.one('#playbacks-' + recordingid).hide();
                                                    }
                                                }
                                            } else {
                                                clearInterval(bigbluebuttonbn_ping_interval_id);
                                            }
                                        },
                                        failure: function() {
                                            clearInterval(bigbluebuttonbn_ping_interval_id);
                                        }
                                    }
                                });

                            } else {
                                var alert = new M.core.alert({
                                    message: e.data.message
                                });
                                alert.show();
                            }
                        }
                    }
                }
            });
        }
    });
};

M.mod_bigbluebuttonbn.broker_endMeeting = function() {
    /* global bigbluebuttonbn */
    var qs = 'action=meeting_end&id=' + bigbluebuttonbn.meetingid;
    qs += '&bigbluebuttonbn=' + bigbluebuttonbn.bigbluebuttonbnid;
    bigbluebuttonbn_dataSource.sendRequest({
        request: qs,
        callback: {
            success: function(e) {
                if (e.data.status) {
                    M.mod_bigbluebuttonbn.view_clean_control_panel();
                    M.mod_bigbluebuttonbn.view_hide_join_button();
                    M.mod_bigbluebuttonbn.view_hide_end_button();
                    location.reload();
                }
            }
        }
    });
};
