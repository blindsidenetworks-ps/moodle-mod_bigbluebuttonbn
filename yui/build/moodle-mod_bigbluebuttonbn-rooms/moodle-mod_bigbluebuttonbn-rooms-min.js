YUI.add("moodle-mod_bigbluebuttonbn-rooms",function(u,t){M.mod_bigbluebuttonbn=M.mod_bigbluebuttonbn||{},M.mod_bigbluebuttonbn.rooms={datasource:null,bigbluebuttonbn:{},panel:null,pinginterval:null,init:function(t){this.datasource=new u.DataSource.Get({source:M.cfg.wwwroot+"/mod/bigbluebuttonbn/bbb_ajax.php?sesskey="+M.cfg.sesskey+"&"}),this.bigbluebuttonbn=t,this.pinginterval=t.ping_interval,0===this.pinginterval&&(this.pinginterval=1e4),-1==this.bigbluebuttonbn.profile_features.indexOf("all")&&-1==this.bigbluebuttonbn.profile_features.indexOf("showroom")||(this.initRoom(),this.initGuestLink()),this.initCompletionValidate()},initRoom:function(){if("open"!==this.bigbluebuttonbn.activity){var t=[M.util.get_string("view_message_conference_has_ended","bigbluebuttonbn")];return"ended"!==this.bigbluebuttonbn.activity&&(t=[M.util.get_string("view_message_conference_not_started","bigbluebuttonbn"),this.bigbluebuttonbn.opening,this.bigbluebuttonbn.closing]),void u.DOM.addHTML(u.one("#status_bar"),this.initStatusBar(t))}this.updateRoom()},initGuestLink:function(){var i=this.bigbluebuttonbn.guestlink,u=this.datasource,s=this.bigbluebuttonbn.bigbluebuttonbnid;window.require(["core/templates"],function(o){o.render("mod_bigbluebuttonbn/guestlink_view",i).then(function(t,e){var n;o.appendNodeContents("#guestlink_panel",t,e),document.getElementById("guestlink-copy").onclick=function(){var t=document.getElementById("guestlink");t.select(),t.setSelectionRange(0,99999),document.execCommand("copy")},document.getElementById("password-copy").onclick=function(){var t=document.getElementById("password");t.value&&!isNaN(t.value)&&(t.select(),t.setSelectionRange(0,6),document.execCommand("copy"))},i.changepassenabled&&(n=function(t){u.sendRequest({request:"action=set_guest_password&bigbluebuttonbn="+s+"&delete="+t,callback:{success:function(t){var e=document.getElementById("password"),n=t.data;n?e.value=("000000"+n).slice(-6):require(["core/str"],function(t){t.get_string("view_guestlink_password_no_password_set","bigbluebuttonbn").then(function(t){e.value=t})["catch"](Notification.exception)})}}})},document.getElementById("password-delete").addEventListener("click",function(){n(!0)}),document.getElementById("password-random").addEventListener("click",function(){n(!1)}))})})},updateRoom:function(t){var e,n,o="false";void 0!==t&&t&&(o="true"),e=this.bigbluebuttonbn.meetingid,n=this.bigbluebuttonbn.bigbluebuttonbnid,this.datasource.sendRequest({request:"action=meeting_info&id="+e+"&bigbluebuttonbn="+n+"&updatecache="+o,callback:{success:function(t){u.DOM.addHTML(u.one("#status_bar"),M.mod_bigbluebuttonbn.rooms.initStatusBar(t.data.status.message)),u.DOM.addHTML(u.one("#control_panel"),M.mod_bigbluebuttonbn.rooms.initControlPanel(t.data)),"undefined"!=typeof t.data.status.can_join&&u.DOM.addHTML(u.one("#join_button"),M.mod_bigbluebuttonbn.rooms.initJoinButton(t.data.status)),"undefined"!=typeof t.data.status.can_end&&t.data.status.can_end&&u.DOM.addHTML(u.one("#end_button"),M.mod_bigbluebuttonbn.rooms.initEndButton(t.data.status)),t.data.status.can_join||M.mod_bigbluebuttonbn.rooms.waitModerator({id:e,bnid:n})}}})},initStatusBar:function(t){var e,n,o=u.DOM.create('<span id="status_bar_span">');if(t.constructor!==Array)return u.DOM.setText(o,t),o;for(e in t)t.hasOwnProperty(e)&&(n=u.DOM.create('<span id="status_bar_span_span">'),u.DOM.setText(n,t[e]),u.DOM.addHTML(o,n),u.DOM.addHTML(o,u.DOM.create("<br>")));return o},initControlPanel:function(t){var e,n=u.DOM.create("<div>");return u.DOM.setAttribute(n,"id","control_panel_div"),e="",t.running&&(e+=this.msgStartedAt(t.info.startTime)+" ",e+=this.msgAttendeesIn(t.info.moderatorCount,t.info.participantCount)),u.DOM.addHTML(n,e),n},msgStartedAt:function(t){var e=parseInt(t,10)-parseInt(t,10)%1e3,n=new Date(e),o=n.getHours(),i=n.getMinutes(),u=M.util.get_string("view_message_session_started_at","bigbluebuttonbn");return u+" <b>"+o+":"+(i<10?"0":"")+i+"</b>."},msgModeratorsIn:function(t){var e=M.util.get_string("view_message_moderators","bigbluebuttonbn");return 1==t&&(e=M.util.get_string("view_message_moderator","bigbluebuttonbn")),e},msgViewersIn:function(t){var e=M.util.get_string("view_message_viewers","bigbluebuttonbn");return 1==t&&(e=M.util.get_string("view_message_viewer","bigbluebuttonbn")),e},msgAttendeesIn:function(t,e){var n,o,i,u;return this.hasParticipants(e)?(n=this.msgModeratorsIn(t),o=e-t,i=this.msgViewersIn(o),u=M.util.get_string("view_message_session_has_users","bigbluebuttonbn"),1<e?u+" <b>"+t+"</b> "+n+" "+M.util.get_string("view_message_and","bigbluebuttonbn")+" <b>"+o+"</b> "+i+".":(u=M.util.get_string("view_message_session_has_user","bigbluebuttonbn"),0<t?u+" <b>1</b> "+n+".":u+" <b>1</b> "+i+".")):M.util.get_string("view_message_session_no_users","bigbluebuttonbn")+"."},hasParticipants:function(t){return void 0!==t&&0<t},initJoinButton:function(t){var e,n,o,i=u.DOM.create("<input>");return u.DOM.setAttribute(i,"id","join_button_input"),u.DOM.setAttribute(i,"type","button"),u.DOM.setAttribute(i,"value",t.join_button_text),u.DOM.setAttribute(i,"class","btn btn-primary"),e=this.bigbluebuttonbn.accesspolicy?'$("#policymodal").modal("show");':"M.mod_bigbluebuttonbn.rooms.join('"+t.join_url+"');",u.DOM.setAttribute(i,"onclick",e),t.can_join||(u.DOM.setAttribute(i,"disabled",!0),n=u.one("#status_bar_span"),o=u.DOM.create("<img>"),u.DOM.setAttribute(o,"id","spinning_wheel"),u.DOM.setAttribute(o,"src","pix/i/processing16.gif"),u.DOM.addHTML(n,"&nbsp;"),u.DOM.addHTML(n,o)),i},initEndButton:function(t){var e=u.DOM.create("<input>");return u.DOM.setAttribute(e,"id","end_button_input"),u.DOM.setAttribute(e,"type","button"),u.DOM.setAttribute(e,"value",t.end_button_text),u.DOM.setAttribute(e,"class","btn btn-secondary"),t.can_end&&u.DOM.setAttribute(e,"onclick","M.mod_bigbluebuttonbn.broker.endMeeting();"),e},endMeeting:function(){u.one("#control_panel_div").remove(),u.one("#join_button").hide(),u.one("#end_button").hide()},remoteUpdate:function(t){setTimeout(
function(){M.mod_bigbluebuttonbn.rooms.cleanRoom(),M.mod_bigbluebuttonbn.rooms.updateRoom(!0)},t)},cleanRoom:function(){u.one("#status_bar_span").remove(),u.one("#control_panel_div").remove(),u.one("#join_button").setContent(""),u.one("#end_button").setContent("")},windowClose:function(){window.onunload=function(){opener.M.mod_bigbluebuttonbn.rooms.remoteUpdate(5e3)},window.close()},waitModerator:function(e){var n=setInterval(function(){M.mod_bigbluebuttonbn.rooms.datasource.sendRequest({request:"action=meeting_info&id="+e.id+"&bigbluebuttonbn="+e.bnid,callback:{success:function(t){if(t.data.running)return M.mod_bigbluebuttonbn.rooms.cleanRoom(),M.mod_bigbluebuttonbn.rooms.updateRoom(),void clearInterval(n)},failure:function(t){e.message=t.error.message}}})},this.pinginterval)},join:function(t){M.mod_bigbluebuttonbn.broker.joinRedirect(t),setTimeout(function(){M.mod_bigbluebuttonbn.rooms.cleanRoom(),M.mod_bigbluebuttonbn.rooms.updateRoom(!0)},15e3)},initCompletionValidate:function(){var t,e=u.one("a[href*=completion_validate]");e&&(t=e.get("hash").substr(1),e.on("click",function(){M.mod_bigbluebuttonbn.broker.completionValidate(t)}))}}},"@VERSION@",{requires:["base","node","datasource-get","datasource-jsonschema","datasource-polling","moodle-core-notification"]});