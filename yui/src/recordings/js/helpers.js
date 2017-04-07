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
    }
  
};
