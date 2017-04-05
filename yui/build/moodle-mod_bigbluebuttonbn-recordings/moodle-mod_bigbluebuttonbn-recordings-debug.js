YUI.add('moodle-mod_bigbluebuttonbn-recordings', function (Y, NAME) {

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

M.mod_bigbluebuttonbn.recordings = {

    datasource: null,
    locale: 'en',
    profilefeatures: {},
    datatable: {},

    /**
     * Initialise recordings code.
     *
     * @method init
     */
    init: function(data) {
        this.datasource = new Y.DataSource.Get({
            source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
        });
        this.locale = data.locale;
        this.profilefeatures = data.profile_features;
        this.datatable.columns = data.columns;
        this.datatable.data = data.data;

        if (data.recordings_html === false &&
            (this.profilefeatures.includes('all') || this.profilefeatures.includes('showrecordings'))) {
            this.datatable_init();
        }
    },

    datatable_init: function() {
        var options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        var columns = this.datatable.columns;
        var data = this.datatable.data;
        for (var i = 0; i < data.length; i++) {
            var date = new Date(data[i].date);
            data[i].date = date.toLocaleDateString(this.locale, options);
        }

        YUI({
            lang: this.locale
        }).use('datatable', 'datatable-sort', 'datatable-paginator', 'datatype-number', function(Y) {
            var table = new Y.DataTable({
                width: "1075px",
                columns: columns,
                data: data,
                rowsPerPage: 10,
                paginatorLocation: ['header', 'footer']
            }).render('#bigbluebuttonbn_yui_table');
            return table;
        });
    },

    recording_action: function(element, confirmation, extras) {
        var payload = this.recording_element_payload(element);

        // Add extras to payload.
        for (var extra in extras) {
            if (extras.hasOwnProperty(extra)) {
                payload[extra] = extras[extra];
            }
        }

        // The action doesn;t require confirmation.
        if (!confirmation) {
            this.recording_action_perform(payload);
            return;
        }

        // Create the confirmation dialogue.
        var confirm = new M.core.confirm({
            modal: true,
            centered: true,
            question: this.recording_confirmation_message(payload.action, payload.recordingid)
        });

        // If it is confirmed.
        confirm.on('complete-yes', function() {
            this.recording_action_perform(payload);
        }, this);
    },

    recording_element_payload: function(element) {
        var nodeelement = Y.one(element);
        var node = nodeelement.ancestor('div');
        return {
            action: nodeelement.getAttribute('data-action'),
            recordingid: node.getAttribute('data-recordingid'),
            meetingid: node.getAttribute('data-meetingid')
        };
    },

    recording_action_perform: function(data) {
        M.mod_bigbluebuttonbn.recordings.recording_action_inprocess(data);
        M.mod_bigbluebuttonbn.broker.recording_action_perform(data);
    },

    recording_publish: function(element) {
        var extras = {
            source: 'published',
            goalstate: true
        };
        this.recording_action(element, false, extras);
    },

    recording_unpublish: function(element) {
        var extras = {
            source: 'published',
            goalstate: false
        };
        this.recording_action(element, true, extras);
    },

    recording_delete: function(element) {
        var extras = {
            source: 'status',
            goalstate: false
        };
        this.recording_action(element, true, extras);
    },

    recording_import: function(element) {
        var extras = {};
        this.recording_action(element, true, extras);
    },

    recording_update: function(element) {
        console.info('Updating...');
        var nodeelement = Y.one(element);
        var node = nodeelement.ancestor('div');
        var extras = {
            target: node.getAttribute('data-target'),
            source: node.getAttribute('data-source'),
            goalstate: nodeelement.getAttribute('data-goalstate')
        };
        this.recording_action(element, false, extras);
    },

    recording_edit: function(element) {
        var nodelink = Y.one(element);
        var node = nodelink.ancestor('div');
        var nodetext = node.one('> span');

        nodetext.hide();
        nodelink.hide();

        var nodeinputtext = Y.Node.create('<input type="text" class="form-control"></input>');
        nodeinputtext.setAttribute('id', nodelink.getAttribute('id'));
        nodeinputtext.setAttribute('value', nodetext.getHTML());
        nodeinputtext.setAttribute('data-value', nodetext.getHTML());
        nodeinputtext.setAttribute('onkeydown', 'M.mod_bigbluebuttonbn.recordings.recording_edit_keydown(this);');
        node.append(nodeinputtext);
    },

    recording_edit_keydown: function(element) {
        /** global: event */

        if (event.keyCode == 13) {
            var text = element.value;
            var nodeinputtext = Y.one(element);
            var node = nodeinputtext.ancestor('div');
            var nodetext = node.one('> span');
            var nodelink = node.one('> a');
            setTimeout((function() {
                // Perform the update.
                nodeinputtext.setAttribute('data-action', 'edit');
                nodeinputtext.setAttribute('data-goalstate', text);
                M.mod_bigbluebuttonbn.recordings.recording_update(nodeinputtext.getDOMNode());
                nodeinputtext.hide();
                nodetext.setHTML(text);
                nodetext.show();
                nodelink.show();
            })(this), 0);
        }
    },

    recording_edit_failover: function(element) {
        var nodelink = Y.one(element);
        var node = nodelink.ancestor('div');
        var nodetext = node.one('> span');
        if (typeof nodetext === 'undefined') {
            return;
        }

        var nodeinputtext = node.one('> input');
        nodetext.setHTML(nodeinputtext.getAttribute('data-value'));
        nodeinputtext.remove();
    },

    recording_confirmation_message: function(action, recordingid) {
        var confirmation = M.util.get_string('view_recording_' + action + '_confirmation', 'bigbluebuttonbn');
        if (typeof confirmation === 'undefined') {
            return '';
        }
        var is_imported_link = Y.one('#playbacks-' + recordingid).get('dataset').imported === 'true';
        var recording_type = M.util.get_string('view_recording', 'bigbluebuttonbn');
        if (is_imported_link) {
            recording_type = M.util.get_string('view_recording_link', 'bigbluebuttonbn');
        }

        confirmation = confirmation.replace("{$a}", recording_type);

        if (action !== 'publish' && action !== 'delete') {
            return confirmation;
        }

        // If it has associated links imported in a different course/activity, show that in confirmation dialog.
        var associated_links = Y.one('a#recording-' + action + '-' + recordingid).get('dataset').links;
        if (associated_links == 0) {
            return confirmation;
        }

        var confirmation_warning = M.util.get_string('view_recording_' + action + '_confirmation_warning_p',
            'bigbluebuttonbn');
        if (associated_links == 1) {
            confirmation_warning = M.util.get_string('view_recording_' + action + '_confirmation_warning_s',
                'bigbluebuttonbn');
        }
        confirmation_warning = confirmation_warning.replace("{$a}", associated_links) + '. ';
        return confirmation_warning + '\n\n' + confirmation;
    },

    recording_action_inprocess: function(data) {
        var elementid = this.recording_action_elementid(data);

        var nodebutton = Y.one('img#recording-' + elementid + '-' + data.recordingid);
        var text = M.util.get_string('view_recording_list_action_' + data.action, 'bigbluebuttonbn');
        nodebutton.setAttribute('alt', text);
        nodebutton.setAttribute('title', text);
        nodebutton.setAttribute('data-src', nodebutton.getAttribute('src'));
        nodebutton.setAttribute('src', M.cfg.wwwroot + "/mod/bigbluebuttonbn/pix/processing16.gif");

        var nodelink = Y.one('a#recording-' + elementid + '-' + data.recordingid);
        nodelink.setAttribute('data-onlcick', nodelink.getAttribute('onclick'));
        nodelink.setAttribute('onclick', '');
    },

    recording_action_completion: function(data) {
        var elementid = this.recording_action_elementid(data);

        // If action = delete or action = import, delete the row on completion.
        if (data.action == 'delete' || data.action == 'import') {
            this.recording_deleteimport_completed(data);
            return;
        }

        var action;
        if (data.action == 'publish' || data.action == 'unpublish') {
            action = this.recording_publishunpublish_completed(data);
        }

        var nodebutton = Y.one('img#recording-' + elementid + '-' + data.recordingid);
        var btnsrc = nodebutton.getAttribute('data-src');
        var link = Y.one('a#recording-' + elementid + '-' + data.recordingid);
        var linkonclick = link.getAttribute('data-onlcick');

        action = this.recording_processing_action(data);
        var linkaction = 'show';
        var text = M.util.get_string('view_recording_list_actionbar_publish', 'bigbluebuttonbn');
        Y.one('#playbacks-' + data.recordingid).hide();
        Y.one('#preview-' + data.recordingid).hide();
        if (data.action === 'publish') {
            action = 'unpublish';
            linkaction = 'hide';
            text = M.util.get_string('view_recording_list_actionbar_unpublish', 'bigbluebuttonbn');
            Y.one('#playbacks-' + data.recordingid).show();
            Y.one('#preview-' + data.recordingid).show();
        }

        nodebutton.setAttribute('id', 'recording-' + action.action + '-' + data.recordingid);
        link.setAttribute('id', 'recording-' + action.action + '-' + data.recordingid);
        link.setAttribute('data-action', action.action);
        nodebutton.setAttribute('src', btnsrc.substring(0, btnsrc.length - 4) + linkaction);
        nodebutton.setAttribute('alt', action.text);
        nodebutton.setAttribute('title', action.text);
        link.setAttribute('onclick', linkonclick.replace(data.action, action.action));
    },

    recording_action_elementid: function(data) {
        var elementid = data.action;
        if (typeof data.target !== 'undefined') {
            elementid += '-' + data.target;
        }
        return elementid;
    },

    recording_processing_action: function(data) {
        /*
        var action = {
            action: 'publish',
            linkaction: 'show',
            text: M.util.get_string('view_recording_list_actionbar_publish', 'bigbluebuttonbn')
        };
        if (data.action === 'publish') {

        }
        */
    },

    recording_action_failover: function(data) {
        var alert = new M.core.alert({
            title: M.util.get_string('error', 'moodle'),
            message: data.message
        });
        alert.show();

        var elementid = this.recording_action_elementid(data);

        var nodebutton = Y.one('img#recording-' + elementid + '-' + data.recordingid);
        var text = M.util.get_string('view_recording_list_action_' + data.action, 'bigbluebuttonbn');
        nodebutton.setAttribute('id', 'recording-' + data.action + '-' + data.recordingid);
        nodebutton.setAttribute('src', nodebutton.getAttribute('data-src'));
        nodebutton.setAttribute('alt', text);
        nodebutton.setAttribute('title', text);

        var nodelink = Y.one('a#recording-' + elementid + '-' + data.recordingid);
        nodelink.setAttribute('id', 'recording-' + data.action + '-' + data.recordingid);
        nodelink.setAttribute('data-action', data.action);
        nodelink.setAttribute('onclick', nodelink.getAttribute('data-onlcick'));

        if (data.action === 'edit') {
            this.recording_edit_failover(nodelink.getDOMNode());
        }
    },

    recording_publishunpublish_completed: function(data) {},

    recording_deleteimport_completed: function(data) {
        Y.one('#recording-td-' + data.recordingid).remove();
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
