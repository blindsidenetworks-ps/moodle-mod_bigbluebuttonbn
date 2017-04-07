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

        if (data.recordings_html === false &&
            (this.profilefeatures.includes('all') || this.profilefeatures.includes('showrecordings'))) {
            this.datatable.columns = data.columns;
            this.datatable.data = this.datatable_init_format_dates(data.data);
            this.datatable_init();
        }
    },

    datatable_init_format_dates: function(data) {
        for (var i = 0; i < data.length; i++) {
            var date = new Date(data[i].date);
            data[i].date = date.toLocaleDateString(this.locale, {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        return data;
    },

    datatable_init: function() {
        var columns = this.datatable.columns;
        var data = this.datatable.data;
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
        payload = Object.assign(payload, extras);

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
            goalstate: 'true'
        };
        this.recording_action(element, false, extras);
    },

    recording_unpublish: function(element) {
        var extras = {
            source: 'published',
            goalstate: 'false'
        };
        this.recording_action(element, false, extras);
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
        nodeinputtext.setAttribute('onfocusout', 'M.mod_bigbluebuttonbn.recordings.recording_edit_onfocusout(this);');
        node.append(nodeinputtext);
    },

    recording_edit_keydown: function(element) {
        /** global: event */
        if (event.keyCode == 13) {
            this.recording_edit_perform(element);
            return;
        }
        if (event.keyCode == 27) {
            this.recording_edit_onfocusout(element);
        }
    },

    recording_edit_onfocusout: function(element) {
        var nodeinputtext = Y.one(element);
        var node = nodeinputtext.ancestor('div');
        nodeinputtext.hide();
        node.one('> span').show();
        node.one('> a').show();
    },

    recording_edit_perform: function(element) {
        var nodeinputtext = Y.one(element);
        var node = nodeinputtext.ancestor('div');
        var text = element.value;

        // Perform the update.
        nodeinputtext.setAttribute('data-action', 'edit');
        nodeinputtext.setAttribute('data-goalstate', text);
        M.mod_bigbluebuttonbn.recordings.recording_update(nodeinputtext.getDOMNode());
        node.one('> span').setHTML(text);

        var nodelink = node.one('> a');
        nodelink.show();
        nodelink.focus();
    },

    recording_edit_completion: function(data, failed) {
        var elementid = this.element_id(data.action, data.target);
        var nodelink = Y.one('a#recording-' + elementid + '-' + data.recordingid);
        var node = nodelink.ancestor('div');
        var nodetext = node.one('> span');
        if (typeof nodetext === 'undefined') {
            return;
        }

        var nodeinputtext = node.one('> input');
        if (failed) {
            nodetext.setHTML(nodeinputtext.getAttribute('data-value'));
        }
        nodeinputtext.remove();
    },

    recording_confirmation_message: function(action, recordingid) {
        var confirmation = M.util.get_string('view_recording_' + action + '_confirmation', 'bigbluebuttonbn');
        if (typeof confirmation === 'undefined') {
            return '';
        }

        var recording_type = M.util.get_string('view_recording', 'bigbluebuttonbn');
        if (Y.one('#playbacks-' + recordingid).get('dataset').imported === 'true') {
            recording_type = M.util.get_string('view_recording_link', 'bigbluebuttonbn');
        }

        confirmation = confirmation.replace("{$a}", recording_type);
        if (action === 'import') {
            return confirmation;
        }

        // If it has associated links imported in a different course/activity, show that in confirmation dialog.
        var associated_links = Y.one('a#recording-' + action + '-' + recordingid).get('dataset').links;
        if (associated_links === 0) {
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
        M.mod_bigbluebuttonbn.helpers.toggle_spinning_wheel_on(data);
    },

    recording_action_completion: function(data) {

        if (data.action == 'delete' || data.action == 'import') {
            Y.one('#recording-td-' + data.recordingid).remove();
            return;
        }

        M.mod_bigbluebuttonbn.helpers.update_data(data);
        M.mod_bigbluebuttonbn.helpers.toggle_spinning_wheel_off(data);
        M.mod_bigbluebuttonbn.helpers.update_id(data);
      
        if (data.action === 'publish' || data.action === 'unpublish') {
            this.recording_publishunpublish_completion(data);
        }
    },

    recording_action_failover: function(data) {
        var alert = new M.core.alert({
            title: M.util.get_string('error', 'moodle'),
            message: data.message
        });
        alert.show();

        M.mod_bigbluebuttonbn.helpers.toggle_spinning_wheel_off(data);

        if (data.action === 'edit') {
            this.recording_edit_completion(data, true);
        }
    },

    recording_publishunpublish_completion: function(data) {
        var playbacks = Y.one('#playbacks-' + data.recordingid);
        var preview = Y.one('#preview-' + data.recordingid);
        if (data.action == 'unpublish') {
            playbacks.hide();
            preview.hide();
            return;
        }
        playbacks.show();
        preview.show();
        M.mod_bigbluebuttonbn.helpers.reload_preview(data);
    }
};
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

M.mod_bigbluebuttonbn.helpers = {

    toggle_spinning_wheel_on: function(data) {
        var elementid = this.element_id(data.action, data.target);

        var nodebutton = Y.one('img#recording-' + elementid + '-' + data.recordingid);
        var text = M.util.get_string('view_recording_list_action_' + data.action, 'bigbluebuttonbn');
        nodebutton.setAttribute('data-alt', nodebutton.getAttribute('alt'));
        nodebutton.setAttribute('alt', text);
        nodebutton.setAttribute('data-title', nodebutton.getAttribute('title'));
        nodebutton.setAttribute('title', text);
        nodebutton.setAttribute('data-src', nodebutton.getAttribute('src'));
        nodebutton.setAttribute('src', M.cfg.wwwroot + "/mod/bigbluebuttonbn/pix/processing16.gif");

        var nodelink = Y.one('a#recording-' + elementid + '-' + data.recordingid);
        nodelink.setAttribute('data-onclick', nodelink.getAttribute('onclick'));
        nodelink.setAttribute('onclick', '');
    },

    toggle_spinning_wheel_off: function(data) {
        var elementid = this.element_id(data.action, data.target);

        var nodebutton = Y.one('img#recording-' + elementid + '-' + data.recordingid);
        nodebutton.setAttribute('alt', nodebutton.getAttribute('data-alt'));
        nodebutton.removeAttribute('data-alt');
        nodebutton.setAttribute('title', nodebutton.getAttribute('data-title'));
        nodebutton.removeAttribute('data-title');
        nodebutton.setAttribute('src', nodebutton.getAttribute('data-src'));
        nodebutton.removeAttribute('data-src');

        var nodelink = Y.one('a#recording-' + elementid + '-' + data.recordingid);
        nodelink.setAttribute('onclick', nodelink.getAttribute('data-onclick'));
        nodelink.removeAttribute('data-onclick');
    },

    update_data: function(data) {
        var action = this.element_action_reversed(data.action);
      
        if (action === data.action) {
            return;
        }

        var elementid = this.element_id(data.action, data.target);
        
        var nodebutton = Y.one('img#recording-' + elementid + '-' + data.recordingid);
        var buttondatatext = M.util.get_string('view_recording_list_actionbar_' + action, 'bigbluebuttonbn');
        var buttondatatag = this.element_tag(action);
        var buttondatasrc = nodebutton.getAttribute('data-src').replace(
            this.element_tag(data.action), buttondatatag);
        nodebutton.setAttribute('data-alt', buttondatatext);
        nodebutton.setAttribute('data-title', buttondatatext);
        nodebutton.setAttribute('data-src', buttondatasrc);
      
        var nodelink = Y.one('a#recording-' + elementid + '-' + data.recordingid);
        nodelink.setAttribute('data-action', action);
        var linkdataonclick = nodelink.getAttribute('data-onclick').replace(data.action, action);
        nodelink.setAttribute('data-onclick', linkdataonclick);
    },

    update_id: function(data) {
        var action = this.element_action_reversed(data.action);
      
        if (action === data.action) {
            return;
        }

        var elementid = this.element_id(data.action, data.target);
        var id = 'recording-' + elementid.replace(data.action, action) + '-' + data.recordingid;

        var nodebutton = Y.one('img#recording-' + elementid + '-' + data.recordingid);
        nodebutton.setAttribute('id', id);

        var nodelink = Y.one('a#recording-' + elementid + '-' + data.recordingid);
        nodelink.setAttribute('id', id);
    },
  
    element_id: function(action, target) {
        var elementid = action;
        if (typeof target !== 'undefined') {
            elementid += '-' + target;
        }
        return elementid;
    },

    element_tag: function(action) {
        var tags = {};
        tags.publish = 'show';
        tags.unpublish = 'hide';
        tags.protect = 'lock';
        tags.unprotect = 'unlock';
        tags.edit = 'edit';
        tags['import'] = 'import';
        tags['delete'] = 'delete';

        return tags[action];
    },

    element_action_reversed: function(action) {
        var reverseactions = {};
        reverseactions.publish = 'unpublish';
        reverseactions.unpublish = 'publish';
        reverseactions.protect = 'unprotect';
        reverseactions.unprotect = 'protect';
        reverseactions.edit = 'edit';
        reverseactions['import'] = 'import';
        reverseactions['delete'] = 'delete';

        return reverseactions[action];
    },

    reload_preview: function(data) {
        var thumbnails = Y.one('#preview-' + data.recordingid).all('> img');
        thumbnails.each(function (thumbnail) {
            var thumbnailsrc = thumbnail.getAttribute('src');
            thumbnailsrc = thumbnailsrc.substring(0, thumbnailsrc.indexOf('?'));
            thumbnailsrc += '?' + new Date().getTime();
            thumbnail.setAttribute('src', thumbnailsrc);
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
