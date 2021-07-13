YUI.add('moodle-mod_bigbluebuttonbn-guestlink', function (Y, NAME) {

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
/** global: opener */

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.guestlink = {

    datasource: null,
    bigbluebuttonbn: {},

    /**
     * Initialise the broker code.
     *
     * @method init
     * @param {object} bigbluebuttonbn
     */
    init: function(bigbluebuttonbn) {
        this.datasource = new Y.DataSource.Get({
            source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_ajax.php?sesskey=" + M.cfg.sesskey + "&"
        });
        this.bigbluebuttonbn = bigbluebuttonbn;
        if (this.bigbluebuttonbn.profile_features.indexOf('all') != -1 ||
            this.bigbluebuttonbn.profile_features.indexOf('guestlink') != -1) {
            this.initGuestLink();
        }
    },

    initGuestLink: function() {
        var context = this.bigbluebuttonbn.guestlink;
        var datasource = this.datasource;
        var bnid = this.bigbluebuttonbn.bigbluebuttonbnid;
        window.require(['core/templates',], function(templates) {
            templates.render('mod_bigbluebuttonbn/guestlink_view', context)
                    .then(function(html, js) {
                        templates.appendNodeContents('#guestlink_panel', html, js);
                        /* guestlink things*/
                        var btn = document.getElementById("guestlink-copy");
                        btn.onclick = function () {
                            var copyText = document.getElementById("guestlink");
                            copyText.select();
                            copyText.setSelectionRange(0, 99999); /*For mobile devices*/
                            document.execCommand("copy");
                        };
                        /* passwordthings */
                        btn = document.getElementById("password-copy");
                        btn.onclick = function () {
                            var copyText = document.getElementById("password");
                            if(copyText.value && !isNaN(copyText.value)) {
                                copyText.select();
                                copyText.setSelectionRange(0, 6); /*For mobile devices*/
                                document.execCommand("copy");
                            }
                        };
                        if(context.changepassenabled) {
                            var setpass = function(del) {
                                datasource.sendRequest({
                                    request: 'action=set_guest_password&bigbluebuttonbn=' + bnid + '&delete=' + del,
                                    callback: {
                                        success: function(e) {
                                            var input = document.getElementById("password");
                                            var result = e.data;
                                            if(result) {
                                                input.value = ("000000" + result).slice(-6);
                                            } else {
                                                require(['core/str'], function(str) {
                                                  str.get_string('view_guestlink_password_no_password_set',
                                                      'bigbluebuttonbn').then(function(langString) {
                                                      input.value = langString;
                                                      return;
                                                      }).catch(Notification.exception);
                                                });
                                            }
                                        }
                                    }
                                });
                            };
                            btn = document.getElementById("password-delete");
                            btn.addEventListener('click', function(){
                                setpass(true);
                            });
                            btn = document.getElementById("password-random");
                            btn.addEventListener('click', function(){
                                setpass(false);
                            });
                        }
                });
        });
    },
};



}, '@VERSION@', {
    "requires": [
        "base",
        "node",
        "datasource-get",
        "datasource-jsonschema",
        "datasource-polling",
        "moodle-core-notification"
    ]
});
