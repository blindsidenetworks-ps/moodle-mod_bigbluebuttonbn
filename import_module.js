/**
 * @package   mod_bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright 2012-2015 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

/**
 * This function is initialized from PHP
 * 
 * @param {Object}
 *            Y YUI instance
 */

M.mod_bigbluebuttonbn.import_view_init = function(Y) {

    // Init event listener for course selector
    Y.one('#menuimport_recording_links_select').on('change', function () {
        console.info("Clicked");
        Y.config.win.location = M.cfg.wwwroot + '/mod/bigbluebuttonbn/import_view.php?bn=' + bigbluebuttonbn.bn + '&tc=' + bigbluebuttonbn.tc;
    });

};
