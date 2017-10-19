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

M.mod_bigbluebuttonbn.imports = {

    /**
     * Initialise the broker code.
     *
     * @method init
     * @param {object} data
     */
    init: function(data) {
        // Init event listener for course selector.
        Y.one('#menuimport_recording_links_select').on('change', function() {
            var endpoint = '/mod/bigbluebuttonbn/import_view.php';
            var qs = '?bn=' + data.bn + '&tc=' + this.get('value');
            Y.config.win.location = M.cfg.wwwroot + endpoint + qs;
        });
    }

};
