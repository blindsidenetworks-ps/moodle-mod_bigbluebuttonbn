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

    join: function(join_url) {
        M.mod_bigbluebuttonbn.broker.join_redirect(join_url);
    },

    join_redirect: function(join_url) {
        window.open(join_url);
        // Update view.
        setTimeout(function() {
            M.mod_bigbluebuttonbn.rooms.clean_room();
            M.mod_bigbluebuttonbn.rooms.update_room(true);
        }, 15000);
    },

    recording_import: function(data) {
        // Create the confirmation dialogue.
        var confirm = new M.core.confirm({
            modal: true,
            centered: true,
            question: this.recording_confirmation_message('import', data.recordingid)
        });

        // If it is confirmed.
        confirm.on('complete-yes', function() {
            this.datasource.sendRequest({
                request: "action=recording_import" + "&id=" + data.recordingid,
                callback: {
                    success: function() {
                        Y.one('#recording-td-' + data.recordingid).remove();
                    }
                }
            });
        }, this);
    },

    recording_action_perform: function(payload) {
        this.datasource.sendRequest({
            request: "action=recording_" + payload.action + "&id=" + payload.recordingid,
            callback: {
                success: function(e) {
                    if (e.data.status) {
                        return M.mod_bigbluebuttonbn.broker.recording_action_performed({
                            attempt: 1,
                            action: payload.action,
                            meetingid: payload.meetingid,
                            recordingid: payload.recordingid,
                            goalstate: payload.goalstate
                        });
                    }

                    payload.message = e.data.message;
                    return M.mod_bigbluebuttonbn.recordings.recording_action_failed(payload);
                },
                failure: function(e) {
                    payload.message = e.error.message;
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
                    var currentstate = M.mod_bigbluebuttonbn.broker.recording_current_state(
                        payload.action, e.data
                    );

                    if (currentstate === null) {
                        payload.message = M.util.get_string('view_error_current_state_not_found', 'bigbluebuttonbn');
                        return M.mod_bigbluebuttonbn.recordings.recording_action_failed(payload);
                    }

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

                    payload.message = M.util.get_string('view_error_action_not_completed', 'bigbluebuttonbn');
                    return M.mod_bigbluebuttonbn.recordings.recording_action_failed(payload);
                },
                failure: function(e) {
                    payload.message = e.error.message;
                    return M.mod_bigbluebuttonbn.recordings.recording_action_failed(payload);
                }
            }
        });
    },

    recording_current_state: function(action, data) {
        if (action === 'publish' || action === 'unpublish') {
            return data.published;
        }

        if (action === 'delete') {
            return data.status;
        }

        if (action === 'secure' || action === 'unsecure') {
            return data.secured;
        }

        if (action === 'update') {
            return data.updated;
        }

        return null;
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
