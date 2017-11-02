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

M.mod_bigbluebuttonbn.rooms = {

    datasource: null,
    bigbluebuttonbn: {},
    panel: null,
    pinginterval: null,

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
        this.pinginterval = bigbluebuttonbn.ping_interval;
        if (this.pinginterval === 0) {
            this.pinginterval = 10000;
        }

        if (this.bigbluebuttonbn.profile_features.includes('all') || this.bigbluebuttonbn.profile_features.includes('showroom')) {
            this.initRoom();
        }
    },

    initRoom: function() {
        if (this.bigbluebuttonbn.activity !== 'open') {
            var status_bar = [M.util.get_string('view_message_conference_has_ended', 'bigbluebuttonbn')];
            if (this.bigbluebuttonbn.activity !== 'ended') {
                status_bar = [
                    M.util.get_string('view_message_conference_not_started', 'bigbluebuttonbn'),
                    this.bigbluebuttonbn.opening,
                    this.bigbluebuttonbn.closing
                  ];
            }
            Y.DOM.addHTML(Y.one('#status_bar'), this.initStatusBar(status_bar));
            return;
        }
        this.updateRoom();
    },

    updateRoom: function(f) {
        var forced = 'false';
        if (typeof f !== 'undefined' && f) {
            forced = 'true';
        }
        var id = this.bigbluebuttonbn.meetingid;
        var bnid = this.bigbluebuttonbn.bigbluebuttonbnid;

        this.datasource.sendRequest({
            request: 'action=meeting_info&id=' + id + '&bigbluebuttonbn=' + bnid + '&forced=' + forced,
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

    initStatusBar: function(status_message) {
        var status_bar_span = Y.DOM.create('<span id="status_bar_span">');

        if (status_message.constructor !== Array) {
            Y.DOM.setText(status_bar_span, status_message);
            return status_bar_span;
        }

        for (var message in status_message) {
            if (!status_message.hasOwnProperty(message)) {
                continue; // Skip keys from the prototype.
            }
            var status_bar_span_span = Y.DOM.create('<span id="status_bar_span_span">');
            Y.DOM.setText(status_bar_span_span, status_message[message]);
            Y.DOM.addHTML(status_bar_span, status_bar_span_span);
            Y.DOM.addHTML(status_bar_span, Y.DOM.create('<br>'));
        }
        return status_bar_span;
    },

    initControlPanel: function(data) {
        var control_panel_div = Y.DOM.create('<div>');

        Y.DOM.setAttribute(control_panel_div, 'id', 'control_panel_div');
        var control_panel_div_html = '';
        if (data.running) {
            control_panel_div_html += this.msgStartedAt(data.info.startTime) + ' ';
            control_panel_div_html += this.msgAttendeesIn(data.info.moderatorCount, data.info.participantCount);
        }
        Y.DOM.addHTML(control_panel_div, control_panel_div_html);

        return (control_panel_div);
    },

    msgStartedAt: function(startTime) {
        var start_timestamp = (parseInt(startTime, 10) - parseInt(startTime, 10) % 1000);
        var date = new Date(start_timestamp);
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var started_at = M.util.get_string('view_message_session_started_at', 'bigbluebuttonbn');

        return started_at + ' <b>' + hours + ':' + (minutes < 10 ? '0' : '') + minutes + '</b>.';
    },

    msgModeratorsIn: function(moderators) {
        var msg_moderators = M.util.get_string('view_message_moderators', 'bigbluebuttonbn');
        if (moderators == 1) {
            msg_moderators = M.util.get_string('view_message_moderator', 'bigbluebuttonbn');
        }
        return msg_moderators;
    },

    msgViewersIn: function(viewers) {
        var msg_viewers = M.util.get_string('view_message_viewers', 'bigbluebuttonbn');
        if (viewers == 1) {
            msg_viewers = M.util.get_string('view_message_viewer', 'bigbluebuttonbn');
        }
        return msg_viewers;
    },

    msgAttendeesIn: function(moderators, participants) {
        var msg_moderators, viewers, msg_viewers, msg;
        if (!this.hasParticipants(participants)) {
            return M.util.get_string('view_message_session_no_users', 'bigbluebuttonbn') + '.';
        }
        msg_moderators = this.msgModeratorsIn(moderators);
        viewers = participants - moderators;
        msg_viewers = this.msgViewersIn(viewers);
        msg = M.util.get_string('view_message_session_has_users', 'bigbluebuttonbn');
        if (participants > 1) {
            return msg + ' <b>' + moderators + '</b> ' + msg_moderators + ' and <b>' + viewers + '</b> ' + msg_viewers + '.';
        }
        msg = M.util.get_string('view_message_session_has_user', 'bigbluebuttonbn');
        if (moderators > 0) {
            return msg + ' <b>1</b> ' + msg_moderators + '.';
        }
        return msg + ' <b>1</b> ' + msg_viewers + '.';
    },

    hasParticipants: function(participants) {
        return (typeof participants != 'undefined' && participants > 0);
    },

    initJoinButton: function(status) {
        var join_button_input = Y.DOM.create('<input>');

        Y.DOM.setAttribute(join_button_input, 'id', 'join_button_input');
        Y.DOM.setAttribute(join_button_input, 'type', 'button');
        Y.DOM.setAttribute(join_button_input, 'value', status.join_button_text);
        Y.DOM.setAttribute(join_button_input, 'class', 'btn btn-primary');

        var input_html = 'M.mod_bigbluebuttonbn.rooms.join(\'' + status.join_url + '\');';
        Y.DOM.setAttribute(join_button_input, 'onclick', input_html);

        if (!status.can_join) {
            // Disable join button.
            Y.DOM.setAttribute(join_button_input, 'disabled', true);
            var status_bar_span = Y.one('#status_bar_span');
            // Create a img element.
            var spinning_wheel = Y.DOM.create('<img>');
            Y.DOM.setAttribute(spinning_wheel, 'id', 'spinning_wheel');
            Y.DOM.setAttribute(spinning_wheel, 'src', 'pix/i/processing16.gif');
            // Add the spinning wheel.
            Y.DOM.addHTML(status_bar_span, '&nbsp;');
            Y.DOM.addHTML(status_bar_span, spinning_wheel);
        }

        return join_button_input;
    },

    initEndButton: function(status) {
        var end_button_input = Y.DOM.create('<input>');

        Y.DOM.setAttribute(end_button_input, 'id', 'end_button_input');
        Y.DOM.setAttribute(end_button_input, 'type', 'button');
        Y.DOM.setAttribute(end_button_input, 'value', status.end_button_text);
        Y.DOM.setAttribute(end_button_input, 'class', 'btn btn-secondary');
        if (status.can_end) {
            Y.DOM.setAttribute(end_button_input, 'onclick', 'M.mod_bigbluebuttonbn.broker.endMeeting();');
        }

        return end_button_input;
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
            /* global: opener */
            opener.M.mod_bigbluebuttonbn.rooms.remoteUpdate(5000);
        };
        window.close();
    },

    waitModerator: function(payload) {
        this.datasource.sendRequest({
            request: "action=meeting_info&id=" + payload.id + "&bigbluebuttonbn=" + payload.bnid,
            callback: {
                success: function(e) {
                    if (e.data.running) {
                        M.mod_bigbluebuttonbn.rooms.cleanRoom();
                        M.mod_bigbluebuttonbn.rooms.updateRoom();
                    }

                    return setTimeout(((function() {
                        return function() {
                            M.mod_bigbluebuttonbn.rooms.waitModerator(payload);
                        };
                    })(this)), M.mod_bigbluebuttonbn.rooms.pinginterval);
                },
                failure: function(e) {
                    payload.message = e.error.message;
                }
            }
        });
    },

    join: function(join_url) {
        M.mod_bigbluebuttonbn.broker.joinRedirect(join_url);
        // Update view.
        setTimeout(function() {
            M.mod_bigbluebuttonbn.rooms.cleanRoom();
            M.mod_bigbluebuttonbn.rooms.updateRoom(true);
        }, 15000);
    }
};
