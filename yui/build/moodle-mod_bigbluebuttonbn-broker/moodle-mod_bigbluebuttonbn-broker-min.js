YUI.add("moodle-mod_bigbluebuttonbn-broker",function(e,t){M.mod_bigbluebuttonbn=M.mod_bigbluebuttonbn||{},M.mod_bigbluebuttonbn.broker={datasource:null,bigbluebuttonbn:{},init:function(t){this.datasource=new e.DataSource.Get({source:M.cfg.wwwroot+"/mod/bigbluebuttonbn/bbb_broker.php?"}),this.bigbluebuttonbn=t},join_redirect:function(e){window.open(e)},recording_action_perform:function(e){var t="action=recording_"+e.action+"&id="+e.recordingid+"&idx="+e.meetingid;t+=this.recording_action_meta_qs(e),e.attempt=1,typeof e.attempts=="undefined"&&(e.attempts=5),this.datasource.sendRequest({request:t,callback:{success:function(t){return t.data.status?typeof e.goalstate=="undefined"?M.mod_bigbluebuttonbn.recordings.recording_action_completion(e):e.attempts<=1?M.mod_bigbluebuttonbn.broker.recording_action_performed_complete(t,e):M.mod_bigbluebuttonbn.broker.recording_action_performed_validate(e):(e.message=t.data.message,M.mod_bigbluebuttonbn.recordings.recording_action_failover(e))},failure:function(t){return e.message=t.error.message,M.mod_bigbluebuttonbn.recordings.recording_action_failover(e)}}})},recording_action_meta_qs:function(e){var t="";if(typeof e.source!="undefined"){var n={};n[e.source]=encodeURIComponent(e.goalstate),t+="&meta="+JSON.stringify(n)}return t},recording_action_performed_validate:function(e){var t="action=recording_info&id="+e.recordingid+"&idx="+e.meetingid;t+=this.recording_action_meta_qs(e),this.datasource.sendRequest({request:t,callback:{success:function(t){if(M.mod_bigbluebuttonbn.broker.recording_action_performed_complete(t,e))return;if(e.attempt<e.attempts){e.attempt+=1,setTimeout(function(){return function(){M.mod_bigbluebuttonbn.broker.recording_action_performed_validate(e)}}(this),(e.attempt-1)*1e3);return}e.message=M.util.get_string("view_error_action_not_completed","bigbluebuttonbn"),M.mod_bigbluebuttonbn.recordings.recording_action_failover(e)},failure:function(t){e.message=t.error.message,M.mod_bigbluebuttonbn.recordings.recording_action_failover(e)}}})},recording_action_performed_complete:function(e,t){if(typeof e.data[t.source]=="undefined")return t.message=M.util.get_string("view_error_current_state_not_found","bigbluebuttonbn"),M.mod_bigbluebuttonbn.recordings.recording_action_failover(t),!0;if(e.data[t.source]===t.goalstate)return M.mod_bigbluebuttonbn.recordings.recording_action_completion(t),!0;return},recording_current_state:function(e,t){return e==="publish"||e==="unpublish"?t.published:e==="delete"?t.status:e==="protect"||e==="unprotect"?t.secured:e==="update"?t.updated:null},end_meeting:function(){var e="action=meeting_end&id="+this.bigbluebuttonbn.meetingid;e+="&bigbluebuttonbn="+this.bigbluebuttonbn.bigbluebuttonbnid,this.datasource.sendRequest({request:e,callback:{success:function(e){e.data.status&&(M.mod_bigbluebuttonbn.rooms.end_meeting(),location.reload())}}})}}},"@VERSION@",{requires:["base","node","datasource-get","datasource-jsonschema","datasource-polling","moodle-core-notification"]});
