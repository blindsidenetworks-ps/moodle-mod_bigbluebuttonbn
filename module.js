/**
 * @namespace
 */
M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};

/**
 * This function is initialized from PHP
 *
 * @param {Object} Y YUI instance
 */

M.mod_bigbluebuttonbn.init_view = function(Y) {
	
	if ( joining == 'true' ){
	    if (ismoderator == 'true' || waitformoderator == 'false'){
	      	M.mod_bigbluebuttonbn.joinURL();
	    } else {
	    	
	    	/////////////////
	        var getDateString = function() {
	            return new Date();
	        };

	        var dataSource = new Y.DataSource.Function({source:getDateString});

	        var request = {
	            callback : {
	                success: function(e) {
	                    console.debug('success');
	                    document.getElementById('txtUpdate').innerHTML = e.response.results[0];
	                },
	                failure: function(e) {
	                    console.debug('failure');
	                }
	            }
	        };

	        var id = dataSource.setInterval(5000, request );
	    	////////////////////////////
	    	
	    }
	}
};


M.mod_bigbluebuttonbn.joinURL = function(){
	console.log(joinurl);
	//window.location = joinurl;
};

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