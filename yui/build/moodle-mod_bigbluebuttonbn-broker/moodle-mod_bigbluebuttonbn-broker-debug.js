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

    join_redirect: function(join_url) {
        window.open(join_url);
    },

    recording_action_perform: function(data) {
        var qs = "action=recording_" + data.action + "&id=" + data.recordingid + "&idx=" + data.meetingid;
        qs += this.recording_action_meta_qs(data);
        data.attempt = 1;
        if (typeof data.attempts === 'undefined') {
            data.attempts = 5;
        }
        this.datasource.sendRequest({
            request: qs,
            callback: {
                success: function(e) {
                    // Something went wrong.
                    if (!e.data.status) {
                        data.message = e.data.message;
                        return M.mod_bigbluebuttonbn.recordings.recording_action_failover(data);
                    }
                    // There is no need for verification
                    if (typeof data.goalstate === 'undefined') {
                        return M.mod_bigbluebuttonbn.recordings.recording_action_completion(data);
                    }
                    // Use the current response for verification
                    if (data.attempts <= 1) {
                        return M.mod_bigbluebuttonbn.broker.recording_action_performed_complete(e, data);
                    }
                    // Iterate the verification.
                    return M.mod_bigbluebuttonbn.broker.recording_action_performed_validate(data);
                },
                failure: function(e) {
                    data.message = e.error.message;
                    return M.mod_bigbluebuttonbn.recordings.recording_action_failover(data);
                }
            }
        });
    },

    recording_action_meta_qs: function(data) {
        var qs = '';
        if (typeof data.source !== 'undefined') {
            var meta = {};
            meta[data.source] = encodeURIComponent(data.goalstate);
            qs += "&meta=" + JSON.stringify(meta);
        }
        return qs;
    },

    recording_action_performed_validate: function(data) {
        var qs = "action=recording_info&id=" + data.recordingid + "&idx=" + data.meetingid;
        this.datasource.sendRequest({
            request: qs,
            callback: {
                success: function(e) {
                    // Evaluates if the current attempt has been completed.
                    if (M.mod_bigbluebuttonbn.broker.recording_action_performed_complete(e, data)) {
                        // It has been completed, so stop the action.
                        return;
                    }
                    // Evaluates if more attempts have to be performed.
                    if (data.attempt < data.attempts) {
                        data.attempt += 1;
                        setTimeout(((function() {
                            return function() {
                                M.mod_bigbluebuttonbn.broker.recording_action_performed_validate(data);
                            };
                        })(this)), (data.attempt - 1) * 1000);
                        return;
                    }
                    // No more attempts to perform, it stops with failing over.
                    data.message = M.util.get_string('view_error_action_not_completed', 'bigbluebuttonbn');
                    M.mod_bigbluebuttonbn.recordings.recording_action_failover(data);
                },
                failure: function(e) {
                    data.message = e.error.message;
                    M.mod_bigbluebuttonbn.recordings.recording_action_failover(data);
                }
            }
        });
    },

    recording_action_performed_complete: function(e, data) {
        // Something went wrong.
        if (typeof e.data[data.source] === 'undefined') {
            data.message = M.util.get_string('view_error_current_state_not_found', 'bigbluebuttonbn');
            M.mod_bigbluebuttonbn.recordings.recording_action_failover(data);
            return true;
        }
        // Evaluates if the state is as expected.
        if (e.data[data.source] === data.goalstate) {
            M.mod_bigbluebuttonbn.recordings.recording_action_completion(data);
            return true;
        }
        return;
    },

    recording_current_state: function(action, data) {
        if (action === 'publish' || action === 'unpublish') {
            return data.published;
        }

        if (action === 'delete') {
            return data.status;
        }

        if (action === 'protect' || action === 'unprotect') {
            return data.secured; // The broker responds with secured as protected is a reserverd word.
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
                        M.mod_bigbluebuttonbn.rooms.end_meeting();
                        location.reload();
                    }
                }
            }
        });
    }
};


}, '@VERSION@', {
    "requires": [
        "base",
        "node",
        "datasource-get",
        "datasource-jsonschema",
        "datasource-polling",
        "moodle-core-notification"
    ]
});
