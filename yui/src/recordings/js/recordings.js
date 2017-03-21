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

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

M.mod_bigbluebuttonbn.recordings = {

    data_source: null,
    polling: null,
    bigbluebuttonbn: {},
    /**
     * Initialise the broker code.
     *
     * @method init
     */
    init: function(bigbluebuttonbn) {
        this.data_source = new Y.DataSource.Get({
            source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
        });
        this.bigbluebuttonbn = bigbluebuttonbn;
    },

};
