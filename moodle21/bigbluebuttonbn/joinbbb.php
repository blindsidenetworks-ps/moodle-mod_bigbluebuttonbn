<?php
/**
 * Handler for join meetings. 
 * 
 * Authors:
 * 	Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *      Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 * 
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2011 Blindside Networks 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once("lib.php");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>BigBlueButton</title>
 <style type="text/css">
   <!--
    html, body
    {
      height: 100%;
      margin: 0px;
    }
    -->
 </style>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
<script type="text/javascript" charset="utf-8">
    //JQuery code STARTS
    $.extend({
        getUrlVars: function(){
            var vars = [], hash;
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
            for(var i = 0; i < hashes.length; i++)
            {
              hash = hashes[i].split('=');
              vars.push(hash[0]);
              vars[hash[0]] = hash[1];
            }
            return vars;
          },
          getUrlVar: function(name){
            return $.getUrlVars()[name];
          }
        });

    var joinurl = $.getUrlVar('joinurl');
    var logouturl = $.getUrlVar('logouturl');
    $(document).ready(function() {
        $('#ifrm_bbbmeeting').attr( "src", decodeURIComponent(joinurl) );
        } );
    //JQuery code ENDS
    
    function closeBBBWindow() {
        if (!document.getElementById) return;
        var el = document.getElementById('ifrm_bbbmeeting');
        if ( el.contentWindow.location == decodeURIComponent(logouturl) ){
            window.close();
        }
     }
</script>

</head>
<body>
    <iFrame id="ifrm_bbbmeeting" name="ifrm_bbbmeeting" src="" width="100%" height="100%" frameborder="0" onLoad="closeBBBWindow();">
        <p>Your browser does not support iframes.</p>
    </iframe>
</body>
</html>