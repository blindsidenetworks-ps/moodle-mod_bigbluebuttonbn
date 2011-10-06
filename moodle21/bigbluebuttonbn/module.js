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

M.mod_bigbluebuttonbn.setgroups = function() {
    var elSel = document.getElementsByName('groupmode')[0];
    if (elSel.length > 0)
    {
        elSel.remove(elSel.length - 1);
    }
}

M.mod_bigbluebuttonbn.setusergroups = function() {
    var elSel = document.getElementsByName('group')[0];
    if (elSel.length > 0)
    {
        elSel.remove(0);
    }
}