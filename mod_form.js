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

/**
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2014-2017 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

/** global: M */
/** global: Y */
/** global: bigbluebuttonbn */

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.mod_form_init = function() {
    M.mod_bigbluebuttonbn.mod_form_update_instance_type_profile();
};

M.mod_bigbluebuttonbn.mod_form_update_instance_type_profile = function() {
    /* global bigbluebuttonbn */

    var selected_type = Y.one('#id_type');
    M.mod_bigbluebuttonbn.mod_form_apply_instance_type_profile(bigbluebuttonbn.instance_type_profiles[
        selected_type.get('value')]);
};

M.mod_bigbluebuttonbn.mod_form_apply_instance_type_profile = function(instance_type_profile) {

    var features = instance_type_profile.features;
    var show_all = features.includes('all');

    // Show room settings validation.
    M.mod_bigbluebuttonbn.mod_form_show_fieldset('id_room', show_all || features.includes('showroom'));

    // Show recordings settings validation.
    M.mod_bigbluebuttonbn.mod_form_show_fieldset('id_recordings', show_all || features.includes('showrecordings'));

    // Preuploadpresentation feature validation.
    M.mod_bigbluebuttonbn.mod_form_show_fieldset('id_preuploadpresentation', show_all || features.includes('preuploadpresentation'));

    // Participants feature validation.
    M.mod_bigbluebuttonbn.mod_form_show_fieldset('id_permissions', show_all || features.includes('permissions'));

    // Schedule feature validation.
    M.mod_bigbluebuttonbn.mod_form_show_fieldset('id_schedule', show_all || features.includes('schedule'));
};

M.mod_bigbluebuttonbn.mod_form_show_fieldset = function(id, show) {
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
};

M.mod_bigbluebuttonbn.mod_form_participant_selection_set = function() {
    /* global bigbluebuttonbn */

    M.mod_bigbluebuttonbn.mod_form_select_clear('bigbluebuttonbn_participant_selection');

    var type = document.getElementById('bigbluebuttonbn_participant_selection_type');
    for (var i = 0; i < type.options.length; i++) {
        if (type.options[i].selected) {
            var options = bigbluebuttonbn.participant_selection[type.options[i].value];
            for (var j = 0; j < options.length; j++) {
                M.mod_bigbluebuttonbn.mod_form_select_add_option(
                    'bigbluebuttonbn_participant_selection', options[j].name, options[j].id
                );
            }
            if (j === 0) {
                M.mod_bigbluebuttonbn.mod_form_select_add_option('bigbluebuttonbn_participant_selection',
                    '---------------', 'all');
                M.mod_bigbluebuttonbn.mod_form_select_disable('bigbluebuttonbn_participant_selection');
            } else {
                M.mod_bigbluebuttonbn.mod_form_select_enable('bigbluebuttonbn_participant_selection');
            }
        }
    }
};

M.mod_bigbluebuttonbn.mod_form_participant_list_update = function() {
    /* global bigbluebuttonbn */

    var participant_list = document.getElementsByName('participants')[0];
    participant_list.value = JSON.stringify(bigbluebuttonbn.participant_list).replace(/"/g, '&quot;');
};

M.mod_bigbluebuttonbn.mod_form_participant_remove = function(type, id) {
    /* global bigbluebuttonbn */

    // Remove from memory.
    for (var i = 0; i < bigbluebuttonbn.participant_list.length; i++) {
        if (bigbluebuttonbn.participant_list[i].selectiontype == type &&
            bigbluebuttonbn.participant_list[i].selectionid == (id === '' ? null : id)) {
            bigbluebuttonbn.participant_list.splice(i, 1);
        }
    }

    // Remove from the form.
    var participant_list_table = document.getElementById('participant_list_table');
    for (var ii = 0; ii < participant_list_table.rows.length; ii++) {
        if (participant_list_table.rows[ii].id == 'participant_list_tr_' + type + '-' + id) {
            participant_list_table.deleteRow(i);
        }
    }
    M.mod_bigbluebuttonbn.mod_form_participant_list_update();
};

M.mod_bigbluebuttonbn.mod_form_participant_add = function() {
    /* global bigbluebuttonbn */

    var participant_selection_type = document.getElementById('bigbluebuttonbn_participant_selection_type');
    var participant_selection = document.getElementById('bigbluebuttonbn_participant_selection');

    // Lookup to see if it has been added already.
    var found = false;
    for (var i = 0; i < bigbluebuttonbn.participant_list.length; i++) {
        if (bigbluebuttonbn.participant_list[i].selectiontype == participant_selection_type.value &&
            bigbluebuttonbn.participant_list[i].selectionid == participant_selection.value) {
            found = true;
        }
    }

    // If not found.
    if (!found) {
        // Add it to memory.
        var participant = {
            "selectiontype": participant_selection_type.value,
            "selectionid": participant_selection.value,
            "role": "viewer"
        };
        bigbluebuttonbn.participant_list.push(participant);

        // Add it to the form.
        var participant_list_table = document.getElementById('participant_list_table');
        var row = participant_list_table.insertRow(participant_list_table.rows.length);
        row.id = "participant_list_tr_" + participant_selection_type.value + "-" + participant_selection.value;
        var cell0 = row.insertCell(0);
        cell0.width = "125px";
        cell0.innerHTML = '<b><i>' + participant_selection_type.options[participant_selection_type.selectedIndex].text;
        cell0.innerHTML += (participant_selection_type.value !== 'all' ? ':&nbsp;' : '') + '</i></b>';
        var cell1 = row.insertCell(1);
        if (participant_selection_type.value == 'all') {
            cell1.innerHTML = '';
        } else {
            cell1.innerHTML = participant_selection.options[participant_selection.selectedIndex].text;
        }
        var cell2 = row.insertCell(2);
        cell2.innerHTML = '<i>&nbsp;' + bigbluebuttonbn.strings.as + '&nbsp;</i><select id="participant_list_role_';
        cell2.innerHTML += participant_selection_type.value + '-' + participant_selection.value;
        cell2.innerHTML += '" onchange="M.mod_bigbluebuttonbn.mod_form_participant_list_role_update(\'';
        cell2.innerHTML += participant_selection_type.value + '\', \'' + participant_selection.value;
        cell2.innerHTML += '\'); return 0;" class="select custom-select"><option value="viewer" selected="selected">';
        cell2.innerHTML += bigbluebuttonbn.strings.viewer + '</option><option value="moderator">';
        cell2.innerHTML += bigbluebuttonbn.strings.moderator + '</option></select>';
        var cell3 = row.insertCell(3);
        cell3.width = "20px";
        if (bigbluebuttonbn.icons_enabled) {
            cell3.innerHTML = '<a class="action-icon" onclick="M.mod_bigbluebuttonbn.mod_form_participant_remove(\'';
            cell3.innerHTML += participant_selection_type.value + '\', \'';
            cell3.innerHTML += participant_selection.value + '\'); return 0;"><img class="btn icon smallicon" alt="';
            cell3.innerHTML += bigbluebuttonbn.strings.remove + '" title="' + bigbluebuttonbn.strings.remove + '" src="';
            cell3.innerHTML += bigbluebuttonbn.pix_icon_delete + '"></img></a>';
        } else {
            cell3.innerHTML = '<a onclick="M.mod_bigbluebuttonbn.mod_form_participant_remove(\'';
            cell3.innerHTML += participant_selection_type.value + '\', \'' + participant_selection.value;
            cell3.innerHTML += '\'); return 0;" title="' + bigbluebuttonbn.strings.remove + '">x</a>';
        }
    }

    M.mod_bigbluebuttonbn.mod_form_participant_list_update();
};

M.mod_bigbluebuttonbn.mod_form_participant_list_role_update = function(type, id) {
    /* global bigbluebuttonbn */

    // Update in memory.
    var participant_list_role_selection = document.getElementById('participant_list_role_' + type + '-' + id);
    for (var i = 0; i < bigbluebuttonbn.participant_list.length; i++) {
        if (bigbluebuttonbn.participant_list[i].selectiontype == type &&
            bigbluebuttonbn.participant_list[i].selectionid == (id === '' ? null : id)) {
            bigbluebuttonbn.participant_list[i].role = participant_list_role_selection.value;
        }
    }

    // Update in the form.
    M.mod_bigbluebuttonbn.mod_form_participant_list_update();
};

M.mod_bigbluebuttonbn.mod_form_select_clear = function(id) {
    var select = document.getElementById(id);
    while (select.length > 0) {
        select.remove(select.length - 1);
    }
};

M.mod_bigbluebuttonbn.mod_form_select_enable = function(id) {
    var select = document.getElementById(id);
    select.disabled = false;
};

M.mod_bigbluebuttonbn.mod_form_select_disable = function(id) {
    var select = document.getElementById(id);
    select.disabled = true;
};

M.mod_bigbluebuttonbn.mod_form_select_add_option = function(id, text, value) {
    var select = document.getElementById(id);
    var option = document.createElement('option');
    option.text = text;
    option.value = value;
    select.add(option, 0);
};
