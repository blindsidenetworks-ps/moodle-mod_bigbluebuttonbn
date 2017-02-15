/**
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2014-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.mod_form_init = function(Y) {
    console.info("mod_form_init");
    console.info(bigbluebuttonbn.instance_type_profiles);
    M.mod_bigbluebuttonbn.mod_form_update_instance_type_profile();
};

M.mod_bigbluebuttonbn.mod_form_update_instance_type_profile = function() {
    console.info("mod_form_update_instance_type_profile");
    var selected_type = Y.one('#id_type option:checked');
    console.info(selected_type.get('value'));
    console.info(selected_type.get('text'));
    console.info(bigbluebuttonbn.instance_type_profiles[selected_type.get('value')]);
    M.mod_bigbluebuttonbn.mod_form_apply_instance_type_profile(bigbluebuttonbn.instance_type_profiles[selected_type.get('value')]);
};

M.mod_bigbluebuttonbn.mod_form_apply_instance_type_profile = function(instance_type_profile) {
    console.info("mod_form_apply_instance_type_profile");
    console.info(instance_type_profile);
    var features = instance_type_profile.features;
    console.info(features);

    // Show room settings validation
    var fieldset_showroom = Y.DOM.byId('id_room');
    if( features.includes('all') || features.includes('showroom') ) {
        console.debug('feature showroom enabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_showroom, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_showroom, 'display', 'block');
    } else {
        console.debug('feature showroom disabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_showroom, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_showroom, 'display', 'none');
    }

    // Show recordings settings validation
    var fieldset_showrecordings = Y.DOM.byId('id_recordings');
    if( features.includes('all') || features.includes('showrecordings') ) {
        console.debug('feature showrecordings enabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_showrecordings, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_showrecordings, 'display', 'block');
    } else {
        console.debug('feature showrecordings disabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_showrecordings, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_showrecordings, 'display', 'none');
    }

    // Preuploadpresentation feature validation
    var fieldset_preuploadpresentation = Y.DOM.byId('id_preuploadpresentation');
    if( features.includes('all') || features.includes('permissions') ) {
        console.debug('feature preuploadpresentation enabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_preuploadpresentation, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_preuploadpresentation, 'display', 'block');
    } else {
        console.debug('feature preuploadpresentation disabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_preuploadpresentation, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_preuploadpresentation, 'display', 'none');
    }

    // Participants feature validation
    var fieldset_permissions = Y.DOM.byId('id_permissions');
    if( features.includes('all') || features.includes('permissions') ) {
        console.debug('feature permissions enabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_permissions, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_permissions, 'display', 'block');
    } else {
        console.debug('feature permissions disabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_permissions, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_permissions, 'display', 'none');
    }

    // Schedule feature validation
    var fieldset_schedule = Y.DOM.byId('id_schedule');
    if( features.includes('all') || features.includes('schedule') ) {
        console.debug('feature schedule enabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_schedule, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_schedule, 'display', 'block');
    } else {
        console.debug('feature schedule disabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_schedule, 'visibility', 'visible');
        Y.DOM.setStyle(fieldset_schedule, 'display', 'none');
    }

    // Groups feature validation
    /*
    var fieldset_groups = Y.DOM.byId('id_modstandardelshdr');
    if( features.includes('all') || features.includes('groups') ) {
        console.debug('feature groups enabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_groups, 'visibility', 'shown');
        Y.DOM.setStyle(fieldset_groups, 'display', 'block');
    } else {
        console.debug('feature groups disabled for ' + instance_type_profile.name);
        //Y.DOM.setStyle(fieldset_groups, 'visibility', 'hidden');
        Y.DOM.setStyle(fieldset_groups, 'display', 'none');
    }
    */
};
