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

var bigbluebuttonbn_ds;
var bigbluebuttonbn_panel;

M.mod_bigbluebuttonbn.datasource_init = function(Y) {
    bigbluebuttonbn_ds = new Y.DataSource.Get({
        source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
    });
};

M.mod_bigbluebuttonbn.datatable_init = function() {
    /* global bigbluebuttonbn */

    var options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    for (var i = 0; i < bigbluebuttonbn.data.length; i++) {
        var date = new Date(bigbluebuttonbn.data[i].date);
        bigbluebuttonbn.data[i].date = date.toLocaleDateString(bigbluebuttonbn.locale, options);
    }

    YUI({
        lang: bigbluebuttonbn.locale
    }).use('datatable', 'datatable-sort', 'datatable-paginator', 'datatype-number', function(Y) {
        var table = new Y.DataTable({
            width: "1075px",
            columns: bigbluebuttonbn.columns,
            data: bigbluebuttonbn.data,
            rowsPerPage: 10,
            paginatorLocation: ['header', 'footer']
        }).render('#bigbluebuttonbn_yui_table');
        return table;
    });
};

M.mod_bigbluebuttonbn.import_view_init = function(Y) {
    /* global bigbluebuttonbn */

    // Init general datasource.
    M.mod_bigbluebuttonbn.datasource_init(Y);

    // Init event listener for course selector.
    Y.one('#menuimport_recording_links_select').on('change', function() {
        var endpoint = '/mod/bigbluebuttonbn/import_view.php';
        var qs = '?bn=' + bigbluebuttonbn.bn + '&tc=' + this.get('value');
        Y.config.win.location = M.cfg.wwwroot + endpoint + qs;
    });
};

M.mod_bigbluebuttonbn.view_init = function(Y) {
    /* global bigbluebuttonbn */

    // Init general datasource.
    M.mod_bigbluebuttonbn.datasource_init(Y);

    if (bigbluebuttonbn.profile_features.includes('all') || bigbluebuttonbn.profile_features.includes('showroom')) {

        if (bigbluebuttonbn.activity === 'open') {
            // Create the main modal form.
            bigbluebuttonbn_panel = new Y.Panel({
                srcNode: '#panelContent',
                headerContent: bigbluebuttonbn.locales.modal_title,
                width: 250,
                zIndex: 5,
                centered: true,
                modal: true,
                visible: false,
                render: true,
                plugins: [Y.Plugin.Drag]
            });

            // Define the apply function -  this will be called when 'Apply' is pressed in the modal form.
            bigbluebuttonbn_panel.addButton({
                value: bigbluebuttonbn.locales.modal_button,
                section: Y.WidgetStdMod.FOOTER,
                action: function(e) {
                    e.preventDefault();
                    bigbluebuttonbn_panel.hide();

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

            M.mod_bigbluebuttonbn.view_update();
        } else {
            if (bigbluebuttonbn.activity === 'ended') {
                Y.DOM.addHTML(Y.one('#status_bar'), M.mod_bigbluebuttonbn.view_init_status_bar(
                    bigbluebuttonbn.locales.conference_ended
                ));
            } else {
                Y.DOM.addHTML(Y.one('#status_bar'), M.mod_bigbluebuttonbn.view_init_status_bar(
                    [bigbluebuttonbn.locales.conference_ended, bigbluebuttonbn.opening, bigbluebuttonbn.closing]
                ));
            }
        }
    }

    if (bigbluebuttonbn.recordings_html === false &&
        (bigbluebuttonbn.profile_features.includes('all') || bigbluebuttonbn.profile_features.includes('showrecordings'))) {
        M.mod_bigbluebuttonbn.datatable_init(Y);
    }
};

M.mod_bigbluebuttonbn.view_update = function() {
    /* global bigbluebuttonbn */

    var status_bar = Y.one('#status_bar');
    var control_panel = Y.one('#control_panel');
    var join_button = Y.one('#join_button');
    var end_button = Y.one('#end_button');

    bigbluebuttonbn_ds.sendRequest({
        request: 'action=meeting_info&id=' + bigbluebuttonbn.meetingid + '&bigbluebuttonbn=' + bigbluebuttonbn.bigbluebuttonbnid,
        callback: {
            success: function(e) {
                Y.DOM.addHTML(status_bar, M.mod_bigbluebuttonbn.view_init_status_bar(e.data.status.message));
                Y.DOM.addHTML(control_panel, M.mod_bigbluebuttonbn.view_init_control_panel(e.data));
                if (typeof e.data.status.can_join != 'undefined') {
                    Y.DOM.addHTML(join_button, M.mod_bigbluebuttonbn.view_init_join_button(e.data.status));
                }
                if (typeof e.data.status.can_end != 'undefined' && e.data.status.can_end) {
                    Y.DOM.addHTML(end_button, M.mod_bigbluebuttonbn.view_init_end_button(e.data.status));
                }
            }
        }
    });
};

M.mod_bigbluebuttonbn.view_clean = function() {
    M.mod_bigbluebuttonbn.view_clean_status_bar();
    M.mod_bigbluebuttonbn.view_clean_control_panel();
    M.mod_bigbluebuttonbn.view_clean_join_button();
    M.mod_bigbluebuttonbn.view_clean_end_button();
};

M.mod_bigbluebuttonbn.view_remote_update = function(delay) {
    setTimeout(function() {
        M.mod_bigbluebuttonbn.view_clean();
        M.mod_bigbluebuttonbn.view_update();
    }, delay);
};

M.mod_bigbluebuttonbn.view_init_status_bar = function(status_message) {
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

    return (status_bar_span);
};

M.mod_bigbluebuttonbn.view_init_control_panel = function(data) {
    var control_panel_div = Y.DOM.create('<div>');

    Y.DOM.setAttribute(control_panel_div, 'id', 'control_panel_div');
    var control_panel_div_html = '';
    if (data.running) {
        control_panel_div_html += M.mod_bigbluebuttonbn.view_msg_started_at(data.info.startTime) + ' ';
        control_panel_div_html += M.mod_bigbluebuttonbn.view_msg_attendees_in(data.info.moderatorCount,
            data.info.participantCount);
    }
    Y.DOM.addHTML(control_panel_div, control_panel_div_html);

    return (control_panel_div);
};

M.mod_bigbluebuttonbn.view_msg_started_at = function(startTime) {
    /* global bigbluebuttonbn */

    var start_timestamp = (parseInt(startTime) - parseInt(startTime) % 1000);
    var date = new Date(start_timestamp);
    var hours = date.getHours();
    var minutes = date.getMinutes();

    return bigbluebuttonbn.locales.started_at + ' <b>' + hours + ':' + (minutes < 10 ? '0' : '') + minutes + '</b>.';
};

M.mod_bigbluebuttonbn.view_msg_users_joined = function(participantCount) {
    /* global bigbluebuttonbn */

    var participants = parseInt(participantCount);
    var msg_users_joined = '<b>' + participants + '</b> ';
    if (participants == 1) {
        msg_users_joined += bigbluebuttonbn.locales.user + ' ' + bigbluebuttonbn.locales.has_joined + '.';
    } else {
        msg_users_joined += bigbluebuttonbn.locales.users + ' ' + bigbluebuttonbn.locales.have_joined + '.';
    }
    return msg_users_joined;
};

M.mod_bigbluebuttonbn.view_msg_attendees_in = function(moderators, participants) {
    /* global bigbluebuttonbn */

    if (typeof moderators == 'undefined' && typeof participants == 'undefined') {
        return bigbluebuttonbn.locales.session_no_users + '.';
    }

    var viewers = participants - moderators;

    if (participants == 1) {
        if (viewers > 0) {
            return bigbluebuttonbn.locales.session_has_user + ' <b>1</b> ' + bigbluebuttonbn.locales.viewer + '.';
        }

        return bigbluebuttonbn.locales.session_has_user + ' <b>1</b> ' + bigbluebuttonbn.locales.moderator + '.';
    }

    var msg = bigbluebuttonbn.locales.session_has_users;

    var msg_moderators = bigbluebuttonbn.locales.moderators;
    if (moderators == 1) {
        msg_moderators = bigbluebuttonbn.locales.moderator;
    }

    var msg_viewers = bigbluebuttonbn.locales.viewers;
    if (moderators == 1) {
        msg_viewers = bigbluebuttonbn.locales.viewer;
    }

    return msg + ' <b>' + moderators + '</b> ' + msg_moderators + ' and <b>' + viewers + '</b> ' + msg_viewers + '.';
};

M.mod_bigbluebuttonbn.view_init_join_button = function(status) {
    var join_button_input = Y.DOM.create('<input>');

    Y.DOM.setAttribute(join_button_input, 'id', 'join_button_input');
    Y.DOM.setAttribute(join_button_input, 'type', 'button');
    Y.DOM.setAttribute(join_button_input, 'value', status.join_button_text);

    if (status.can_join) {
        var input_html = 'M.mod_bigbluebuttonbn.broker.join(\'';
        input_html += status.join_url + '\', \'' + bigbluebuttonbn.locales.in_progress;
        input_html += '\', ' + status.can_tag + ');';
        Y.DOM.setAttribute(join_button_input, 'onclick', input_html);
    } else {
        Y.DOM.setAttribute(join_button_input, 'disabled', true);
        M.mod_bigbluebuttonbn.broker.waitModerator(status.join_url);
    }

    return join_button_input;
};

M.mod_bigbluebuttonbn.view_init_end_button = function(status) {
    var end_button_input = Y.DOM.create('<input>');

    Y.DOM.setAttribute(end_button_input, 'id', 'end_button_input');
    Y.DOM.setAttribute(end_button_input, 'type', 'button');
    Y.DOM.setAttribute(end_button_input, 'value', status.end_button_text);
    if (status.can_end) {
        Y.DOM.setAttribute(end_button_input, 'onclick', 'M.mod_bigbluebuttonbn.broker.endMeeting();');
    }

    return end_button_input;
};


M.mod_bigbluebuttonbn.view_clean_status_bar = function() {
    Y.one('#status_bar_span').remove();
};

M.mod_bigbluebuttonbn.view_clean_control_panel = function() {
    Y.one('#control_panel_div').remove();
};

M.mod_bigbluebuttonbn.view_clean_join_button = function() {
    Y.one('#join_button').setContent('');
};

M.mod_bigbluebuttonbn.view_hide_join_button = function() {
    Y.DOM.setStyle(Y.one('#join_button'), 'visibility', 'hidden');
};

M.mod_bigbluebuttonbn.view_show_join_button = function() {
    Y.DOM.setStyle(Y.one('#join_button'), 'visibility', 'shown');
};

M.mod_bigbluebuttonbn.view_clean_end_button = function() {
    Y.one('#end_button').setContent('');
};

M.mod_bigbluebuttonbn.view_hide_end_button = function() {
    Y.DOM.setStyle(Y.one('#end_button'), 'visibility', 'hidden');
};

M.mod_bigbluebuttonbn.view_show_end_button = function() {
    Y.DOM.setStyle(Y.one('#end_button'), 'visibility', 'shown');
};

M.mod_bigbluebuttonbn.view_windowClose = function() {
    window.onunload = function() {
        /* global: opener */
        opener.M.mod_bigbluebuttonbn.view_remote_update(5000);
    };
    window.close();
};
