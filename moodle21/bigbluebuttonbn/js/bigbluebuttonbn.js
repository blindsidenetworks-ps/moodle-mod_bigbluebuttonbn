        function bigbluebuttonbn_joinURL(){
            if ( newwindow == 'true' ){
                window.location = M.cfg.wwwroot + '/mod/bigbluebuttonbn/joinbbb.php?joinurl=' + encodeURIComponent(joinurl)+'&logouturl=' + encodeURIComponent(logouturl);
            } else {
                window.location = joinurl;
            }
        }
    
        function bigbluebuttonbn_callback() {
            // Not elegant, but works around a bug in IE8
            var isMeeting = ($("#HeartBeatDIV").text().search("true")  > 0 );
            if ( isMeeting ) {
                bigbluebuttonbn_joinURL();
            }
        }


        function actionCall(url, action, recordingID){
            action = (typeof action == 'undefined') ? 'publish' : action;
       
            if ( action == 'publish' || (action == 'delete' && confirm("Are you sure to delete this recording?") ) ) {
                if (window.XMLHttpRequest) {
                    req = new XMLHttpRequest();
                } else if (window.ActiveXObject) {

                    try {
                        req = new ActiveXObject("Msxml2.XMLHTTP");
                    } catch (e) {

                        try {
                            req = new ActiveXObject("Microsoft.XMLHTTP");
                        } catch (e) {}
                    }
                }

                var req = new XMLHttpRequest();
                if ( action == 'publish') {
                    var el_img = document.getElementById('actionbar-publish-img-' + recordingID);
                    var el_a = document.getElementById('actionbar-publish-img-' + recordingID);
                    if (el_img ) { 
                        if ( el_img.alt == 'Hide' ) {
                            el_img.alt = 'Show';
                            el_img.src = 'pix/show.gif';
                            el_a.title = 'Show';

                        } else {
                            el_img.alt = 'Hide';
                            el_img.src = 'pix/hide.gif';
                            el_a.title = 'Hide';
                           
                        }
                        
                    }
                } else {
                    //Deletes the line in the dataTable 
                    var row = $(document.getElementById('actionbar-publish-img-' + recordingID)).closest("tr").get(0);
                    oTable.fnDeleteRow(oTable.fnGetPosition(row));

                }

                req.open("GET", url, true);
                req.onreadystatechange = function () {}

                req.send(null);
            }
        }

        $.fn.dataTableExt.oApi.fnReloadAjax = function ( oSettings, sNewSource, fnCallback, bStandingRedraw ){
            if ( typeof sNewSource != 'undefined' && sNewSource != null ){
                oSettings.sAjaxSource = sNewSource;
            }
            this.oApi._fnProcessingDisplay( oSettings, true );
            var that = this;
            var iStart = oSettings._iDisplayStart;
	
            oSettings.fnServerData( oSettings.sAjaxSource, null, function(json) {
	    /* Clear the old information from the table */
	    that.oApi._fnClearTable( oSettings );
		
	    /* Got the data - add it to the table */
	    for ( var i=0 ; i<json.aaData.length ; i++ ){
                //that.oApi._fnAddData( oSettings, json.aaData[i] );
                that.oApi._fnAddData(oSettings, json[oSettings.sAjaxDataProp][i]);
            }
		
            oSettings.aiDisplay = oSettings.aiDisplayMaster.slice();
            that.fnDraw( that );
		
            if ( typeof bStandingRedraw != 'undefined' && bStandingRedraw === true ){
                oSettings._iDisplayStart = iStart;
			    that.fnDraw( false );
		    }
		
		    that.oApi._fnProcessingDisplay( oSettings, false );
		
		    /* Callback user function - for event handlers etc */
		    if ( typeof fnCallback == 'function' && fnCallback != null ){
			    fnCallback( oSettings );
		    }
                }, oSettings );
        }
            
        var oTable;

        $(document).ready(function(){
            if ( joining == 'true' ){
                if (ismoderator == 'true' || waitformoderator == 'false'){
                    bigbluebuttonbn_joinURL();
                } else {
                    $.jheartbeat.set({
                        url: M.cfg.wwwroot + "/mod/bigbluebuttonbn/test.php?name=" + meetingid,
                        delay: 5000
                        }, function() {
                            bigbluebuttonbn_callback();
                    });
                }
            } else if ( bigbluebuttonbn_view == 'after' ){
                oTable = $('#example').dataTable( {
                    "aoColumns": [
                        {"sTitle": view_recording_list_recording, "sWidth": "150px"},
                        {"sTitle": view_recording_list_course, "sWidth": "150px"},
                        {"sTitle": view_recording_list_activity, "sWidth": "150px"},
                        {"sTitle": view_recording_list_description, "sWidth": "150px"},
                        {"sTitle": view_recording_list_date, "sWidth": "200px"},
                        {"sTitle": view_recording_list_actionbar, "sWidth": "50px", "sClass": "right", "bVisible" : false},
                        ],
		    
                    "oTableTools": {
                        "sRowSelect": "multi",
                        "aButtons": [ "select_all", "select_none" ]
                        },
                    
                    "sAjaxSource": M.cfg.wwwroot + "/mod/bigbluebuttonbn/ajax.php?name=" + meetingid + "&admin=" + ismoderator,
                    "bFilter": false,
                    "bPaginate": false,
                    "bInfo": false,
                    "fnInitComplete": function () {
                        oTable.fnReloadAjax();
                    }
                });
                
                if (ismoderator == 'true' )
                    oTable.fnSetColumnVis( 5, true );			
                 
                setInterval(function() {
                    oTable.fnReloadAjax();
                }, 10000);

            }
        });
