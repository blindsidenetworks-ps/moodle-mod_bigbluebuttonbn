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
     * @param {object} bigbluebuttonbn
     */
    init: function(bigbluebuttonbn) {
        this.datasource = new Y.DataSource.Get({
            source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_ajax.php?sesskey=" + M.cfg.sesskey + "&"
        });
        this.bigbluebuttonbn = bigbluebuttonbn;
    },

    joinRedirect: function(joinUrl) {
        window.open(joinUrl);
    },

    recordingActionPerform: function(data) {
        var qs = "action=recording_" + data.action + "&id=" + data.recordingid + "&idx=" + data.meetingid;
        qs += this.recordingActionMetaQS(data);
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
                        return M.mod_bigbluebuttonbn.recordings.recordingActionFailover(data);
                    }
                    // There is no need for verification.
                    if (typeof data.goalstate === 'undefined') {
                        return M.mod_bigbluebuttonbn.recordings.recordingActionCompletion(data);
                    }
                    // Use the current response for verification.
                    if (data.attempts <= 1) {
                        return M.mod_bigbluebuttonbn.broker.recordingActionPerformedComplete(e, data);
                    }
                    // Iterate the verification.
                    return M.mod_bigbluebuttonbn.broker.recordingActionPerformedValidate(data);
                },
                failure: function(e) {
                    data.message = e.error.message;
                    return M.mod_bigbluebuttonbn.recordings.recordingActionFailover(data);
                }
            }
        });
    },

    recordingActionMetaQS: function(data) {
        var qs = '';
        if (typeof data.source !== 'undefined') {
            var meta = {};
            meta[data.source] = encodeURIComponent(data.goalstate);
            qs += "&meta=" + JSON.stringify(meta);
        }
        return qs;
    },

    recordingActionPerformedValidate: function(data) {
        var qs = "action=recording_info&id=" + data.recordingid + "&idx=" + data.meetingid;
        qs += this.recordingActionMetaQS(data);
        this.datasource.sendRequest({
            request: qs,
            callback: {
                success: function(e) {
                    // Evaluates if the current attempt has been completed.
                    if (M.mod_bigbluebuttonbn.broker.recordingActionPerformedComplete(e, data)) {
                        // It has been completed, so stop the action.
                        return;
                    }
                    // Evaluates if more attempts have to be performed.
                    if (data.attempt < data.attempts) {
                        data.attempt += 1;
                        setTimeout(((function() {
                            return function() {
                                M.mod_bigbluebuttonbn.broker.recordingActionPerformedValidate(data);
                            };
                        })(this)), (data.attempt - 1) * 1000);
                        return;
                    }
                    // No more attempts to perform, it stops with failing over.
                    data.message = M.util.get_string('view_error_action_not_completed', 'bigbluebuttonbn');
                    M.mod_bigbluebuttonbn.recordings.recordingActionFailover(data);
                },
                failure: function(e) {
                    data.message = e.error.message;
                    M.mod_bigbluebuttonbn.recordings.recordingActionFailover(data);
                }
            }
        });
    },

    recordingActionPerformedComplete: function(e, data) {
        // Something went wrong.
        if (typeof e.data[data.source] === 'undefined') {
            data.message = M.util.get_string('view_error_current_state_not_found', 'bigbluebuttonbn');
            M.mod_bigbluebuttonbn.recordings.recordingActionFailover(data);
            return true;
        }
        // Evaluates if the state is as expected.
        if (e.data[data.source] === data.goalstate) {
            M.mod_bigbluebuttonbn.recordings.recordingActionCompletion(data);
            return true;
        }
        return false;
    },

    recordingCurrentState: function(action, data) {
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

    endMeeting: function() {
        var qs = 'action=meeting_end&id=' + this.bigbluebuttonbn.meetingid;
        qs += '&bigbluebuttonbn=' + this.bigbluebuttonbn.bigbluebuttonbnid;
        this.datasource.sendRequest({
            request: qs,
            callback: {
                success: function(e) {
                    if (e.data.status) {
                        M.mod_bigbluebuttonbn.rooms.endMeeting();
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
