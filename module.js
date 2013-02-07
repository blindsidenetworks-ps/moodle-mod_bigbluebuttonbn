/**
 * @namespace
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

M.mod_bigbluebuttonbn.modform_Editting = function() {
    var elSel = document.getElementsByName('groupmode')[0];
    if (elSel.length > 0) {
        elSel.remove(elSel.length - 1);
    }
}

M.mod_bigbluebuttonbn.viewend_CloseWindow = function() {
    window.close();
}

M.mod_bigbluebuttonbn.setusergroups = function() {
    var elSel = document.getElementsByName('group')[0];
    if (elSel.length > 0) {
        elSel.options[0].text = 'Select group';
        elSel.options[0].value = elSel.options[1].value;
    }
}
