/**
 * @namespace
 */
M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

/**
 * This function is initialized from PHP
 *
 * @param {Object} Y YUI instance
 */
M.mod_bigbluebuttonbn.init = function(Y) {
    
    if (M.yui.bigbluebuttonbn_page == 'mod_form'){
        document.getElementsByName("timeavailable[enabled]")[0].removeAttribute("checked");
        document.getElementsByName("timedue[enabled]")[0].removeAttribute("checked");
    }
}