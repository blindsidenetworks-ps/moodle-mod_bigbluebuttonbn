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
/** global: YUI */
/** global: event */

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.recordings = {

    datasource: null,
    datatable: {},
    locale: 'en',
    windowVideoPlay: null,
    table: null,
    bbbid: 0,

    /**
     * Initialise recordings code.
     *
     * @method init
     * @param {object} dataobj
     */
    init: function(dataobj) {
        var hidetable = (dataobj && dataobj.hide_table && dataobj.hide_table === true);
        this.bbbid = dataobj.bbbid;
        this.datasource = new Y.DataSource.Get({
            source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_ajax.php?sesskey=" + M.cfg.sesskey + '&'
        });
        var thisbbb = this;
        this.datasource.sendRequest({
            request: "id=" + this.bbbid + "&action=recording_list_table",
            callback: {
                success: function (data) {
                    var bbinfo = data.data;
                    if (!hidetable && bbinfo.recordings_html === false &&
                        (bbinfo.profile_features.indexOf('all') != -1 || bbinfo.profile_features.indexOf('showrecordings') != -1)) {
                        thisbbb.locale = bbinfo.locale;
                        thisbbb.datatable.columns = bbinfo.data.columns;
                        thisbbb.datatable.data = thisbbb.datatableInitFormatDates(bbinfo.data.data);
                        thisbbb.datatableInit();
                    }
                }
            }
        });
        var searchform = Y.one('#bigbluebuttonbn_recordings_searchform');
        if (searchform) {
            searchform.delegate('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var value = null;
                if (e.target.get('id') == 'searchsubmit') {
                    value = Y.one('#searchtext').get('value');
                } else {
                    Y.one('#searchtext').set('value', '');
                }

                this.filterByText(value);
            }, 'input[type=submit]', this);
        }
        M.mod_bigbluebuttonbn.helpers.init();
    },

    datatableInitFormatDates: function(data) {
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

    initExtraLanguage: function(Y1) {
        Y1.Intl.add(
            'datatable-paginator',
            Y1.config.lang,
            {
                first: M.util.get_string('view_recording_yui_first', 'bigbluebuttonbn'),
                prev:  M.util.get_string('view_recording_yui_prev', 'bigbluebuttonbn'),
                next:  M.util.get_string('view_recording_yui_next', 'bigbluebuttonbn'),
                last:  M.util.get_string('view_recording_yui_last', 'bigbluebuttonbn'),
                goToLabel:  M.util.get_string('view_recording_yui_page', 'bigbluebuttonbn'),
                goToAction:  M.util.get_string('view_recording_yui_go', 'bigbluebuttonbn'),
                perPage:  M.util.get_string('view_recording_yui_rows', 'bigbluebuttonbn'),
                showAll:  M.util.get_string('view_recording_yui_show_all', 'bigbluebuttonbn')
            }
        );
    },

    escapeRegex: function(value) {
        return value.replace( /[\-\[\]{}()*+?.,\\\^$|#\s]/g, "\\$&" );
    },

    filterByText: function(searchvalue) {
        if (this.table) {
            this.table.set('data', this.datatable.data);
            if (searchvalue) {
                var tlist = this.table.data;
                var rsearch = new RegExp('<span>.*?' + this.escapeRegex(searchvalue) + '.*?</span>', 'i');
                var filterdata = tlist.filter({asList: true}, function(item) {
                    var name = item.get('recording');
                    var description = item.get('description');
                    return (
                        (name && rsearch.test(name)) || (description && rsearch.test(description))
                    );
                });
                this.table.set('data', filterdata);
            }
        }
    },

    datatableInit: function() {
        var columns = this.datatable.columns;
        var data = this.datatable.data;
        var func = this.initExtraLanguage;
        YUI({
            lang: this.locale
        }).use('intl', 'datatable', 'datatable-sort', 'datatable-paginator', 'datatype-number', function(Y) {
            func(Y);
            var table = new Y.DataTable({
                width: "1195px",
                columns: columns,
                data: data,
                rowsPerPage: 10,
                paginatorLocation: ['header', 'footer']
            }).render('#bigbluebuttonbn_recordings_table');
            M.mod_bigbluebuttonbn.recordings.table = table;
            return table;
        });
    },

    recordingElementPayload: function(element) {
        var nodeelement = Y.one(element);
        var node = nodeelement.ancestor('div');
        return {
            action: nodeelement.getAttribute('data-action'),
            recordingid: node.getAttribute('data-recordingid'),
            meetingid: node.getAttribute('data-meetingid')
        };
    },

    recordingAction: function(element, confirmation, extras) {
        var payload = this.recordingElementPayload(element);
        for (var attrname in extras) {
            payload[attrname] = extras[attrname];
        }
        // The action doesn't require confirmation.
        if (!confirmation) {
            this.recordingActionPerform(payload);
            return;
        }
        // Create the confirmation dialogue.
        var confirm = new M.core.confirm({
            modal: true,
            centered: true,
            question: this.recordingConfirmationMessage(payload)
        });
        // If it is confirmed.
        confirm.on('complete-yes', function() {
            this.recordingActionPerform(payload);
        }, this);
    },

    recordingActionPerform: function(data) {
        M.mod_bigbluebuttonbn.helpers.toggleSpinningWheelOn(data);
        M.mod_bigbluebuttonbn.broker.recordingActionPerform(data);

        var thisbbb = this;
        this.datasource.sendRequest({
            request: "&id=" + this.bbbid + "&action=recording_list_table",
            callback: {
                success: function (data) {
                    var bbinfo = data.data;
                    if (bbinfo.recordings_html === false &&
                        (bbinfo.profile_features.indexOf('all') != -1 || bbinfo.profile_features.indexOf('showrecordings') != -1)) {
                        thisbbb.locale = bbinfo.locale;
                        thisbbb.datatable.columns = bbinfo.data.columns;
                        thisbbb.datatable.data = thisbbb.datatableInitFormatDates(bbinfo.data.data);
                    }
                }
            }
        });
    },

    recordingPublish: function(element) {
        var extras = {
            source: 'published',
            goalstate: 'true'
        };
        this.recordingAction(element, false, extras);
    },

    recordingUnpublish: function(element) {
        var extras = {
            source: 'published',
            goalstate: 'false'
        };
        this.recordingAction(element, false, extras);
    },

    recordingProtect: function(element) {
        var extras = {
            source: 'protected',
            goalstate: 'true'
        };
        this.recordingAction(element, false, extras);
    },

    recordingUnprotect: function(element) {
        var extras = {
            source: 'protected',
            goalstate: 'false'
        };
        this.recordingAction(element, false, extras);
    },

    recordingDelete: function(element) {
        var extras = {
            source: 'found',
            goalstate: false
        };
        var requireConfirmation = true;
        if (this.recordingIsImported(element)) {
            // When recordingDelete is performed on imported recordings use default response for validation.
            requireConfirmation = false;
            extras.source = 'status';
            extras.goalstate = true;
            extras.attempts = 1;
        }
        this.recordingAction(element, requireConfirmation, extras);
    },

    recordingImport: function(element) {
        var extras = {};
        this.recordingAction(element, true, extras);
    },

    recordingUpdate: function(element) {
        var nodeelement = Y.one(element);
        var node = nodeelement.ancestor('div');
        var extras = {
            target: node.getAttribute('data-target'),
            source: node.getAttribute('data-source'),
            goalstate: nodeelement.getAttribute('data-goalstate')
        };
        this.recordingAction(element, false, extras);
    },

    recordingEdit: function(element) {
        var link = Y.one(element);
        var node = link.ancestor('div');
        var text = node.one('> span');
        text.hide();
        link.hide();
        var inputtext = Y.Node.create('<input type="text" class="form-control"></input>');
        inputtext.setAttribute('id', link.getAttribute('id'));
        inputtext.setAttribute('value', text.getHTML());
        inputtext.setAttribute('data-value', text.getHTML());
        inputtext.on('keydown', M.mod_bigbluebuttonbn.recordings.recordingEditKeydown);
        inputtext.on('focusout', M.mod_bigbluebuttonbn.recordings.recordingEditOnfocusout);
        node.append(inputtext);
        inputtext.focus().select();
    },

    recordingEditKeydown: function(event) {
        var keyCode = event.which || event.keyCode;
        if (keyCode == 13) {
            M.mod_bigbluebuttonbn.recordings.recordingEditPerform(event.currentTarget);
            return;
        }
        if (keyCode == 27) {
            M.mod_bigbluebuttonbn.recordings.recordingEditOnfocusout(event.currentTarget);
        }
    },

    recordingEditOnfocusout: function(nodeelement) {
        var node = nodeelement.ancestor('div');
        nodeelement.hide();
        node.one('> span').show();
        node.one('> a').show();
    },

    recordingEditPerform: function(nodeelement) {
        var node = nodeelement.ancestor('div');
        var text = nodeelement.get('value').trim();
        // Perform the update.
        nodeelement.setAttribute('data-action', 'edit');
        nodeelement.setAttribute('data-goalstate', text);
        nodeelement.hide();
        this.recordingUpdate(nodeelement.getDOMNode());
        node.one('> span').setHTML(text).show();
        node.one('> a').show();
    },

    recordingEditCompletion: function(data, failed) {
        var elementid = M.mod_bigbluebuttonbn.helpers.elementId(data.action, data.target);
        var link = Y.one('a#' + elementid + '-' + data.recordingid);
        var node = link.ancestor('div');
        var text = node.one('> span');
        if (typeof text === 'undefined') {
            return;
        }
        var inputtext = node.one('> input');
        if (failed) {
            text.setHTML(inputtext.getAttribute('data-value'));
        }
        inputtext.remove();
    },

    recordingPlay: function(element) {
        var nodeelement = Y.one(element);
        if (nodeelement.getAttribute('data-href') === '') {
            M.mod_bigbluebuttonbn.helpers.alertError(
                M.util.get_string('view_recording_format_errror_unreachable', 'bigbluebuttonbn')
              );
            return;
        }
        var extras = {
            target: nodeelement.getAttribute('data-target'),
            source: 'published',
            goalstate: 'true',
            attempts: 1,
            dataset: nodeelement.getData()
        };
        // New window for video play must be created previous to ajax requests.
        this.windowVideoPlay = window.open('', '_blank');
        // Prevent malicious modification over window opener to use window.open().
        this.windowVideoPlay.opener = null;
        this.recordingAction(element, false, extras);
    },

    recordingConfirmationMessage: function(data) {
        var confirmation, recordingType, elementid, associatedLinks, confirmationWarning;
        confirmation = M.util.get_string('view_recording_' + data.action + '_confirmation', 'bigbluebuttonbn');
        if (typeof confirmation === 'undefined') {
            return '';
        }
        recordingType = M.util.get_string('view_recording', 'bigbluebuttonbn');
        if (Y.one('#playbacks-' + data.recordingid).get('dataset').imported === 'true') {
            recordingType = M.util.get_string('view_recording_link', 'bigbluebuttonbn');
        }
        confirmation = confirmation.replace("{$a}", recordingType);
        if (data.action === 'import') {
            return confirmation;
        }
        // If it has associated links imported in a different course/activity, show that in confirmation dialog.
        elementid = M.mod_bigbluebuttonbn.helpers.elementId(data.action, data.target);
        associatedLinks = Y.one('a#' + elementid + '-' + data.recordingid).get('dataset').links;
        if (associatedLinks === 0) {
            return confirmation;
        }
        confirmationWarning = M.util.get_string('view_recording_' + data.action + '_confirmation_warning_p',
            'bigbluebuttonbn');
        if (associatedLinks == 1) {
            confirmationWarning = M.util.get_string('view_recording_' + data.action + '_confirmation_warning_s',
                'bigbluebuttonbn');
        }
        confirmationWarning = confirmationWarning.replace("{$a}", associatedLinks) + '. ';
        return confirmationWarning + '\n\n' + confirmation;
    },

    recordingActionCompletion: function(data) {
        var container, table, row;
        if (data.action == 'delete') {
            row = Y.one('div#recording-actionbar-' + data.recordingid).ancestor('td').ancestor('tr');
            table = row.ancestor('tbody');
            if (table.all('tr').size() == 1) {
                container = Y.one('#bigbluebuttonbn_view_recordings_content');
                container.prepend('<span>' + M.util.get_string('view_message_norecordings', 'bigbluebuttonbn') + '</span>');
                container.one('#bigbluebuttonbn_recordings_table').remove();
                return;
            }
            row.remove();
            return;
        }
        if (data.action == 'import') {
            row = Y.one('div#recording-actionbar-' + data.recordingid).ancestor('td').ancestor('tr');
            row.remove();
            return;
        }
        if (data.action == 'play') {
            M.mod_bigbluebuttonbn.helpers.toggleSpinningWheelOff(data);
            // Update url in window video to show the video.
            this.windowVideoPlay.location.href = data.dataset.href;
            return;
        }
        M.mod_bigbluebuttonbn.helpers.updateData(data);
        M.mod_bigbluebuttonbn.helpers.toggleSpinningWheelOff(data);
        M.mod_bigbluebuttonbn.helpers.updateId(data);
        if (data.action === 'publish') {
            this.recordingPublishCompletion(data.recordingid);
            return;
        }
        if (data.action === 'unpublish') {
            this.recordingUnpublishCompletion(data.recordingid);
            return;
        }
    },

    recordingActionFailover: function(data) {
        M.mod_bigbluebuttonbn.helpers.alertError(data.message);
        M.mod_bigbluebuttonbn.helpers.toggleSpinningWheelOff(data);
        if (data.action === 'edit') {
            this.recordingEditCompletion(data, true);
        }
    },

    recordingPublishCompletion: function(recordingid) {
        var playbacks = Y.one('#playbacks-' + recordingid);
        playbacks.show();
        var preview = Y.one('#preview-' + recordingid);
        if (preview === null) {
            return;
        }
        preview.show();
        M.mod_bigbluebuttonbn.helpers.reloadPreview(recordingid);
    },

    recordingUnpublishCompletion: function(recordingid) {
        var playbacks = Y.one('#playbacks-' + recordingid);
        playbacks.hide();
        var preview = Y.one('#preview-' + recordingid);
        if (preview === null) {
            return;
        }
        preview.hide();
    },

    recordingIsImported: function(element) {
        var nodeelement = Y.one(element);
        var node = nodeelement.ancestor('tr');
        return (node.getAttribute('data-imported') === 'true');
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

    elementTag: {},
    elementFaClass: {},
    elementActionReversed: {},

    /**
     * Initialise helpers code.
     *
     * @method init
     */
    init: function() {
        this.elementTag = this.initElementTag();
        this.elementFaClass = this.initElementFAClass();
        this.elementActionReversed = this.initElementActionReversed();
    },

    toggleSpinningWheelOn: function(data) {
        var elementid, link, button, text;
        elementid = this.elementId(data.action, data.target);
        text = M.util.get_string('view_recording_list_action_' + data.action, 'bigbluebuttonbn');
        link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('data-onclick', link.getAttribute('onclick'));
        link.setAttribute('onclick', '');
        button = link.one('> i');
        if (button === null) {
            // For backward compatibility.
            this.toggleSpinningWheelOnCompatible(link, text);
            return;
        }
        button.setAttribute('data-aria-label', button.getAttribute('aria-label'));
        button.setAttribute('aria-label', text);
        button.setAttribute('data-title', button.getAttribute('title'));
        button.setAttribute('title', text);
        button.setAttribute('data-class', button.getAttribute('class'));
        button.setAttribute('class', this.elementFaClass.process);
    },

    toggleSpinningWheelOnCompatible: function(link, text) {
        var button = link.one('> img');
        if (button === null) {
            // Button doesn't even have an icon.
            return;
        }
        button.setAttribute('data-alt', button.getAttribute('alt'));
        button.setAttribute('alt', text);
        button.setAttribute('data-title', button.getAttribute('title'));
        button.setAttribute('title', text);
        button.setAttribute('data-src', button.getAttribute('src'));
        button.setAttribute('src', 'pix/i/processing16.gif');
    },

    toggleSpinningWheelOff: function(data) {
        var elementid, link, button;
        elementid = this.elementId(data.action, data.target);
        link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('onclick', link.getAttribute('data-onclick'));
        link.removeAttribute('data-onclick');
        button = link.one('> i');
        if (button === null) {
            // For backward compatibility.
            this.toggleSpinningWheelOffCompatible(link.one('> img'));
            return;
        }
        button.setAttribute('aria-label', button.getAttribute('data-aria-label'));
        button.removeAttribute('data-aria-label');
        button.setAttribute('title', button.getAttribute('data-title'));
        button.removeAttribute('data-title');
        button.setAttribute('class', button.getAttribute('data-class'));
        button.removeAttribute('data-class');
    },

    toggleSpinningWheelOffCompatible: function(button) {
        if (button === null) {
            // Button doesn't have an icon.
            return;
        }
        button.setAttribute('alt', button.getAttribute('data-alt'));
        button.removeAttribute('data-alt');
        button.setAttribute('title', button.getAttribute('data-title'));
        button.removeAttribute('data-title');
        button.setAttribute('src', button.getAttribute('data-src'));
        button.removeAttribute('data-src');
    },

    updateData: function(data) {
        var action, elementid, link, linkdataonclick, button, buttondatatext, buttondatatag;
        action = this.elementActionReversed[data.action];
        if (action === data.action) {
            return;
        }
        elementid = this.elementId(data.action, data.target);
        link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('data-action', action);
        linkdataonclick = link.getAttribute('data-onclick').replace(this.capitalize(data.action), this.capitalize(action));
        link.setAttribute('data-onclick', linkdataonclick);
        buttondatatext = M.util.get_string('view_recording_list_actionbar_' + action, 'bigbluebuttonbn');
        buttondatatag = this.elementTag[action];
        button = link.one('> i');
        if (button === null) {
            // For backward compatibility.
            this.updateDataCompatible(link.one('> img'), this.elementTag[data.action], buttondatatag, buttondatatext);
            return;
        }
        button.setAttribute('data-aria-label', buttondatatext);
        button.setAttribute('data-title', buttondatatext);
        button.setAttribute('data-class', this.elementFaClass[action]);
    },

    updateDataCompatible: function(button, action, buttondatatag, buttondatatext) {
        if (button === null) {
            // Button doesn't have an icon.
            return;
        }
        var buttondatasrc = button.getAttribute('data-src');
        button.setAttribute('data-alt', buttondatatext);
        button.setAttribute('data-title', buttondatatext);
        button.setAttribute('data-src', buttondatasrc.replace(buttondatatag, action));
    },

    updateId: function(data) {
        var action, elementid, link, button, id;
        action = this.elementActionReversed[data.action];
        if (action === data.action) {
            return;
        }
        elementid = this.elementId(data.action, data.target);
        link = Y.one('a#' + elementid + '-' + data.recordingid);
        id = '' + elementid.replace(data.action, action) + '-' + data.recordingid;
        link.setAttribute('id', id);
        button = link.one('> i');
        if (button === null) {
            // For backward compatibility.
            button = link.one('> img');
        }
        button.removeAttribute('id');
    },

    elementId: function(action, target) {
        var elementid = 'recording-' + action;
        if (typeof target !== 'undefined') {
            elementid += '-' + target;
        }
        return elementid;
    },

    initElementTag: function() {
        var tags = {};
        tags.play = 'play';
        tags.publish = 'hide';
        tags.unpublish = 'show';
        tags.protect = 'lock';
        tags.unprotect = 'unlock';
        tags.edit = 'edit';
        tags.process = 'process';
        tags['import'] = 'import';
        tags['delete'] = 'delete';
        return tags;
    },

    initElementFAClass: function() {
        var tags = {};
        tags.publish = 'icon fa fa-eye-slash fa-fw iconsmall';
        tags.unpublish = 'icon fa fa-eye fa-fw iconsmall';
        tags.protect = 'icon fa fa-unlock fa-fw iconsmall';
        tags.unprotect = 'icon fa fa-lock fa-fw iconsmall';
        tags.edit = 'icon fa fa-pencil fa-fw iconsmall';
        tags.process = 'icon fa fa-spinner fa-spin iconsmall';
        tags['import'] = 'icon fa fa-download fa-fw iconsmall';
        tags['delete'] = 'icon fa fa-trash fa-fw iconsmall';
        return tags;
    },

    initElementActionReversed: function() {
        var actions = {};
        actions.play = 'play';
        actions.publish = 'unpublish';
        actions.unpublish = 'publish';
        actions.protect = 'unprotect';
        actions.unprotect = 'protect';
        actions.edit = 'edit';
        actions['import'] = 'import';
        actions['delete'] = 'delete';
        return actions;
    },

    reloadPreview: function(recordingid) {
        var thumbnails = Y.one('#preview-' + recordingid).all('> img');
        thumbnails.each(function(thumbnail) {
            var thumbnailsrc = thumbnail.getAttribute('src');
            thumbnailsrc = thumbnailsrc.substring(0, thumbnailsrc.indexOf('?'));
            thumbnailsrc += '?' + new Date().getTime();
            thumbnail.setAttribute('src', thumbnailsrc);
        });
    },

    capitalize: function(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    },

    alertError: function(message, title) {
        if (typeof title == 'undefined') {
            title = 'error';
        }
        var alert = new M.core.alert({
            title: M.util.get_string(title, 'moodle'),
            message: message
        });
        alert.show();
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
