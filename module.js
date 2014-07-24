/**
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2014 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

/**
 * This function is initialized from PHP
 * 
 * @param {Object}
 *            Y YUI instance
 */

M.mod_bigbluebuttonbn.init_view = function(Y) {

    if (bigbluebuttonbn.joining == 'true') {
        if (bigbluebuttonbn.ismoderator == 'true' || bigbluebuttonbn.waitformoderator == 'false') {
            M.mod_bigbluebuttonbn.joinURL();
        } else {

            var dataSource = new Y.DataSource.Get({
                source : M.cfg.wwwroot + "/mod/bigbluebuttonbn/ping.php?"
            });

            var request = {
                request : "meetingid=" + bigbluebuttonbn.meetingid,
                callback : {
                    success : function(e) {
                        if (e.data.status == 'true') {
                            M.mod_bigbluebuttonbn.joinURL();
                        }
                    },
                    failure : function(e) {
                        console.debug(e.error.message);
                    }
                }
            };

            var id = dataSource.setInterval(10000, request);

        }
    }
};

M.mod_bigbluebuttonbn.joinURL = function() {
    window.location = bigbluebuttonbn.joinurl;
};

M.mod_bigbluebuttonbn.viewend_CloseWindow = function() {
    window.close();
};
