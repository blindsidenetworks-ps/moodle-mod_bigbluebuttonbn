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

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.rooms = {

    data_source: null,
    polling: null,
    bigbluebuttonbn: {},
    panel: null,

    /**
     * Initialise the broker code.
     *
     * @method init
     */
    init: function(bigbluebuttonbn) {
        console.info("init");
        this.data_source = new Y.DataSource.Get({
            source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
        });
        this.bigbluebuttonbn = bigbluebuttonbn;

        if (this.bigbluebuttonbn.profile_features.includes('all') || this.bigbluebuttonbn.profile_features.includes('showroom')) {
            this.init_room();
        }
    },

    init_room: function() {
        console.info("init_room");
        if (this.bigbluebuttonbn.activity !== 'open') {
            var room_state = "Room ended;"
            var status_bar = [this.bigbluebuttonbn.locales.conference_ended];
            if (bigbluebuttonbn.activity !== 'ended') {
                room_state = "Room is not open";
                status_bar.push(this.bigbluebuttonbn.opening);
                status_bar.push(this.bigbluebuttonbn.closing);
            }
            console.info(room_state);
            Y.DOM.addHTML(Y.one('#status_bar'), M.mod_bigbluebuttonbn.view_init_status_bar(status_bar));
            return;
        }
        console.info("Room open");
        this.init_room_open();
    },

    init_room_open: function() {
        console.info("init_room_open");
        // Create the main modal form.
        this.panel = new Y.Panel({
            srcNode: '#panelContent',
            headerContent: this.bigbluebuttonbn.locales.modal_title,
            width: 250,
            zIndex: 5,
            centered: true,
            modal: true,
            visible: false,
            render: true,
            plugins: [Y.Plugin.Drag]
        });

        // Define the apply function - this will be called when 'Apply' is pressed in the modal form.
        this.panel.addButton({
            value: this.bigbluebuttonbn.locales.modal_button,
            section: Y.WidgetStdMod.FOOTER,
            action: function(e) {
                e.preventDefault();
                this.panel.hide();

                var joinField = Y.one('#meeting_join_url');
                var messageField = Y.one('#meeting_message');
                var nameField = Y.one('#recording_name');
                var descriptionField = Y.one('#recording_description');
                var tagsField = Y.one('#recording_tags');

                // Gatter the fields thay will be passed as metaparameters to the bbb server.
                var name = nameField.get('value').replace(/</g, "&lt;").replace(/>/g, "&gt;");
                var description = descriptionField.get('value').replace(/</g, "&lt;").replace(/>/g, "&gt;");
                var tags = tagsField.get('value').replace(/</g, "&lt;").replace(/>/g, "&gt;");

                // Prepare the new join_url.
                var join_url = joinField.get('value') + '&name=' + name + '&description=' + description + '&tags=' + tags;

                // Executes the join.
                M.mod_bigbluebuttonbn.broker.executeJoin(join_url, messageField.get('value'));

                // Clean values in case the for is used again.
                nameField.set('value', '');
                descriptionField.set('value', '');
                tagsField.set('value', '');
                joinField.set('value', '');
                messageField.set('value', '');
            }
        });

        this.update_room();
    },

    update_room: function() {
        console.info("update_room");

        var status_bar = Y.one('#status_bar');
        var control_panel = Y.one('#control_panel');
        var join_button = Y.one('#join_button');
        var end_button = Y.one('#end_button');
        var qs = 'action=meeting_info';
        qs += '&id=' + this.bigbluebuttonbn.meetingid;
        qs += '&bigbluebuttonbn=' + this.bigbluebuttonbn.bigbluebuttonbnid;
        console.info(qs);
        this.data_source.sendRequest({
            request: qs,
            callback: {
                success: function(e) {
                    console.info("success");
                    Y.DOM.addHTML(status_bar, M.mod_bigbluebuttonbn.rooms.init_status_bar(e.data.status.message));
                    Y.DOM.addHTML(control_panel, M.mod_bigbluebuttonbn.rooms.init_control_panel(e.data));
                    if (typeof e.data.status.can_join != 'undefined') {
                        Y.DOM.addHTML(join_button, M.mod_bigbluebuttonbn.rooms.init_join_button(e.data.status));
                    }
                    if (typeof e.data.status.can_end != 'undefined' && e.data.status.can_end) {
                        Y.DOM.addHTML(end_button, this.init_end_button(e.data.status));
                    }
                },
                failure: function() {
                    console.info("Could not retrieve data: " + e.error.message);
                }
            }
        });
    },

    init_status_bar: function(status_message) {
        var status_bar_span = Y.DOM.create('<span>');

        if (status_message.constructor === Array) {
            for (var message in status_message) {
                if (!status_message.hasOwnProperty(message)) {
                    continue; // Skip keys from the prototype.
                }
                var status_bar_span_span = Y.DOM.create('<span>');
                Y.DOM.setAttribute(status_bar_span_span, 'id', 'status_bar_span_span');
                Y.DOM.setText(status_bar_span_span, status_message[message]);
                Y.DOM.addHTML(status_bar_span, status_bar_span_span);
                Y.DOM.addHTML(status_bar_span, Y.DOM.create('<br>'));
            }
        } else {
            Y.DOM.setAttribute(status_bar_span, 'id', 'status_bar_span');
            Y.DOM.setText(status_bar_span, status_message);
        }

        return status_bar_span;
    },

    init_control_panel: function(data) {
        var control_panel_div = Y.DOM.create('<div>');

        Y.DOM.setAttribute(control_panel_div, 'id', 'control_panel_div');
        var control_panel_div_html = '';
        if (data.running) {
            control_panel_div_html += this.msg_started_at(data.info.startTime) + ' ';
            control_panel_div_html += this.msg_attendees_in(data.info.moderatorCount, data.info.participantCount);
        }
        Y.DOM.addHTML(control_panel_div, control_panel_div_html);

        return (control_panel_div);
    },

    msg_started_at: function(startTime) {

        var start_timestamp = (parseInt(startTime) - parseInt(startTime) % 1000);
        var date = new Date(start_timestamp);
        var hours = date.getHours();
        var minutes = date.getMinutes();

        return M.mod_bigbluebuttonbn.rooms.bigbluebuttonbn.locales.started_at + ' <b>' + hours + ':' + (minutes < 10 ? '0' : '') + minutes + '</b>.';
    },

    msg_attendees_in: function(moderators, participants) {

        if (typeof moderators == 'undefined' && typeof participants == 'undefined') {
            return M.mod_bigbluebuttonbn.rooms.bigbluebuttonbn.locales.session_no_users + '.';
        }

        var viewers = participants - moderators;

        var msg = M.mod_bigbluebuttonbn.rooms.bigbluebuttonbn.locales.session_has_users;

        var msg_moderators = M.mod_bigbluebuttonbn.rooms.bigbluebuttonbn.locales.moderators;
        if (moderators == 1) {
            msg_moderators = M.mod_bigbluebuttonbn.rooms.bigbluebuttonbn.locales.moderator;
        }

        var msg_viewers = M.mod_bigbluebuttonbn.rooms.bigbluebuttonbn.locales.viewers;
        if (moderators == 1) {
            msg_viewers = M.mod_bigbluebuttonbn.rooms.bigbluebuttonbn.locales.viewer;
        }

        if (participants == 1) {
            if (viewers > 0) {
                return msg + ' <b>1</b> ' + msg_viewers + '.';
            }

            return msg + ' <b>1</b> ' + msg_moderators + '.';
        }

        return msg + ' <b>' + moderators + '</b> ' + msg_moderators + ' and <b>' + viewers + '</b> ' + msg_viewers + '.';
    },

    init_join_button: function(status) {
        var join_button_input = Y.DOM.create('<input>');

        Y.DOM.setAttribute(join_button_input, 'id', 'join_button_input');
        Y.DOM.setAttribute(join_button_input, 'type', 'button');
        Y.DOM.setAttribute(join_button_input, 'value', status.join_button_text);

        if (status.can_join) {
            var input_html = 'M.mod_bigbluebuttonbn.broker.join(\'';
            input_html += status.join_url + '\', \'' + M.mod_bigbluebuttonbn.rooms.bigbluebuttonbn.locales.in_progress;
            input_html += '\', ' + status.can_tag + ');';
            Y.DOM.setAttribute(join_button_input, 'onclick', input_html);
        } else {
            Y.DOM.setAttribute(join_button_input, 'disabled', true);
            M.mod_bigbluebuttonbn.broker.waitModerator(status.join_url);
        }

        return join_button_input;
    },

    init_end_button: function(status) {
        var end_button_input = Y.DOM.create('<input>');

        Y.DOM.setAttribute(end_button_input, 'id', 'end_button_input');
        Y.DOM.setAttribute(end_button_input, 'type', 'button');
        Y.DOM.setAttribute(end_button_input, 'value', status.end_button_text);
        if (status.can_end) {
            Y.DOM.setAttribute(end_button_input, 'onclick', 'M.mod_bigbluebuttonbn.broker.endMeeting();');
        }

        return end_button_input;
    }

};
