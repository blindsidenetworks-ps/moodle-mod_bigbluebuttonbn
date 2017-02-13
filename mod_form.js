/**
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2014-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

bigbluebuttonbn_participant_selection_set = function() {
    bigbluebuttonbn_select_clear('bigbluebuttonbn_participant_selection');

    var type = document.getElementById('bigbluebuttonbn_participant_selection_type');
    for( var i = 0; i < type.options.length; i++ ){
        if( type.options[i].selected ) {
            var options = bigbluebuttonbn_participant_selection[type.options[i].value];
            for( var j = 0; j < options.length; j++ ) {
                bigbluebuttonbn_select_add_option('bigbluebuttonbn_participant_selection', options[j].name, options[j].id);
            }
            if( j == 0){
                bigbluebuttonbn_select_add_option('bigbluebuttonbn_participant_selection', '---------------', 'all');
                bigbluebuttonbn_select_disable('bigbluebuttonbn_participant_selection')
            } else {
                bigbluebuttonbn_select_enable('bigbluebuttonbn_participant_selection')
            }
        }
    }
}

bigbluebuttonbn_participant_list_update = function() {
    var participant_list = document.getElementsByName('participants')[0];
    participant_list.value = JSON.stringify(bigbluebuttonbn_participant_list).replace(/"/g, '&quot;');
}

bigbluebuttonbn_participant_remove = function(type, id) {
    //Remove from memory
    for( var i = 0; i < bigbluebuttonbn_participant_list.length; i++ ){
        if( bigbluebuttonbn_participant_list[i].selectiontype == type && bigbluebuttonbn_participant_list[i].selectionid == (id == ''? null: id) ){
            bigbluebuttonbn_participant_list.splice(i, 1);
        }
    }

    //Remove from the form
    var participant_list_table = document.getElementById('participant_list_table');
    for( var i = 0; i < participant_list_table.rows.length; i++ ){
        if( participant_list_table.rows[i].id == 'participant_list_tr_' + type + '-' + id  ) {
            participant_list_table.deleteRow(i);
        }
    }
    bigbluebuttonbn_participant_list_update();
}

bigbluebuttonbn_participant_add = function() {
    var participant_selection_type = document.getElementById('bigbluebuttonbn_participant_selection_type');
    var participant_selection = document.getElementById('bigbluebuttonbn_participant_selection');

    //Lookup to see if it has been added already
    var found = false;
    for( var i = 0; i < bigbluebuttonbn_participant_list.length; i++ ){
        if( bigbluebuttonbn_participant_list[i].selectiontype == participant_selection_type.value && bigbluebuttonbn_participant_list[i].selectionid == participant_selection.value ){
            found = true;
        }
    }

    //If not found
    if( !found ){
        // Add it to memory
        var participant = {"selectiontype": participant_selection_type.value, "selectionid": participant_selection.value, "role": "viewer"};
        bigbluebuttonbn_participant_list.push(participant);

        // Add it to the form
        var participant_list_table = document.getElementById('participant_list_table');
        var row = participant_list_table.insertRow(participant_list_table.rows.length);
        row.id = "participant_list_tr_" + participant_selection_type.value + "-" + participant_selection.value;
        var cell0 = row.insertCell(0);
        cell0.width = "20px";
        cell0.innerHTML = '<a onclick="bigbluebuttonbn_participant_remove(\'' + participant_selection_type.value + '\', \'' + participant_selection.value + '\'); return 0;" title="' + bigbluebuttonbn_strings.remove + '">x</a>';
        var cell1 = row.insertCell(1);
        cell1.width = "125px";
        if( participant_selection_type.value == 'all' )
            cell1.innerHTML = '<b><i>' + participant_selection_type.options[participant_selection_type.selectedIndex].text + '</i></b>';
        else
            cell1.innerHTML = '<b><i>' + participant_selection_type.options[participant_selection_type.selectedIndex].text + ':&nbsp;</i></b>';
        var cell2 = row.insertCell(2);
        if( participant_selection_type.value == 'all' )
            cell2.innerHTML = '';
        else
            cell2.innerHTML = participant_selection.options[participant_selection.selectedIndex].text;
        var cell3 = row.insertCell(3);
        cell3.innerHTML = '<i>&nbsp;' + bigbluebuttonbn_strings.as + '&nbsp;</i><select id="participant_list_role_' + participant_selection_type.value + '-' + participant_selection.value + '" onchange="bigbluebuttonbn_participant_list_role_update(\'' + participant_selection_type.value + '\', \'' + participant_selection.value + '\'); return 0;"><option value="viewer" selected="selected">' + bigbluebuttonbn_strings.viewer + '</option><option value="moderator">' + bigbluebuttonbn_strings.moderator + '</option></select>';
    }

    bigbluebuttonbn_participant_list_update();
}

bigbluebuttonbn_participant_list_role_update = function(type, id) {
    // Update in memory
    var participant_list_role_selection = document.getElementById('participant_list_role_' + type + '-' + id);
    for( var i = 0; i < bigbluebuttonbn_participant_list.length; i++ ){
        if( bigbluebuttonbn_participant_list[i].selectiontype == type && bigbluebuttonbn_participant_list[i].selectionid == (id == ''? null: id) ){
            bigbluebuttonbn_participant_list[i].role = participant_list_role_selection.value;
            //participant_list_role_selection.options[participant_list_role_selection.selectedIndex].text
        }
    }

    // Update in the form
    bigbluebuttonbn_participant_list_update();
}

bigbluebuttonbn_select_clear = function(id) {
    var select = document.getElementById(id);
    while( select.length > 0 ){
        select.remove(select.length-1);
    }
}

bigbluebuttonbn_select_enable = function(id) {
    var select = document.getElementById(id);
    select.disabled = false;
}

bigbluebuttonbn_select_disable = function(id) {
    var select = document.getElementById(id);
    select.disabled = true;
}

bigbluebuttonbn_select_add_option = function(id, text, value) {
    var select = document.getElementById(id);
    var option = document.createElement('option');
    option.text = text;
    option.value = value;
    select.add(option , 0);
}

bigbluebuttonbn_update_instance_type_profile = function(instanceprofile) {
    console.info("bigbluebuttonbn_update_instance_type_profile");
    var selected_type = Y.one('#id_type option:checked');
    console.info(selected_type);
    console.info(selected_type.get('value'));
    console.info(selected_type.get('text'));
    var features = bigbluebuttonbn_instance_type_profiles[selected_type.get('value')].features;
    console.info(features);


    // Show room settings validation
    var fieldset_showroom = Y.DOM.byId('id_room');
    if( features.includes('all') || features.includes('showroom') ) {
        console.debug('feature showroom enabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_showroom, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_showroom, 'display', 'block');
    } else {
        console.debug('feature showroom disabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_showroom, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_showroom, 'display', 'none');
    }

    // Show recordings settings validation
    var fieldset_showrecordings = Y.DOM.byId('id_recordings');
    if( features.includes('all') || features.includes('showrecordings') ) {
        console.debug('feature showrecordings enabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_showrecordings, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_showrecordings, 'display', 'block');
    } else {
        console.debug('feature showrecordings disabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_showrecordings, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_showrecordings, 'display', 'none');
    }

    // Preuploadpresentation feature validation
    var fieldset_preuploadpresentation = Y.DOM.byId('id_preuploadpresentation');
    if( features.includes('all') || features.includes('permissions') ) {
        console.debug('feature preuploadpresentation enabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_preuploadpresentation, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_preuploadpresentation, 'display', 'block');
    } else {
        console.debug('feature preuploadpresentation disabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_preuploadpresentation, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_preuploadpresentation, 'display', 'none');
    }

    // Participants feature validation
    var fieldset_permissions = Y.DOM.byId('id_permissions');
    if( features.includes('all') || features.includes('permissions') ) {
        console.debug('feature permissions enabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_permissions, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_permissions, 'display', 'block');
    } else {
        console.debug('feature permissions disabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_permissions, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_permissions, 'display', 'none');
    }

    // Schedule feature validation
    var fieldset_schedule = Y.DOM.byId('id_schedule');
    if( features.includes('all') || features.includes('schedule') ) {
        console.debug('feature schedule enabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_schedule, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_schedule, 'display', 'block');
    } else {
        console.debug('feature schedule disabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_schedule, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_schedule, 'display', 'none');
    }

    // Groups feature validation
    var fieldset_groups = Y.DOM.byId('id_modstandardelshdr');
    if( features.includes('all') || features.includes('groups') ) {
        console.debug('feature groups enabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_groups, 'visibility', 'shown');
        Y.DOM.setStyle(fieldset_groups, 'display', 'block');
    } else {
        console.debug('feature groups disabled for ' + selected_type.get('text') );
        //Y.DOM.setStyle(fieldset_groups, 'visibility', 'hidden');
        Y.DOM.setStyle(fieldset_groups, 'display', 'none');
    }
}
