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

    recording_action_inprocess: function(data) {
        var btn = Y.one('#recording-btn-' + data.action + '-' + data.recordingid);
        var text = M.util.get_string('view_recording_list_actionbar_unpublishing', 'bigbluebuttonbn');
        if (data.action == 'publish') {
            text = M.util.get_string('view_recording_list_actionbar_publishing', 'bigbluebuttonbn');
        }
        if (data.action == 'delete') {
            text = M.util.get_string('view_recording_list_actionbar_deleting', 'bigbluebuttonbn');
        }
        btn.setAttribute('alt', text);
        btn.setAttribute('title', text);
        btn.setAttribute('data-src', btn.getAttribute('src'));
        btn.setAttribute('src', M.cfg.wwwroot + "/mod/bigbluebuttonbn/pix/processing16.gif");

        var link = Y.one('#recording-link-' + data.action + '-' + data.recordingid);
        link.setAttribute('data-onlcick', link.getAttribute('onclick'));
        link.setAttribute('onclick', '');
    },

    recording_action_completed: function(data) {
        var btn = Y.one('#recording-btn-' + data.action + '-' + data.recordingid);
        var btnsrc = btn.getAttribute('data-src');
        var link = Y.one('#recording-link-' + data.action + '-' + data.recordingid);
        var linkonclick = link.getAttribute('data-onlcick');

        var action = 'publish';
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

        btn.setAttribute('id', 'recording-btn-' + action + '-' + data.recordingid);
        link.setAttribute('id', 'recording-link-' + action + '-' + data.recordingid);
        btn.setAttribute('src', btnsrc.substring(0, btnsrc.length - 4) + linkaction);
        btn.setAttribute('alt', text);
        btn.setAttribute('title', text);
        link.setAttribute('onclick', linkonclick.replace(data.action, action));
    },

    recording_action_failed: function(data) {
        var alert = new M.core.alert({
            title: M.util.get_string('error', 'moodle'),
            message: data.message
        });
        alert.show();

        var btn = Y.one('#recording-btn-' + data.action + '-' + data.recordingid);
        var link = Y.one('#recording-link-' + data.action + '-' + data.recordingid);

        var text = M.util.get_string('view_recording_list_actionbar_unpublish', 'bigbluebuttonbn');
        if (data.action === 'publish') {
            text = M.util.get_string('view_recording_list_actionbar_publish', 'bigbluebuttonbn');
        }

        btn.setAttribute('id', 'recording-btn-' + data.action + '-' + data.recordingid);
        link.setAttribute('id', 'recording-link-' + data.action + '-' + data.recordingid);
        btn.setAttribute('src', btn.getAttribute('data-src'));
        btn.setAttribute('alt', text);
        btn.setAttribute('title', text);
        link.setAttribute('onclick', link.getAttribute('data-onlcick'));
    },

    recording_delete_completed: function(data) {
        Y.one('#recording-td-' + data.recordingid).remove();
    },

    //recording_edit: function(recordingid, meetingid, target) {
    recording_edit: function(element) {
        var nodeeditlink = Y.one(element);
        var node = nodeeditlink.ancestor('div');
        console.info('Editing ' + node.getAttribute('data-target') + '...');
        var nodetext = node.one('> span');

        nodetext.hide();
        nodeeditlink.hide();

        var nodeinputtext = Y.Node.create('<input type="text" class="form-control"></input>');
        nodeinputtext.setAttribute('id', node.getAttribute('id'));
        nodeinputtext.setAttribute('value', nodetext.getHTML());
        nodeinputtext.setAttribute('onkeydown', 'M.mod_bigbluebuttonbn.recordings.recording_edit_keydown(this);');
        node.append(nodeinputtext);

        //console.info(node.get('children'));
        //console.info(node.one('> span'));
        //console.info(node.one('> a'));
    },

    recording_edit_keydown: function(element) {
        if (event.keyCode == 13) {
            var text = element.value;
            var nodeinputtext = Y.one(element);
            var node = nodeinputtext.ancestor('div');
            var nodetext = node.one('> span');
            var nodeeditlink = node.one('> a');
            return setTimeout((function() {
                M.mod_bigbluebuttonbn.broker.recording_action(
                    'update', node.getAttribute('data-recordingid'), node.getAttribute('data-meetingid'));
                nodetext.setHTML(text);
                nodeinputtext.hide();
                nodetext.show();
                nodeeditlink.show();
            }), 0);
        }
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
