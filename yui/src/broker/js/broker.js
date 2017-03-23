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

/** global: M */
/** global: Y */

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.broker = {

    datasource: null,
    polling: null,
    bigbluebuttonbn: {},

    /**
     * Initialise the broker code.
     *
     * @method init
     */
    init: function(bigbluebuttonbn) {
        this.datasource = new Y.DataSource.Get({
            source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
        });
        this.bigbluebuttonbn = bigbluebuttonbn;
    },

    waitModerator: function() {

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
        this.polling = this.datasource.setInterval(this.bigbluebuttonbn.ping_interval, {
            request: qs,
            callback: {
                success: function(e) {
                    if (e.data.running) {
                        clearInterval(this.polling);
                        M.mod_bigbluebuttonbn.rooms.clean_room();
                        M.mod_bigbluebuttonbn.rooms.update_room();
                    }
                },
                failure: function() {
                    clearInterval(this.polling);
                }
            }
        });
    },

    join: function(join_url, status_message, can_tag) {
        var qs = '';

        if (!can_tag) {
            M.mod_bigbluebuttonbn.broker.joinRedirect(join_url);
            return;
        }

        Y.one('#panelContent').removeClass('hidden');
        qs += 'action=meeting_info';
        qs += '&id=' + this.bigbluebuttonbn.meetingid;
        qs += '&bigbluebuttonbn=' + this.bigbluebuttonbn.bigbluebuttonbnid;
        this.datasource.sendRequest({
            request: qs,
            callback: {
                success: function(e) {
                    if (!e.data.running) {
                        Y.one('#meeting_join_url').set('value', join_url);
                        Y.one('#meeting_message').set('value', e.data.status.message);
                        // Show error message.
                        var alert = new M.core.alert({
                            message: 'Something went wrong, the meeting is not running.'
                        });
                        alert.show();
                        return;
                    }

                    M.mod_bigbluebuttonbn.broker.join_redirect(join_url, e.data.status.message);
                }
            }
        });
    },

    join_redirect: function(join_url) {
        window.open(join_url);
        // Update view.
        setTimeout(function() {
            M.mod_bigbluebuttonbn.rooms.clean_room();
            M.mod_bigbluebuttonbn.rooms.update_room();
        }, 15000);
    },

    recording_action: function(action, recordingid, meetingid) {
        if (action === 'import') {
            this.recording_import(recordingid);
            return;
        }

        if (action === 'delete') {
            this.recording_delete(recordingid);
            return;
        }

        if (action === 'publish') {
            this.recording_publish(recordingid, meetingid);
            return;
        }

        if (action === 'unpublish') {
            this.recording_unpublish(recordingid, meetingid);
            return;
        }
    },

    recording_import: function(recordingid) {
        // Create the confirmation dialogue.
        var confirm = new M.core.confirm({
            modal: true,
            centered: true,
            question: this.recording_confirmation_message('import', recordingid)
        });

        // If it is confirmed.
        confirm.on('complete-yes', function() {
            this.datasource.sendRequest({
                request: "action=recording_import" + "&id=" + recordingid,
                callback: {
                    success: function() {
                        Y.one('#recording-td-' + recordingid).remove();
                    }
                }
            });
        }, this);
    },

    recording_delete: function(recordingid) {
        // Create the confirmation dialogue.
        var confirm = new M.core.confirm({
            modal: true,
            centered: true,
            question: this.recording_confirmation_message('delete', recordingid)
        });

        // If it is confirmed.
        confirm.on('complete-yes', function() {
            this.datasource.sendRequest({
                request: "action=recording_delete" + "&id=" + recordingid,
                callback: {
                    success: function() {
                        Y.one('#recording-td-' + recordingid).remove();
                    }
                }
            });
        }, this);
    },

    recording_publish: function(recordingid, meetingid) {
        this.recording_perform({
            action: 'publish',
            recordingid: recordingid,
            meetingid: meetingid,
            goalstate: 'true'
        });
    },

    recording_unpublish: function(recordingid, meetingid) {
        // Create the confirmation dialogue.
        var confirm = new M.core.confirm({
            modal: true,
            centered: true,
            question: this.recording_confirmation_message('unpublish', recordingid)
        });

        // If it is confirmed.
        confirm.on('complete-yes', function() {
            this.recording_perform({
                action: 'unpublish',
                recordingid: recordingid,
                meetingid: meetingid,
                goalstate: 'false'
            });
        }, this);
    },

    recording_perform: function(payload) {
        M.mod_bigbluebuttonbn.recordings.recording_action_inprocess(payload);
        this.datasource.sendRequest({
            request: "action=recording_" + payload.action + "&id=" + payload.recordingid,
            callback: {
                success: function(e) {
                    if (e.data.status === 'true') {
                        return M.mod_bigbluebuttonbn.broker.recording_action_performed({
                            attempt: 1,
                            action: payload.action,
                            meetingid: payload.meetingid,
                            recordingid: payload.recordingid,
                            goalstate: payload.goalstate
                        });
                    }

                    var alert = new M.core.alert({
                        message: e.data.message
                    });
                    alert.show();
                    return M.mod_bigbluebuttonbn.recordings.recording_action_failed(payload);
                },
                failure: function() {
                    return M.mod_bigbluebuttonbn.recordings.recording_action_failed(payload);
                }
            }
        });
    },

    recording_action_performed: function(payload) {
        this.datasource.sendRequest({
            request: "action=recording_info&id=" + payload.recordingid + "&idx=" + payload.meetingid,
            callback: {
                success: function(e) {
                    var currentstate = e.data.published;
                    if (currentstate === payload.goalstate) {
                        return M.mod_bigbluebuttonbn.recordings.recording_action_completed(payload);
                    }

                    if (payload.attempt < 5) {
                        payload.attempt += 1;
                        return setTimeout(((function() {
                            return function() {
                                M.mod_bigbluebuttonbn.broker.recording_action_performed(payload);
                            };
                        })(this)), (payload.attempt - 1) * 1000);
                    }

                    return M.mod_bigbluebuttonbn.recordings.recording_action_failed(payload);
                },
                failure: function() {
                    return M.mod_bigbluebuttonbn.recordings.recording_action_failed(payload);
                }
            }
        });
    },

    recording_confirmation_message: function(action, recordingid) {

        if (M.mod_bigbluebuttonbn.locales.strings[action + '_confirmation'] === 'undefined') {
            return '';
        }
        var is_imported_link = Y.one('#playbacks-' + recordingid).get('dataset').imported === 'true';
        var recording_type = M.mod_bigbluebuttonbn.locales.strings.recording;
        if (is_imported_link) {
            recording_type = M.mod_bigbluebuttonbn.locales.strings.recording_link;
        }

        var confirmation = M.mod_bigbluebuttonbn.locales.strings[action + '_confirmation'];
        confirmation = confirmation.replace("{$a}", recording_type);

        if (action === 'publish' || action === 'delete') {
            // If it has associated links imported in a different course/activity, show a confirmation dialog.
            var associated_links = Y.one('#recording-link-' + action + '-' + recordingid).get('dataset').links;
            var confirmation_warning = M.mod_bigbluebuttonbn.locales.strings[action + '_confirmation_warning_p'];
            if (associated_links == 1) {
                confirmation_warning = M.mod_bigbluebuttonbn.locales.strings[action + '_confirmation_warning_s'];
            }
            confirmation_warning = confirmation_warning.replace("{$a}", associated_links) + '. ';
            confirmation = confirmation_warning + '\n\n' + confirmation;
        }

        return confirmation;
    },

    end_meeting: function() {
        var qs = 'action=meeting_end&id=' + this.bigbluebuttonbn.meetingid;
        qs += '&bigbluebuttonbn=' + this.bigbluebuttonbn.bigbluebuttonbnid;
        this.datasource.sendRequest({
            request: qs,
            callback: {
                success: function(e) {
                    if (e.data.status) {
                        M.mod_bigbluebuttonbn.rooms.clean_control_panel();
                        M.mod_bigbluebuttonbn.rooms.hide_join_button();
                        M.mod_bigbluebuttonbn.rooms.hide_end_button();
                        location.reload();
                    }
                }
            }
        });
    }
};
