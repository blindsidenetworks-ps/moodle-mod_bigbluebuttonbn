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

    element_tag: {},
    element_fa_class: {},
    element_action_reversed: {},
  
    /**
     * Initialise helpers code.
     *
     * @method init
     */
    init: function() {
        this.element_tag = this.init_element_tag();
        this.element_fa_class = this.init_element_fa_class();
        this.element_action_reversed = this.init_element_action_reversed();
    },

    toggle_spinning_wheel_on: function(data) {
        var elementid, link, button, text;

        elementid = this.element_id(data.action, data.target);
        text = M.util.get_string('view_recording_list_action_' + data.action, 'bigbluebuttonbn');
        link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('data-onclick', link.getAttribute('onclick'));
        link.setAttribute('onclick', '');
        button = link.one('> i');
        if (button === null) {
            // For backward compatibility
            this.toggle_spinning_wheel_on_compatible(link.one('> img'), text);
            return;
        }
        button.setAttribute('data-aria-label', button.getAttribute('aria-label'));
        button.setAttribute('aria-label', text);
        button.setAttribute('data-title', button.getAttribute('title'));
        button.setAttribute('title', text);
        button.setAttribute('data-class', button.getAttribute('class'));
        button.setAttribute('class', this.element_fa_class.process);
    },

    toggle_spinning_wheel_on_compatible: function(button, text) {
        button.setAttribute('data-alt', button.getAttribute('alt'));
        button.setAttribute('alt', text);
        button.setAttribute('data-title', button.getAttribute('title'));
        button.setAttribute('title', text);
        button.setAttribute('data-src', button.getAttribute('src'));
        button.setAttribute('src', M.cfg.wwwroot + "/mod/bigbluebuttonbn/pix/processing16.gif");
    },

    toggle_spinning_wheel_off: function(data) {
        var elementid, link, button;

        elementid = this.element_id(data.action, data.target);
        link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('onclick', link.getAttribute('data-onclick'));
        link.removeAttribute('data-onclick');
        button = link.one('> i');
        if (button === null) {
            // For backward compatibility
            this.toggle_spinning_wheel_off_compatible(link.one('> img'));
            return;
        }
        button.setAttribute('aria-label', button.getAttribute('data-aria-label'));
        button.removeAttribute('data-aria-label');
        button.setAttribute('title', button.getAttribute('data-title'));
        button.removeAttribute('data-title');
        button.setAttribute('class', button.getAttribute('data-class'));
        button.removeAttribute('data-class');
    },

    toggle_spinning_wheel_off_compatible: function(button) {
        button.setAttribute('alt', button.getAttribute('data-alt'));
        button.removeAttribute('data-alt');
        button.setAttribute('title', button.getAttribute('data-title'));
        button.removeAttribute('data-title');
        button.setAttribute('src', button.getAttribute('data-src'));
        button.removeAttribute('data-src');
    },

    update_data: function(data) {
        var action, elementid, link, linkdataonclick, button, buttondatatext, buttondatatag;

        action = this.element_action_reversed[data.action];
        if (action === data.action) {
            return;
        }
        elementid = this.element_id(data.action, data.target);
        link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('data-action', action);
        linkdataonclick = link.getAttribute('data-onclick').replace(data.action, action);
        link.setAttribute('data-onclick', linkdataonclick);

        buttondatatext = M.util.get_string('view_recording_list_actionbar_' + action, 'bigbluebuttonbn');
        buttondatatag = this.element_tag[action];
        button = link.one('> i');
        if (button === null) {
            // For backward compatibility
            this.update_data_compatible(link.one('> img'), this.element_tag[data.action], buttondatatag, buttondatatext);
            return;
        }

        button.setAttribute('data-aria-label', buttondatatext);
        button.setAttribute('data-title', buttondatatext);
        button.setAttribute('data-class', this.element_fa_class[action]);
    },

    update_data_compatible: function(button, action, buttondatatag, buttondatatext) {
        var buttondatasrc;

        buttondatasrc = button.getAttribute('data-src').replace(action, buttondatatag);
        button.setAttribute('data-alt', buttondatatext);
        button.setAttribute('data-title', buttondatatext);
        button.setAttribute('data-src', buttondatasrc);
    },

    update_id: function(data) {
        var action, elementid, link, button, id;

        action = this.element_action_reversed[data.action];
        if (action === data.action) {
            return;
        }
        elementid = this.element_id(data.action, data.target);
        link = Y.one('a#' + elementid + '-' + data.recordingid);
        id = '' + elementid.replace(data.action, action) + '-' + data.recordingid;
        link.setAttribute('id', id);

        button = link.one('> i');
        if (button === null) {
            // For backward compatibility
            button = link.one('> img');
        }

        button.setAttribute('id', id);
    },

    element_id: function(action, target) {
        var elementid = 'recording-' + action;
        if (typeof target !== 'undefined') {
            elementid += '-' + target;
        }
        return elementid;
    },

    init_element_tag: function() {
        var tags = {};
        tags.publish = 'show';
        tags.unpublish = 'hide';
        tags.protect = 'lock';
        tags.unprotect = 'unlock';
        tags.edit = 'edit';
        tags.process = 'process';
        tags['import'] = 'import';
        tags['delete'] = 'delete';
        return tags;
    },

    init_element_fa_class: function() {
        var tags = {};
        tags.publish = 'icon fa fa-eye fa-fw iconsmall';
        tags.unpublish = 'icon fa fa-eye-slash fa-fw iconsmall';
        tags.protect = 'icon fa fa-lock fa-fw iconsmall';
        tags.unprotect = 'icon fa fa-unlock fa-fw iconsmall';
        tags.edit = 'icon fa fa-pencil fa-fw iconsmall';
        tags.process = 'icon fa fa-spinner fa-spin iconsmall';
        tags['import'] = 'icon fa fa-download fa-fw iconsmall';
        tags['delete'] = 'icon fa fa-trash fa-fw iconsmall';
        return tags;
    },

    init_element_action_reversed: function() {
        var actions = {};
        actions.publish = 'unpublish';
        actions.unpublish = 'publish';
        actions.protect = 'unprotect';
        actions.unprotect = 'protect';
        actions.edit = 'edit';
        actions['import'] = 'import';
        actions['delete'] = 'delete';
        return actions;
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
