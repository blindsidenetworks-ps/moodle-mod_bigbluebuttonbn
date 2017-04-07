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

        var button = Y.one('img#' + elementid + '-' + data.recordingid);
        var text = M.util.get_string('view_recording_list_action_' + data.action, 'bigbluebuttonbn');
        button.setAttribute('data-alt', button.getAttribute('alt'));
        button.setAttribute('alt', text);
        button.setAttribute('data-title', button.getAttribute('title'));
        button.setAttribute('title', text);
        button.setAttribute('data-src', button.getAttribute('src'));
        button.setAttribute('src', M.cfg.wwwroot + "/mod/bigbluebuttonbn/pix/processing16.gif");

        var link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('data-onclick', link.getAttribute('onclick'));
        link.setAttribute('onclick', '');
    },

    toggle_spinning_wheel_off: function(data) {
        var elementid = this.element_id(data.action, data.target);

        var button = Y.one('img#' + elementid + '-' + data.recordingid);
        button.setAttribute('alt', button.getAttribute('data-alt'));
        button.removeAttribute('data-alt');
        button.setAttribute('title', button.getAttribute('data-title'));
        button.removeAttribute('data-title');
        button.setAttribute('src', button.getAttribute('data-src'));
        button.removeAttribute('data-src');

        var link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('onclick', link.getAttribute('data-onclick'));
        link.removeAttribute('data-onclick');
    },

    update_data: function(data) {
        var action = this.element_action_reversed(data.action);
      
        if (action === data.action) {
            return;
        }

        var elementid = this.element_id(data.action, data.target);
        
        var button = Y.one('img#' + elementid + '-' + data.recordingid);
        var buttondatatext = M.util.get_string('view_recording_list_actionbar_' + action, 'bigbluebuttonbn');
        var buttondatatag = this.element_tag(action);
        var buttondatasrc = button.getAttribute('data-src').replace(
            this.element_tag(data.action), buttondatatag);
        button.setAttribute('data-alt', buttondatatext);
        button.setAttribute('data-title', buttondatatext);
        button.setAttribute('data-src', buttondatasrc);
      
        var link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('data-action', action);
        var linkdataonclick = link.getAttribute('data-onclick').replace(data.action, action);
        link.setAttribute('data-onclick', linkdataonclick);
    },

    update_id: function(data) {
        var action = this.element_action_reversed(data.action);
      
        if (action === data.action) {
            return;
        }

        var elementid = this.element_id(data.action, data.target);
        var id = '' + elementid.replace(data.action, action) + '-' + data.recordingid;

        var button = Y.one('img#' + elementid + '-' + data.recordingid);
        button.setAttribute('id', id);

        var link = Y.one('a#' + elementid + '-' + data.recordingid);
        link.setAttribute('id', id);
    },
  
    element_id: function(action, target) {
        var elementid = 'recording-' + action;
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
