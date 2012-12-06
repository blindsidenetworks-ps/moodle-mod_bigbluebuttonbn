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
	    	/*
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
	        */
	    	console.debug('Starting');
	    	
	    	var dataSource = new Y.DataSource.Get({
	    	    //source: "http://query.yahooapis.com/v1/public/yql?format=json&"
	    		//source: "http://192.168.119.131/moodle233/test/dataSource.php?format=json&"
	    		source: M.cfg.wwwroot + "/mod/bigbluebuttonbn/ping.php?"
	    	});
	    	
	    	var request = {
	    		//request: "q=select%20*%20from%20music.artist.search%20where%20keyword%3D%22{query}%22",
	    		request: "id=" + cmid + "&meetingid=" + meetingid,
	    		callback : {
	    			success : function(e) {
	    				console.debug('success');
	    				//console.debug(e.data.query.results);
	    				//document.getElementById('txtUpdate').innerHTML = e.data.query.results.event[0].name;
	    				console.debug(e);
	    				//document.getElementById('txtUpdate').innerHTML = e.data.query.results.event[0].name;
	    			},
	    			failure: function(e) {
	    				console.debug('failure');
	    				//console.debug(e.error.message);
	    				console.debug(e);
	    			}
	    		}
	    	};
	    	
	    	dataSource.plug({fn: Y.Plugin.DataSourceJSONSchema, cfg: {
	    	    schema: {
	    	        resultListLocator: "status",
	    	        resultFields: ["status"]
	    	    }
	    	}});
	    	
	    	var id = dataSource.setInterval(20000, request);
	    	
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