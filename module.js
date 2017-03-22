/**
 * JavaScript library for the bigbluebuttonbn module.
 *
 * @package    mod
 * @subpackage bigbluebuttonbn
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @copyright  2012-2016 Blindside Networks Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** global: M */
/** global: Y */
/** global: bigbluebuttonbn */

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

var bigbluebuttonbn_ds;
var bigbluebuttonbn_panel;

M.mod_bigbluebuttonbn.datasource_init = function(Y) {
    bigbluebuttonbn_ds = new Y.DataSource.Get({
        source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/bbb_broker.php?"
    });
};

M.mod_bigbluebuttonbn.import_view_init = function(Y) {
    /* global bigbluebuttonbn */

    // Init general datasource.
    M.mod_bigbluebuttonbn.datasource_init(Y);

    // Init event listener for course selector.
    Y.one('#menuimport_recording_links_select').on('change', function() {
        var endpoint = '/mod/bigbluebuttonbn/import_view.php';
        var qs = '?bn=' + bigbluebuttonbn.bn + '&tc=' + this.get('value');
        Y.config.win.location = M.cfg.wwwroot + endpoint + qs;
    });
};
