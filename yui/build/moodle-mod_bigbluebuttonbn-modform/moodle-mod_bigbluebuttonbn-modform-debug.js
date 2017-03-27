YUI.add('moodle-mod_bigbluebuttonbn-modform', function (Y, NAME) {

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

M.mod_bigbluebuttonbn.modform = {

    bigbluebuttonbn: {},

    /**
     * Initialise the broker code.
     *
     * @method init
     */
    init: function(bigbluebuttonbn) {
        this.bigbluebuttonbn = bigbluebuttonbn;
        this.update_instance_type_profile();
    },

    update_instance_type_profile: function() {

        var selected_type = Y.one('#id_type');
        this.apply_instance_type_profile(this.bigbluebuttonbn.instance_type_profiles[
            selected_type.get('value')]);
    },

    apply_instance_type_profile: function(instance_type_profile) {

        var features = instance_type_profile.features;
        var show_all = features.includes('all');

        // Show room settings validation.
        this.show_fieldset('id_room', show_all || features.includes('showroom'));

        // Show recordings settings validation.
        this.show_fieldset('id_recordings', show_all || features.includes('showrecordings'));

        // Preuploadpresentation feature validation.
        this.show_fieldset('id_preuploadpresentation', show_all ||
            features.includes('preuploadpresentation'));

        // Participants feature validation.
        this.show_fieldset('id_permissions', show_all || features.includes('permissions'));

        // Schedule feature validation.
        this.show_fieldset('id_schedule', show_all || features.includes('schedule'));
    },

    show_fieldset: function(id, show) {
        // Show room settings validation.
        var fieldset = Y.DOM.byId(id);
        if (!fieldset) {
            return;
        }

        if (show) {
            Y.DOM.setStyle(fieldset, 'display', 'block');
            return;
        }

        Y.DOM.setStyle(fieldset, 'display', 'none');
    },

    participant_selection_set: function() {

        this.select_clear('bigbluebuttonbn_participant_selection');

        var type = document.getElementById('bigbluebuttonbn_participant_selection_type');
        for (var i = 0; i < type.options.length; i++) {
            if (type.options[i].selected) {
                var options = this.bigbluebuttonbn.participant_selection[type.options[i].value];
                for (var j = 0; j < options.length; j++) {
                    this.select_add_option(
                        'bigbluebuttonbn_participant_selection', options[j].name, options[j].id
                    );
                }
                if (j === 0) {
                    this.select_add_option('bigbluebuttonbn_participant_selection',
                        '---------------', 'all');
                    this.select_disable('bigbluebuttonbn_participant_selection');
                } else {
                    this.select_enable('bigbluebuttonbn_participant_selection');
                }
            }
        }
    },

    participant_list_update: function() {
        var participant_list = document.getElementsByName('participants')[0];
        participant_list.value = JSON.stringify(this.bigbluebuttonbn.participant_list).replace(/"/g, '&quot;');
    },

    participant_remove: function(type, id) {
        // Remove from memory.
        for (var i = 0; i < this.bigbluebuttonbn.participant_list.length; i++) {
            if (this.bigbluebuttonbn.participant_list[i].selectiontype == type &&
                this.bigbluebuttonbn.participant_list[i].selectionid == (id === '' ? null : id)) {
                this.bigbluebuttonbn.participant_list.splice(i, 1);
            }
        }

        // Remove from the form.
        var participant_list_table = document.getElementById('participant_list_table');
        for (var ii = 0; ii < participant_list_table.rows.length; ii++) {
            if (participant_list_table.rows[ii].id == 'participant_list_tr_' + type + '-' + id) {
                participant_list_table.deleteRow(i);
            }
        }
        this.participant_list_update();
    },

    participant_add: function() {
        var selection_type = document.getElementById('bigbluebuttonbn_participant_selection_type');
        var selection = document.getElementById('bigbluebuttonbn_participant_selection');

        // Lookup to see if it has been added already.
        for (var i = 0; i < this.bigbluebuttonbn.participant_list.length; i++) {
            if (this.bigbluebuttonbn.participant_list[i].selectiontype == selection_type.value &&
                this.bigbluebuttonbn.participant_list[i].selectionid == selection.value) {
                return;
            }
        }

        // Add it to memory.
        this.participant_add_to_memory(selection_type, selection);

        // Add it to the form.
        this.participant_add_to_form(selection_type, selection);
    },

    participant_add_to_memory: function(selection_type, selection) {
        this.bigbluebuttonbn.participant_list.push({
            "selectiontype": selection_type.value,
            "selectionid": selection.value,
            "role": "viewer"
        });
    },

    participant_add_to_form: function(selection_type, selection) {
        var participant_list_table = document.getElementById('participant_list_table');
        var row = participant_list_table.insertRow(participant_list_table.rows.length);
        row.id = "participant_list_tr_" + selection_type.value + "-" + selection.value;
        var cell0 = row.insertCell(0);
        cell0.width = "125px";
        cell0.innerHTML = '<b><i>' + selection_type.options[selection_type.selectedIndex].text;
        cell0.innerHTML += (selection_type.value !== 'all' ? ':&nbsp;' : '') + '</i></b>';
        var cell1 = row.insertCell(1);
        cell1.innerHTML = '';
        if (selection_type.value !== 'all') {
            cell1.innerHTML = selection.options[selection.selectedIndex].text;
        }
        var innerHTML;
        innerHTML = '&nbsp;<i>' + this.bigbluebuttonbn.strings.as + '</i>&nbsp;<select id="participant_list_role_';
        innerHTML += selection_type.value + '-' + selection.value;
        innerHTML += '" onchange="this.participant_list_role_update(\'';
        innerHTML += selection_type.value + '\', \'' + selection.value;
        innerHTML += '\'); return 0;" class="select custom-select"><option value="viewer" selected="selected">';
        innerHTML += this.bigbluebuttonbn.strings.viewer + '</option><option value="moderator">';
        innerHTML += this.bigbluebuttonbn.strings.moderator + '</option></select>';
        var cell2 = row.insertCell(2);
        cell2.innerHTML = innerHTML;
        var cell3 = row.insertCell(3);
        cell3.width = "20px";
        innerHTML = '<a onclick="this.participant_remove(\'';
        innerHTML += selection_type.value + '\', \'' + selection.value;
        innerHTML += '\'); return 0;" title="' + this.bigbluebuttonbn.strings.remove + '">x</a>';
        if (this.bigbluebuttonbn.icons_enabled) {
            innerHTML = '<a class="action-icon" onclick="this.participant_remove(\'';
            innerHTML += selection_type.value + '\', \'';
            innerHTML += selection.value + '\'); return 0;"><img class="btn icon smallicon" alt="';
            innerHTML += this.bigbluebuttonbn.strings.remove + '" title="' + this.bigbluebuttonbn.strings.remove + '" src="';
            innerHTML += this.bigbluebuttonbn.pix_icon_delete + '"></img></a>';
        }
        cell3.innerHTML = innerHTML;
    },

    participant_list_role_update: function(type, id) {

        // Update in memory.
        var participant_list_role_selection = document.getElementById('participant_list_role_' + type + '-' + id);
        for (var i = 0; i < this.bigbluebuttonbn.participant_list.length; i++) {
            if (this.bigbluebuttonbn.participant_list[i].selectiontype == type &&
                this.bigbluebuttonbn.participant_list[i].selectionid == (id === '' ? null : id)) {
                this.bigbluebuttonbn.participant_list[i].role = participant_list_role_selection.value;
            }
        }

        // Update in the form.
        this.participant_list_update();
    },

    select_clear: function(id) {
        var select = document.getElementById(id);
        while (select.length > 0) {
            select.remove(select.length - 1);
        }
    },

    select_enable: function(id) {
        var select = document.getElementById(id);
        select.disabled = false;
    },

    select_disable: function(id) {
        var select = document.getElementById(id);
        select.disabled = true;
    },

    select_add_option: function(id, text, value) {
        var select = document.getElementById(id);
        var option = document.createElement('option');
        option.text = text;
        option.value = value;
        select.add(option, 0);
    }

};


}, '@VERSION@', {"requires": ["base", "node"]});
