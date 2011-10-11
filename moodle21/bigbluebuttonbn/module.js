/**
 * @namespace
 */
M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

/**
 * This function is initialized from PHP
 *
 * @param {Object} Y YUI instance
 */
M.mod_bigbluebuttonbn.modform_Adding_withSchedule = function(Y) {
    document.getElementsByName("timeavailable[enabled]")[0].removeAttribute("checked");
    document.getElementsByName("timedue[enabled]")[0].removeAttribute("checked");
    setGroupMode();
}

M.mod_bigbluebuttonbn.modform_Adding_withoutSchedule = function(Y) {
    setGroupMode();
}

M.mod_bigbluebuttonbn.modform_Editting = function() {
    setGroupMode();
}

M.mod_bigbluebuttonbn.viewend_CloseWindow = function() {
    window.close();
}

M.mod_bigbluebuttonbn.setusergroups = function() {
    var elSel = document.getElementsByName('group')[0];
    if (elSel.length > 0)
    {
        elSel.options[0].text = 'Select group';
        elSel.options[0].value = elSel.options[1].value;
    }
}

function setGroupMode(){
    var elSel = document.getElementsByName('groupmode')[0];
    if (elSel.length > 0)
    {
        elSel.remove(elSel.length - 1);
    }
}