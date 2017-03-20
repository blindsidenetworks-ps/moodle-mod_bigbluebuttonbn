YUI.add('moodle-mod_bigbluebuttonbn-broker', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.broker = {

    data_source: null,
    polling: null,
    bigbluebuttonbn: {},
    /**
     * Initialise the broker code.
     *
     * @method init
     */
    init: function (bigbluebuttonbn) {
        this.data_source = new Y.DataSource.Get({
            source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
        });
        this.bigbluebuttonbn = bigbluebuttonbn;
    },

    waitModerator: function () {

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
        var qs = 'action=meeting_info';
        qs += '&id=' + this.bigbluebuttonbn.meetingid;
        qs += '&bigbluebuttonbn=' + this.bigbluebuttonbn.bigbluebuttonbnid;
        this.polling = this.data_source.setInterval(this.bigbluebuttonbn.ping_interval, {
            request: qs,
            callback: {
                success: function (e) {
                    if (e.data.running) {
                        clearInterval(this.polling);
                        M.mod_bigbluebuttonbn.view_clean();
                        M.mod_bigbluebuttonbn.view_update();
                    }
                },
                failure: function () {
                    clearInterval(this.polling);
                }
            }
        });
    },

    joinNow: function (join_url, status_message, can_tag) {
        /* global bigbluebuttonbn_panel */
        var qs = '';

        if (can_tag) {
            Y.one('#panelContent').removeClass('hidden');
            qs += 'action=meeting_info';
            qs += '&id=' + this.bigbluebuttonbn.meetingid;
            qs += '&bigbluebuttonbn=' + this.bigbluebuttonbn.bigbluebuttonbnid;
            this.data_source.sendRequest({
                request: qs,
                callback: {
                    success: function (e) {
                        if (e.data.running) {
                            M.mod_bigbluebuttonbn.broker.executeJoin(join_url, e.data.status.message);
                        } else {
                            Y.one('#meeting_join_url').set('value', join_url);
                            Y.one('#meeting_message').set('value', e.data.status.message);

                            YUI({
                                lang: this.bigbluebuttonbn.locale
                            }).use('panel', function () {
                                bigbluebuttonbn_panel.show();
                            });
                        }
                    }
                }
            });

        } else {
            M.mod_bigbluebuttonbn.broker.executeJoin(join_url);
        }
    },

    executeJoin: function (join_url) {
        window.open(join_url);
        // Update view.
        setTimeout(function () {
            M.mod_bigbluebuttonbn.view_clean();
            M.mod_bigbluebuttonbn.view_update();
        }, 15000);
    },

    actionVerification: function (action, recordingid, meetingid, callback) {

        var is_imported_link = Y.one('#playbacks-' + recordingid).get('dataset').imported === 'true';
        var data = {
            'id': recordingid
        };
        var confirm;

        if (!is_imported_link && (action === 'unpublish' || action === 'delete')) {
            this.data_source.sendRequest({
                request: 'action=recording_links&id=' + recordingid,
                callback: {
                    success: function (e) {
                        if (e.data.status) {
                            data.links = e.data.links;
                            if (e.data.links === 0) {
                                data.confirmed = true;
                            } else {
                                var confirmation_warning = this.bigbluebuttonbn.locales[action + "_confirmation_warning_p"];
                                if (e.data.links == 1) {
                                    confirmation_warning = this.bigbluebuttonbn.locales[action + "_confirmation_warning_s"];
                                }
                                confirmation_warning = confirmation_warning.replace("{$a}", e.data.links) + '. ';
                                var recording_type = this.bigbluebuttonbn.locales.recording;
                                if (is_imported_link) {
                                    recording_type = this.bigbluebuttonbn.locales.recording_link;
                                }
                                var confirmation = this.bigbluebuttonbn.locales[action + "_confirmation"];
                                confirmation = confirmation.replace("{$a}", recording_type);

                                // Create the confirmation dialogue.
                                confirm = new M.core.confirm({
                                    modal: true,
                                    centered: true,
                                    question: confirmation_warning + '\n\n' + confirmation
                                });

                                // If it is confirmed.
                                confirm.on('complete-yes', function (data, callback) {
                                    data.confirmed = true;
                                    callback(data);
                                }, this);
                            }
                        } else {
                            data.error = 'Big failiure';
                        }
                        callback(data);
                    },
                    failure: function (e) {
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
                question: this.bigbluebuttonbn.locales.import_confirmation
            });

            // If it is confirmed.
            confirm.on('complete-yes', function (data, callback) {
                data.confirmed = true;
                callback(data);
            }, this);
        } else {
            data.confirmed = true;
            callback(data);
        }
    },

    manageRecording: function (action, recordingid, meetingid) {

        // Before sending the request, let's process a verification.
        M.mod_bigbluebuttonbn.broker.actionVerification(action, recordingid, meetingid, function (data) {
            if (data.confirmed) {
                this.data_source.sendRequest({
                    request: "action=recording_" + action + "&id=" + recordingid,
                    callback: {
                        success: function (e) {
                            if (action == 'delete') {
                                Y.one('#recording-td-' + recordingid).remove();

                            } else if (action == 'import') {
                                Y.one('#recording-td-' + recordingid).remove();

                            } else if (action == 'publish' || action == 'unpublish') {
                                if (e.data.status == 'true') {
                                    var ping_data = {
                                        action: action,
                                        meetingid: meetingid,
                                        recordingid: recordingid
                                    };
                                    // Start pooling until the action has been executed.
                                    this.polling = this.data_source.setInterval(
                                        this.bigbluebuttonbn.ping_interval,
                                        M.mod_bigbluebuttonbn.broker.pingRecordingObject(ping_data)
                                    );
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
    },

    pingRecordingObject: function (data) {

        var btn_action = Y.one('#recording-btn-' + data.action + '-' + data.recordingid);
        var btn_action_src_current = btn_action.getAttribute('src');
        var btn_action_src_url = btn_action_src_current.substring(0, btn_action_src_current.length - 4);
        btn_action.setAttribute('src', M.cfg.wwwroot + "/mod/bigbluebuttonbn/pix/processing16.gif");
        if (data.action == 'publish') {
            btn_action.setAttribute('alt', this.bigbluebuttonbn.locales.publishing);
            btn_action.setAttribute('title', this.bigbluebuttonbn.locales.publishing);
        } else {
            btn_action.setAttribute('alt', this.bigbluebuttonbn.locales.unpublishing);
            btn_action.setAttribute('title', this.bigbluebuttonbn.locales.unpublishing);
        }
        var link_action = Y.one('#recording-link-' + data.action + '-' + data.recordingid);
        var link_action_current_onclick = link_action.getAttribute('onclick');
        link_action.setAttribute('onclick', '');

        return {
            request: "action=recording_info&id=" + data.recordingid + "&idx=" + data.meetingid,
            callback: {
                success: function (e) {
                    if (e.data.status !== 'true') {
                        clearInterval(this.polling);
                        return;
                    }

                    if (data.action === 'publish' && e.data.published === 'true') {
                        clearInterval(this.polling);
                        btn_action.setAttribute('id', 'recording-btn-unpublish-' + data.recordingid);
                        link_action.setAttribute('id', 'recording-link-unpublish-' + data.recordingid);
                        btn_action.setAttribute('src', btn_action_src_url + 'hide');
                        btn_action.setAttribute('alt', this.bigbluebuttonbn.locales.unpublish);
                        btn_action.setAttribute('title', this.bigbluebuttonbn.locales.unpublish);
                        link_action.setAttribute('onclick', link_action_current_onclick.replace('publish', 'unpublish'));
                        Y.one('#playbacks-' + data.recordingid).show();
                        return;
                    }

                    if (data.action === 'unpublish' && e.data.published === 'false') {
                        clearInterval(this.polling);
                        btn_action.setAttribute('id', 'recording-btn-publish-' + data.recordingid);
                        link_action.setAttribute('id', 'recording-link-publish-' + data.recordingid);
                        btn_action.setAttribute('src', btn_action_src_url + 'show');
                        btn_action.setAttribute('alt', this.bigbluebuttonbn.locales.publish);
                        btn_action.setAttribute('title', this.bigbluebuttonbn.locales.publish);
                        link_action.setAttribute('onclick', link_action_current_onclick.replace('unpublish', 'publish'));
                        Y.one('#playbacks-' + data.recordingid).hide();
                    }
                },
                failure: function () {
                    clearInterval(this.polling);
                }
            }
        };
    },

    endMeeting: function () {

        var qs = 'action=meeting_end&id=' + this.bigbluebuttonbn.meetingid;
        qs += '&bigbluebuttonbn=' + this.bigbluebuttonbn.bigbluebuttonbnid;
        this.data_source.sendRequest({
            request: qs,
            callback: {
                success: function (e) {
                    if (e.data.status) {
                        M.mod_bigbluebuttonbn.view_clean_control_panel();
                        M.mod_bigbluebuttonbn.view_hide_join_button();
                        M.mod_bigbluebuttonbn.view_hide_end_button();
                        location.reload();
                    }
                }
            }
        });
    }
};


}, '@VERSION@', {"requires": ["base", "node", "datasource-get", "datasource-jsonschema", "datasource-polling"]});
