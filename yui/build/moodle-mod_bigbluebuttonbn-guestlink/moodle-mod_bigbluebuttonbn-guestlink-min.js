YUI.add("moodle-mod_bigbluebuttonbn-guestlink",function(t,e){M.mod_bigbluebuttonbn=M.mod_bigbluebuttonbn||{},M.mod_bigbluebuttonbn.guestlink={datasource:null,bigbluebuttonbn:{},init:function(e){this.datasource=new t.DataSource.Get({source:M.cfg.wwwroot+"/mod/bigbluebuttonbn/bbb_ajax.php?sesskey="+M.cfg.sesskey+"&"}),this.bigbluebuttonbn=e,-1==this.bigbluebuttonbn.profile_features.indexOf("all")&&-1==this.bigbluebuttonbn.profile_features.indexOf("guestlink")||this.initGuestLink()},initGuestLink:function(){var u=this.bigbluebuttonbn.guestlink,i=this.datasource,s=this.bigbluebuttonbn.bigbluebuttonbnid;window.require(["core/templates"],function(o){o.render("mod_bigbluebuttonbn/guestlink_view",u).then(function(e,t){var n;o.appendNodeContents("#guestlink_panel",e,t),document.getElementById("guestlink-copy").onclick=function(){var e=document.getElementById("guestlink");e.select(),e.setSelectionRange(0,99999),document.execCommand("copy")},document.getElementById("password-copy").onclick=function(){var e=document.getElementById("password");e.value&&!isNaN(e.value)&&(e.select(),e.setSelectionRange(0,6),document.execCommand("copy"))},u.changepassenabled&&(n=function(e){i.sendRequest({request:"action=set_guestlink_password&bigbluebuttonbn="+s+"&deletepassword="+e,callback:{success:function(e){var t=document.getElementById("password"),n=e.data;n?t.value=("000000"+n).slice(-6):require(["core/str"],function(e){e.get_string("view_guestlink_password_no_password_set","bigbluebuttonbn").then(function(e){t.value=e})["catch"](Notification.exception)})}}})},document.getElementById("password-delete").addEventListener("click",function(){n(!0)}),document.getElementById("password-random").addEventListener("click",function(){n(!1)}))})})}}},"@VERSION@",{requires:["base","node","datasource-get"]});