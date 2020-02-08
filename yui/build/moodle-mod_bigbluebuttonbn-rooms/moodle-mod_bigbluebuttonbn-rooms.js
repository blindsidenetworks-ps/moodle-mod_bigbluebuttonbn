YUI.add('moodle-mod_bigbluebuttonbn-rooms', function (Y, NAME) {

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
/** global: opener */

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.rooms = {

    datasource: null,
    bigbluebuttonbn: {},
    panel: null,
    pinginterval: null,

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
        this.pinginterval = bigbluebuttonbn.ping_interval;
        if (this.pinginterval === 0) {
            this.pinginterval = 10000;
        }
        if (this.bigbluebuttonbn.profile_features.indexOf('all') != -1 ||
            this.bigbluebuttonbn.profile_features.indexOf('showroom') != -1) {
            this.initRoom();
        }
    },

    initRoom: function() {
        if (this.bigbluebuttonbn.activity !== 'open') {
            var statusBar = [M.util.get_string('view_message_conference_has_ended', 'bigbluebuttonbn')];
            if (this.bigbluebuttonbn.activity !== 'ended') {
                statusBar = [
                    M.util.get_string('view_message_conference_not_started', 'bigbluebuttonbn'),
                    this.bigbluebuttonbn.opening,
                    this.bigbluebuttonbn.closing
                  ];
            }
            Y.DOM.addHTML(Y.one('#status_bar'), this.initStatusBar(statusBar));
            return;
        }
        this.updateRoom();
    },

    updateRoom: function(f) {
        var updatecache = 'false';
        if (typeof f !== 'undefined' && f) {
            updatecache = 'true';
        }
        var id = this.bigbluebuttonbn.meetingid;
        var bnid = this.bigbluebuttonbn.bigbluebuttonbnid;
        this.datasource.sendRequest({
            request: 'action=meeting_info&id=' + id + '&bigbluebuttonbn=' + bnid + '&updatecache=' + updatecache,
            callback: {
                success: function(e) {
                    Y.DOM.addHTML(Y.one('#status_bar'),
                        M.mod_bigbluebuttonbn.rooms.initStatusBar(e.data.status.message));
                    Y.DOM.addHTML(Y.one('#control_panel'),
                        M.mod_bigbluebuttonbn.rooms.initControlPanel(e.data));
                    if (typeof e.data.status.can_join != 'undefined') {
                        Y.DOM.addHTML(Y.one('#join_button'),
                            M.mod_bigbluebuttonbn.rooms.initJoinButton(e.data.status));
                    }
                    if (typeof e.data.status.can_end != 'undefined' && e.data.status.can_end) {
                        Y.DOM.addHTML(Y.one('#end_button'),
                            M.mod_bigbluebuttonbn.rooms.initEndButton(e.data.status));
                    }
                    if (!e.data.status.can_join) {
                        M.mod_bigbluebuttonbn.rooms.waitModerator({
                            id: id,
                            bnid: bnid
                        });
                    }
                }
            }
        });
    },

    initStatusBar: function(statusMessage) {
        var statusBarSpan = Y.DOM.create('<span id="status_bar_span">');
        if (statusMessage.constructor !== Array) {
            Y.DOM.setText(statusBarSpan, statusMessage);
            return statusBarSpan;
        }
        for (var message in statusMessage) {
            if (!statusMessage.hasOwnProperty(message)) {
                continue; // Skip keys from the prototype.
            }
            var statusBarSpanSpan = Y.DOM.create('<span id="status_bar_span_span">');
            Y.DOM.setText(statusBarSpanSpan, statusMessage[message]);
            Y.DOM.addHTML(statusBarSpan, statusBarSpanSpan);
            Y.DOM.addHTML(statusBarSpan, Y.DOM.create('<br>'));
        }
        return statusBarSpan;
    },

    initControlPanel: function(data) {
        var controlPanelDiv = Y.DOM.create('<div>');
        Y.DOM.setAttribute(controlPanelDiv, 'id', 'control_panel_div');
        var controlPanelDivHtml = '';
        if (data.running) {
            controlPanelDivHtml += this.msgStartedAt(data.info.startTime) + ' ';
            controlPanelDivHtml += this.msgAttendeesIn(data.info.moderatorCount, data.info.participantCount);
        }
        Y.DOM.addHTML(controlPanelDiv, controlPanelDivHtml);
        return (controlPanelDiv);
    },

    msgStartedAt: function(startTime) {
        var startTimestamp = (parseInt(startTime, 10) - parseInt(startTime, 10) % 1000);
        var date = new Date(startTimestamp);
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var startedAt = M.util.get_string('view_message_session_started_at', 'bigbluebuttonbn');
        return startedAt + ' <b>' + hours + ':' + (minutes < 10 ? '0' : '') + minutes + '</b>.';
    },

    msgModeratorsIn: function(moderators) {
        var msgModerators = M.util.get_string('view_message_moderators', 'bigbluebuttonbn');
        if (moderators == 1) {
            msgModerators = M.util.get_string('view_message_moderator', 'bigbluebuttonbn');
        }
        return msgModerators;
    },

    msgViewersIn: function(viewers) {
        var msgViewers = M.util.get_string('view_message_viewers', 'bigbluebuttonbn');
        if (viewers == 1) {
            msgViewers = M.util.get_string('view_message_viewer', 'bigbluebuttonbn');
        }
        return msgViewers;
    },

    msgAttendeesIn: function(moderators, participants) {
        var msgModerators, viewers, msgViewers, msg;
        if (!this.hasParticipants(participants)) {
            return M.util.get_string('view_message_session_no_users', 'bigbluebuttonbn') + '.';
        }
        msgModerators = this.msgModeratorsIn(moderators);
        viewers = participants - moderators;
        msgViewers = this.msgViewersIn(viewers);
        msg = M.util.get_string('view_message_session_has_users', 'bigbluebuttonbn');
        if (participants > 1) {
            return msg + ' <b>' + moderators + '</b> ' + msgModerators + ' ' +
                M.util.get_string('view_message_and', 'bigbluebuttonbn') + ' <b>' + viewers + '</b> ' + msgViewers + '.';
        }
        msg = M.util.get_string('view_message_session_has_user', 'bigbluebuttonbn');
        if (moderators > 0) {
            return msg + ' <b>1</b> ' + msgModerators + '.';
        }
        return msg + ' <b>1</b> ' + msgViewers + '.';
    },

    hasParticipants: function(participants) {
        return (typeof participants != 'undefined' && participants > 0);
    },

    initJoinButton: function(status) {
        var joinButtonInput = Y.DOM.create('<input>');
        Y.DOM.setAttribute(joinButtonInput, 'id', 'join_button_input');
        Y.DOM.setAttribute(joinButtonInput, 'type', 'button');
        Y.DOM.setAttribute(joinButtonInput, 'value', status.join_button_text);
        Y.DOM.setAttribute(joinButtonInput, 'class', 'btn btn-primary');
        var inputHtml = 'M.mod_bigbluebuttonbn.rooms.join(\'' + status.join_url + '\');';
        Y.DOM.setAttribute(joinButtonInput, 'onclick', inputHtml);
        if (!status.can_join) {
            // Disable join button.
            Y.DOM.setAttribute(joinButtonInput, 'disabled', true);
            var statusBarSpan = Y.one('#status_bar_span');
            // Create a img element.
            var spinningWheel = Y.DOM.create('<img>');
            Y.DOM.setAttribute(spinningWheel, 'id', 'spinning_wheel');
            Y.DOM.setAttribute(spinningWheel, 'src', 'pix/i/processing16.gif');
            // Add the spinning wheel.
            Y.DOM.addHTML(statusBarSpan, '&nbsp;');
            Y.DOM.addHTML(statusBarSpan, spinningWheel);
        }
        return joinButtonInput;
    },

    initEndButton: function(status) {
        var endButtonInput = Y.DOM.create('<input>');
        Y.DOM.setAttribute(endButtonInput, 'id', 'end_button_input');
        Y.DOM.setAttribute(endButtonInput, 'type', 'button');
        Y.DOM.setAttribute(endButtonInput, 'value', status.end_button_text);
        Y.DOM.setAttribute(endButtonInput, 'class', 'btn btn-secondary');
        if (status.can_end) {
            Y.DOM.setAttribute(endButtonInput, 'onclick', 'M.mod_bigbluebuttonbn.broker.endMeeting();');
        }
        return endButtonInput;
    },

    endMeeting: function() {
        Y.one('#control_panel_div').remove();
        Y.one('#join_button').hide();
        Y.one('#end_button').hide();
    },

    remoteUpdate: function(delay) {
        setTimeout(function() {
            M.mod_bigbluebuttonbn.rooms.cleanRoom();
            M.mod_bigbluebuttonbn.rooms.updateRoom(true);
        }, delay);
    },

    cleanRoom: function() {
        Y.one('#status_bar_span').remove();
        Y.one('#control_panel_div').remove();
        Y.one('#join_button').setContent('');
        Y.one('#end_button').setContent('');
    },

    windowClose: function() {
        window.onunload = function() {
            opener.M.mod_bigbluebuttonbn.rooms.remoteUpdate(5000);
        };
        window.close();
    },

    waitModerator: function(payload) {
        var pooling = setInterval(function() {
            M.mod_bigbluebuttonbn.rooms.datasource.sendRequest({
                request: "action=meeting_info&id=" + payload.id + "&bigbluebuttonbn=" + payload.bnid,
                callback: {
                    success: function(e) {
                        if (e.data.running) {
                            M.mod_bigbluebuttonbn.rooms.cleanRoom();
                            M.mod_bigbluebuttonbn.rooms.updateRoom();
                            clearInterval(pooling);
                            return;
                        }
                    },
                    failure: function(e) {
                        payload.message = e.error.message;
                    }
                }
            });
        }, this.pinginterval);
    },

    join: function(joinUrl) {
        M.mod_bigbluebuttonbn.broker.joinRedirect(joinUrl);
        // Update view.
        setTimeout(function() {
            M.mod_bigbluebuttonbn.rooms.cleanRoom();
            M.mod_bigbluebuttonbn.rooms.updateRoom(true);
        }, 15000);
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
